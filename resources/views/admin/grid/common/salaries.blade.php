<div class="box-body no-padding d-flex flex">
    <div>
        <ul class="nav nav-pills nav-stacked">
            <li class="{{ request()->name == 'users' || request()->name == null ? 'active' : '' }}"><a
                    href="?name=users" class="charge_action"><i
                        class="fa fa-arrow-right text-red"></i>{{ __('users wallet') }}</a></li>
            <li class="{{ request()->name == 'agencies' ? 'active' : '' }}"><a href="?name=agencies"
                                                                               class="charge_action"><i
                        class="fa fa-arrow-right text-red"></i>{{ __('agencies wallet') }}</a></li>
        </ul>
    </div>
</div>


