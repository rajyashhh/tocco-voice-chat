@php
    $types = ['wealth', 'charm', 'game', 'charge'];
    $schedules = ['daily', 'weekly', 'monthly'];
@endphp

<ul class="nav nav-tabs">
    @foreach($types as $t)
        <li class="{{ $t == $type ? 'active' : '' }}">
            <a href="?type={{ $t }}&schedule=daily">{{ __($t) }}</a>
        </li>
    @endforeach
</ul>

<div style="margin-top:20px; display: flex; justify-content: flex-start;">
    <ul class="nav nav-pills">
        @foreach($schedules as $sch)
            <li class="{{ $sch == $schedule ? 'active' : '' }}">
                <a href="?type={{ $type }}&schedule={{ $sch }}">{{ __($sch) }}</a>
            </li>
        @endforeach
    </ul>
</div>

<style>
    .nav-pills>li.active>a, .nav-pills>li.active>a:hover, .nav-pills>li.active>a:focus{
        background: var(--primary-color) !important;
    }
</style>
