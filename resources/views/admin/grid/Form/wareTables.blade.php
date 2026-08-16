<div class="box-body no-padding">
    <div class="nav-scroll-container">
        <ul class="nav nav-pills">
            @foreach($types as $id => $name)
                @php
                    $selectedType = request()->get('type', 4); // Default to 1
                @endphp
                <li class="{{ $selectedType == $id ? 'active' : '' }}">
                    <a href="{{ request()->fullUrlWithQuery(['type' => $id]) }}" class="charge_action">
                        {{ __($name) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<style>
    .nav-scroll-container {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
    }

    .nav-pills {
        display: inline-flex;
        padding: 10px 0;
    }

    .nav-pills li {
        display: inline-block;
    }

    

.nav-pills>li.active>a, .nav-pills>li.active>a:hover, .nav-pills>li.active>a:focus {
    border-top-color: var(--primary-color);
}
.nav-pills>li.active>a, .nav-pills>li.active>a:focus, .nav-pills>li.active>a:hover {
    color: #fff;
    background-color: var(--primary-color);
}
</style>
