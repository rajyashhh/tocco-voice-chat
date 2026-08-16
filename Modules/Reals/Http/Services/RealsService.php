<?php

namespace Modules\Reals\Http\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Reals\Entities\Real;

use Illuminate\Support\Collection;
use Nwidart\Modules\Facades\Module;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Reals\Entities\ReportReals;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\FollowRepository;

define('PAGINATION', 10);
define('REEL_PAGINATION', 10);

class RealsService extends BaseModelService
{
    public function __construct(Model $model)
    {
        parent::__construct($model);
    }

    public function getUserReals(User $user, int $currentUserId)
    {
        $userId = $user->id;
        return Real::query()->ready()->with([
            'user' => function ($query) use ($currentUserId) {
                $query->withoutAppends()->isFollow($currentUserId)->with('profile');
            }
        ])->withCount(['likes', 'comments'])->withExists([
            'likes' => function ($query) use ($currentUserId) {
                $query->where('user_id', $currentUserId);
            }
        ])->where('user_id', $userId)->orderByDesc('id')->paginate(PAGINATION);
    }

    public function getUserFollowersReals(User $user)
    {
        $userId = $user->id;

        $builder = Real::query()->ready()->whereHas('user', function ($query) use ($userId) {
            $query->withoutAppends()->getFollowers($userId);
        });
        if (!request("page")  || request("page") == 1) {
            // Anchor must be max(id): the page filter below is id-based, and
            // created_at order can diverge from id order (seeded/imported rows).
            $user->last_following_reel_id = (clone $builder)->max('reals.id');
        }
        $reels   =  $builder->whereDoesntHave('likes', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with([
            'user' => function ($query) use ($userId) {
                $query->withoutAppends()->isFollow($userId)->with('profile');
            }
        ])->withCount(['likes', 'comments'])->withExists([
            'likes' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }
        ])->where('reals.id', '<=', $user->last_following_reel_id ?? PHP_INT_MAX)->inRandomOrder($user->following_unique_value);

        $countPrimary      = $reels->count();
        $currentPage       = request()->page ?? 1;
        $pagination        = REEL_PAGINATION;

        $reels = $reels->paginate($pagination);
        $allData = $reels->items();
        $allData = collect($allData);


        [$_, $allData] =
            $this->getReels($countPrimary, $userId, $allData, $user->following_unique_value, $user->last_following_reel_id, function ($userId) {
                return $this->getLikedReels($userId, true);
            });

        return $allData;
    }

    public function showNew(User $user, $filter = null)
    {
        $userId        = $user->id;

        if (!request("page")  || request("page") == 1) {
            // max(id), not latest(): the page filter is id-based.
            $user->last_all_reel_id = Real::query()->ready()->max('id');
        }
        $reals = Real::query()->ready()

        ->with([
            'user' => function ($query) use ($userId) {
                $query->withoutAppends()->isFollow($userId)->with('profile');
            }
        ])->withCount(['likes', 'comments'])->withExists([
            'likes' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }
        ])
        // Anchor at the newest reel seen on page 1: with seeded RAND ordering,
        // a reel published mid-scroll would otherwise shift offsets and cause
        // duplicated/skipped items across pages.
        ->where('reals.id', '<=', $user->last_all_reel_id ?? PHP_INT_MAX)
        ->inRandomOrder($user->real_type);

        if ($filter === 'following') {
            
            $followedUserIds = auth()->user()->friendsFollowedId(); // جلب معرفات الأصدقاء فقط
            $reals->whereIn('user_id', $followedUserIds);
        }

        $countPrimary      = $reals->count();
        $currentPage       = request()->page ?? 1;
        $pagination        = 10;

        $reals = $reals->paginate($pagination);


        $allData = $reals->items();
        $allData = collect($allData);

        $maxRealId = $user->last_all_reel_id;

        [$countNotLiked, $allData] =
            $this->getReels($countPrimary, $userId, $allData, $user->real_type, $maxRealId, function ($userId) {
                return $this->getNotLikedReels($userId);
            });


        [$_, $allData] =
            $this->getReels(($countPrimary + ($countNotLiked)), $userId, $allData, $user->real_type, $maxRealId, function ($userId) {
                return $this->getLikedReels($userId);
            });


        $paginator = new LengthAwarePaginator($allData, 100, $pagination, $currentPage, [
            'path' => request()->url(),
            'query' => request()->query()
        ]);



        return $reals;
    }

    /**
     * @param $countPrimary
     * @param int $perPage
     * @param mixed $currentPage
     * @return array
     */
    public function getNewLimitAndOffset($countPrimary, int $perPage, mixed $currentPage): array
    {
        $primaryPageCount                   = (float) $countPrimary / $perPage;
        $numOfAdminsPages    = (int)$primaryPageCount;
        $diffWithCurrentPage = $currentPage - $numOfAdminsPages;

        $limit = $perPage;
        if ($diffWithCurrentPage == 1) {
            $limit = $perPage - ($countPrimary % $perPage);
            $limit = $limit == 0 ? $perPage : $limit;
        }

        if (($numOfAdminsPages == 0 && $diffWithCurrentPage == 1)) {
            $offset = 0;
        } else if ($countPrimary < $perPage && $diffWithCurrentPage == 2) {

            $offset = $perPage - $countPrimary;
        } else if (($primaryPageCount - $numOfAdminsPages) > 0.0) {
            $offset = (($currentPage - 1) * $perPage) - (($countPrimary) % $perPage) + ($perPage * $numOfAdminsPages);
        } else {
            if ($countPrimary == 0) {
                $countPrimary = 1;
            }
            $offset = (($currentPage - 1) * $perPage) - (($countPrimary) % $perPage) + ($perPage * $numOfAdminsPages);

            //            $offset = $countPrimary % $perPage * (($diffWithCurrentPage - 1) * $perPage);
        }

        return [$limit, $offset];
    }

    public function create($data, int $userId)
    {
        $urlVideo = $data['video'];
        // Older app builds may still post a `categories` field; it must never
        // reach Real::create (no such column).
        unset($data['video'], $data['categories']);

        if (is_file($urlVideo)) {
            $urlVideo = $this->upload($urlVideo);
        }

        // Reel is created as `processing` and only flips to `ready` when the
        // transcode job succeeds — feeds never serve a non-ready reel, so the
        // publish/transcode race that produced unplayable videos is gone.
        $data['user_id']    = $userId;
        $data['url']        = $urlVideo;
        $data['source_url'] = $urlVideo;
        $data['status']     = Real::STATUS_PROCESSING;
        $real               = Real::query()->create($data);

        \Modules\Reals\Jobs\ProcessReelVideoJob::dispatch($real->id)->onQueue('optimization-images');

        return $real;
    }

    public function oldReal()
    {
        // $reals = Real::chunk(100)->get();
        // foreach( $reals as $real)
        // {
        //     (new FfmpegService())->extract(getDriverUrl().'/'.$real->url,$real->id);
        // }

        Real::chunk(600, function ($reals) {
            foreach ($reals as $real) {
                (new FfmpegService())->extract(getDriverUrl() . '/' . $real->url, $real->id);
            }
        });
    }

    public function makeSubVideo(string $videoPath, ?string $outPutPath, string $storage = 'local'): ?string
    {
        if ($outPutPath == null) {
            $outPutPath = storage_path('app/public/sub-video');
        }

        $outGifName = $outPutPath . DIRECTORY_SEPARATOR . uniqid() . '.gif';
        $videoName  = $outPutPath . DIRECTORY_SEPARATOR . uniqid() . '.mp4';

        $videoPath        = getDriverUrl() . DIRECTORY_SEPARATOR . $videoPath;
        $pythonScriptPath = base_path('/Modules/Reals/Http/Services/script.py');
        $videoPath        = str_replace('\\', '/', $videoPath);


        $path              = null;
        $command           = "python3 $pythonScriptPath $videoPath $videoName $outGifName";
        $output            = shell_exec($command);
        $outGifNameStorage = substr($outGifName, strpos($outGifName, 'public') - 1);
        $videoNameStorage  = substr($videoName, strpos($videoName, 'public') - 1);

        if (Storage::disk('local')->exists($outGifNameStorage)) {
            $path = 'sub-video/' . uniqid() . '.gif';
            Storage::disk($storage)->put($path, file_get_contents($outGifName));

            Storage::disk('local')->delete($outGifNameStorage);
            Storage::disk('local')->delete($videoNameStorage);
        }

        return $path;
    }

    public function showReal(int $realId, $userId)
    {
        return Real::query()->ready()->with([
            'user' => function ($query) use ($userId) {
                $query->withoutAppends()->with(['profile'])->isFollow($userId);
            }
        ])->withCount(['likes', 'comments'])->withExists([
            'likes' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }
        ])->where('id', $realId)->first();
    }


    public  static function upload($file): ?string
    {
        // Reject files with non-ASCII characters (Arabic, emoji, etc.) in the original name.
        // This prevents ffprobe failures caused by encoding issues in GCS URLs.
        $originalName = $file->getClientOriginalName();
        if (!mb_check_encoding($originalName, 'ASCII')) {
            throw new \InvalidArgumentException(
                'File name must contain only English letters, numbers, and common symbols. Arabic or special characters are not allowed.'
            );
        }

        $extension      = $file->getClientOriginalExtension();
        $uniqueFileName = Str::random(20) . '_' . uniqid() . '.' . $extension;
        $file->storeAs('videos', $uniqueFileName, 'gcs');
        return 'videos' . DIRECTORY_SEPARATOR . $uniqueFileName;
    }

    public function delete(int|Module $real)
    {
        if (gettype($real) == 'integer') {
            $real = Real::query()->find($real);
        }

        if (auth()->id() != @$real->user_id) {
            return false;
        }


        $real->delete();
        return true;
    }

    /**
     * @param int $countPrimary
     * @param int $pagination
     * @param mixed $currentPage
     * @return float|int
     */
    public function getDiffCountWithPage(int $countPrimary, int $pagination, mixed $currentPage): int|float
    {
        return $countPrimary - ($pagination * $currentPage);
    }

    /**
     * @param mixed $userId
     * @return Builder
     */
    public function getNotLikedReels(mixed $userId, $lastId = PHP_INT_MAX): Builder
    {
        return Real::query()->ready()->with([
            'user' => function ($query) use ($userId) {
                $query->withoutAppends()->isFollow($userId)->with([
                    'profile' => function ($query) {
                        $query->select([
                            'id',
                            'avatar',
                            'user_id',
                        ]);
                    }
                ]);
            }
        ])->withCount(['likes', 'comments'])
            ->whereDoesntHave('likes', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->where('reals.id', '<=', $lastId);
    }

    /**
     * @param mixed $userId
     * @return Builder
     */
    public function getLikedReels(mixed $userId, bool $isFollowing = false): Builder
    {

        $builder = Real::query()->ready();
        if ($isFollowing) {
            $builder->whereHas('user', function ($query) use ($userId) {
                $query->withoutAppends()->getFollowers($userId);
            });
        } else {
            $builder->whereHas('user', function ($query) use ($userId) {
                $query->withoutAppends();
            });
        }
        return $builder->with([
            'user' => function ($query) use ($userId) {
                $query->withoutAppends()->isFollow($userId)->with([
                    'profile' => function ($query) {
                        $query->select([
                            'id',
                            'avatar',
                            'user_id',
                        ]);
                    }
                ]);
            }
        ])->withCount(['likes', 'comments'])
            ->withExists([
                'likes' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }
            ])
            ->whereHas('likes', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            });
    }

    /**
     * @param int $countPrimary
     * @param mixed $userId
     * @param Collection $allData
     * @return array
     */
    public function getReels(int $countPrimary, mixed $userId, Collection $allData, string $seed, $lastId, \Closure $closure): array
    {
        if ($lastId === null) $lastId = PHP_INT_MAX;
        $currentPage       = request()->page ?? 1;
        $pagination = REEL_PAGINATION;

        $diffCountWithPage = $this->getDiffCountWithPage($countPrimary, $pagination, $currentPage);
        if ($diffCountWithPage < 0) {
            [$limit, $offset] = $this->getNewLimitAndOffset($countPrimary, $pagination, $currentPage);

            $anotherData = $closure($userId, $lastId)->where('reals.id', '<=', $lastId)->inRandomOrder($seed);

            $countSecondary = $anotherData->count();
            $anotherData      = $anotherData->limit($limit)->offset($offset)->get();

            $allData = $allData->merge($anotherData);
        }
        return array($countSecondary ?? 0, $allData);
    }

    public function deleteReeltAndReport($reelId, $reportId)
    {
        // Find the moment by ID
        $reel = Real::find($reelId);
        if (!$reel) {
            return [
                'success' => false,
                'message' => 'Reel not found',
                'status' => 404,
            ];
        }

        // Find the report moment by ID and delete it
        $reportReel = ReportReals::find($reportId);
        if ($reportReel) $reportReel->delete();

        // Delete the moment
        $reel->delete();

        return [
            'success' => true,
            'message' => 'Reel and report successfully deleted',
            'status' => 200,
        ];
    }

    public function update( $reel_id, array $data)
    {
        $reel = Real::find($reel_id);

        if (!$reel) {
            throw new \Exception('Reel not found');
        }

        $updateData = [];

       
        $videoReplaced = false;
        if (isset($data['video']) && is_file($data['video'])) {
            $urlVideo = $this->upload($data['video']);
            $updateData['url']        = $urlVideo;
            $updateData['source_url'] = $urlVideo;
            $updateData['status']     = Real::STATUS_PROCESSING;
            $videoReplaced = true;
        }


        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

     
        if (!empty($updateData)) {
            $reel->update($updateData);
        }

        if ($videoReplaced) {
            \Modules\Reals\Jobs\ProcessReelVideoJob::dispatch($reel->id)->onQueue('optimization-images');
        }

        return $reel;
      
    }

    
}
