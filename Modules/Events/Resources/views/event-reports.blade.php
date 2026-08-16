<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('admin.Labels') }}</h3>

        <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body no-padding">
        <ul class="nav nav-pills nav-stacked">
            <li class="{{ request()->name == 'weekly_star' || request()->name == null ? 'active' : '' }}"><a href="?name=weekly_star" class="charge_action"><i class="fa fa-arrow-right text-red"></i>{{__('admin.weekly_star')}}</a></li>
            <li class="{{ request()->name == 'pk_event' ? 'active' : '' }}"><a href="?name=pk_event" class="charge_action"><i class="fa fa-arrow-right text-red"></i>{{__('admin.pk_event')}}</a></li>
        </ul>
    </div>
    <!-- /.box-body -->
</div>
