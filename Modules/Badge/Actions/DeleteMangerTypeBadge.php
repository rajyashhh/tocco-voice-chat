<?php

namespace Modules\Badge\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Modules\Badge\Jobs\SyncMangerTypeBadgesJob;

class DeleteMangerTypeBadge extends RowAction
{
    public function name(): string
    {
        return __('Delete');
    }

    public function handle(Model $model)
    {
        $mangerTypeId = (int) $model->manger_type_id;

        $model->delete();

        SyncMangerTypeBadgesJob::dispatch($mangerTypeId);

        return $this->response()->success(__('Deleted successfully'))->refresh();
    }
}