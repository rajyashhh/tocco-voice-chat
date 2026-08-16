@if(request()->is('admin/*'))
    <h6>{{ __("bdDescription") }}</h6>
@else
    <h6>{{ __("bdsDescription") }}</h6>
@endif