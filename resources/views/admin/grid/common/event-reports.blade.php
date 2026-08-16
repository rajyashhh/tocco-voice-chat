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
            <li class="{{ request()->name == 'event_period' ? 'active' : '' }}"><a href="?name=event_period" class="charge_action"><i class="fa fa-arrow-right text-red"></i>{{__('admin.event_period')}}</a></li>
            <li class="{{ request()->name == 'pk_event' ? 'active' : '' }}"><a href="?name=pk_event" class="charge_action"><i class="fa fa-arrow-right text-red"></i>{{__('admin.pk_event')}}</a></li>
            <li class="{{ request()->name == 'charges_reports' ? 'active' : '' }}"><a href="?name=charges_reports" class="charge_action"><i class="fa fa-arrow-right text-red"></i>{{__('charges reports')}}</a></li>
        </ul>
    </div>
    <style>
        .nav-pills .active {
    background-color: #d9534f !important; /* لون الخلفية */
    color: #fff !important; /* لون النص */
    font-weight: bold; /* جعل النص عريضًا */
    border-radius: 5px; /* تدوير الحواف */
}

.nav-pills .active a {
    color: #fff !important; /* لون النص داخل الرابط */
}

.nav-pills li a {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
    transition: background 0.3s, color 0.3s;
}

.nav-pills li a:hover {
    background-color: var(--primary-color) ; /* لون خلفية عند التحويم */
    color: var(--text-secondary-color) ;
}

.nav-pills li.active a {
    background-color: var(--primary-color) !important;
}

    </style>
    <!-- /.box-body -->
</div>
