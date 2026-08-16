{{-- <h6>{{ __("superadminDescription") }}</h6> --}}

@if (request()->is('admin/superadmin-users'))
    <h6>{{ __("superadminDescription") }}</h6>
@else
    <h6>{{ __("superAdminRewardDescription") }}</h6>
@endif
