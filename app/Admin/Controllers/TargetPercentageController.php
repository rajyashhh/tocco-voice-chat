<?php

namespace App\Admin\Controllers;

use Encore\Admin\Layout\Content;
use Illuminate\Support\HtmlString;
use App\Http\Controllers\Controller;
use App\Admin\Controllers\MainController;


class TargetPercentageController extends MainController
{
    public $permission_name = 'target-percentage';
    public function index(Content $content)
    {
        $route = 'admin.target-percentage';

        $hours = settings()->get('hours');
        $days = settings()->get('days');
        $moments = settings()->get('moments');
        $reels = settings()->get('reels');
        $diamonds = settings()->get('diamonds');

        $errors = session()->get('errors');
        $errorMessage = $errors ? $errors->first('msg') : null;

        $isRTL = app()->getLocale() === 'ar';
        $textDirection = $isRTL ? 'rtl' : 'ltr';
        $labelAlign = $isRTL ? 'right' : 'left';
        $buttonAlignStyle = $isRTL ? 'text-align: left;' : 'text-align: right;';

        $form = '<div class="box box-primary" style="max-width: 800px; margin: 0 auto; direction: '.$textDirection.';">';
        $form .= '<div class="box-header with-border">';
        $form .= '<h3 class="box-title text-center" style="font-size: 24px; margin: 10px 0;">' . __('Target Percentage') . '</h3>';
        $form .= '</div>';

        $form .= '<div class="box-body">';
        $form .= '<form method="POST" action="' . route($route) . '" class="form-horizontal">';
        $form .= csrf_field();

        if ($errorMessage) {
            $form .= '<div class="alert alert-danger text-center" style="margin-bottom: 20px;">' . $errorMessage . '</div>';
        }

        $fields = [
            ['id' => 'hours', 'value' => $hours, 'label' => __('Hours')],
            ['id' => 'days', 'value' => $days, 'label' => __('Days')],
            ['id' => 'moments', 'value' => $moments, 'label' => __('Moments')],
            ['id' => 'reels', 'value' => $reels, 'label' => __('Reels')],
            ['id' => 'diamonds', 'value' => $diamonds, 'label' => __('Diamonds')],
        ];

        foreach ($fields as $field) {
            $form .= '<div class="form-group">';
            if ($isRTL) {
                $form .= '<div class="col-sm-10">';
                $form .= '<input min="0" type="number" id="'.$field['id'].'" name="'.$field['id'].'" placeholder="'.$field['label'].'" value="'.$field['value'].'" class="form-control" required>';
                $form .= '</div>';
                $form .= '<label for="'.$field['id'].'" class="col-sm-2 control-label" style="text-align: '.$labelAlign.';">'.$field['label'].'</label>';
            } else {
                $form .= '<label for="'.$field['id'].'" class="col-sm-2 control-label" style="text-align: '.$labelAlign.';">'.$field['label'].'</label>';
                $form .= '<div class="col-sm-10">';
                $form .= '<input min="0" ' . ($field['id'] == 'diamonds' ? 'max="100" ' : '') . 'type="number" id="'.$field['id'].'" name="'.$field['id'].'" placeholder="'.$field['label'].'" value="'.$field['value'].'" class="form-control" required>';
                $form .= '</div>';
            }
            $form .= '</div>';
        }

        $form .= '<div class="form-group">';
        $form .= '<div class="col-sm-12" style="'.$buttonAlignStyle.'">';
        $form .= '<button type="submit" class="btn btn-primary" style="padding: 8px 20px; font-size: 16px;">' . __('Submit') . '</button>';
        $form .= '</div></div>';

        $form .= '</form>';
        $form .= '</div></div>';

        return parent::index($content
            ->title(trans('Salary Distribution Ratio'))
            ->body(new \Illuminate\Support\HtmlString($form)));
    }





}
