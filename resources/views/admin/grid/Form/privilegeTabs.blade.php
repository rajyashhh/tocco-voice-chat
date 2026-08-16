<div class="box-body no-padding">
    <div class="nav-scroll-container" id="navContainer">
        @if ($alert == true)
            <div style="color: var(--inverse-color) !important;"
                 class="alert alert-warning d-flex justify-content-between align-items-center" role="alert">
                <div style="color: var(--inverse-color) !important;">
                    {{ __('vip_alert_message') }}
                </div>
                <a href="{{ url("admin/ovip/{$level}/edit") }}"
                   class="btn btn-sm btn"
                   style="background-color: var(--primary-color) !important; color: var(--inverse-color) !important;">
                    {{ __('vip_alert_button') }}
                </a>
            </div>
        @endif
        <ul class="nav nav-pills">
            @if ($types)
                @foreach($types as $type => $name)
                    @php
                        $selectedType = request()->get('type', $types->keys()->first());
                    @endphp
                    <li class="{{ $selectedType == $type ? 'active' : '' }}">
                        <a href="{{ request()->fullUrlWithQuery(['type' => $type]) }}"
                           class="privilege_tab"
                           onclick="scrollToTab(this, event)">
                            {{ __($name ?? 'test') }}
                        </a>
                    </li>
                @endforeach
            @endif
        </ul>
    </div>
</div>

<style>
    .nav-pills>li.active>a, .nav-pills>li.active>a:focus, .nav-pills>li.active>a:hover {
        background-color: var(--primary-color);
    }
    .nav-scroll-container {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
    }
    .nav-pills>li.active>a, .nav-pills>li.active>a:hover, .nav-pills>li.active>a:focus {
        border-top-color: var(--primary-color);
    }
    .nav-pills {
        display: inline-flex;
        padding: 10px 0;
    }
    .nav-pills li {
        display: inline-block;
    }
</style>

<script>
    function scrollToTab(element, event) {
        event.preventDefault();

        const container = document.getElementById('navContainer');
        const tab = element.parentElement;

        const scrollPosition = tab.offsetLeft - (container.clientWidth / 2) + (tab.clientWidth / 2);

        container.scrollTo({
            left: scrollPosition,
            behavior: 'smooth'
        });

        setTimeout(() => {
            window.location.href = element.href;
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const activeTab = document.querySelector('.nav-pills li.active');
        if (activeTab) {
            const container = document.getElementById('navContainer');
            const scrollPosition = activeTab.offsetLeft - (container.clientWidth / 2) + (activeTab.clientWidth / 2);
            container.scrollTo({
                left: scrollPosition,
                behavior: 'smooth'
            });
        }
    });
</script>
