<aside id="main-sidebar" class="main-sidebar">
    <link rel="stylesheet" href="{{ asset('css/admin-menu.css') }}">
    <script src="{{ asset('js/admin-menu.js') }}"></script>
    </section>
    <section class="sidebar">
        @if(config('admin.enable_menu_search'))
            <!-- search form (Optional) -->
            <form class="sidebar-form" style="overflow: initial;" onsubmit="return false;">
                <div class="input-group">
                    <input type="text" autocomplete="off" class="form-control autocomplete" placeholder="Search...">
                    <span class="input-group-btn">
                <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i>🔍</i>
                </button>
              </span>
                    <ul class="dropdown-menu" role="menu" style="min-width: 210px;max-height: 300px;overflow: auto;">
                        @if (Admin::user()->type == 'bd')
                            @foreach(Admin::menuLinks() as $link)
                                <li>
                                    <a href="{{ bd_url($link['uri']) }}"><i
                                            class="fa {{ $link['icon'] }}"></i>{{ admin_trans($link['title']) }}</a>
                                </li>
                            @endforeach
                        @endif

                        @if (Admin::user()->type == 'superadmin')
                            @foreach(Admin::menuLinks() as $link)
                                <li>
                                    <a href="{{ superadmin_url($link['uri']) }}"><i
                                            class="fa {{ $link['icon'] }}"></i>{{ admin_trans($link['title']) }}</a>
                                </li>
                            @endforeach
                        @endif

                        @if (Admin::user()->type != 'bd' || Admin::user()->type != 'superadmin')
                            @foreach(Admin::menuLinks() as $link)
                                <li>
                                    <a href="{{ admin_url($link['uri']) }}"><i
                                            class="fa {{ $link['icon'] }}"></i>{{ admin_trans($link['title']) }}</a>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </form>
            <!-- /.search form -->
        @endif

        @php
            use Illuminate\Support\Str;
            $menu = Admin::menu();
            $filteredMenu = collect($menu)->filter(function ($item) {
                if ((Str::startsWith($item['uri'] ?? '', 'bd') || ($item['uri'] ?? '') === '*') && !Admin::user()->type == 'bd') {
                    return false;
                }
                return true;
            })->values()->all();

            // Helper closures (Octane-safe: variables, not global functions)
            $hasPermission = function($permission) {
                if (Admin::user()->can('*')) {
                    return true;
                }

                if (is_null($permission)) {
                    return true;
                }

                return Admin::user()->can('browse-' . $permission);
            };

            $hasVisibleChildren = function($children) use ($hasPermission) {
                foreach ($children as $child) {
                    if ($hasPermission($child['permission'] ?? null)) {
                        return true;
                    }
                }
                return false;
            };
        @endphp

        <ul class="crs-menu">
            <!-- <li class="header">{{ trans('admin.menu') }}</li> -->

            @if (Admin::user()->type == 'bd')
                @php
                    $bdLinks = [
                        ['uri' => '/', 'icon' => '🏠', 'title' => __('Home')],
                        ['uri' => '/charges', 'icon' => '💸', 'title' => __('charges')],
                        ['uri' => '/agencies', 'icon' => '🏠', 'title' => __('agencies')],
                        ['uri' => '/salaries', 'icon' => '💰', 'title' => __('salaries')],
                        ['uri' => '/request-agencies', 'icon' => '📝', 'title' => __('request-agencies')],
                    ];
                @endphp

                @foreach($bdLinks as $link)
                    <li class="crs-item">
                        <a href="{{ bd_url($link['uri']) }}" class="crs-link crs-leaf">
                            @if(str_contains($link['icon'] ?? '', 'fa-'))
                                <i class="fa {{ $link['icon'] }} crs-icon" aria-hidden="true"></i>
                            @else
                                <span class="crs-icon emoji-icon">{{ $link['icon'] }}</span>
                            @endif
                            <span class="crs-title">{{ $link['title'] }}</span>
                        </a>
                    </li>
                @endforeach
            @endif

            @if (in_array(Admin::user()->type, ['superadmin', 'sub_super_admin']))
                @php
                    $superadminLinks = [
                        ['uri' => '/', 'icon' => '🏠', 'title' => __('Dashboard'), 'permission' => 'dashboard'],
                        ['uri' => '/users', 'icon' => '👥', 'title' => __('Users'), 'permission' => 'users'],
                        ['uri' => '/charges', 'icon' => '💸', 'title' => __('charges'), 'permission' => 'coin-recharge']
                        ,
                        [
                            'uri' => '#',
                            'icon' => '👨‍💼',
                            'title' => __('BD'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/usersBd', 'icon' => '💼', 'title' => __('BD'), 'permission' => 'Bds'],
                                ['uri' => '/professional-bd', 'icon' => '✈️', 'title' => __('Professional BD'), 'permission' => 'professional-bd'],
                            ],
                        ],
                        [
                            'uri' => '#',
                            'icon' => '🏢',
                            'title' => __('Agencies'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/agencies', 'icon' => '🏠', 'title' => __('Host Agencies'), 'permission' => 'agency'],
                                ['uri' => '/charge-agencies', 'icon' => '💳', 'title' => __('Shipping Agencies'), 'permission' => 'shipping-agency'],
                                ['uri' => '/ag/users', 'icon' => '👥', 'title' => __('Hosts'), 'permission' => 'host'],
                                ['uri' => '/ag/professional/users', 'icon' => '✈️', 'title' => __('Professional Host'), 'permission' => 'professional-users'],
                            ],
                        ],
                        [
                            'uri' => '#',
                            'icon' => '🏠',
                            'title' => __('rooms'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/rooms', 'icon' => '🚪', 'title' => __('rooms'), 'permission' => 'rooms'],
                                ['uri' => '/live-rooms', 'icon' => '📺', 'title' => __('Live Rooms'), 'permission' => 'live-rooms'],
                            ],
                        ],
                        [
                            'uri' => '#',
                            'icon' => '📢',
                            'title' => __('Advertisements'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/home-carousel', 'icon' => '🎠', 'title' => __('HomeCarousel'), 'permission' => 'banner'],
                                ['uri' => '/official-message', 'icon' => '📋', 'title' => __('Official messages'), 'permission' => 'banner'],

                            ],
                        ],
                        ['uri' => '/super-admin-rewards', 'icon' => '🎁', 'title' => __('reward dedicate'), 'permission' => 'reward-center'],
                        [
                            'uri' => '#',
                            'icon' => '👔',
                            'title' => __('Employees and Permissions'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/roles', 'icon' => '🔐', 'title' => __('roles'), 'permission' => 'roles'],
                                ['uri' => '/auth-users', 'icon' => '👥', 'title' => __('Sub Super Admin'), 'permission' => 'auth-users'],
                            ],
                        ],
                    ];
                @endphp

                @foreach($superadminLinks as $link)

                    @if(isset($link['children']))
                        @if($hasVisibleChildren($link['children']))
                            <li class="crs-tree crs-item">
                                <a href="#" class="crs-link crs-toggle">
                                    @if(str_contains($link['icon'] ?? '', 'fa-'))
                                        <i class="fa {{ $link['icon'] }} crs-icon" aria-hidden="true"></i>
                                    @else
                                        <span class="crs-icon emoji-icon">{{ $link['icon'] }}</span>
                                    @endif
                                    <span class="crs-title">{{ $link['title'] }}</span>
                                    <i class="fa fa-angle-left crs-arrow"></i>
                                </a>
                                <ul class="crs-submenu">
                                    @foreach($link['children'] as $child)
                                        @if($hasPermission($child['permission'] ?? null))
                                            <li class="crs-item">
                                                <a href="{{ superadmin_url($child['uri']) }}" class="crs-link crs-leaf">
                                                    @if(str_contains($child['icon'] ?? '', 'fa-'))
                                                        <i class="fa {{ $child['icon'] }} crs-icon"
                                                           aria-hidden="true"></i>
                                                    @else
                                                        <span class="crs-icon emoji-icon">{{ $child['icon'] }}</span>
                                                    @endif
                                                    <span class="crs-title">{{ $child['title'] }}</span>
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                    @else
                        {{-- @if($hasPermission($link['permission'] ?? null))
                            <li class="crs-item">
                                <a href="{{ superadmin_url($link['uri']) }}" class="crs-link crs-leaf">
                                    @if(str_contains($link['icon'] ?? '', 'fa-'))
                                        <i class="fa {{ $link['icon'] }} crs-icon" aria-hidden="true"></i>
                                    @else
                                        <span class="crs-icon emoji-icon">{{ $link['icon'] }}</span>
                                    @endif
                                    <span class="crs-title">{{ $link['title'] }}</span>
                                </a>
                            </li>
                        @endif --}}

                        @if($hasPermission($link['permission'] ?? null))
                            @php
                                $icon = is_array($link['icon'] ?? null)
                                    ? ($link['icon'][0] ?? '')
                                    : ($link['icon'] ?? '');

                                $title = is_array($link['title'] ?? null)
                                    ? ($link['title'][app()->getLocale()] ?? '')
                                    : ($link['title'] ?? '');
                            @endphp

                            <li class="crs-item">
                                <a href="{{ superadmin_url($link['uri'] ?? '#') }}" class="crs-link crs-leaf">
                                    @if(str_contains($icon, 'fa-'))
                                        <i class="fa {{ $icon }} crs-icon"></i>
                                    @else
                                        <span class="crs-icon emoji-icon">{{ $icon }}</span>
                                    @endif

                                    <span class="crs-title">{{ $title }}</span>
                                </a>
                            </li>
                        @endif
                    @endif
                @endforeach
            @endif
            {{--                area manager--}}
            @if (in_array(Admin::user()->type, ['area-manager', 'sub_area_manager']) )
                @php
                    $areaManagerLinks = [
                        ['uri' => '/', 'icon' => '🏠', 'title' => __('Dashboard'), 'permission' => 'dashboard'],
                        [
                            'uri' => '#',
                            'icon' => '🌍',
                            'title' => __('Super Admin'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/superadmin-users', 'icon' => '👤', 'title' => __('Super Admin'), 'permission' => 'superadmin'],
                            ],
                        ],
                        ['uri' => '/charges', 'icon' => '💸', 'title' => __('charges'), 'permission' => 'coin-recharge'],
                        [
                            'uri' => '#',
                            'icon' => '💼',
                            'title' => __('BD'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/user-Bds', 'icon' => '👥', 'title' => __('BD'), 'permission' => 'Bds'],
                                ['uri' => '/professional-bd', 'icon' => '✈️', 'title' => __('Professional BD'), 'permission' => 'professional-bd'],
                            ],
                        ],
                        ['uri' => '/users', 'icon' => '👥', 'title' => __('Users'), 'permission' => 'users'],
                        [
                            'uri' => '#',
                            'icon' => '🏢',
                            'title' => __('Agencies'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/agencies', 'icon' => '🏠', 'title' => __('Host Agencies'), 'permission' => 'agency'],
                                ['uri' => '/charge-agencies', 'icon' => '💳', 'title' => __('Shipping Agencies'), 'permission' => 'shipping-agency'],
                                ['uri' => '/ag/users', 'icon' => '👥', 'title' => __('Hosts'), 'permission' => 'host'],
                                ['uri' => '/ag/professional/users', 'icon' => '✈️', 'title' => __('Professional Host'), 'permission' => 'professional-users'],
                            ],
                        ],
                        [
                            'uri' => '#',
                            'icon' => '🏠',
                            'title' => __('rooms'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/rooms', 'icon' => '🚪', 'title' => __('rooms'), 'permission' => 'rooms'],
                                ['uri' => '/live-rooms', 'icon' => '📺', 'title' => __('Live Rooms'), 'permission' => 'live-rooms'],
                            ],
                        ],
                        [
                            'uri' => '#',
                            'icon' => '📢',
                            'title' => __('Advertisements'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/official-message', 'icon' => '📋', 'title' => __('Official messages'), 'permission' => 'official-messages'],
                            ],
                        ],
                          ['uri' => '/rewards?type=vip', 'icon' => '🎁', 'title' => __('reward dedicate'), 'permission' => 'reward-center'],
                        [
                            'uri' => '#',
                            'icon' => '👔',
                            'title' => __('Employees and Permissions'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/roles', 'icon' => '🔐', 'title' => __('roles'), 'permission' => 'roles'],
                                ['uri' => '/auth-users', 'icon' => '👥', 'title' => __('users'), 'permission' => 'auth-users'],
                            ],
                        ],
                    ];
                @endphp

                @foreach($areaManagerLinks as $link)
                    @if(isset($link['children']))
                        @if($hasVisibleChildren($link['children']))
                            <li class="crs-tree crs-item">
                                <a href="#" class="crs-link crs-toggle">
                                    @if(str_contains($link['icon'] ?? '', 'fa-'))
                                        <i class="fa {{ $link['icon'] }} crs-icon" aria-hidden="true"></i>
                                    @else
                                        <span class="crs-icon emoji-icon">{{ $link['icon'] }}</span>
                                    @endif
                                    <span class="crs-title">{{ $link['title'] }}</span>
                                    <i class="fa fa-angle-left crs-arrow"></i>
                                </a>
                                <ul class="crs-submenu">
                                    @foreach($link['children'] as $child)
                                        @if($hasPermission($child['permission'] ?? null))
                                            <li class="crs-item">
                                                <a href="{{ areaManager_url($child['uri']) }}"
                                                   class="crs-link crs-leaf">
                                                    @if(str_contains($child['icon'] ?? '', 'fa-'))
                                                        <i class="fa {{ $child['icon'] }} crs-icon"
                                                           aria-hidden="true"></i>
                                                    @else
                                                        <span class="crs-icon emoji-icon">{{ $child['icon'] }}</span>
                                                    @endif
                                                    <span class="crs-title">{{ $child['title'] }}</span>
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                    @else
                        @if($hasPermission($link['permission'] ?? null))
                            <li class="crs-item">
                                <a href="{{ areaManager_url((string) ($link['uri'] ?? '')) }}" class="crs-link crs-leaf">
                                    @if(is_string($link['icon'] ?? null) && str_contains($link['icon'], 'fa-'))
                                        <i class="fa {{ $link['icon'] }} crs-icon"></i>
                                    @else
                                        <span class="crs-icon emoji-icon">
                                            {{ is_array($link['icon'] ?? null) ? '' : ($link['icon'] ?? '') }}
                                        </span>
                                    @endif

                                    <span class="crs-title">
                                        {{ is_array($link['title'] ?? null) ? ($link['title'][app()->getLocale()] ?? '') : ($link['title'] ?? '') }}
                                    </span>
                                </a>
                            </li>
                        @endif
                    @endif
                @endforeach
            @endif

            @php
                $adminTypes = ['bd', 'superadmin', 'sub_super_admin', 'area-manager', 'sub_area_manager'];
            @endphp

            @if (!in_array(Admin::user()->type, $adminTypes) && !session('preview_superadmin') &&!session('preview_area_manager'))
                @each('vendor.admin.partials.menu', $filteredMenu, 'item')
            @elseif(session('preview_superadmin'))
                @php
                    $superadminPreviewLinks = [
                        ['uri' => '/superadmin/statistics','icon' => '🏠','title' => __('Dashboard')],
                        ['uri' => '/superadmin/profile','icon' => '👤','title' => __('Super Admin Profile')],
                        ['uri' => '/users','icon' => '👥','title' => __('Users')],
                        ['uri' => '/usersBd','icon' => '💼','title' => __('BD')],
                        [
                            'uri' => '#',
                            'icon' => '🏢',
                            'title' => __('Agencies'),
                            'children' => [
                                ['uri' => '/agencies', 'icon' => '🏠', 'title' => __('Host Agencies')],
                                ['uri' => '/charge-agencies', 'icon' => '💳', 'title' => __('Shipping Agencies')],
                                ['uri' => '/ag/users', 'icon' => '👥', 'title' => __('Hosts')],
                                ['uri' => '/ag/professional/users', 'icon' => '✈️', 'title' => __('Professional Host')],
                            ],
                        ],
                        ['uri' => '/rooms','icon' => '🚪','title' => __('rooms')],
                        ['uri' => '/live-rooms','icon' => '📺','title' => __('Live Rooms')],
                        ['uri' => '/superadmin/home-carousel','icon' => '🎠','title' => __('HomeCarousel')],
                    ];
                @endphp

                @foreach($superadminPreviewLinks as $link)
                    @if(isset($link['children']))
                        <li class="crs-tree crs-item">
                            <a href="#" class="crs-link crs-toggle">
                                @if(str_contains($link['icon'] ?? '', 'fa-'))
                                    <i class="fa {{ $link['icon'] }} crs-icon" aria-hidden="true"></i>
                                @else
                                    <span class="crs-icon emoji-icon">{{ $link['icon'] }}</span>
                                @endif
                                <span class="crs-title">{{ $link['title'] }}</span>
                                <i class="fa fa-angle-left crs-arrow"></i>
                            </a>
                            <ul class="crs-submenu">
                                @foreach($link['children'] as $child)
                                    <li class="crs-item">
                                        <a href="{{ admin_url($child['uri']) }}" class="crs-link crs-leaf">
                                            @if(str_contains($child['icon'] ?? '', 'fa-'))
                                                <i class="fa {{ $child['icon'] }} crs-icon" aria-hidden="true"></i>
                                            @else
                                                <span class="crs-icon emoji-icon">{{ $child['icon'] }}</span>
                                            @endif
                                            <span class="crs-title">{{ $child['title'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="crs-item">
                            <a href="{{ admin_url($link['uri']) }}" class="crs-link crs-leaf">
                                @if(str_contains($link['icon'] ?? '', 'fa-'))
                                    <i class="fa {{ $link['icon'] }} crs-icon" aria-hidden="true"></i>
                                @else
                                    <span class="crs-icon emoji-icon">{{ $link['icon'] }}</span>
                                @endif
                                <span class="crs-title">{{ $link['title'] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            @elseif(session('preview_area_manager'))
                @php
                    $areaManagerPreviewLinks = [
                        ['uri' => '/', 'icon' => '🏠', 'title' => __('Dashboard'), 'permission' => 'dashboard'],
                        [
                            'uri' => '#',
                            'icon' => '🌍',
                            'title' => __('Super Admin'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/superadmin-users', 'icon' => '👤', 'title' => __('Super Admin'), 'permission' => 'superadmin'],
                            ],
                        ],
                        ['uri' => '/area-manager-charges-reports', 'icon' => '📃', 'title' => __('charges')],
                        [
                            'uri' => '#',
                            'icon' => '💼',
                            'title' => __('BD'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/user-Bds', 'icon' => '👥', 'title' => __('BD'), 'permission' => 'Bds'],
                                ['uri' => '/professional-bd', 'icon' => '✈️', 'title' => __('Professional BD'), 'permission' => 'professional-bd'],
                            ],
                        ],
                        ['uri' => '/users', 'icon' => '👥', 'title' => __('Users'), 'permission' => 'users'],
                        [
                            'uri' => '#',
                            'icon' => '🏢',
                            'title' => __('Agencies'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/agencies', 'icon' => '🏠', 'title' => __('Host Agencies'), 'permission' => 'agency'],
                                ['uri' => '/charge-agencies', 'icon' => '💳', 'title' => __('Shipping Agencies'), 'permission' => 'shipping-agency'],
                                ['uri' => '/ag/users', 'icon' => '👥', 'title' => __('Hosts'), 'permission' => 'host'],
                                ['uri' => '/ag/professional/users', 'icon' => '✈️', 'title' => __('Professional Host'), 'permission' => 'professional-users'],
                            ],
                        ],
                        [
                            'uri' => '#',
                            'icon' => '🏠',
                            'title' => __('rooms'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/rooms', 'icon' => '🚪', 'title' => __('rooms'), 'permission' => 'rooms'],
                                ['uri' => '/live-rooms', 'icon' => '📺', 'title' => __('Live Rooms'), 'permission' => 'live-rooms'],
                            ],
                        ],
                        [
                            'uri' => '#',
                            'icon' => '📢',
                            'title' => __('Advertisements'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/official_msgs', 'icon' => '📋', 'title' => __('Official messages'), 'permission' => 'official-messages'],
                            ],
                        ],
                        [
                            'uri' => '#',
                            'icon' => '👔',
                            'title' => __('Employees and Permissions'),
                            'permission' => null,
                            'children' => [
                                ['uri' => '/auth/roles', 'icon' => '🔐', 'title' => __('roles'), 'permission' => 'roles'],
                                ['uri' => '/auth/users', 'icon' => '👥', 'title' => __('users'), 'permission' => 'auth-users'],
                            ],
                        ],
                    ];
                @endphp

                @foreach($areaManagerPreviewLinks as $link)
                    @if(isset($link['children']))
                        <li class="crs-tree crs-item">
                            <a href="#" class="crs-link crs-toggle">
                                @if(str_contains($link['icon'] ?? '', 'fa-'))
                                    <i class="fa {{ $link['icon'] }} crs-icon" aria-hidden="true"></i>
                                @else
                                    <span class="crs-icon emoji-icon">{{ $link['icon'] }}</span>
                                @endif
                                <span class="crs-title">{{ $link['title'] }}</span>
                                <i class="fa fa-angle-left crs-arrow"></i>
                            </a>
                            <ul class="crs-submenu">
                                @foreach($link['children'] as $child)
                                    <li class="crs-item">
                                        <a href="{{ admin_url($child['uri']) }}" class="crs-link crs-leaf">
                                            <i class="fa {{ $child['icon'] }} crs-icon"></i>
                                            <span class="crs-title">{{ $child['title'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="crs-item">
                            <a href="{{ admin_url($link['uri']) }}" class="crs-link crs-leaf">
                                @if(str_contains($link['icon'] ?? '', 'fa-'))
                                    <i class="fa {{ $link['icon'] }} crs-icon" aria-hidden="true"></i>
                                @else
                                    <span class="crs-icon emoji-icon">{{ $link['icon'] }}</span>
                                @endif
                                <span class="crs-title">{{ $link['title'] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            @endif
        </ul>


    </section>

    <script>
        /*
         * Keep the sidebar visually stable across full page loads.
         * Under Octane the panel navigates with full reloads (X-PJAX is stripped),
         * so the menu re-renders from scratch on every click: scroll resets to top,
         * entry animations replay, then a delayed scrollIntoView jumps back down.
         * This inline block runs synchronously right after the menu markup — before
         * first paint of the content — restoring open sections and scroll position
         * so the user never sees the menu move.
         */
        (function () {
            'use strict';

            var KEY = 'crsMenuState:' + (location.pathname.split('/')[1] || '');
            var aside = document.getElementById('main-sidebar');
            if (!aside) return;

            var section = aside.querySelector('.sidebar');

            function openTreeSilently(tree) {
                tree.classList.add('crs-open');

                var toggle = tree.querySelector(':scope > .crs-toggle');
                if (toggle) toggle.setAttribute('aria-expanded', 'true');

                var submenu = tree.querySelector(':scope > .crs-submenu');
                if (submenu) {
                    submenu.style.transition = 'none';
                    submenu.style.maxHeight = 'none';
                    submenu.style.opacity = '1';
                    submenu.style.transform = 'none';
                }

                var arrow = toggle ? toggle.querySelector('.crs-arrow') : null;
                if (arrow) arrow.style.transition = 'none';

                return { submenu: submenu, arrow: arrow };
            }

            var raw = null;
            try { raw = sessionStorage.getItem(KEY); } catch (e) { /* storage blocked */ }

            if (raw) {
                try {
                    var state = JSON.parse(raw);
                    var trees = aside.querySelectorAll('.crs-tree');
                    var suppressed = [];

                    aside.classList.add('crs-nav-restore');

                    (state.o || []).forEach(function (index) {
                        if (trees[index]) suppressed.push(openTreeSilently(trees[index]));
                    });

                    var top = state.s || 0;
                    aside.scrollTop = top;
                    if (section) section.scrollTop = top;

                    window.__crsScrollRestored = true;

                    // Re-enable transitions after the restored frame has painted,
                    // so manual open/close keeps its normal animation.
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            suppressed.forEach(function (parts) {
                                if (parts.submenu) parts.submenu.style.removeProperty('transition');
                                if (parts.arrow) parts.arrow.style.removeProperty('transition');
                            });
                        });
                    });
                } catch (e) { /* corrupt state: fall back to default behaviour */ }
            }

            function saveState() {
                var top = Math.max(aside.scrollTop || 0, section ? (section.scrollTop || 0) : 0);
                var open = [];
                var trees = aside.querySelectorAll('.crs-tree');
                for (var i = 0; i < trees.length; i++) {
                    if (trees[i].classList.contains('crs-open')) open.push(i);
                }
                try {
                    sessionStorage.setItem(KEY, JSON.stringify({ s: top, o: open }));
                } catch (e) { /* storage blocked */ }
            }

            // Covers every navigation away from the page (menu links, content links,
            // refresh) and is bfcache-safe.
            window.addEventListener('pagehide', saveState);

            // Menu-link clicks: save immediately and mark the position as known so
            // the active-item auto-centering (admin-menu.js) does not move the menu
            // — also covers the pjax case where the document is kept alive.
            aside.addEventListener('click', function (e) {
                if (e.target && e.target.closest && e.target.closest('a.crs-link.crs-leaf')) {
                    window.__crsScrollRestored = true;
                    saveState();
                }
            }, true);
        })();
    </script>

    <style>
        #tab-loading {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--primary-color);
            color: var(--text-primary-color);
            z-index: 9999;
            padding: 30px 40px;
            border-radius: 10px;
            font-size: 20px;
            font-weight: bold;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }
    </style>

    <div id="tab-loading">
        Loading...
    </div>
    <!-- /.sidebar -->

    <div class="sidebar-resizer" id="sidebar-resizer"></div>
</aside>

<script>
    (function() {
        'use strict';

        const MIN_WIDTH = 18;
        const MAX_WIDTH = 40;
        const DEFAULT_WIDTH = 18;
        const STORAGE_KEY = 'sidebar-width-percent';
        const MOBILE_BREAKPOINT = 768; // pixels

        let isResizing = false;
        let startX = 0;
        let startWidthPercent = 0;

        function isRTL() {
            return document.documentElement.dir === 'rtl' ||
                document.body.dir === 'rtl' ||
                document.body.classList.contains('rtl') ||
                document.documentElement.classList.contains('rtl');
        }

        function isSidebarCollapsed() {
            return document.body.classList.contains('sidebar-collapse');
        }

        function isMobile() {
            return window.innerWidth <= MOBILE_BREAKPOINT;
        }

        function pxToPercent(px) {
            return (px / window.innerWidth) * 100;
        }

        function clearInlineStyles() {
            const sidebar = document.querySelector('.main-sidebar');
            const navbar = document.querySelector('.navbar-static-top');
            const contentWrapper = document.querySelector('.content-wrapper');
            const logo = document.querySelector('.main-header .logo');

            if (sidebar) sidebar.style.width = '';
            if (navbar) navbar.style.width = '';
            if (logo) logo.style.width = '';
            if (contentWrapper) {
                contentWrapper.style.marginLeft = '';
                contentWrapper.style.marginRight = '';
            }
        }

        function updateLayout(sidebarWidthPercent) {
            // Don't apply if mobile or sidebar is collapsed
            if (isMobile() || isSidebarCollapsed()) {
                clearInlineStyles();
                return;
            }

            const contentWrapper = document.querySelector('.content-wrapper');
            const navbar = document.querySelector('.navbar-static-top');
            const sidebar = document.querySelector('.main-sidebar');
            const logo = document.querySelector('.main-header .logo');

            const navbarWidth = 100 - sidebarWidthPercent - 0.5;

            const logoOffset = isRTL() ? 0.6 : 0.7;
            const logoWidth = sidebarWidthPercent - logoOffset;

            if (sidebar) {
                sidebar.style.width = sidebarWidthPercent + '%';
            }

            if (logo) {
                logo.style.setProperty('width', logoWidth + '%', 'important');
            }

            if (navbar) {
                navbar.style.width = navbarWidth + '%';
            }

            if (contentWrapper) {
                if (isRTL()) {
                    contentWrapper.style.marginRight = sidebarWidthPercent + '%';
                    contentWrapper.style.marginLeft = '0';
                } else {
                    contentWrapper.style.marginLeft = sidebarWidthPercent + '%';
                    contentWrapper.style.marginRight = '0';
                }
            }
        }

        function init() {
            const resizer = document.getElementById('sidebar-resizer');
            const sidebar = document.querySelector('.main-sidebar');

            if (!resizer || !sidebar) return;

            // Don't initialize on mobile
            if (isMobile()) {
                clearInlineStyles();
                return;
            }

            // Only apply saved width if sidebar is NOT collapsed and NOT mobile
            if (!isSidebarCollapsed() && !isMobile()) {
                const savedWidth = localStorage.getItem(STORAGE_KEY);
                if (savedWidth) {
                    updateLayout(parseFloat(savedWidth));
                }
            }

            // Watch for sidebar collapse/expand changes
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        // Skip on mobile
                        if (isMobile()) {
                            clearInlineStyles();
                            return;
                        }

                        if (isSidebarCollapsed()) {
                            clearInlineStyles();
                        } else {
                            const savedWidth = localStorage.getItem(STORAGE_KEY);
                            if (savedWidth) {
                                updateLayout(parseFloat(savedWidth));
                            }
                        }
                    }
                });
            });

            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class']
            });

            // Handle window resize - clear styles if resized to mobile
            window.addEventListener('resize', function() {
                if (isMobile()) {
                    clearInlineStyles();
                    isResizing = false;
                    document.body.classList.remove('sidebar-resizing');
                } else if (!isSidebarCollapsed()) {
                    const savedWidth = localStorage.getItem(STORAGE_KEY);
                    if (savedWidth) {
                        updateLayout(parseFloat(savedWidth));
                    }
                }
            });

            resizer.addEventListener('mousedown', function(e) {
                // Don't allow resizing on mobile or when collapsed
                if (isMobile() || isSidebarCollapsed()) return;

                e.preventDefault();
                isResizing = true;
                startX = e.clientX;
                startWidthPercent = pxToPercent(sidebar.offsetWidth);
                document.body.classList.add('sidebar-resizing');
            });

            document.addEventListener('mousemove', function(e) {
                if (!isResizing) return;

                // Stop if mobile or sidebar gets collapsed during resize
                if (isMobile() || isSidebarCollapsed()) {
                    isResizing = false;
                    document.body.classList.remove('sidebar-resizing');
                    clearInlineStyles();
                    return;
                }

                const deltaX = e.clientX - startX;
                const deltaPercent = (deltaX / window.innerWidth) * 100;

                let newWidthPercent;
                if (isRTL()) {
                    newWidthPercent = startWidthPercent - deltaPercent;
                } else {
                    newWidthPercent = startWidthPercent + deltaPercent;
                }

                newWidthPercent = Math.min(Math.max(newWidthPercent, MIN_WIDTH), MAX_WIDTH);

                updateLayout(newWidthPercent);
            });

            document.addEventListener('mouseup', function() {
                if (!isResizing) return;

                isResizing = false;
                document.body.classList.remove('sidebar-resizing');

                // Only save if not mobile and sidebar is not collapsed
                if (!isMobile() && !isSidebarCollapsed()) {
                    const finalWidth = pxToPercent(sidebar.offsetWidth).toFixed(2);
                    localStorage.setItem(STORAGE_KEY, finalWidth);
                }
            });

            resizer.addEventListener('dblclick', function() {
                // Don't do anything if mobile or collapsed
                if (isMobile() || isSidebarCollapsed()) return;

                clearInlineStyles();
                localStorage.removeItem(STORAGE_KEY);
            });
        }

        document.addEventListener('DOMContentLoaded', init);
    })();
</script>
