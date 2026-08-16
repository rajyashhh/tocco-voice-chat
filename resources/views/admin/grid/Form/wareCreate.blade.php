<div class="box-body no-padding">
    <div class="nav-scroll-container">
        <ul class="nav nav-pills">
            @foreach($types as $id => $name)
                @php
                    // Get the type from the request or use the default type (1)
                    $selectedType = request()->route('type', 1); 
                    
                    // Generate the new URL dynamically while preserving query parameters
                    $newUrl = url('admin/ware-managements/create/' . $id) . '?' . http_build_query(request()->except('type'));
                @endphp
                <li class="{{ $selectedType == $id ? 'active' : '' }}">
                    <a href="{{ $newUrl }}" class="charge_action">
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
</style>
