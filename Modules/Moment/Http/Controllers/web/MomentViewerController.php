<?php

namespace Modules\Moment\Http\Controllers\web;

use Encore\Admin\Layout\Content;
use Encore\Admin\Auth\Permission;
use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use Illuminate\Http\JsonResponse;
use Modules\Moment\Entities\Moment;
use Modules\Moment\Entities\MomentCommint;
use Modules\Moment\Entities\MomentLikes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MomentViewerController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Moment Viewer';

    public $permission_name = 'moment';

    /**
     * عرض صفحة Moment Viewer
     *
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->title(__('Moment Viewer'))
            ->description(__('View moments like in the app'))
            ->body(view('moment::viewer.index'));
    }

    /**
     * عرض ملف CSS الديناميكي مع ألوان من config
     *
     * @return \Illuminate\Http\Response
     */
    public function getViewerCss()
    {
        $css = view('moment::viewer.viewer-css')->render();

        return response($css)
            ->header('Content-Type', 'text/css')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * جلب قائمة الـ Moments بناءً على الترتيب المحدد
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMoments(Request $request)
    {
        try {
            $sortBy = $request->get('sort', config('moment.viewer.default_sort', 'newest')); // random, newest, oldest
            $perPage = $request->get('per_page', 10); // تحميل 10 moments في كل مرة
            $page = $request->get('page', 1); // رقم الصفحة الحالية
            $countryId = Common::filterCountryIds();
            $search = $request->get('search', '');
            $userId = $request->get('user_id', ''); // فلتر بالمعرف

            $query = Moment::select(['id', 'user_id', 'description', 'img', 'created_at'])
                ->with([
                    'user' => fn($q) => $q->select(['id', 'uuid', 'name'])
                        ->with([
                            'profile:id,user_id,avatar',
                        ]),
                    'images' => fn($q) => $q->select(['id', 'moment_id', 'image']),
                ])
                ->withCount(['likes', 'comments', 'gifts']);

            // فلتر بالمعرف (ID)
            if (!empty($userId)) {
                $query->where('user_id', $userId);
            }

            // تطبيق فلتر البحث بالمستخدم
            if (!empty($search)) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('uuid', 'like', "%{$search}%");
                });
            }

            // تطبيق فلتر الدولة
            if ($countryId) {
                $query->whereHas('user', function ($q) use ($countryId) {
                    $q->whereIn('country_id', $countryId);
                });
            }

            // تطبيق الترتيب
            if ($sortBy === 'random') {
                // جلب عشوائي مباشر - بدون session
                $query->inRandomOrder();
                $moments = $query->paginate($perPage);

                return response()->json([
                    'success' => true,
                    'data' => $moments->items(),
                    'pagination' => [
                        'current_page' => $moments->currentPage(),
                        'last_page' => $moments->lastPage(),
                        'per_page' => $moments->perPage(),
                        'total' => $moments->total(),
                    ]
                ]);
            }

            // الترتيب العادي
            switch ($sortBy) {
                case 'newest':
                    $query->orderByDesc('created_at');
                    break;
                case 'oldest':
                    $query->orderBy('created_at');
                    break;
            }

            $moments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $moments->items(),
                'pagination' => [
                    'current_page' => $moments->currentPage(),
                    'last_page' => $moments->lastPage(),
                    'per_page' => $moments->perPage(),
                    'total' => $moments->total(),
                ]
            ]);
        } catch (\Exception $e) {
            //\Log::error('Error loading moments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading moments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب تفاصيل Moment محدد
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMoment($id)
    {
        $moment = Moment::with([
            'user' => fn($q) => $q->select(['id', 'uuid', 'name', 'special_id', 'sender_level', 'received_level', 'charge_level', 'now_room_uid', 'type_user', 'manger_type_id', 'color_id', 'image_color_id', 'is_bd'])
                ->with([
                    'packs' => fn($q) => $q->whereIn('type', [4, 25, 18])
                        ->where(fn($q) => $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp))
                        ->where('is_used', 1)
                        ->with(['ware:id,img1,img2,show_img,color,value']),
                    'UserVip' => fn($q) => $q->with('OVip:id,img'),
                    'receiverLevel:id,img,level',
                    'senderLevel:id,img,level',
                    'chargeLevel:id,img,level',
                    'profile:id,user_id,avatar',
                    'room:id,uid,room_pass',
                    'shippingAgency:id,app_owner_id,name,img'
                ]),
            'images:id,moment_id,image',
            'likes.user:id,uuid,name',
            'comments.user:id,uuid,name',
        ])
            ->withCount(['likes', 'comments'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $moment
        ]);
    }

    /**
     * جلب قائمة المستخدمين الذين قاموا بعمل Like
     *
     * @param int $momentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLikes($momentId, Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $likes = MomentLikes::where('moment_id', $momentId)
            ->with(['user' => fn($q) => $q->select(['id', 'uuid', 'name'])
                ->with(['profile:id,user_id,avatar'])
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $likes->items(),
            'pagination' => [
                'current_page' => $likes->currentPage(),
                'last_page' => $likes->lastPage(),
                'per_page' => $likes->perPage(),
                'total' => $likes->total(),
            ]
        ]);
    }

    /**
     * جلب قائمة التعليقات
     *
     * @param int $momentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getComments($momentId, Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $comments = MomentCommint::where('moment_id', $momentId)
            ->with(['user' => fn($q) => $q->select(['id', 'uuid', 'name'])
                ->with(['profile:id,user_id,avatar'])
            ])
            ->orderBy('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $comments->items(),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ]
        ]);
    }

    /**
     * حذف تعليق
     *
     * @param int $commentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteComment($commentId)
    {
        Permission::check('delete-' . $this->permission_name);

        try {
            $comment = MomentCommint::findOrFail($commentId);
            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => __('Comment deleted successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete comment')
            ], 500);
        }
    }

    /**
     * جلب قائمة الهدايا
     *
     * @param int $momentId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGifts($momentId, Request $request)
    {
        $sortBy = $request->get('sort', 'newest'); // newest, highest_value

        $query = DB::table('moment_user_gifts')
            ->where('moment_id', $momentId)
            ->join('users', 'moment_user_gifts.user_id', '=', 'users.id')
            ->join('gifts', 'moment_user_gifts.gift_id', '=', 'gifts.id')
            ->select([
                'moment_user_gifts.*',
                'users.name as user_name',
                'users.uuid as user_uuid',
                DB::raw('COALESCE(gifts.e_name, gifts.name) as gift_name'),
                'gifts.img as gift_img',
                'gifts.price as gift_value'
            ]);

        // تطبيق الترتيب
        switch ($sortBy) {
            case 'highest_value':
                $query->orderByDesc('gifts.price');
                break;
            case 'newest':
            default:
                $query->orderByDesc('moment_user_gifts.created_at');
                break;
        }

        $gifts = $query->get();

        return response()->json([
            'success' => true,
            'data' => $gifts,
            'total' => $gifts->count()
        ]);
    }

    /**
     * تحديث وصف Moment
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDescription(Request $request, $id)
    {
        Permission::check('edit-' . $this->permission_name);

        try {
            $moment = Moment::findOrFail($id);

            $request->validate([
                'description' => 'nullable|string|max:5000'
            ]);

            $moment->description = $request->input('description');
            $moment->save();

            return response()->json([
                'success' => true,
                'message' => __('Description updated successfully'),
                'description' => $moment->description
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to update description: ') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف Moment
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteMoment($id)
    {
        Permission::check('delete-' . $this->permission_name);

        try {
            $moment = Moment::findOrFail($id);

            // حذف البيانات المرتبطة
            $moment->likes()->delete();
            $moment->comments()->delete();
            $moment->images()->delete();
            DB::table('moment_user_gifts')->where('moment_id', $id)->delete();

            // حذف الـ Moment
            $moment->delete();

            return response()->json([
                'success' => true,
                'message' => __('Moment deleted successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete moment: ') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إعادة تعيين seed للترتيب العشوائي
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetRandomSeed(Request $request)
    {
        $keysDeleted = 0;

        // مسح جميع الـ random IDs المخزنة في الـ session
        $sessionKeys = $request->session()->all();
        foreach ($sessionKeys as $key => $value) {
            if (strpos($key, 'moment_random_ids_') === 0) {
                $request->session()->forget($key);
                $keysDeleted++;
            }
        }

        // مسح الـ seed القديم (للتوافق مع الإصدارات السابقة)
        $request->session()->forget('moment_random_seed');

        return response()->json([
            'success' => true,
            'message' => __('Random order reset successfully'),
            'keys_deleted' => $keysDeleted
        ]);
    }

    public function getUsersWithMoments(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search', '');
            $perPage = $request->get('per_page', 20);
            $countryId = Common::filterCountryIds();

            $query = \App\Models\User::select(['id', 'uuid', 'name'])
                ->withCount('moments')
                ->having('moments_count', '>', 0)
                ->with(['profile:id,user_id,avatar']);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('uuid', 'like', "%{$search}%")
                        ->orWhere('id', $search);
                });
            }

            if ($countryId) {
                $query->whereIn('country_id', $countryId);
            }

            $users = $query->orderByDesc('moments_count')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

}
