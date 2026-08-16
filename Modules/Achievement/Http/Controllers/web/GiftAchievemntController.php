<?php

namespace Modules\Achievement\Http\Controllers\web;


use App\Models\Gift;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Admin\Controllers\MainController;
use Encore\Admin\Auth\Permission;
use Illuminate\Support\Facades\Validator;
use Modules\Achievement\Entities\Achievement;
use Modules\Achievement\Enums\AchievementType;
use Modules\Achievement\Entities\GiftAchievement;
use Modules\Achievement\Entities\AchievementLevel;
;

class GiftAchievemntController extends MainController
{

    public $permission_name = 'gift_achievement';
    public function postAddGiftAchievemnt(Request $request){
        Permission::check('create-' . $this->permission_name);
        $user_id=$request->user_id;
        $gift_id=$request->gift_id;
        $achievement_id=$request->achievement_id ?? Achievement::query()->where('type', AchievementType::GIFT_TARGET)->value('id');
        $validator = Validator::make($request->all(), [
            'gift_id' => 'required|unique:gift_achievements,gift_id',
            'achievement_id' =>'required|exists:achievements,id',
            'user_id'=>'required|exists:users,id',
        ]);
        if ($validator->fails()) {
            return back()->with('message', 'الهدية مستخدمة من قبل ');
        }
         GiftAchievement::create([
           'achievement_id'=>$achievement_id,
           'gift_id'=>$gift_id,
           'user_id'=>$user_id
         ]);
         $prefix = request()->route()->getPrefix();
         $baseUrl = ($prefix === 'preview/admin') ? url('preview/admin/gift-achievements') : url('admin/gift-achievements');
         return Redirect::to($baseUrl);
         
       }


       public function postAddGiftAchievementLevel(Request $request){
          Permission::check('create-' . $this->permission_name);

          $target_type=$request->target_type;
          $target=$request->target;
          $achievement_id=$request->achievement_id;
          $ar_description=$request->ar_description;
          $en_description=$request->en_description;
          $valid_image = Common::upload('images', $request->file('valid_image'));
          $invalid_image = Common::upload('images', $request->file('invalid_image'));

          AchievementLevel::create([
           'target_type' => $target_type,
           'target' => $target,
           'valid_image' => $valid_image,
           'invalid_image' => $invalid_image,
           'achievement_id'=>$achievement_id,
           'ar_description'=>$ar_description,
           'en_description'=>$en_description
         ]);
         return  redirect()->route(nameRoute('admin.achievements.index'));



        }

        public function posteditGiftAchievementLevel(Request $request){
         Permission::check('edit-' . $this->permission_name);

         $achievementLevel = AchievementLevel::findOrFail($request->id);

         if ($request->target_type){
           $achievementLevel->target_type = $request->target_type;
         }
         if ($request->target){
           $achievementLevel->target = $request->target;
         }
         if ($request->ar_description){
           $achievementLevel->ar_description = $request->ar_description;
         }
         if ($request->en_description){
           $achievementLevel->en_description = $request->en_description;
         }

         if ($request->hasFile('valid_image')) {
             $validImage = Common::upload('images', $request->file('valid_image'));
             $achievementLevel->valid_image = $validImage;
         }

         if ($request->hasFile('invalid_image')) {
             $invalidImage = Common::upload('images', $request->file('invalid_image'));
             $achievementLevel->invalid_image = $invalidImage;
         }

         // Save the updated achievement level
         $achievementLevel->save();
        return  redirect()->route(nameRoute('admin.achievements.index'));



       }

}
