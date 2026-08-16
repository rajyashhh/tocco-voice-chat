<style>
    :root {
        --primary-color: {{ config('themes.primaryColor') ?: '#2563eb' }};
        --secondary-color: {{ config('themes.secondaryColor') ?: '#1f2937' }};
        --text-primary-color: {{ config('themes.textPrimaryColor') ?: '#ffffff' }};
        --text-secondary-color: {{ config('themes.textSecondaryColor') ?: '#9ca3af' }};
        --box-background-color: {{ config('themes.boxBackgroundColor') ?: '#ffffff' }};
        --table-background-color: {{ config('themes.tableBackGroundColor') ?: '#f9fafb' }};
        --background-image: {{ config('themes.backgroundImage') ?: 'none' }};
        --brand_background-image: url({{ getImagePath(config('themes.brandBackgroundImage')) ?: '' }});
        --second-alpha: rgba(31, 41, 55, 0.1);
        --primary-hover-alpha: rgba(37, 99, 235, 0.1);
        --scroll-second-color: rgba(255, 255, 255, 0.8);
        --scroll-first-color: rgba(37, 99, 235, 0.2);

        --inverse-color: #ffffff;
        --inverse-box-color: #1f2937;
        --success-button: linear-gradient(135deg, #10b981 0%, #059669 100%);
        --primary-button: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);

        /* Additional unified color variables */
        --white: #ffffff;
        --gray-800: #1f2937;
        --gray-700: #374151;
        --gray-50: #f9fafb;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-900: #111827;

        /* Modern Design Variables */
        --sidebar-width: 280px;
        --header-height: 70px;
        --border-radius: 12px;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --crs-red-hover: #e74c3c;
        --crs-white-faint: rgba(255, 255, 255, 0.06);
        --crs-white-faint-2: rgba(255, 255, 255, 0.16);
        --crs-transition: 320ms;
        --crs-ease: cubic-bezier(0.25, 0.8, 0.25, 1);
        --crs-font: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
</style>


@php
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/* =========================================================
 | Helper: can user see THIS item itself?
 |=========================================================*/
$canSeeSelf = function ($item) {
    $roles = Arr::get($item, 'roles', []);
    $permission = Arr::get($item, 'permission');

    if (!Admin::user()->visible($roles)) {
        return false;
    }

    // Super admin (slug=administrator) bypasses menu gating entirely,
    // matching laravel-admin's standard behaviour. Limited roles below
    // are still gated by their permissions.
    if (Admin::user()->isAdministrator()) {
        return true;
    }

    // MUST have permission and be allowed
    if (empty($permission)) {
        return false;
    }

    return Admin::user()->can($permission);
};

/* =========================================================
 | Helper: recursively filter children
 |=========================================================*/
$filterChildren = function ($children) use (&$filterChildren, $canSeeSelf) {
    $visible = [];

    foreach ($children as $child) {
        $childChildren = Arr::get($child, 'children', []);

        // recurse first
        $visibleGrandChildren = $filterChildren($childChildren);

        // show child ONLY if:
        // - user can see this child
        // - OR it has any visible descendant
        if ($canSeeSelf($child) || count($visibleGrandChildren) > 0) {
            $child['children'] = $visibleGrandChildren;
            $visible[] = $child;
        }
    }

    return $visible;
};

/* =========================================================
 | URI / BD visibility
 |=========================================================*/
$uri = Arr::get($item, 'uri', '');
$shouldHideBd = Str::startsWith($uri, 'bd/')
    && !Admin::user()->inRoles(['bd']);

/* =========================================================
 | Prevent duplicate rendering
 |=========================================================*/
$renderedMenu = $renderedMenu ?? [];
$itemId = Arr::get($item, 'id');

/* =========================================================
 | Normalize title
 |=========================================================*/
$title = Arr::get($item, 'title', '');
if (is_array($title)) {
    $title = $title[app()->getLocale()] ?? reset($title);
}
$title = (string) $title;

/* =========================================================
 | Filter children (RECURSIVE & STRICT)
 |=========================================================*/
$children = Arr::get($item, 'children', []);
$visibleChildren = $filterChildren($children);

/* =========================================================
 | FINAL visibility decision
 |=========================================================*/
$isVisible =
    !$shouldHideBd
    && !in_array($itemId, $renderedMenu)
    && (
        $canSeeSelf($item)
        || count($visibleChildren) > 0
    );

// إخفاء charisma-levels إذا كانت الميزة مغلقة
if ($uri === 'charisma-levels' && !config('charisma.badge', false)) {
    $isVisible = false;
}
@endphp

{{-- ===================== RENDER ===================== --}}
@if($isVisible)
@php
    $renderedMenu[] = $itemId;
    $href = url()->isValidUrl($uri) ? $uri : admin_url($uri);
    $isRtl = app()->getLocale() === 'ar';
@endphp

{{-- ===================== LEAF ===================== --}}
@if(count($visibleChildren) === 0)
<li class="crs-item" data-crs-id="{{ $itemId }}">
    <a href="{{ $href }}" class="crs-link crs-leaf">
        <span class="crs-icon">@if(Str::startsWith($item['icon'] ?? '', 'fa-'))<i class="fa {{ $item['icon'] }}"></i>@else{{ $item['icon'] ?? '•' }}@endif</span>
        <span class="crs-title">{{ $title }}</span>
    </a>
</li>

{{-- ===================== PARENT ===================== --}}
@else
<li class="crs-tree crs-item" data-crs-id="{{ $itemId }}">
    <a href="#" class="crs-link crs-toggle">
        <span class="crs-icon">@if(Str::startsWith($item['icon'] ?? '', 'fa-'))<i class="fa {{ $item['icon'] }}"></i>@else{{ $item['icon'] ?? '•' }}@endif</span>
        <span class="crs-title">{{ $title }}</span>
        <i class="fa {{ $isRtl ? 'fa-angle-left' : 'fa-angle-right' }}"></i>
    </a>

    <ul class="crs-submenu">
        @foreach($visibleChildren as $child)
            @include('vendor.admin.partials.menu', [
                'item' => $child,
                'renderedMenu' => $renderedMenu
            ])
        @endforeach
    </ul>
</li>
@endif
@endif
