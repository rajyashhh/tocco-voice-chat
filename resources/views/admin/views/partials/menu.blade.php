@php
    $anyChild = false;
    $permissionExists = false;
            $roles = \Illuminate\Support\Arr::get($item, 'roles', []);
            $roles = count($roles) > 0 ? $roles : null;


            if (!function_exists('getPermissions')){
                function getPermissions($child) {
                    if (!$child) {
                        return ['permission' => [], 'roles' => [], 'has_empty' => false, 'open_roles' => []];
                    }

                    $permission = Arr::get($child, 'permission');
                    $roles = Arr::get($child, 'roles', []);

                    $permissions = [
                        // Only non-empty permissions are checked against the user.
                        'permission' => $permission ? [$permission] : [],
                        'roles' => $roles,
                        // has_empty: any node in this subtree (incl. self) carries no
                        // permission at all — such nodes have no permission gate.
                        'has_empty' => empty($permission),
                        // open_free: an empty-permission node with no role restriction
                        // is visible to everyone, so the parent must show too.
                        'open_free' => empty($permission) && count($roles) === 0,
                        // open_roles: roles of the nodes with an empty permission, so
                        // their visibility can still be gated by role restrictions.
                        'open_roles' => empty($permission) ? $roles : [],
                    ];

                    // Check if there are children and merge their permissions and roles recursively
                    if (isset($child['children']) && is_array($child['children'])) {
                        foreach ($child['children'] as $subChild) {
                            $childPermissions = getPermissions($subChild);
                            $permissions['permission'] = array_merge($permissions['permission'], $childPermissions['permission']);
                            $permissions['roles'] = array_merge($permissions['roles'], $childPermissions['roles']);
                            $permissions['has_empty'] = $permissions['has_empty'] || $childPermissions['has_empty'];
                            $permissions['open_free'] = $permissions['open_free'] || $childPermissions['open_free'];
                            $permissions['open_roles'] = array_merge($permissions['open_roles'], $childPermissions['open_roles']);
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

                    // A child (or the item itself) with an empty permission has no
                    // permission gate: it counts as a visible child when its own role
                    // restrictions allow it (no roles = no restriction).
                    // open_free covers children with no role restriction (always visible);
                    // otherwise visible() is ANY-match across the merged open_roles, so a
                    // matching role on any empty-permission child reveals the parent.
                    $openRolesL = count($data['open_roles']) > 0 ? $data['open_roles'] : null;
                    // open_free items (no permission + no role restriction) are
                    // visible to all logged-in admins — the backend handles access.
                    // Items with roles only show when the user matches those roles.
                    $openChildVisible = $data['has_empty'] && (
                        ($data['open_free'] && Admin::user()->isSuperAdmin())
                        || ($openRolesL !== null && Admin::user()->visible($openRolesL))
                    );

                    if (@$permissionExists || $isRoleVisible || $openChildVisible ){
                        $anyChild = true;
                    }
    @endphp
@endif


@php
    $hasRoles = $roles && Admin::user()->visible($roles);
    $permission = Arr::get($item, 'permission');
    if (empty($permission)) {
        // Leaf items with no permission: visible only to super admins.
        // Non-super admins must hold an explicit permission to see a menu
        // item — this aligns sidebar visibility with route-level enforcement
        // in AdminRbacGuard and MainController::Permission::check().
        if (!isset($item['children'])) {
            $hasPermission = Admin::user()->isSuperAdmin();
        } else {
            $hasPermission = false;
        }
    } else {
        // Explicit permission stays enforced.
        $hasPermission = Admin::user()->can($permission);
    }
    $anyChildExists = $anyChild ?? false;
    $allPermission = Admin::user()->can('*');

    // Visibility: items with explicit permissions are gated by can();
    // items with no permission (NULL) are visible to all logged-in admins;
    // the backend middleware + controller Permission::check() is the
    // source of truth for actual access control.
    $isVisible = ($hasRoles || $hasPermission || $allPermission || $anyChildExists);

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
            @else
                @if (Admin::user()->type != 'bd')
                <a href="{{ admin_url($item['uri']) }}">
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
                @endif
            @endif
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
