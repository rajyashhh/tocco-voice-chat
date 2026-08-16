@php
    $anyChild = false;
    $permissionExists = false;
            $roles = \Illuminate\Support\Arr::get($item, 'roles', []);
            $roles = count($roles) > 0 ? $roles : null;


            if (!function_exists('getPermissions')){
                function getPermissions($child) {
                    if (!$child) {
                        return ['permission' => [], 'roles' => []];
                    }

                    $permissions = [
                        'permission' => Arr::get($child, 'permission') ? [Arr::get($child, 'permission')] : [],
                        'roles' => Arr::get($child, 'roles', [])
                    ];

                    // Check if there are children and merge their permissions and roles recursively
                    if (isset($child['children']) && is_array($child['children'])) {
                        foreach ($child['children'] as $subChild) {
                            $childPermissions = getPermissions($subChild);
                            $permissions['permission'] = array_merge($permissions['permission'], $childPermissions['permission']);
                            $permissions['roles'] = array_merge($permissions['roles'], $childPermissions['roles']);
                        }
                    }

                    return $permissions;
                }
                }
@endphp

@if(isset($item['children']))
    @php
        $data = getPermissions(@$item);

                    $rolesL = $data['roles'];
                    $rolesL = count($rolesL) > 0 ? $rolesL : null;

                    $isRoleVisible = $rolesL && Admin::user()->visible($rolesL);

                    foreach ($data['permission'] as $permission){

                        if (!$permission)  continue;

                        $permissionExists =   ( Admin::user()->can($permission));

                        if ($permissionExists) break;
                    }

                    if (@$permissionExists || $isRoleVisible ){
                        $anyChild = true;
                    }
    @endphp
@endif


@php
    $hasRoles = $roles && Admin::user()->visible($roles);
    $hasPermission = !empty(Arr::get($item, 'permission')) && Admin::user()->can(Arr::get($item, 'permission'));
    $anyChildExists = $anyChild ?? false;
    $allPermission = Admin::user()->can('*');
    $isVisible = ($hasRoles || $hasPermission|| $allPermission || $anyChildExists );

    // إخفاء charisma-levels إذا كانت الميزة مغلقة
    if (Arr::get($item, 'uri') == 'charisma-levels' && !config('charisma.badge', false)) {
        $isVisible = false;
    }

    // if (Arr::get($item, 'id') == '13'){
    //     dump(Admin::user()->can(Arr::get($item, 'permission')));

    //         dump($isVisible, $hasRoles , $hasPermission, $allPermission , $anyChildExists);
    //     }
@endphp

@if($isVisible)
    @if(!isset($item['children']))
        <li>
            @if(url()->isValidUrl($item['uri']))
                <a href="{{ $item['uri'] }}" target="_blank">
                    @else



                    @if (Admin::user()->type != 'bd')
                    <a href="{{ admin_url($item['uri']) }}">

                    @endif

                            @endif
                            <i class="fa {{$item['icon']}}"></i>
                            @if (Lang::has($titleTranslation = 'admin.menu_titles.' . trim(str_replace(' ', '_', strtolower($item['title'])))))
                                <span>{{ __($titleTranslation) }}</span>
                                @if ($item['uri'] == 'soon' || $item['uri'] == '/soon')
                                <i  style="float: {{ app()->getLocale()== 'en'? 'right' : 'left' }};">{{ __('soon') }}</i>
                                @endif
                            @else
                                <span>{{ admin_trans($item['title']) }}</span>
                                @if ($item['uri'] == 'soon' || $item['uri'] == '/soon')
                                    <i  style="float: {{ app()->getLocale()== 'en'? 'right' : 'left' }};">{{ __('soon') }}</i>
                                @endif
                            @endif
                        </a>
        </li>
    @else
        <li class="treeview">
            <a href="#">
                <i class="fa {{ $item['icon'] }}"></i>
                @if (Lang::has($titleTranslation = 'admin.menu_titles.' . trim(str_replace(' ', '_', strtolower($item['title'])))))
                    <span>{{ __($titleTranslation) }}</span>
                @else
                    <span>{{ admin_trans($item['title']) }}</span>
                @endif
{{--                @if ($item['title'] == 'المحفظة')--}}
{{--                    <i class="pull-left" style="margin-right: 2px;">{{ __('soon') }}</i>--}}
{{--                @endif--}}
{{--                @if ($item['title'] == 'Wallet')--}}
{{--                    <i class="pull-right" style="margin-right: 2px;">{{ __('soon') }}</i>--}}
{{--                @endif--}}

                <i class="fa fa-angle-left pull-right"></i>
            </a>
            <ul class="treeview-menu">
                @foreach($item['children'] as $item)
                    @include('admin::partials.menu', $item)
                @endforeach
            </ul>
        </li>
    @endif
@endif
