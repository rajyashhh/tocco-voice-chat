{{-- ============================================================
     UNIFIED ADMIN THEME — single source of truth (2026-08)
     Loaded LAST in <head> so it wins the cascade over the legacy
     dynamic-style / dark_mode / admin.css layers. Light and dark
     are the SAME selectors reading the SAME tokens; only the token
     values change between modes. Toggle persists in localStorage.
     ============================================================ --}}

{{-- Apply saved theme synchronously (before paint) to avoid a flash. --}}
<script>
    (function () {
        try {
            var saved = localStorage.getItem('admin-theme');
            if (!saved) { saved = 'dark'; } // preserve current default
            var root = document.documentElement;
            if (saved === 'dark') { root.classList.add('dark-mode'); }
            else { root.classList.remove('dark-mode'); }
        } catch (e) { document.documentElement.classList.add('dark-mode'); }
    })();
</script>

<style>
    /* ---------- LIGHT tokens (default) ---------- */
    :root {
        --app-bg:            #eef2f7;
        --surface:           #ffffff;
        --surface-raised:    #f8fafc;
        --surface-sunken:    #f1f5f9;
        --border:            #e2e8f0;
        --border-strong:     #cbd5e1;

        --text-primary:      #1e293b;
        --text-secondary:    #64748b;
        --text-muted:        #94a3b8;

        --accent:            #2563eb;
        --accent-strong:     #1d4ed8;
        --accent-contrast:   #ffffff;
        --accent-soft:       color-mix(in srgb, var(--accent) 12%, transparent);

        --input-bg:          #ffffff;
        --input-border:      #cbd5e1;
        --input-text:        #1e293b;
        --placeholder:       #94a3b8;

        --table-head-bg:     #f1f5f9;
        --row-odd:           #ffffff;
        --row-even:          #f8fafc;
        --row-hover:         #eef2f7;

        --success:           #16a34a;
        --danger:            #dc2626;
        --warning:           #d97706;
        --info:              #2563eb;

        --shadow-card:       0 1px 3px rgba(15, 23, 42, .08), 0 1px 2px rgba(15, 23, 42, .04);

        /* ==============================================================
           DERIVED TOKENS for the standalone profile family
           (agency / user / bd / room profiles). Every value points at a
           BASE token above, so recoloring the panel = editing the base
           tokens only. These are additive and never override the base.
           They resolve correctly in dark automatically, because the base
           tokens they reference are redefined under .dark-mode below.
           ============================================================== */
        --radius:            16px;
        --radius-sm:         8px;
        --radius-md:         12px;
        --radius-lg:         16px;
        --shadow-sm:         var(--shadow-card);
        --shadow-md:         0 4px 20px rgba(0, 0, 0, .10);
        --shadow-lg:         0 8px 40px rgba(0, 0, 0, .12);

        --page-bg:           var(--app-bg);
        --card-bg:           var(--surface);
        --card-border:       var(--border);
        --profile-hairline:  var(--border);
        --profile-subtle-bg: var(--surface-raised);

        --profile-heading:   var(--text-primary);
        --meta-value:        var(--text-primary);
        --meta-label:        var(--text-secondary);
        --profile-muted:     var(--text-secondary);
        --profile-faint:     var(--text-muted);
        --empty-state-fg:    var(--text-muted);

        --meta-chip-bg:      var(--surface-sunken);
        --on-accent:         var(--accent-contrast);
        /* Text that sits on a saturated colored fill (info/success/danger
           badges, brand banner). Near-white in BOTH modes on purpose —
           it's a contrast constant, not a brand identity color. */
        --on-color:          #ffffff;

        /* Brand banner + decorative gradients — all accent-driven so the
           whole profile family carries ONE brand color from one place.
           The info/success variants stay distinct on purpose: they mark
           dashboard stat semantics, not arbitrary rainbow decoration. */
        --profile-banner: linear-gradient(135deg, var(--accent), var(--accent-strong));
        --gradient-1:     linear-gradient(135deg, var(--accent), var(--accent-strong));
        --gradient-2:     linear-gradient(135deg, var(--accent), var(--accent-strong));
        --gradient-3:     linear-gradient(135deg, var(--info), color-mix(in srgb, var(--info) 65%, #000));
        --gradient-4:     linear-gradient(135deg, var(--success), color-mix(in srgb, var(--success) 72%, #000));
        --gradient-5:     linear-gradient(135deg, var(--accent), var(--accent-strong));
        --gradient-danger:  linear-gradient(135deg, var(--danger), color-mix(in srgb, var(--danger) 68%, #000));
        --gradient-warning: linear-gradient(135deg, var(--warning), color-mix(in srgb, var(--warning) 70%, #000));

        /* Soft semantic chip fills (money / status pills). bg + matching fg. */
        --chip-success-bg: color-mix(in srgb, var(--success) 15%, transparent);
        --chip-success-fg: var(--success);
        --chip-danger-bg:  color-mix(in srgb, var(--danger) 15%, transparent);
        --chip-danger-fg:  var(--danger);
        --chip-warning-bg: color-mix(in srgb, var(--warning) 16%, transparent);
        --chip-warning-fg: var(--warning);
        --chip-info-bg:    color-mix(in srgb, var(--info) 15%, transparent);
        --chip-info-fg:    var(--info);
        --chip-neutral-bg: var(--surface-sunken);
        --chip-neutral-fg: var(--text-secondary);

        /* Sidebar follows the theme: light surface in light mode, dark slate in
           dark mode (redefined under .dark-mode below). Owner decision 2026-08:
           the menu must be light in light mode like every other surface. */
        --sidebar-bg:        #ffffff;
        --sidebar-border:    var(--border);
        --sidebar-text:      var(--text-primary);
        --sidebar-text-strong:#0f172a;
        --sidebar-hover:     rgba(15, 23, 42, .06);
        --sidebar-submenu-bg:var(--surface-sunken);

        /* Keep legacy variable names in sync so old rules inherit the palette */
        --primary-color:        var(--accent);
        --secondary-color:      var(--accent-strong);
        --text-primary-color:   var(--text-primary);
        --text-secondary-color: var(--text-secondary);
        --box-background-color: var(--surface);
        --white:                var(--surface);
        --gray-50:              var(--surface-raised);
        --gray-200:             var(--border);
        --gray-300:             var(--border-strong);
        --gray-700:             var(--text-primary);
    }

    /* ---------- DARK tokens (same variables, redefined) ---------- */
    html.dark-mode, .dark-mode {
        --app-bg:            #0c1420;
        --surface:           #1e2634;
        --surface-raised:    #273040;
        --surface-sunken:    #161e2b;
        --border:            rgba(255, 255, 255, .09);
        --border-strong:     rgba(255, 255, 255, .16);

        --text-primary:      #e6eaf1;
        --text-secondary:    #9aa6b8;
        --text-muted:        #6b7688;

        --accent:            #3b82f6;
        --accent-strong:     #2563eb;
        --accent-contrast:   #ffffff;
        --accent-soft:       color-mix(in srgb, var(--accent) 18%, transparent);

        --input-bg:          #131c2b;
        --input-border:      rgba(255, 255, 255, .14);
        --input-text:        #e6eaf1;
        --placeholder:       #7c8798;

        --table-head-bg:     #273040;
        --row-odd:           #1e2634;
        --row-even:          #232c3b;
        --row-hover:         #2c3547;

        --success:           #22c55e;
        --danger:            #ef4444;
        --warning:           #f59e0b;
        --info:              #3b82f6;

        --shadow-card:       0 1px 3px rgba(0, 0, 0, .40), 0 1px 2px rgba(0, 0, 0, .30);

        /* Sidebar: dark slate in dark mode (mirrors the surfaces) */
        --sidebar-bg:        #0f1b2d;
        --sidebar-border:    rgba(255, 255, 255, .06);
        --sidebar-text:      #cbd5e1;
        --sidebar-text-strong:#ffffff;
        --sidebar-hover:     rgba(255, 255, 255, .07);
        --sidebar-submenu-bg:rgba(0, 0, 0, .22);
    }

    /* ==================================================================
       CORE SURFACES — token-driven, work identically in both modes
       ================================================================== */
    body,
    .skin-black-light .wrapper,
    .skin-black-light .content-wrapper {
        background-color: var(--app-bg) !important;
        background-image: none !important;
        color: var(--text-primary) !important;
    }

    /* Cards / boxes — the scaffold (AdminLTE) + Bootstrap ships many card-like
       component classes each with a hardcoded #fff. Normalize them ALL here in
       one place so any page — present or future — inherits the themed surface
       instead of leaking white. This is the general layer the panel needs. */
    .box,
    .card,
    .panel,
    .panel-default,
    .well,
    .list-group,
    .list-group-item,
    .modal-content,
    .popover,
    .nav-tabs-custom {
        background: var(--surface) !important;
        color: var(--text-primary) !important;
        border-color: var(--border) !important;
        box-shadow: var(--shadow-card) !important;
    }
    .card-header,
    .card-footer,
    .panel-heading,
    .panel-footer,
    .list-group-item {
        background: var(--surface-raised) !important;
        color: var(--text-primary) !important;
        border-color: var(--border) !important;
    }
    .card-body,
    .card-title,
    .card-text,
    .panel-body,
    .panel-title { color: var(--text-primary) !important; background: transparent !important; }
    .box .box-header,
    .box .box-header .box-title { color: var(--text-primary) !important; }
    .box-header.with-border { border-bottom: 1px solid var(--border) !important; }
    .box-footer { border-top: 1px solid var(--border) !important; background: transparent !important; }

    /* Content header bar */
    .content-header,
    .skin-black-light .content-header {
        background: var(--surface) !important;
        border: 1px solid var(--border) !important;
        color: var(--text-primary) !important;
        box-shadow: var(--shadow-card) !important;
    }
    .content-header > h1,
    .content-header > h1 > small,
    .content-header > .breadcrumb { color: var(--text-primary) !important; background: transparent !important; }
    .content-header > .breadcrumb > li > a { color: var(--accent) !important; }
    .content-header > .breadcrumb > .active { color: var(--text-secondary) !important; }

    /* Top navbar */
    .skin-black-light .main-header > .navbar,
    .main-header .navbar-static-top {
        background: var(--surface) !important;
        color: var(--text-primary) !important;
    }
    .main-header .navbar .nav > li > a { color: var(--text-secondary) !important; }
    .main-header .navbar .nav > li > a:hover { color: var(--text-primary) !important; background: var(--surface-sunken) !important; }
    .main-header .user-menu .hidden-xs { color: var(--text-primary) !important; }

    /* ==================================================================
       TABLES
       ================================================================== */
    .table,
    .grid-table,
    .data-table,
    .table-section {
        background: var(--surface) !important;
        color: var(--text-primary) !important;
        box-shadow: none !important;
    }
    .table > thead > tr > th,
    .table thead th,
    .grid-table thead th,
    .data-table thead th {
        background: var(--table-head-bg) !important;
        color: var(--text-primary) !important;
        border-bottom: 1px solid var(--border) !important;
    }
    .table > tbody > tr > td,
    .table tbody td { color: var(--text-primary) !important; border-top: 1px solid var(--border) !important; }

    .table-striped > tbody > tr:nth-of-type(odd),
    .table tbody tr:nth-child(odd),
    .table tbody tr:nth-child(odd) > td { background: var(--row-odd) !important; }
    .table-striped > tbody > tr:nth-of-type(even),
    .table tbody tr:nth-child(even),
    .table tbody tr:nth-child(even) > td { background: var(--row-even) !important; }

    .table-hover > tbody > tr:hover,
    .table tbody tr:hover,
    .table tbody tr:hover > td { background: var(--row-hover) !important; color: var(--text-primary) !important; }

    .table-responsive { border-color: var(--border) !important; }

    /* ==================================================================
       FORMS & INPUTS
       ================================================================== */
    form,
    .form-horizontal,
    .box-body { background: transparent !important; color: var(--text-primary) !important; }

    .form-control,
    input[type="text"], input[type="password"], input[type="email"],
    input[type="number"], input[type="search"], input[type="url"],
    input[type="tel"], input[type="date"], input[type="datetime-local"],
    textarea, select {
        background-color: var(--input-bg) !important;
        color: var(--input-text) !important;
        border: 1px solid var(--input-border) !important;
    }
    .form-control:focus,
    input:focus, textarea:focus, select:focus {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px var(--accent-soft) !important;
    }
    ::placeholder { color: var(--placeholder) !important; opacity: 1; }
    .control-label, label, .form-group > label { color: var(--text-primary) !important; }
    .help-block, .text-muted, small { color: var(--text-secondary) !important; }

    /* Native <select> + its OPTIONS (the open list). The browser renders
       <option> from UA/select colors — it does NOT reliably inherit — so the
       open list showed black-on-dark and unselected options were invisible
       (owner report 2026-08). Legacy dynamic-style also ships
       `select > option { background: var(--white); color: black }` which stays
       black in dark. Set BOTH select and option explicitly from the tokens so
       the list is readable in both modes. Loaded last → wins the cascade. */
    select,
    select > option,
    select optgroup {
        background-color: var(--input-bg) !important;
        color: var(--input-text) !important;
    }

    /* ==================================================================
       TABS
       ================================================================== */
    .nav-tabs-custom,
    .nav-tabs-custom > .tab-content,
    .tab-content { background: var(--surface) !important; color: var(--text-primary) !important; }
    .nav-tabs { border-bottom: 1px solid var(--border) !important; }
    .nav-tabs > li > a { color: var(--text-secondary) !important; }
    .nav-tabs > li.active > a,
    .nav-tabs > li.active > a:focus,
    .nav-tabs > li.active > a:hover {
        background: var(--surface) !important;
        color: var(--text-primary) !important;
        border-color: var(--border) var(--border) transparent !important;
    }
    .nav-tabs-custom > .nav-tabs > li.active { border-top-color: var(--accent) !important; }

    /* ==================================================================
       PAGINATION / DROPDOWNS / MODALS
       ================================================================== */
    .pagination > li > a,
    .pagination > li > span {
        background: var(--surface) !important;
        border: 1px solid var(--border) !important;
        color: var(--text-primary) !important;
    }
    .pagination > li > a:hover { background: var(--row-hover) !important; }
    .pagination > .active > a,
    .pagination > .active > a:focus,
    .pagination > .active > a:hover,
    .pagination > .active > span {
        background: var(--accent) !important;
        border-color: var(--accent) !important;
        color: var(--accent-contrast) !important;
    }

    .dropdown-menu {
        background: var(--surface) !important;
        border: 1px solid var(--border) !important;
        box-shadow: var(--shadow-card) !important;
    }
    .dropdown-menu > li > a { color: var(--text-primary) !important; }
    .dropdown-menu > li > a:hover { background: var(--row-hover) !important; }
    .dropdown-menu .divider { background-color: var(--border) !important; }

    .modal-content { background: var(--surface) !important; color: var(--text-primary) !important; }
    .modal-header, .modal-footer { border-color: var(--border) !important; }

    /* ==================================================================
       CUSTOM / JS DROPDOWNS — select2, bootstrap-select, generic .dropdown.
       These render their own popup DOM (often appended to <body>, outside
       .content-wrapper) so they must be themed globally, not page-scoped,
       or the open list leaks its scaffold colors (owner report 2026-08).
       Navbar select2 stays handled by select_menu_style.
       ================================================================== */
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        background: var(--input-bg) !important;
        border: 1px solid var(--input-border) !important;
        color: var(--input-text) !important;
    }
    .select2-selection__rendered,
    .select2-selection__placeholder,
    .select2-results__option { color: var(--input-text) !important; }
    .select2-dropdown,
    .select2-search--dropdown { background: var(--surface) !important; border: 1px solid var(--border) !important; }
    .select2-search__field { background: var(--input-bg) !important; color: var(--input-text) !important; border: 1px solid var(--input-border) !important; }
    .select2-results__option[aria-selected="true"] { background: var(--accent-soft) !important; color: var(--text-primary) !important; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background: var(--accent) !important; color: var(--accent-contrast) !important; }

    /* bootstrap-select + generic Bootstrap .dropdown popups */
    .bootstrap-select .dropdown-menu,
    .dropdown-item {
        background: var(--surface) !important;
        color: var(--text-primary) !important;
    }
    .dropdown-item:hover,
    .dropdown-item:focus,
    .bootstrap-select .dropdown-menu li a:hover { background: var(--row-hover) !important; color: var(--text-primary) !important; }
    .dropdown-item.active,
    .dropdown-item:active { background: var(--accent) !important; color: var(--accent-contrast) !important; }

    /* ==================================================================
       CUSTOM SETTINGS PAGES (cp-settings / *-settings shared vocabulary)
       These blades ship page-embedded <style> with hardcoded #fff cards
       and dark text and NO .dark-mode coverage, so their cards stayed
       white-on-dark (owner report 2026-08). Fixing the shared class names
       here (theme-tokens loads last) covers every settings page at once
       instead of patching them one by one.
       ================================================================== */
    .settings-content,
    .settings-sidebar,
    .field-card,
    .feature-description-container,
    .settings-page-card {
        background: var(--surface) !important;
        border-color: var(--border) !important;
        color: var(--text-primary) !important;
        box-shadow: var(--shadow-card) !important;
    }
    /* Inner surfaces sit one step down from the card */
    .settings-content form,
    .field-card-icon {
        background: var(--surface-raised) !important;
        border-color: var(--border) !important;
    }
    .settings-content h2,
    .settings-section h2,
    .field-card-header label,
    .feature-description-container h4 { color: var(--text-primary) !important; }
    .section-desc,
    .feature-description-container .external-content,
    .settings-content .external-content { color: var(--text-secondary) !important; }
    /* Settings side menu buttons follow the theme; active keeps the accent */
    .settings-menu button {
        background: var(--surface-raised) !important;
        border-color: var(--border) !important;
        color: var(--text-primary) !important;
    }
    .settings-menu button:hover { background: var(--row-hover) !important; }
    .settings-menu button.active {
        background: var(--accent) !important;
        border-color: var(--accent) !important;
        color: var(--accent-contrast) !important;
    }

    /* ==================================================================
       DASHBOARD WIDGETS
       ================================================================== */
    .small-box { background: var(--surface) !important; color: var(--text-primary) !important; border: 1px solid var(--border) !important; box-shadow: var(--shadow-card) !important; }
    .small-box h3, .small-box p { color: var(--text-primary) !important; }
    .small-box .icon { color: var(--text-muted) !important; opacity: .5; }
    .info-box { background: var(--surface) !important; color: var(--text-primary) !important; box-shadow: var(--shadow-card) !important; }
    .info-box-content { color: var(--text-primary) !important; }

    /* ==================================================================
       BUTTONS — accent-consistent
       ================================================================== */
    .btn-success { background: var(--accent) !important; border-color: var(--accent-strong) !important; color: var(--accent-contrast) !important; }
    .btn-success:hover, .btn-success:focus { background: var(--accent-strong) !important; color: var(--accent-contrast) !important; }
    .btn-primary { background: var(--info) !important; border-color: var(--info) !important; color: #fff !important; }
    .btn-danger { background: var(--danger) !important; border-color: var(--danger) !important; color: #fff !important; }
    .btn-default {
        background: var(--surface-raised) !important;
        border: 1px solid var(--border-strong) !important;
        color: var(--text-primary) !important;
    }
    .btn-default:hover { background: var(--row-hover) !important; }

    /* ==================================================================
       SIDEBAR — neutral slate in BOTH modes (kills orange wash + neon)
       ================================================================== */
    .skin-black-light .main-sidebar,
    .skin-black-light .left-side {
        background: var(--sidebar-bg) !important;
        border-right: 1px solid var(--sidebar-border) !important;
        animation: none !important;
        box-shadow: 2px 0 12px rgba(0, 0, 0, .18) !important;
    }
    /* NOTE: legacy dynamic-style ships `.skin-black-light .sidebar a` (spec
       0,2,1) which out-specifies a plain `.main-sidebar .crs-link` (0,2,0) and
       forced the washed-out --text-secondary-color on top-level links in light
       mode. These selectors are prefixed with .skin-black-light so they win. */
    .skin-black-light .main-sidebar .crs-link,
    .skin-black-light .sidebar .crs-link {
        color: var(--sidebar-text) !important;
        box-shadow: none !important;
    }
    .skin-black-light .main-sidebar .crs-link:hover,
    .skin-black-light .main-sidebar .crs-link:focus,
    .skin-black-light .sidebar .crs-link:hover,
    .skin-black-light .sidebar .crs-link:focus {
        background: var(--sidebar-hover) !important;
        color: var(--sidebar-text-strong) !important;
    }
    .skin-black-light .main-sidebar .crs-title,
    .skin-black-light .main-sidebar .crs-icon,
    .skin-black-light .main-sidebar .crs-arrow,
    .skin-black-light .sidebar .crs-link i,
    .skin-black-light .sidebar .crs-title,
    .skin-black-light .sidebar .crs-icon,
    .skin-black-light .sidebar .crs-arrow { color: inherit !important; }
    .main-sidebar .crs-submenu {
        background: var(--sidebar-submenu-bg) !important;
        filter: none !important;
    }
    .main-sidebar .crs-submenu .crs-link { color: var(--sidebar-text) !important; }
    .main-sidebar .crs-submenu .crs-link:hover { background: var(--sidebar-hover) !important; color: var(--sidebar-text-strong) !important; }
    /* Popover / tooltip fly-outs when collapsed */
    .crs-popover, .crs-tooltip {
        background: var(--sidebar-bg) !important;
        border: 1px solid var(--sidebar-border) !important;
        filter: none !important;
    }
    .crs-popover .crs-link,
    .crs-popover .crs-title,
    .crs-tooltip { color: var(--sidebar-text) !important; }
    .crs-popover .crs-link:hover { background: var(--sidebar-hover) !important; color: var(--sidebar-text-strong) !important; }

    /* Sane, quiet scrollbars everywhere (kills the neon animated 24px bar) */
    * { scrollbar-width: thin; scrollbar-color: var(--border-strong) transparent; }
    ::-webkit-scrollbar { width: 10px !important; height: 10px !important; }
    ::-webkit-scrollbar-track { background: transparent !important; box-shadow: none !important; border: none !important; margin: 0 !important; }
    ::-webkit-scrollbar-thumb { background: var(--border-strong) !important; border-radius: 8px !important; box-shadow: none !important; animation: none !important; }
    ::-webkit-scrollbar-thumb:hover { background: var(--text-muted) !important; }
    .skin-black-light .main-sidebar::-webkit-scrollbar { width: 8px !important; }
    .skin-black-light .main-sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .18) !important; }

    /* ==================================================================
       THEME TOGGLE SWITCH (in navbar)
       ================================================================== */
    #theme-toggle-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        margin: 10px 4px;
        border: 1px solid var(--border) !important;
        border-radius: 12px;
        background: var(--surface-raised) !important;
        color: var(--text-secondary) !important;
        cursor: pointer;
        font-size: 18px;
        line-height: 1;
        transition: all .2s ease;
    }
    #theme-toggle-btn:hover { background: var(--row-hover) !important; color: var(--accent) !important; border-color: var(--accent) !important; }
    #theme-toggle-btn .tt-sun { display: none; }
    #theme-toggle-btn .tt-moon { display: inline; }
    .dark-mode #theme-toggle-btn .tt-sun { display: inline; }
    .dark-mode #theme-toggle-btn .tt-moon { display: none; }

    /* ==================================================================
       LETTER AVATAR (Task B) — first initial on a stable colored circle
       ================================================================== */
    .letter-avatar {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        color: #fff !important;
        font-weight: 700;
        text-transform: uppercase;
        border-radius: 50%;
        line-height: 1;
        overflow: hidden;
        vertical-align: middle;
    }
    .user-image.letter-avatar { width: 30px; height: 30px; font-size: 13px; }
    .user-header .letter-avatar,
    .sidebar .user-panel .letter-avatar,
    img.img-circle.letter-avatar { width: 90px; height: 90px; font-size: 36px; }

    /* ==================================================================
       Universal text safety net for DARK (scoped; never touches light)
       Legacy dark_mode already forces white on most nodes; keep parity
       for late-loaded nodes without leaking into light mode.
       ================================================================== */
    .dark-mode .box-title,
    .dark-mode .box-header,
    .dark-mode h1, .dark-mode h2, .dark-mode h3,
    .dark-mode h4, .dark-mode h5, .dark-mode label { color: var(--text-primary) !important; }
</style>

{{-- Toggle behaviour: flip class, persist, keep icon in sync. Rebinds on pjax. --}}
<script>
    (function () {
        function currentIsDark() { return document.documentElement.classList.contains('dark-mode'); }
        function apply(theme) {
            var root = document.documentElement;
            if (theme === 'dark') { root.classList.add('dark-mode'); }
            else { root.classList.remove('dark-mode'); }
            try { localStorage.setItem('admin-theme', theme); } catch (e) {}
        }
        function bind() {
            var btn = document.getElementById('theme-toggle-btn');
            if (!btn || btn.__bound) return;
            btn.__bound = true;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                apply(currentIsDark() ? 'light' : 'dark');
            });
        }
        if (document.readyState !== 'loading') { bind(); }
        else { document.addEventListener('DOMContentLoaded', bind); }
        document.addEventListener('pjax:complete', bind);
    })();
</script>
