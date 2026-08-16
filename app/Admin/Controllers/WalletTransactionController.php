<?php

namespace App\Admin\Controllers;

use App\Models\WalletTransaction;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class WalletTransactionController extends AdminController
{
    /**
     * Batch-resolved references for the current grid page,
     * keyed by type then id, to avoid per-row find() N+1 queries.
     *
     * @var array
     */
    protected $descriptionRefs = [
        'target' => [],
        'user'   => [],
        'agency' => [],
    ];

    /**
     * Title for current resource.
     *
     * @var string
     */
    public function title()
    {
        return __('WalletTransaction');
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new WalletTransaction());
        $grid->model()
            ->with(['user:id,name,uuid', 'user.profile:id,user_id,avatar'])
            ->when(request('user_id'), function ($query) {
                $query->where('user_id', request('user_id'));
            });

        // Batch-resolve the target/user/agency references for the whole page in
        // one query per type instead of a find() per row (N+1). $refs is shared
        // by reference with the description column closure below.
        $refs = &$this->descriptionRefs;
        $grid->model()->collection(function ($collection) use (&$refs) {
            $targetIds = [];
            $userIds   = [];
            $agencyIds = [];

            foreach ($collection as $row) {
                $data = json_decode($row->description_data, true) ?? [];
                switch ($row->description) {
                    case 'target_achieved':
                        if (!empty($data['target_id'])) {
                            $targetIds[] = $data['target_id'];
                        }
                        break;
                    case 'transfer_to_user':
                        if (!empty($data['receiver_id'])) {
                            $userIds[] = $data['receiver_id'];
                        }
                        break;
                    case 'transfer_to_agency':
                        if (!empty($data['agency_id'])) {
                            $agencyIds[] = $data['agency_id'];
                        }
                        break;
                }
            }

            if ($targetIds) {
                $refs['target'] = \App\Models\Target::whereIn('id', array_unique($targetIds))
                    ->get()->keyBy('id');
            }
            if ($userIds) {
                $refs['user'] = \App\Models\User::with('profile:id,user_id,avatar')
                    ->whereIn('id', array_unique($userIds))
                    ->get()->keyBy('id');
            }
            if ($agencyIds) {
                $refs['agency'] = \App\Models\Agency::whereIn('id', array_unique($agencyIds))
                    ->get()->keyBy('id');
            }

            return $collection;
        });
        $grid->column('id', __('Id'));
        $grid->column('user.name', __('User'))->display(function () {
            $name = $this->user?->name ?? '';
            $uid = $this->user?->uuid ?? '';
            $path = $this->user?->profile?->avatar ?? null;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;
        
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
        
            $image = handleShowImageWithTypes($this->user->id ?? 0, $url, 40, 40);
            $showUrl = $this->user ? url("admin/users/{$this->user->id}") : "#";
        
            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UUID: $uid</span>
                    </div>
                </div>
            ";
        });
        
        $grid->column('type', __('Type'))->display(function($type) {
            $types = [
                'add' => __('Add'),
                'cut' => __('Cut'),
                'pending' => __('Pending'),
            ];
        
            return $types[$type] ?? __('Unknown');
        });
        $grid->column('value', __('Value'));
        // $grid->column('description', __('Description'));
        $grid->column('description_data', __('Description'))->display(function () use (&$refs) {
            $description = $this->description ?? '';
            $data = json_decode($this->description_data, true) ?? [];

            switch ($description) {
                case 'target_achieved':
                    $targetId = $data['target_id'] ?? null;
                    $target = $targetId ? ($refs['target'][$targetId] ?? null) : null;
                    if ($target) {
                        $targetUrl = url("admin/targets/{$target->id}"); // رابط عرض الهدف
                        return "
                            <div style='display: ; align-items: center; gap: 10px;'>
                                <a href='{$targetUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                                    <span >" . __('') . " {$target->diamonds}</span>
                                </a>
                                <spanstyle='font-size: smaller; color: #888;'>" . __('Target ID:') . " {$target->id}</span>

                            </div>
                        ";
                    }
                    return __('Target not found');
                
                case 'transfer_to_user':
                    $userId = $data['receiver_id'] ?? null;
                    $user = $userId ? ($refs['user'][$userId] ?? null) : null;
                    if ($user) {
                        $path = $user->profile?->avatar ?? null;
                        $defaultImage = asset("images/businessman-icon.jpg");
                        $url = getImagePath($path) ?? $defaultImage;
                        if (!isImageExists($url)) {
                            $url = $defaultImage;
                        }
                        $image = handleShowImageWithTypes($user->id ?? 0, $url, 40, 40);
                        $showUrl = url("admin/users/{$user->id}");
        
                        return "
                            <div style='display: flex; align-items: center; gap: 10px;'>
                                $image
                                <div>
                                   <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                                     <span style='text-decoration: underline; cursor: pointer;'>{$user->name}</span>
                                    </a>
                                    <span style='color: #aaa; font-size: smaller;'>" . __('UUID:') . " {$user->uuid}</span>
                                </div>
                            </div>
                        ";
                    }
                    return __('User not found');
        
                case 'transfer_to_agency':
                    $agencyId = $data['agency_id'] ?? null;
                    $agency = $agencyId ? ($refs['agency'][$agencyId] ?? null) : null;
                    if ($agency) {
                        $path = $agency->image ?? null;
                        $defaultImage = asset("images/businessman-icon.jpg");
                        $url = getImagePath($path) ?? $defaultImage;
                        $image = handleShowImageWithTypes($user->id ?? 0, $url, 40, 40);
                        $agencyUrl = url("admin/agencies/{$agency->id}"); // رابط عرض الوكالة
                        return "
                         <div style='display: flex; align-items: center; gap: 10px;'>
                                $image
                            <div style='display: ; align-items: center; gap: 10px;'>
                                <a href='{$agencyUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                                    <span>" . __('Agency:') . " {$agency->name}</span>
                                </a>
                              <span>" . __('id:') . " {$agency->id}</span>

                            </div>
                            </div>
                        ";
                    }
                    return __('Agency not found');
        
                default:
                    return json_encode($data); // fallback
            }
        });
        
        $grid->column('created_at', __('Created at'))->display(function ($created_at) {
            return Carbon::parse($created_at)->format('Y-m-d H:i');
        }); 
        $grid->disableCreateButton();

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
        $show = new Show(WalletTransaction::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('type', __('Type'));
        $show->field('value', __('Value'));
        $show->field('description', __('Description'));
        $show->field('description_data', __('Description data'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new WalletTransaction());

        $form->number('user_id', __('User id'));
        $form->text('type', __('Type'));
        $form->decimal('value', __('Value'));
        $form->textarea('description', __('Description'));
        $form->textarea('description_data', __('Description data'));

        return $form;
    }
}
