@php use App\Helpers\Common;use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->

    <style>
        /* === RTL Support === */
        [dir="rtl"] .form-check { padding-right: 0; padding-left: 25px; flex-direction: row-reverse; }
        [dir="rtl"] .form-check-input { margin-right: 0; margin-left: 0; }
        [dir="rtl"] .permission-group-title { flex-direction: row-reverse; }

        :root {
            --primary-color: {{ config('themes.primaryColor') }};
            --secondary-color: {{ config('themes.secondaryColor') }};
            --text-primary-color: {{ config('themes.textPrimaryColor') }};
            --text-secondary-color: {{ config('themes.textSecondaryColor') }};
            --box-background-color: {{ config('themes.boxBackgroundColor') }};
            --primary-rgb: {{ implode(',', array_map('hexdec', str_split(ltrim(config('themes.primaryColor'), '#'), 2))) }};
        }

        * { box-sizing: border-box; }

        /* === Main Role Tabs (Country / Area Manager) === */
        .agency-tabs {
            display: flex;
            gap: 6px;
            margin-bottom: 24px;
            overflow-x: auto;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0;
        }

        .tab-btn {
            padding: 14px 28px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.25s ease;
            white-space: nowrap;
            color: #8898aa;
            letter-spacing: 0.3px;
            position: relative;
            bottom: -2px;
        }

        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            background: linear-gradient(180deg, transparent 60%, rgba(var(--primary-rgb), 0.06) 100%);
        }

        .tab-btn:hover:not(.active) {
            color: #525f7f;
            border-bottom-color: #dee2e6;
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* === Main Card === */
        .card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
            overflow: hidden;
        }

        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-color) 100%);
        }

        .card-title {
            color: #ffffff;
            font-weight: 700;
            font-size: 17px;
            letter-spacing: 0.3px;
            margin: 0;
        }

        .card-body {
            padding: 20px 24px;
        }

        /* === Category Nav Tabs (inside card) === */
        .nav-tabs {
            background: var(--secondary-color);
            border-bottom: none;
            border-radius: 10px;
            padding: 6px;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .nav-tabs > li > a:hover { border-color: transparent; }

        .nav-link {
            color: rgba(255, 255, 255, 0.7);
            border: none !important;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            letter-spacing: 0.2px;
        }

        .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active {
            background: var(--primary-color) !important;
            color: #fff !important;
            box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.35);
        }

        /* === Select All Category Checkbox === */
        .category-select-all-container {
            padding: 0 4px !important;
        }

        .category-select-wrapper .form-check {
            background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.06) 0%, rgba(var(--primary-rgb), 0.02) 100%);
            border: 1px solid rgba(var(--primary-rgb), 0.15);
            border-radius: 10px;
            padding: 12px 18px;
            margin: 0;
            transition: all 0.2s ease;
        }

        .category-select-wrapper .form-check:hover {
            border-color: rgba(var(--primary-rgb), 0.3);
            box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.08);
        }

        /* === Permissions Grid === */
        .permissions-section { display: none; }
        .permissions-section.active { display: block; animation: fadeIn 0.25s ease; }

        #permissions-container {
            margin: 0 !important;
            padding: 0 4px;
        }

        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        @media (max-width: 1200px) {
            .permissions-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .permissions-grid { grid-template-columns: 1fr; }
        }

        /* === Permission Group Card === */
        .permission-group {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 0;
            transition: all 0.25s ease;
            overflow: hidden;
        }

        .permission-group:hover {
            border-color: rgba(var(--primary-rgb), 0.25);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
        }

        .permission-group-title {
            font-size: 14px;
            font-weight: 700;
            color: #2d3748;
            padding: 14px 16px;
            margin: 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f8 100%);
        }

        .label-small-font {
            font-size: 13px;
            font-weight: 700;
            color: #2d3748;
        }

        .group-select-all { margin: 0; }

        /* === Individual Permission Checkboxes === */
        .form-check {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 0;
            padding: 10px 16px;
            transition: background 0.15s ease;
            border-bottom: 1px solid #f7f7f7;
        }

        .form-check:last-child {
            border-bottom: none;
        }

        .form-check:hover {
            background: rgba(var(--primary-rgb), 0.03);
        }

        /* === Custom Checkbox Styling === */
        .form-check-input {
            margin: 0;
            flex-shrink: 0;
            margin-top: 2px;
            width: 18px;
            height: 18px;
            border: 2px solid #cbd5e0;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
            appearance: auto;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-input:hover {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        }

        .form-check-label {
            font-size: 13px;
            color: #4a5568;
            line-height: 1.5;
            word-wrap: break-word;
            flex: 1;
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .form-check:hover .form-check-label {
            color: #2d3748;
        }

        /* === Save Button === */
        .save-btn {
            margin: 20px 0 !important;
            padding: 12px 36px !important;
            font-size: 15px;
            font-weight: 700;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color) 100%);
            border: none;
            color: #fff;
            box-shadow: 0 4px 14px rgba(var(--primary-rgb), 0.3);
            transition: all 0.25s ease;
            letter-spacing: 0.3px;
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(var(--primary-rgb), 0.4);
        }

        .save-btn:active {
            transform: translateY(0);
        }

        /* === Loading Overlay === */
        #tab-loading {
            backdrop-filter: blur(4px);
        }
    </style>

</head>
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="..." crossorigin="anonymous" /> -->

<body>
  @php
           $activeTab = request('tab', 'packs');

    @endphp
        <!-- Navigation Tabs -->
    <div class="agency-tabs">

          <a href="?tab=packs" class="tab-btn" data-target="packs-tab">{{ __('country manager') }}</a>

        <a href="?tab=vips" class="tab-btn {{ $activeTab == 'vips' ? 'active' : '' }}" data-target="vips-tab">{{ __('area manager') }}</a>



    </div>
    <div id="tab-loading" style="
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            /* transform: translate(-50%, -50%); */
            background: var(--primary-color);
            color: var(--text-primary-color);
            z-index: 9999;
            padding: 30px 40px;
            border-radius: 10px;
            font-size: 20px;
            font-weight: bold;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        ">
        {{ __('Loading...') }}
    </div>


    <!-- packs Section -->

   <div class="tab-content active" id="packs-tab">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title" style="text-align: left;">{{ __('Permissions') }}</h4>
        </div>

        <div class="card-body">

            @php
                        use App\Models\RoleCategory;
                        use App\Enums\PermissionType;
                        // Group permissions by category first
                        $grouped = $permissions->groupBy('category');
                        $permissionType = $permissionType ??'role-country-manager';
                        $categories = RoleCategory::orderBy('sort')
                        ->select('slug', 'type')
                        ->where(function ($q) use($permissionType) {
                            $q->where('type',$permissionType);
                        })->get();
                        //dd($categories);
                        $selected = $selectedPermissions ?? [];

                        // Pre-process all permissions by category and group
                        $allGroupedPermissions = [];
                    foreach ($grouped as $categorySlug => $categoryPermissions) {
                        $allGroupedPermissions[$categorySlug] = $categoryPermissions->groupBy(function ($permission) {
                            $slug = $permission->slug;

                            if (str_contains($slug, '-switch-')) {
                                // Split by '-switch-'
                                $parts = explode('-switch-', $slug);
                                // $parts[1] is what comes after 'switch-'
                                return $parts[1];  // group by 'user' or 'agency' or whatever after switch-
                            } else {
                                // Normal grouping: remove first part and group by the rest
                                $parts = explode('-', $slug);
                                array_shift($parts);
                                return implode('-', $parts);
                            }
                        });
                    }
                    //dd($allGroupedPermissions);
                        $firstCategory = $categories->first()->slug ?? null;
                    @endphp
            <form action="{{ url('admin/update-super-roles') }}" method="POST">
                @csrf
                <input type="hidden" name="permissions_all" id="permissions_all">
                <input type="hidden" name="role_id" id="super_role_id"value="{{ $superAdminRole->id }}">

                {{-- Category Tabs --}}
                <ul class="nav nav-tabs mb-3" role="tablist" id="permission-tabs">
                    @foreach($categories as $category)
                        <li class="nav-item">
                            <a class="nav-link {{ $loop->first ? 'active' : '' }}"
                               data-category="{{ $category->slug }}"
                               href="#">
                                {{ is_array(__($category->slug)) ? ucwords(str_replace(['-', '_'], ' ', $category->slug)) : __($category->slug) }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- Select All per Category --}}
                <div class="category-select-all-container mb-3" style="padding: 0 20px;">
                    @foreach($categories as $category)
                        <div class="category-select-wrapper {{ $category->slug === $firstCategory ? 'active' : '' }}"
                             data-category="{{ $category->slug }}"
                             style="display: {{ $category->slug === $firstCategory ? 'block' : 'none' }};">
                            <div class="form-check">
                                <input class="form-check-input category-select-all"
                                       type="checkbox"
                                       data-category="{{ $category->slug }}"
                                       id="category-{{ $category->slug }}">
                                <label class="form-check-label fw-bold" for="category-{{ $category->slug }}">
                                    {{ __('Select All') }} {{ is_array(__($category->slug)) ? ucwords(str_replace(['-', '_'], ' ', $category->slug)) : __($category->slug) }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Permissions Grid --}}
                <div id="permissions-container">
                    @foreach($allGroupedPermissions as $categorySlug => $groupedPermissions)
                        <div class="permissions-section {{ $categorySlug === $firstCategory ? 'active' : '' }}"
                             data-category="{{ $categorySlug }}">
                            <div class="permissions-grid">
                                @foreach($groupedPermissions as $group => $perms)
                                    <div class="permission-group">
                                        <h6 class="permission-group-title">
                                            <input class="form-check-input group-select-all"
                                                   type="checkbox"
                                                   data-group="{{ $group }}"
                                                   data-category="{{ $categorySlug }}"
                                                   id="group-{{ $categorySlug }}-{{ $group }}">
                                            <label for="group-{{ $categorySlug }}-{{ $group }}" class="label-small-font">
                                                {{ is_array(__(ucwords(str_replace(['-', '_'], ' ', $group)))) ? ucwords(str_replace(['-', '_'], ' ', $group)) : __(ucwords(str_replace(['-', '_'], ' ', $group))) }}
                                            </label>
                                        </h6>
                                        @foreach($perms as $perm)
                                            <div class="form-check">
                                                <input class="form-check-input permission-checkbox"
                                                       type="checkbox"
                                                       name="permissions[]"
                                                       value="{{ $perm->id }}"
                                                       data-slug="{{ $perm->slug }}"
                                                       data-group="{{ $group }}"
                                                       data-category="{{ $categorySlug }}"
                                                       id="perm-{{ $perm->id }}"
                                                    {{ in_array($perm->id, $selected) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="perm-{{ $perm->id }}">
                                                    {{ is_array(__($perm->name)) ? $perm->name : __($perm->name) }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
               <br>
                {{-- Save Button --}}
                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-primary save-btn">{{ __('Save Permissions') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>



 <div class="tab-content active" id="vips-tab">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title" style="text-align: left;">{{ __('Permissions') }}</h4>
        </div>

        <div class="card-body">

            @php

                        // Group permissions by category first
                        $grouped = $areaPermissions->groupBy('category');
                        $permissionType = $areaPermissionType ??'role-country-manager';
                        $categories = RoleCategory::orderBy('sort')
                        ->select('slug', 'type')
                        ->where(function ($q) use($permissionType) {
                            $q->where('type',$permissionType);
                        })->get();
                        //dd($categories);
                        $selected = $areaSelectedPermissions ?? [];

                        // Pre-process all permissions by category and group
                        $allGroupedPermissions = [];
                    foreach ($grouped as $categorySlug => $categoryPermissions) {
                        $allGroupedPermissions[$categorySlug] = $categoryPermissions->groupBy(function ($permission) {
                            $slug = $permission->slug;

                            if (str_contains($slug, '-switch-')) {
                                // Split by '-switch-'
                                $parts = explode('-switch-', $slug);
                                // $parts[1] is what comes after 'switch-'
                                return $parts[1];  // group by 'user' or 'agency' or whatever after switch-
                            } else {
                                // Normal grouping: remove first part and group by the rest
                                $parts = explode('-', $slug);
                                array_shift($parts);
                                return implode('-', $parts);
                            }
                        });
                    }
                    //dd($allGroupedPermissions);
                        $firstCategory = $categories->first()->slug ?? null;
                    @endphp
            <form action="{{ url('admin/update-super-roles') }}" method="POST">
                @csrf
                <input type="hidden" name="area_permissions_all" id="permissions_all">
                <input type="hidden" name="role_id" id="role_id"value="{{ $areaManagerRole->id }}">

                {{-- Category Tabs --}}
                <ul class="nav nav-tabs mb-3" role="tablist" id="permission-tabs">
                    @foreach($categories as $category)
                        <li class="nav-item">
                            <a class="nav-link {{ $loop->first ? 'active' : '' }}"
                               data-category="{{ $category->slug }}"
                               href="#">
                                {{ is_array(__($category->slug)) ? ucwords(str_replace(['-', '_'], ' ', $category->slug)) : __($category->slug) }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- Select All per Category --}}
                <div class="category-select-all-container mb-3" style="padding: 0 20px;">
                    @foreach($categories as $category)
                        <div class="category-select-wrapper {{ $category->slug === $firstCategory ? 'active' : '' }}"
                             data-category="{{ $category->slug }}"
                             style="display: {{ $category->slug === $firstCategory ? 'block' : 'none' }};">
                            <div class="form-check">
                                <input class="form-check-input category-select-all"
                                       type="checkbox"
                                       data-category="{{ $category->slug }}"
                                       id="category-{{ $category->slug }}">
                                <label class="form-check-label fw-bold" for="category-{{ $category->slug }}">
                                    {{ __('Select All') }} {{ is_array(__($category->slug)) ? ucwords(str_replace(['-', '_'], ' ', $category->slug)) : __($category->slug) }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Permissions Grid --}}
                <div id="permissions-container">
                    @foreach($allGroupedPermissions as $categorySlug => $groupedPermissions)
                        <div class="permissions-section {{ $categorySlug === $firstCategory ? 'active' : '' }}"
                             data-category="{{ $categorySlug }}">
                            <div class="permissions-grid">
                                @foreach($groupedPermissions as $group => $perms)
                                    <div class="permission-group">
                                        <h6 class="permission-group-title">
                                            <input class="form-check-input group-select-all"
                                                   type="checkbox"
                                                   data-group="{{ $group }}"
                                                   data-category="{{ $categorySlug }}"
                                                   id="group-{{ $categorySlug }}-{{ $group }}">
                                            <label for="group-{{ $categorySlug }}-{{ $group }}" class="label-small-font">
                                                {{ is_array(__(ucwords(str_replace(['-', '_'], ' ', $group)))) ? ucwords(str_replace(['-', '_'], ' ', $group)) : __(ucwords(str_replace(['-', '_'], ' ', $group))) }}
                                            </label>
                                        </h6>
                                        @foreach($perms as $perm)
                                            <div class="form-check">
                                                <input class="form-check-input permission-checkbox"
                                                       type="checkbox"
                                                       name="permissions[]"
                                                       value="{{ $perm->id }}"
                                                       data-slug="{{ $perm->slug }}"
                                                       data-group="{{ $group }}"
                                                       data-category="{{ $categorySlug }}"
                                                       id="perm-{{ $perm->id }}"
                                                    {{ in_array($perm->id, $selected) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="perm-{{ $perm->id }}">
                                                    {{ is_array(__($perm->name)) ? $perm->name : __($perm->name) }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>



                <br>
                {{-- Save Button --}}
                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-primary save-btn">{{ __('Save Permissions') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>












<script>





    document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedTab = urlParams.get('tab') || 'packs';

        const allTabs = document.querySelectorAll('.tab-btn');
        const allTabContents = document.querySelectorAll('[id$="-tab"]');

        let targetElement = null;

        allTabs.forEach(tab => {
            const target = tab.getAttribute('data-target');
            const content = document.getElementById(target);

            if (target.startsWith(selectedTab)) {
                tab.classList.add('active');
                content.style.display = 'block';
                targetElement = content; // خزن العنصر لعمل scroll إليه لاحقًا
            } else {
                tab.classList.remove('active');
                content.style.display = 'none';
            }

            tab.addEventListener('click', function (e) {
                e.preventDefault();
                allTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                allTabs.forEach(t => {
                    const panelId = t.getAttribute('data-target');
                    const panel = document.getElementById(panelId);
                    if (!panel) return;
                    if (panelId === target) { panel.style.display = 'block'; panel.classList.add('active'); }
                    else { panel.style.display = 'none'; panel.classList.remove('active'); }
                });
                const url = new URL(window.location.href);
                const tabParam = new URLSearchParams(this.getAttribute('href').replace('?', ''));
                url.searchParams.set('tab', tabParam.get('tab'));
                window.history.replaceState({}, '', url.toString());
            });
        });
    });



         $(function () {
        let selectedPermissions = new Set(@json($selected ?? []));

        function updateHiddenInput() {
            $('#permissions_all').val([...selectedPermissions].join(','));
        }

        function getBrowsePermissionId(currentCheckbox) {
            const groupContainer = currentCheckbox.closest('.permission-group');
            const browseCheckbox = groupContainer.find('.permission-checkbox').filter(function() {
                const permSlug = $(this).data('slug');
                return permSlug && permSlug.includes('browse-');
            });

            return browseCheckbox.length ? parseInt(browseCheckbox.val()) : null;
        }

        function updateGroupCheckboxState(group, category) {
            const groupCheckboxes = $(`.permission-checkbox[data-group="${group}"][data-category="${category}"]`);
            const groupSelectAll = $(`.group-select-all[data-group="${group}"][data-category="${category}"]`);

            const totalCheckboxes = groupCheckboxes.length;
            const checkedCheckboxes = groupCheckboxes.filter(':checked').length;

            // Only checked when ALL permissions are selected, otherwise unchecked
            if (checkedCheckboxes === totalCheckboxes) {
                groupSelectAll.prop('checked', true).prop('indeterminate', false);
            } else {
                groupSelectAll.prop('checked', false).prop('indeterminate', false);
            }
        }

        function updateCategoryCheckboxState(category) {
            const categoryCheckboxes = $(`.permission-checkbox[data-category="${category}"]`);
            const categorySelectAll = $(`.category-select-all[data-category="${category}"]`);

            const totalCheckboxes = categoryCheckboxes.length;
            const checkedCheckboxes = categoryCheckboxes.filter(':checked').length;

            // Only checked when ALL permissions are selected, otherwise unchecked
            if (checkedCheckboxes === totalCheckboxes) {
                categorySelectAll.prop('checked', true).prop('indeterminate', false);
            } else {
                categorySelectAll.prop('checked', false).prop('indeterminate', false);
            }
        }

        function bindPermissionCheckboxes() {
            $('.permission-checkbox').on('change', function () {
                const id = parseInt($(this).val());
                const permSlug = $(this).data('slug');
                const group = $(this).data('group');
                const category = $(this).data('category');

                if ($(this).is(':checked')) {
                    selectedPermissions.add(id);

                    if (permSlug && (
                        permSlug.includes('create-') ||
                        permSlug.includes('edit-') ||
                        permSlug.includes('delete-')
                    )) {
                        const browsePermissionId = getBrowsePermissionId($(this));
                        if (browsePermissionId) {
                            selectedPermissions.add(browsePermissionId);
                            $(`#perm-${browsePermissionId}`).prop('checked', true);
                        }
                    }
                } else {
                    selectedPermissions.delete(id);

                    if (permSlug && permSlug.includes('browse-')) {
                        const groupContainer = $(this).closest('.permission-group');
                        groupContainer.find('.permission-checkbox').each(function() {
                            const relatedSlug = $(this).data('slug');
                            if (relatedSlug && (
                                relatedSlug.includes('create-') ||
                                relatedSlug.includes('edit-') ||
                                relatedSlug.includes('delete-')
                            )) {
                                const relatedId = parseInt($(this).val());
                                selectedPermissions.delete(relatedId);
                                $(this).prop('checked', false);
                            }
                        });
                    }
                }

                updateGroupCheckboxState(group, category);
                updateCategoryCheckboxState(category);
                updateHiddenInput();
            });
        }

        function bindGroupSelectAll() {
            $('.group-select-all').on('change', function() {
                const group = $(this).data('group');
                const category = $(this).data('category');
                const isChecked = $(this).is(':checked');

                const groupCheckboxes = $(`.permission-checkbox[data-group="${group}"][data-category="${category}"]`);

                groupCheckboxes.each(function() {
                    const id = parseInt($(this).val());
                    const currentlyChecked = $(this).is(':checked');

                    if (isChecked && !currentlyChecked) {
                        selectedPermissions.add(id);
                        $(this).prop('checked', true);
                    } else if (!isChecked && currentlyChecked) {
                        selectedPermissions.delete(id);
                        $(this).prop('checked', false);
                    }
                });

                updateCategoryCheckboxState(category);
                updateHiddenInput();
            });
        }

        function bindCategorySelectAll() {
            $('.category-select-all').on('change', function() {
                const category = $(this).data('category');
                const isChecked = $(this).is(':checked');

                const categoryCheckboxes = $(`.permission-checkbox[data-category="${category}"]`);
                const categoryGroupCheckboxes = $(`.group-select-all[data-category="${category}"]`);

                categoryCheckboxes.each(function() {
                    const id = parseInt($(this).val());
                    const currentlyChecked = $(this).is(':checked');

                    if (isChecked && !currentlyChecked) {
                        selectedPermissions.add(id);
                        $(this).prop('checked', true);
                    } else if (!isChecked && currentlyChecked) {
                        selectedPermissions.delete(id);
                        $(this).prop('checked', false);
                    }
                });

                // Update all group checkboxes in this category
                categoryGroupCheckboxes.each(function() {
                    $(this).prop('checked', isChecked).prop('indeterminate', false);
                });

                updateHiddenInput();
            });
        }

        function initializeGroupCheckboxes() {
            // Initialize all group checkboxes based on current state
            $('.group-select-all').each(function() {
                const group = $(this).data('group');
                const category = $(this).data('category');
                updateGroupCheckboxState(group, category);
            });
        }

        function initializeCategoryCheckboxes() {
            // Initialize all category checkboxes based on current state
            $('.category-select-all').each(function() {
                const category = $(this).data('category');
                updateCategoryCheckboxState(category);
            });
        }

        bindPermissionCheckboxes();
        bindGroupSelectAll();
        bindCategorySelectAll();
        initializeGroupCheckboxes();
        initializeCategoryCheckboxes();
        updateHiddenInput();

        $('#permission-tabs .nav-link').on('click', function (e) {
            e.preventDefault();
            $('#permission-tabs .nav-link').removeClass('active tab-highlight');
            $(this).addClass('active tab-highlight');
            const category = $(this).data('category');
            $('.permissions-section').removeClass('active');
            $(`.permissions-section[data-category="${category}"]`).addClass('active');

            // Show/hide the appropriate category select-all checkbox
            $('.category-select-wrapper').hide().removeClass('active');
            $(`.category-select-wrapper[data-category="${category}"]`).show().addClass('active');
        });
    });









</script>





