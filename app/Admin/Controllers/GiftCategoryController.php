<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use Encore\Admin\Form;

use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\GiftCategory;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use App\Admin\Controllers\MainController;

class GiftCategoryController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'GiftCategory';
    public $permission_name = 'gift-categories';




    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Gift Categories'))
            ->body($this->grid()));
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('Gift Categories'))
            ->body($this->detail($id)));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('Gift Categories'))
            ->body($this->form($id)->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Gift Categories'))
            ->body($this->form()));
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update($id)
    {
        // Clear ALL caches before update (Octane fix)
        Cache::flush();
        
        $response = parent::update($id);
        
        // Force fresh data from database for Octane
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        
        Cache::flush();
        
        return $response;
    }
    
    /**
     * Clear cache for Octane (called from JavaScript before sorting)
     */
    public function clearCache()
    {
        try {
            Cache::tags(['gift_categories'])->flush();

            return response()->json([
                'status' => true,
                'message' => 'Cache cleared'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle sort update from grid-sortable extension (for Octane compatibility)
     */
    public function sortUpdate()
    {
        $sorts = request()->input('_sort');
        
        if (empty($sorts)) {
            return response()->json([
                'status' => false,
                'message' => 'No sort data provided'
            ]);
        }
        
        try {
            // Clear ALL caches before updating (Octane requirement)
            Cache::flush();
            \Artisan::call('cache:clear');
            
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            
            // Use DB transaction for atomicity
            \DB::beginTransaction();
            
            $updated = 0;
            foreach ($sorts as $sort) {
                // Use raw DB query to bypass Eloquent caching
                $result = \DB::table('gift_categories')
                    ->where('id', $sort['id'])
                    ->update([
                        'sort' => $sort['sort'],
                        'updated_at' => now()
                    ]);
                $updated += $result;
                
            }
            
            \DB::commit();
            
            // Clear all caches after updating
            Cache::flush();
            \Artisan::call('cache:clear');
            
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            
            // Force PHP to clear stat cache
            if (function_exists('clearstatcache')) {
                clearstatcache(true);
            }
            
            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الترتيب بنجاح'
            ])->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
            // \Log::error('GiftCategory Sort Update Failed', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            
            return response()->json([
                'status' => false,
                'message' => 'فشل تحديث الترتيب: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        // Force fresh query from database (Octane fix)
        $model = new GiftCategory();
        
        $grid = new Grid($model);
        
        // Enable sortable with custom route
        $grid->sortable();
        
        // Force the grid to always fetch fresh data
        $grid->model()->orderBy('sort', 'asc');
        
        $grid->column('id', __('Id'))->width(50);
        $grid->column('sort', __('Sort Order'))->width(80)->sortable();
        $grid->column('title', __('title'))->display(function ($value) {
            $locale = App::getLocale();
            return $value[$locale] ?? ($value['en'] ?? '');
        });
        $grid->column('type', __('type'));

        $this->extendGrid($grid);
        $grid->disableExport();
        $grid->disableRowSelector();
        
        // Add JavaScript to fix Octane caching issues
        $grid->tools(function ($tools) {
            $tools->append('
            <script>
            $(document).ready(function() {
                console.log("🚀 Octane sortable fix v2 initialized");
                
                // Override the default save handler
                var originalSaveOrder = window.saveOrder;
                
                // Clear cache before sorting starts
                $(".grid-sortable tbody").on("sortstart", function(event, ui) {
                    console.log("⚡ Sort started - clearing cache...");
                    $.ajax({
                        url: "/admin/gift-categories/clear-cache",
                        method: "POST",
                        data: { _token: LA.token },
                        async: false
                    });
                });
                
                // Intercept save order button
                $(document).on("click", ".grid-save-order", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log("💾 Saving order with Octane fix...");
                    
                    var $btn = $(this);
                    var sorts = [];
                    
                    // Collect sort data
                    $(".grid-sortable tbody tr").each(function(index) {
                        sorts.push({
                            id: $(this).data("id"),
                            sort: index + 1
                        });
                    });
                    
                    console.log("📊 Sort data:", sorts);
                    
                    // Send to custom endpoint with Octane fixes
                    $.ajax({
                        url: "/admin/gift-categories/sort-update",
                        method: "POST",
                        data: {
                            _token: LA.token,
                            _sort: sorts
                        },
                        cache: false,
                        headers: {
                            "Cache-Control": "no-cache, no-store, must-revalidate",
                            "Pragma": "no-cache",
                            "Expires": "0"
                        },
                        success: function(response) {
                            console.log("✅ Sort saved:", response);
                            
                            $.pjax.reload("#pjax-container");
                            
                            toastr.success(response.message || "تم حفظ الترتيب بنجاح");
                            
                            // Force reload after 1 second to ensure fresh data
                            setTimeout(function() {
                                console.log("🔄 Force reloading...");
                                location.reload(true);
                            }, 1000);
                        },
                        error: function(xhr) {
                            console.error("❌ Sort failed:", xhr);
                            toastr.error("فشل حفظ الترتيب");
                        }
                    });
                    
                    return false;
                });
                
                console.log("✅ Octane handlers ready");
            });
            </script>
            ');
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
        $show = new Show(GiftCategory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('sort', __('Sort'));
        $show->field('title', __('Title'));
        $show->field('type', __('Type'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id = null)
    {
        $form = new Form(new GiftCategory());

        // Pass model to view (this makes edit mode show old values)
        $form->html(view('admin.multi_lang_tabs', [

            'model' => $id != null ? GiftCategory::find($id) : [],
        ]));

        // Type field
        $form->select('type', __('Type'))->options([
            'normal'     => __('Normal'),
            'lucky_gift' => __('Lucky gifts'),
            'cp'         => __('CP'),
            'vip'        => __('VIP'),
        ])->required();
        
        $form->number('sort', __('sort'))
            ->rules('required|integer|min:1')      // minimum value 1
            ->required();

        // Save titles back as array
        $form->saving(function (Form $form) {
            $titles = request()->input('title', []);
            $form->model()->title = $titles;
            
            // Clear cache when saving
            Cache::tags(['gift_categories'])->flush();
        });

        return $form;
    }
}
