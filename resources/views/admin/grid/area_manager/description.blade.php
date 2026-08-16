{{-- <h6>{{ __("superadminDescription") }}</h6> --}}

@if (request()->is('admin/area-manager-users'))
    <h6>{{ __("areaManagerDescription") }}</h6>
@else
    <h6>{{ __("areaManagerRewardDescription") }}</h6>
@endif
