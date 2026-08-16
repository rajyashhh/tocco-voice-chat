<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">الحقول</h3>

        <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body no-padding d-flex flex">
        <div>
            <ul class="nav nav-pills nav-stacked">
                <li class="{{ request()->name == 'users' || request()->name == null ? 'active' : '' }}"><a
                        href="?name=users" class="charge_action"><i
                            class="fa fa-arrow-right text-red"></i>{{ __('users statistics') }}</a></li>
                <li class="{{ request()->name == 'agencies' ? 'active' : '' }}"><a href="?name=agencies"
                        class="charge_action"><i
                            class="fa fa-arrow-right text-red"></i>{{ __('agencies statistics') }}</a></li>
            </ul>
        </div>
    </div>
    <!-- /.box-body -->
</div>
