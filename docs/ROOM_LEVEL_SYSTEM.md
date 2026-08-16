# 🏠 نظام Room Level - دليل التنفيذ الكامل

## 📋 نظرة عامة

نظام Room Level هو نظام لترقية مستوى الغرف بناءً على إجمالي الهدايا (Diamonds) التي تم استلامها في الغرفة، مع إعطاء مكافآت لصاحب الغرفة عند الوصول لكل مستوى.

---

## ✅ ما هو موجود بالفعل في المشروع

### 1. الجداول الموجودة في `rooms`

| الحقل | الوصف |
|-------|-------|
| `level` | المستوى الحالي للغرفة |
| `exp` | نقاط الخبرة |
| `total_diamond` | إجمالي الألماس المستلم |
| `level_id` | معرف المستوى |

**الملف:** `database/migrations/2024_05_19_105140_add_columns_to_rooms.php`

### 2. Services الموجودة

| الملف | الوصف |
|-------|-------|
| `app/Services/RoomLevelServices.php` | خدمة أساسية لتحديث المستوى |
| `Modules/Public/Http/Services/UpgradeRoomLevelServices.php` | خدمة ترقية المستوى |

### 3. مستويات VIP نوع 4

المستويات محفوظة في جدول `vips` بـ `type = 4`

### 4. Admin Controller

**الملف:** `app/Admin/Controllers/RoomVipController.php`

---

## 📋 ما تحتاج لإكماله أو تطويره

### الخطوة 1️⃣: إنشاء Module جديد (اختياري لكن مُفضل)

```
Modules/RoomLevel/
├── Config/
│   └── config.php
├── Database/
│   ├── Migrations/
│   │   ├── create_room_levels_table.php
│   │   ├── create_room_level_rewards_table.php
│   │   └── create_room_level_winners_table.php
│   ├── Seeders/
│   └── factories/
├── Entities/
│   ├── RoomLevel.php
│   ├── RoomLevelReward.php
│   └── RoomLevelWinner.php
├── Http/
│   ├── Controllers/
│   │   ├── api/
│   │   │   └── RoomLevelController.php
│   │   └── admin/
│   │       └── RoomLevelAdminController.php
│   ├── Middleware/
│   ├── Requests/
│   └── Services/
│       └── RoomLevelService.php
├── Providers/
│   └── RoomLevelServiceProvider.php
├── Resources/
│   └── views/
├── Routes/
│   ├── api.php
│   └── web.php
├── Transformers/
│   └── RoomLevelResource.php
├── Tests/
├── module.json
└── composer.json
```

---

### الخطوة 2️⃣: Migrations اللازمة

#### جدول `room_levels`

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoomLevelsTable extends Migration
{
    public function up()
    {
        Schema::create('room_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('img')->nullable();
            $table->integer('level');
            $table->bigInteger('exp_required'); // الـ diamonds المطلوبة
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('room_levels');
    }
}
```

#### جدول `room_level_rewards`

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoomLevelRewardsTable extends Migration
{
    public function up()
    {
        Schema::create('room_level_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_level_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['coins', 'vip', 'ware', 'badge', 'achievement']);
            $table->string('target'); // الـ ID للمكافأة أو القيمة
            $table->integer('expire')->nullable(); // مدة الصلاحية بالأيام
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('room_level_rewards');
    }
}
```

#### جدول `room_level_winners`

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoomLevelWinnersTable extends Migration
{
    public function up()
    {
        Schema::create('room_level_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_level_id')->constrained()->onDelete('cascade');
            $table->timestamp('achieved_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('room_level_winners');
    }
}
```

---

### الخطوة 3️⃣: إنشاء Models

#### RoomLevel.php

```php
<?php

namespace Modules\RoomLevel\Entities;

use Illuminate\Database\Eloquent\Model;

class RoomLevel extends Model
{
    protected $guarded = [];

    public function rewards()
    {
        return $this->hasMany(RoomLevelReward::class, 'room_level_id', 'id');
    }

    public function winners()
    {
        return $this->hasMany(RoomLevelWinner::class, 'room_level_id', 'id');
    }
}
```

#### RoomLevelReward.php

```php
<?php

namespace Modules\RoomLevel\Entities;

use Illuminate\Database\Eloquent\Model;

class RoomLevelReward extends Model
{
    protected $guarded = [];

    public function level()
    {
        return $this->belongsTo(RoomLevel::class, 'room_level_id', 'id');
    }
}
```

#### RoomLevelWinner.php

```php
<?php

namespace Modules\RoomLevel\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Models\Room;

class RoomLevelWinner extends Model
{
    protected $guarded = [];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function roomLevel()
    {
        return $this->belongsTo(RoomLevel::class, 'room_level_id', 'id');
    }
}
```

---

### الخطوة 4️⃣: تحديث Model الـ Room

أضف العلاقات التالية في `app/Models/Room.php`:

```php
// العلاقة مع المستوى الحالي
public function currentLevel()
{
    return $this->belongsTo(\Modules\RoomLevel\Entities\RoomLevel::class, 'level_id');
}

// تاريخ المستويات المحققة
public function levelHistory()
{
    return $this->hasMany(\Modules\RoomLevel\Entities\RoomLevelWinner::class);
}
```

---

### الخطوة 5️⃣: إنشاء Service

#### RoomLevelService.php

```php
<?php

namespace Modules\RoomLevel\Http\Services;

use App\Models\Room;
use App\Models\Ware;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Enums\UserCoinLogType;
use Modules\Vip\Entities\OVip;
use App\Helpers\UserCoinLogHelper;
use Modules\RoomLevel\Entities\RoomLevel;
use Modules\RoomLevel\Entities\RoomLevelWinner;
use Modules\Achievement\Entities\UserAchievementLevel;

class RoomLevelService
{
    /**
     * تحديث مستوى الغرفة بعد استلام هدية
     */
    public function updateRoomLevel(Room $room, int $diamonds): void
    {
        $room->total_diamond += $diamonds;
        $newLevel = $this->calculateLevel($room->total_diamond);
        
        if ($newLevel && $newLevel->id > ($room->level_id ?? 0)) {
            $oldLevelId = $room->level_id;
            $room->level_id = $newLevel->id;
            $room->level = $newLevel->level;
            $room->exp = $newLevel->exp_required;
            
            // تسجيل الإنجاز
            $this->recordLevelAchievement($room, $newLevel);
            
            // إعطاء المكافآت
            $this->grantRewards($room, $newLevel);
        }
        
        $room->save();
    }

    /**
     * حساب المستوى بناءً على إجمالي الألماس
     */
    private function calculateLevel(int $totalDiamond): ?RoomLevel
    {
        return RoomLevel::where('exp_required', '<=', $totalDiamond)
            ->orderByDesc('level')
            ->first();
    }

    /**
     * تسجيل تحقيق المستوى
     */
    private function recordLevelAchievement(Room $room, RoomLevel $level): void
    {
        RoomLevelWinner::create([
            'room_id' => $room->id,
            'room_level_id' => $level->id,
            'achieved_at' => now(),
        ]);
    }

    /**
     * إعطاء المكافآت لصاحب الغرفة
     */
    private function grantRewards(Room $room, RoomLevel $level): void
    {
        $user = $room->owner;
        if (!$user) return;

        $notifications = [];

        foreach ($level->rewards as $reward) {
            switch ($reward->type) {
                case 'coins':
                    $amountBefore = $user->di;
                    UserCoinLogHelper::logByType(
                        $user->id,
                        $reward->target,
                        $amountBefore,
                        UserCoinLogType::ROOM_LEVEL,
                    );
                    $user->di += $reward->target;
                    $user->save();

                    $notifications[] = [
                        'title' => __('Coin Reward'),
                        'body'  => str_replace(':coin', $reward->target, __('You have received :coin coin.')),
                    ];
                    break;

                case 'vip':
                    $vip = OVip::query()->find($reward->target);
                    if ($vip) {
                        UserCommon::addVipToUser($user, $vip, $reward->expire, null, 'room-level');
                        $notifications[] = [
                            'title' => __('congratulations'),
                            'body'  => __('vip_message', ['vip_name' => $vip->name]),
                        ];
                    }
                    break;

                case 'ware':
                    $ware = Ware::query()->find($reward->target);
                    if ($ware) {
                        UserCommon::addEvintsWareToUser($user, $ware, $reward->expire, null, 'room-level');
                        $notifications[] = [
                            'title' => __('congratulations'),
                            'body'  => str_replace(':ware', $ware->name ?? '', __('You have received a gift: :ware')),
                        ];
                    }
                    break;

                case 'badge':
                    Common::userBadge($user->id, $reward->target, $reward->expire, 'room-level');
                    $notifications[] = [
                        'title' => __('congratulations'),
                        'body'  => __('badge_gift_message'),
                    ];
                    break;

                case 'achievement':
                    UserAchievementLevel::create([
                        'user_id' => $user->id,
                        'custom_image' => $reward->target,
                    ]);
                    $notifications[] = [
                        'title' => __('Achievement Reward'),
                        'body'  => __('You have received a new achievement.'),
                    ];
                    break;
            }
        }

        $this->sendNotifications($user, $notifications);
    }

    /**
     * إرسال الإشعارات
     */
    private function sendNotifications($user, array $notifications): void
    {
        foreach ($notifications as $notification) {
            Common::sendOfficialMessage($user->id, $notification['title'], $notification['body']);
            Common::send_firebase_notification($user->notification_id, $notification['title'], $notification['body']);
        }
    }

    /**
     * الحصول على معلومات مستوى الغرفة
     */
    public function getRoomLevelInfo(Room $room): array
    {
        $currentLevel = $room->currentLevel;
        $nextLevel = RoomLevel::where('level', '>', $room->level ?? 0)
            ->orderBy('level')
            ->first();

        return [
            'current_level' => $currentLevel ? [
                'level' => $currentLevel->level,
                'name' => app()->getLocale() === 'ar' ? $currentLevel->name_ar : $currentLevel->name_en,
                'image' => $currentLevel->img,
            ] : null,
            'next_level' => $nextLevel ? [
                'level' => $nextLevel->level,
                'name' => app()->getLocale() === 'ar' ? $nextLevel->name_ar : $nextLevel->name_en,
                'exp_required' => $nextLevel->exp_required,
                'remaining' => max(0, $nextLevel->exp_required - ($room->total_diamond ?? 0)),
            ] : null,
            'total_diamond' => $room->total_diamond ?? 0,
            'progress_percentage' => $this->calculateProgress($room, $currentLevel, $nextLevel),
        ];
    }

    /**
     * حساب نسبة التقدم
     */
    private function calculateProgress(Room $room, ?RoomLevel $current, ?RoomLevel $next): float
    {
        if (!$next) return 100;
        
        $currentExp = $current ? $current->exp_required : 0;
        $nextExp = $next->exp_required;
        $roomDiamond = $room->total_diamond ?? 0;

        if ($nextExp <= $currentExp) return 0;

        $progress = (($roomDiamond - $currentExp) / ($nextExp - $currentExp)) * 100;
        return round(max(0, min(100, $progress)), 2);
    }
}
```

---

### الخطوة 6️⃣: إنشاء API Controller

#### RoomLevelController.php

```php
<?php

namespace Modules\RoomLevel\Http\Controllers\api;

use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RoomLevel\Entities\RoomLevel;
use Modules\RoomLevel\Http\Services\RoomLevelService;

class RoomLevelController extends Controller
{
    public function __construct(private RoomLevelService $roomLevelService)
    {
    }

    /**
     * الحصول على قائمة المستويات
     */
    public function index()
    {
        $levels = RoomLevel::with('rewards')
            ->orderBy('level', 'asc')
            ->get()
            ->map(function ($level) {
                return [
                    'id' => $level->id,
                    'level' => $level->level,
                    'name' => app()->getLocale() === 'ar' ? $level->name_ar : $level->name_en,
                    'image' => $level->img,
                    'exp_required' => $level->exp_required,
                    'rewards' => $level->rewards->map(function ($reward) {
                        return [
                            'type' => $reward->type,
                            'target' => $reward->target,
                            'expire' => $reward->expire,
                        ];
                    }),
                ];
            });

        return Common::apiResponse(true, '', $levels, 200);
    }

    /**
     * الحصول على معلومات مستوى غرفة معينة
     */
    public function roomProgress(int $roomId)
    {
        $room = \App\Models\Room::find($roomId);
        
        if (!$room) {
            return Common::apiResponse(false, __('Room not found'), null, 404);
        }

        $info = $this->roomLevelService->getRoomLevelInfo($room);

        return Common::apiResponse(true, '', $info, 200);
    }
}
```

---

### الخطوة 7️⃣: إنشاء Admin Controller

#### RoomLevelAdminController.php

```php
<?php

namespace Modules\RoomLevel\Http\Controllers\admin;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use Modules\RoomLevel\Entities\RoomLevel;

class RoomLevelAdminController extends MainController
{
    public $permission_name = 'room-levels';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Room Levels'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('Room Levels'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('Room Levels'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Room Levels'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new RoomLevel());
        $grid->model()->orderBy('level');

        $grid->column('id', __('Id'));
        $grid->column('level', __('Level'))->editable();
        $grid->column('name_ar', __('Name AR'))->editable();
        $grid->column('name_en', __('Name EN'))->editable();
        $grid->column('exp_required', __('Exp Required'))->display(function ($value) {
            return number_format($value);
        })->editable();
        $grid->column('img', __('Image'))->image('', '50');
        $grid->column('created_at', __('Created at'));

        $grid->quickSearch('name_ar', 'name_en');
        $grid->disableExport();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(RoomLevel::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('level', __('Level'));
        $show->field('name_ar', __('Name AR'));
        $show->field('name_en', __('Name EN'));
        $show->field('exp_required', __('Exp Required'));
        $show->field('img', __('Image'))->image();
        $show->field('created_at', __('Created at'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new RoomLevel());

        $form->number('level', __('Level'))->required();
        $form->text('name_ar', __('Name AR'));
        $form->text('name_en', __('Name EN'));
        $form->number('exp_required', __('Exp Required'))->required();
        $form->image('img', __('Image'));

        $form->hasMany('rewards', __('Rewards'), function (Form\NestedForm $form) {
            $form->select('type', __('Type'))->options([
                'coins' => __('Coins'),
                'vip' => __('VIP'),
                'ware' => __('Ware'),
                'badge' => __('Badge'),
                'achievement' => __('Achievement'),
            ])->required();
            $form->text('target', __('Target/Value'))->required();
            $form->number('expire', __('Expire (days)'));
        });

        return $form;
    }
}
```

---

### الخطوة 8️⃣: إضافة Routes

#### API Routes (`Modules/RoomLevel/Routes/api.php`)

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\RoomLevel\Http\Controllers\api\RoomLevelController;

Route::middleware(['auth:sanctum', 'update.last.seen'])->group(function () {
    Route::prefix('room-level')->group(function () {
        Route::get('/levels', [RoomLevelController::class, 'index']);
        Route::get('/room/{roomId}', [RoomLevelController::class, 'roomProgress']);
    });
});
```

#### Admin Routes (`app/Admin/routes.php`)

أضف السطر التالي:

```php
Route::resource('room-levels', \Modules\RoomLevel\Http\Controllers\admin\RoomLevelAdminController::class);
```

---

### الخطوة 9️⃣: تفعيل الـ Module

في ملف `modules_statuses.json`:

```json
{
    "RoomLevel": true
}
```

---

## 🔄 آلية العمل

```
┌─────────────────────────────────────────────────────────────┐
│                    عند إرسال هدية في الغرفة                  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│         استدعاء RoomLevelService::updateRoomLevel()         │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              إضافة الـ diamonds إلى total_diamond            │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    حساب المستوى الجديد                       │
└─────────────────────────────────────────────────────────────┘
                              │
                    ┌─────────┴─────────┐
                    │                   │
              لم يترقى               ترقى المستوى
                    │                   │
                    ▼                   ▼
              ┌─────────┐     ┌─────────────────────┐
              │  حفظ    │     │ تسجيل في winners    │
              │ الغرفة  │     │ + إعطاء المكافآت   │
              └─────────┘     │ + إرسال إشعارات    │
                              └─────────────────────┘
```

---

## 📝 ملاحظات مهمة

1. **النظام الحالي:** يستخدم جدول `vips` بـ `type = 4` للمستويات
2. **البديل:** يمكنك إنشاء جدول منفصل `room_levels` لمزيد من المرونة
3. **التكامل:** يجب استدعاء `RoomLevelService` من `GiftLogController` عند إرسال الهدايا
4. **الأداء:** استخدم Queue للمكافآت الكبيرة

---

## 🚀 أوامر التنفيذ

```bash
# إنشاء Module جديد
php artisan module:make RoomLevel

# تشغيل Migrations
php artisan module:migrate RoomLevel

# إنشاء Seeder للمستويات الافتراضية
php artisan module:make-seed RoomLevelSeeder RoomLevel

# مسح Cache
php artisan cache:clear
php artisan config:clear
```

---

**تاريخ الإنشاء:** January 22, 2026  
**المشروع:** Meow Live  
**الإصدار:** 1.0
