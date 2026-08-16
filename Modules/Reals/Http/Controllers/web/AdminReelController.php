<?php

namespace Modules\Reals\Http\Controllers\web;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Modules\Reals\Entities\Real;
use App\Admin\Controllers\MainController;

class AdminReelController extends MainController
{
    public function index(Content $content)
    {
        // Force full page reload for PJAX requests (Alpine.js requires clean page load)
        if (request()->pjax()) {
            return redirect()->to(request()->fullUrl());
        }

        // Generate or retrieve random seed for this session
        if (!session()->has('reels_random_seed')) {
            session(['reels_random_seed' => mt_rand(1, 999999)]);
        }
        $seed = session('reels_random_seed');
        
        $reels = Real::with(['user.profile', 'user.country'])
            ->withCount(['likes', 'comments', 'Views'])
            ->orderByRaw("RAND(?)", [$seed])
            ->limit(15) 
            ->get()
            ->map(function ($reel) {
                $videoUrl = $this->buildMediaUrl($reel->url);
                $thumbnailUrl = $this->buildMediaUrl($reel->thumbnail);

                return [
                    'id' => $reel->id,
                    'user_id' => $reel->user_id,
                    'user' => $reel->user ? [
                        'id' => $reel->user->id,
                        'name' => $reel->user->name ?? 'Unknown',
                    ] : null,
                    'title' => $reel->description ?: 'بدون عنوان',
                    'description' => $reel->description,
                    'video_url' => $videoUrl,
                    'thumbnail_url' => $thumbnailUrl ?: null,
                    'thumbnail_needs_capture' => empty($thumbnailUrl),
                    'likes_count' => $reel->likes_count ?? 0,
                    'comments_count' => $reel->comments_count ?? 0,
                    'views_count' => $reel->views_count ?? 0,
                    'gifts_count' => 0,
                    'created_at' => $reel->created_at,
                ];
            });

        $reelsScriptPath = public_path('modules/reals/js/reels-manager.js');
        $reelsScriptUrl = asset('modules/reals/js/reels-manager.js');

        if (is_file($reelsScriptPath)) {
            $reelsScriptUrl .= '?v=' . filemtime($reelsScriptPath);
        }

        Admin::css('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap');
        Admin::css('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
        Admin::js('https://cdn.tailwindcss.com');
        Admin::js('https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js');
        Admin::js($reelsScriptUrl);

        return $content
            ->body(view('reals::admin.reels.index', compact('reels', 'seed')));
    }
    
    public function loadMore(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 20);
        $excludeIds = $request->input('exclude_ids', []);
        $seed = $request->input('seed', session('reels_random_seed', mt_rand(1, 999999)));
        
        // Store seed in session if not exists
        if (!session()->has('reels_random_seed')) {
            session(['reels_random_seed' => $seed]);
        }
        
        $query = Real::with(['user.profile', 'user.country'])
            ->withCount(['likes', 'comments', 'Views']);
        
        if (!empty($excludeIds) && is_array($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }
        
        $reels = $query->orderByRaw("RAND(?)", [$seed])
            ->take($limit)
            ->get()
            ->map(function ($reel) {
                $videoUrl = $this->buildMediaUrl($reel->url);
                $thumbnailUrl = $this->buildMediaUrl($reel->thumbnail);

                return [
                    'id' => $reel->id,
                    'user_id' => $reel->user_id,
                    'user' => $reel->user ? [
                        'id' => $reel->user->id,
                        'name' => $reel->user->name ?? 'Unknown',
                    ] : null,
                    'title' => $reel->description ?: 'بدون عنوان',
                    'description' => $reel->description,
                    'video_url' => $videoUrl,
                    'thumbnail_url' => $thumbnailUrl ?: null,
                    'thumbnail_needs_capture' => empty($thumbnailUrl),
                    'likes_count' => $reel->likes_count ?? 0,
                    'comments_count' => $reel->comments_count ?? 0,
                    'views_count' => $reel->views_count ?? 0,
                    'gifts_count' => 0,
                    'created_at' => $reel->created_at,
                ];
            });
        
  
            
        return response()->json([
            'reels' => $reels,
            'seed' => $seed,
            'has_more' => Real::whereNotIn('id', array_merge($excludeIds, $reels->pluck('id')->toArray()))->exists()
        ]);
    }

    public function show($id, Content $content)
    {
        $reel = Real::with(['user.profile', 'user.country', 'likes.user.profile', 'likes.user.country', 'comments.user.profile', 'comments.user.country'])
            ->withCount(['likes', 'comments', 'Views'])
            ->findOrFail($id);

        $videoUrl = $this->buildMediaUrl($reel->url);
        $thumbnailUrl = $this->buildMediaUrl($reel->thumbnail);

        return response()->json([
            'reel' => [
                'id' => $reel->id,
                'user_id' => $reel->user_id,
                'user' => $reel->user ? [
                    'id' => $reel->user->id,
                    'name' => $reel->user->name ?? 'Unknown',
                ] : null,
                'title' => $reel->description ?: 'بدون عنوان',
                'description' => $reel->description,
                'video_url' => $videoUrl,
                'thumbnail_url' => $thumbnailUrl ?: null,
                'thumbnail_needs_capture' => empty($thumbnailUrl),
                'likes_count' => $reel->likes_count ?? 0,
                'comments_count' => $reel->comments_count ?? 0,
                'views_count' => $reel->views_count ?? 0,
                'gifts_count' => 0,
                'created_at' => $reel->created_at,
            ],
            'likes' => $reel->likes,
            'comments' => $reel->comments,
            'gifts' => [],
        ]);
    }

    public function getLikes($id)
    {
        $reel = Real::findOrFail($id);
        $likes = $reel->likes()->with(['user.profile', 'user.country'])->get();

        return response()->json(['likes' => $likes]);
    }

    public function getComments($id)
    {
        $reel = Real::findOrFail($id);
        $comments = $reel->comments()->with(['user.profile', 'user.country'])->orderBy('created_at', 'desc')->get();

        return response()->json(['comments' => $comments]);
    }
    
    public function getGifts($id)
    {
        return response()->json(['gifts' => []]);
    }
    
    public function batchCounts(Request $request)
    {
        try {
            $reelIds = $request->input('reel_ids', []);
            
            if (empty($reelIds) || !is_array($reelIds)) {
                return response()->json(['reels' => []]);
            }
            
            $reelIds = array_slice($reelIds, 0, 10);
            
            $reels = Real::whereIn('id', $reelIds)
                ->withCount(['likes', 'comments', 'Views'])
                ->get(['id'])
                ->map(function($reel) {
                    return [
                        'id' => $reel->id,
                        'likes_count' => $reel->likes_count ?? 0,
                        'comments_count' => $reel->comments_count ?? 0,
                        'views_count' => $reel->views_count ?? 0,
                        'gifts_count' => 0,
                    ];
                });
            
            return response()->json(['reels' => $reels]);
        } catch (\Exception $e) {
            // \Log::error('Error fetching batch counts', [
            //     'error' => $e->getMessage()
            // ]);
            
            return response()->json(['reels' => []], 500);
        }
    }
    
    public function update($id, \Illuminate\Http\Request $request = null)
    {
        $request = $request ?? request();
        
        try {
            $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
            ]);
            
            $reel = Real::findOrFail($id);
            
            if ($request->has('description')) {
                $reel->description = $request->description;
            }
            
            if ($request->has('title') && !$request->has('description')) {
                $reel->description = $request->title;
            }
            
            $reel->save();
            
    
            
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الريل بنجاح',
                'reel' => [
                    'id' => $reel->id,
                    'title' => $reel->description ?: 'بدون عنوان',
                    'description' => $reel->description,
                ]
            ]);
        } catch (\Exception $e) {
            // \Log::error('Error updating reel', [
            //     'reel_id' => $id,
            //     'error' => $e->getMessage()
            // ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحديث: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function destroy($id)
    {
        try {
            $reel = Real::findOrFail($id);
            
  
            $reel->likes()->delete();
            $reel->comments()->delete();
            $reel->Views()->delete();
            
            $reel->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الريل بنجاح'
            ]);
        } catch (\Exception $e) {
            // \Log::error('Error deleting reel', [
            //     'reel_id' => $id,
            //     'error' => $e->getMessage()
            // ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف: ' . $e->getMessage()
            ], 500);
        }
    }

    private function buildMediaUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $envBase = env('MEDIA_BASE_URL');
        if ($envBase) {
            return rtrim($envBase, '/') . '/' . ltrim($path, '/');
        }

        $base = getDriverUrl();
        if ($base) {
            return rtrim($base, '/') . '/' . ltrim($path, '/');
        }

        $gcsBase = config('filesystems.disks.gcs.url');
        if ($gcsBase) {
            return rtrim($gcsBase, '/') . '/' . ltrim($path, '/');
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
