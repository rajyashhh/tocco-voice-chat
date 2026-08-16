<?php

namespace App\Admin\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\helper\HelperType; 
use App\Models\Notification;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Cache;
use App\Models\NotificationTranslation;
use App\Admin\Controllers\MainController;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\AdminController;

require_once app_path('helper/helperType.php'); 

class NotificationsTemplatesController extends MainController
{


  
    public $permission_name = 'notification';
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'NotificationTemplate';

    public function index(\Encore\Admin\Layout\Content $content)
    {
        return parent::index($content
            ->title(__('Notification Templates'))
            ->body($this->grid()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    
     protected function grid()
     {
         $grid = new Grid(new Notification());
     
         $grid->column('key', __('Key'));
     
         $languages = ['ar' => 'Arabic', 'en' => 'English', 'tr' => 'Turkish', 'hi' => 'Indian'];
     
    //      foreach ($languages as $code => $lang) {
    //          $grid->column($code, __($lang))->display(function () use ($code, $lang) {
    //              $translation = $this->translations->where('language', $code)->first();
    //              $message = $translation ? htmlentities($translation->message) : '-';
    //              $title = $translation ? htmlentities($translation->title) : '-';

    //              return "<a href='#' class='view-lang' data-title='{$title}'  data-lang='{$lang}' data-value='{$message}'>عرض</a>";
    //          });
    //      }
    //      Admin::script("
    //      $(document).ready(function () {
    //          console.log('Modal Script Loaded');
     
    //          $('.view-lang').click(function (e) {
    //              e.preventDefault();
     
    //              var lang = $(this).data('lang');
    //              var title = $(this).data('title');
    //              var message = $(this).data('value');
     
    //              $('#modalLangTitle').text(lang + ' Content');
    //              $('#modalNotifTitle').text(title);
    //              $('#modalNotifMessage').text(message);
     
    //              $('#langModal').modal('show');
    //          });
    //      });
    //  ");
     
    
    
     
         $grid->tools(function ($tools) {
            $tools->append("<a href='/admin/notification-templates/create' class='btn btn-success'>إنشاء جديد</a>");
        });
         return $grid;
     }
     
     


     
    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Notification::with('translations')->findOrFail($id));
    
        $show->field('key', __('Name'));
    
        $languages = ['ar' => 'Arabic', 'en' => 'English', 'tr' => 'Turkish', 'hi' => 'Indian'];
    
        foreach ($languages as $code => $lang) {
            $show->field($lang, __($lang))->as(function () use ($code, $lang) {
                $translation = $this->translations->where('language', $code)->first();
                if ($translation) {
                    return "<strong>📌 " . __("{$lang} Title") . ":</strong> {$translation->title}<br> 
                            <strong>📩 " . __("{$lang} Message") . ":</strong> {$translation->message}";
                }
                return '-';
            })->unescape();
        }
    
        return $show;
    }
    
    


    // public function create(\Encore\Admin\Layout\Content $content)
    // {
    //     return $content
    //         ->title('إنشاء قالب جديد')
    //         ->body(view('admin.notifications.create'));
    // }

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }

    public function edit($id, \Encore\Admin\Layout\Content $content)
    {
        $template = Notification::with('translations')->findOrFail($id);

        return $content
            ->title('تعديل القالب')
            ->body(view('admin.notifications.edit', compact('template')));
    }



 
    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Notification());
    
        $form->text('key', __('Key'))->rules(function ($form) {
            return $form->model()->id
                ? 'required|max:255|unique:notifications,key,' . $form->model()->id
                : 'required|max:255|unique:notifications,key';
        });
    
        // $languages = ['ar' => 'Arabic', 'en' => 'English', 'tr' => 'Turkish', 'hi' => 'Indian'];
    
        // foreach ($languages as $code => $lang) {
        //     $form->textarea("title_{$code}", __("{$lang} Title"))->rules('nullable|max:255');
        //     $form->textarea("message_{$code}", __("{$lang} Message"))->rules('nullable');
        // }

    // foreach ($languages as $code => $lang) {
    //     $form->textarea("title_{$code}", __("{$lang} Title"))
    //         ->default(function ($form) use ($code) {
    //             if ($form->model()->id) {
    //                 $translation = $form->model()->translations->where('language', $code)->first();
    //                 return $translation ? $translation->title : null;
    //             }
    //             return null;
    //         })
    //         ->rules('nullable|max:255');

    //     $form->textarea("message_{$code}", __("{$lang} Message"))
    //         ->default(function ($form) use ($code) {
    //             if ($form->model()->id) {
    //                 $translation = $form->model()->translations->where('language', $code)->first();
    //                 return $translation ? $translation->message : null;
    //             }
    //             return null;
    //         })
    //         ->rules('nullable');
    // }

        // $form->ignore(['title_ar', 'message_ar', 'title_en', 'message_en', 'title_tr', 'message_tr', 'title_hi', 'message_hi']);

    
        return $form;
    }

    // تضمين الملف للوصول إلى المتغيرات

   
 





    
}    
