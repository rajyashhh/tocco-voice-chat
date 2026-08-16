<?php

namespace Modules\Achievement\Http\Controllers\web;

use App\Models\Gift;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;

use Illuminate\Support\HtmlString;
use App\Admin\Controllers\MainController;
use Modules\Achievement\Entities\GiftAchievement;


class AddGiftUserAchievemntsController extends MainController
{
    public $permission_name = 'gift_achievement';
    public function index(Content $content)
    {
        $achievement_id = request()->input('achievement_id');
        $mainModel = GiftAchievement::with('user')->get();
        if(isset($achievement_id)){

                $route = 'postAddGiftAchievemnt';

                $gift=Gift::where('type',7)->get();
                $users=User::get();
                $form = '<div style= "    height: 417px;
                ">';

                $form .= '<form method="POST" action="' . route($route) . '"  class="formcustumPage">';

                $form .= csrf_field();
                $form .= '<input type="hidden" id="currentVersion" name="achievement_id" placeholder="" value="'.$achievement_id.'" class="inputs_cus_form">';

                $form .= '<label for="currentVersion" class="control-label">المستخدمين</label>';
                $form .= '<select name="user" id="cars" class="inputs_cus_form">';
                foreach ($users as  $label) {
                    $form .= '<option value="' . $label->id . '">' . $label->name . '</option>';
                }
                $form .= '</select>';

                $form .= '<label for="currentVersion" class="control-label">الهدايا</label>';
                $form .= '<select name="gift" id="cars" class="inputs_cus_form">';
                foreach ($gift as  $label) {
                    $form .= '<option value="' . $label->id . '">' . $label->name . '</option>';
                }
                $form .= '</select>';
                $form .= '<button type="submit" class="button_form_cus">Submit</button>';
                $form .= '</form>';
                $form .= '</div>';

                $form .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">';
                $form .= '<div>';
                $form .= '<table class="table">';
                $form .= '<thead>';
                $form .= '<tr scope="row">';
                $form .= '<th scope="col">Header 1</th>';
                $form .= '<th scope="col">Header 2</th>';
                $form .= '</tr>';
                $form .= '</thead>';
                $form .= '<tbody>';
                foreach($mainModel as $item){
                $form .= '<tr scope="row">';
                $form .= '<td scope="col">"'.$item->user->name .'"</td>';
                $form .= '<td scope="col">"'. $item->user->name .'"</td>';
                $form .= '</tr>';
                 }
                $form .= '</tbody>';
                $form .= '</table>';
                $form .= '</div>';





                return $content
                    ->header('Custom Page')
                    ->description('This is a custom page')
                    ->body(new HtmlString($form));
        }
    }

}
