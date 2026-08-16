<style>
    /* ============================================
       Additional Variables for Select2
       (Uses your existing --primary-color)
       ============================================ */
    :root {
        /* Derived from your primary color */
        --primary-hover-color: color-mix(in srgb, var(--primary-color) 85%, black);
        --primary-light-alpha: color-mix(in srgb, var(--primary-color) 10%, transparent);
        --primary-shadow-alpha: color-mix(in srgb, var(--primary-color) 15%, transparent);
        --select-border-color: color-mix(in srgb, var(--primary-color) 20%, white);
        --select-bg-tint: color-mix(in srgb, var(--primary-color) 2%, white);
    }

    /* ============================================
       Select2 Single Selection - NAVBAR ONLY
       ============================================ */
    .main-header .select2-container--default .select2-selection--single,
    .navbar .select2-container--default .select2-selection--single,
    .navbar-nav .select2-container--default .select2-selection--single {
        height: 42px !important;
        border: 2px solid var(--gray-200, #e5e7eb) !important;
        border-radius: var(--border-radius, 12px) !important;
        background: linear-gradient(135deg, var(--white, #ffffff) 0%, var(--gray-50, #f9fafb) 100%) !important;
        transition: var(--transition) !important;
        box-shadow: var(--shadow-sm) !important;
        padding: 0 !important;
    }

    .dark-mode .main-header .select2-container--default .select2-selection--single,
    .dark-mode .navbar .select2-container--default .select2-selection--single,
    .dark-mode .navbar-nav .select2-container--default .select2-selection--single {
        background: var(--dark-primary-color) !important;
    }

    .dark-mode .main-header .select2-container--default .select2-selection--single .select2-selection__placeholder,
    .dark-mode .navbar .select2-container--default .select2-selection--single .select2-selection__placeholder,
    .dark-mode .navbar-nav .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: var(--white) !important;
    }

    .main-header .select2-container--default .select2-selection--single:hover,
    .navbar .select2-container--default .select2-selection--single:hover,
    .navbar-nav .select2-container--default .select2-selection--single:hover {
        border-color: var(--secondary-color) !important;
        box-shadow: var(--shadow-md) !important;
        transform: translateY(-1px);
    }

    .main-header .select2-container--default.select2-container--open .select2-selection--single,
    .main-header .select2-container--focus .select2-selection--single,
    .navbar .select2-container--default.select2-container--open .select2-selection--single,
    .navbar .select2-container--focus .select2-selection--single,
    .navbar-nav .select2-container--default.select2-container--open .select2-selection--single,
    .navbar-nav .select2-container--focus .select2-selection--single {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 4px var(--primary-hover-alpha),
        var(--shadow-md) !important;
        outline: none !important;
    }

    .main-header .select2-container--default .select2-selection--single .select2-selection__rendered,
    .navbar .select2-container--default .select2-selection--single .select2-selection__rendered,
    .navbar-nav .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 43px !important;
        padding-right: 16px !important;
        padding-left: 40px !important;
        color: var(--gray-700, #374151) !important;
        font-weight: 500 !important;
        font-size: 14px !important;
    }

    .dark-mode .main-header .select2-container--default .select2-selection--single .select2-selection__rendered,
    .dark-mode.navbar .select2-container--default .select2-selection--single .select2-selection__rendered,
    .dark-mode.navbar-nav .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--white) !important;
    }

    .main-header .select2-container--default .select2-selection--single .select2-selection__placeholder,
    .navbar .select2-container--default .select2-selection--single .select2-selection__placeholder,
    .navbar-nav .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: black !important;
    }

    .main-header .select2-container--default .select2-selection--single .select2-selection__arrow,
    .navbar .select2-container--default .select2-selection--single .select2-selection__arrow,
    .navbar-nav .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 20px !important;
        width: 0 !important;
    }

    .main-header .select2-container--default .select2-selection--single .select2-selection__arrow b,
    .navbar .select2-container--default .select2-selection--single .select2-selection__arrow b,
    .navbar-nav .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: var(--secondary-color) transparent transparent transparent !important;
        border-width: 6px 5px 0 5px !important;
        margin-top: -3px !important;
        transition: transform 0.3s ease !important;
    }

    .main-header .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b,
    .navbar .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b,
    .navbar-nav .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent var(--primary-color) transparent !important;
        border-width: 0 5px 6px 5px !important;
    }

    /* ============================================
       Select2 Multiple Selection - NAVBAR ONLY
       ============================================ */
    .main-header .select2-container--default .select2-selection--multiple,
    .navbar .select2-container--default .select2-selection--multiple,
    .navbar-nav .select2-container--default .select2-selection--multiple {
        min-height: 42px !important;
        border: 2px solid var(--gray-200, #e5e7eb) !important;
        border-radius: var(--border-radius, 12px) !important;
        background: linear-gradient(135deg, var(--white, #ffffff) 0%, var(--gray-50, #f9fafb) 100%) !important;
        transition: var(--transition) !important;
        box-shadow: var(--shadow-sm) !important;
        padding: 4px 8px !important;
    }

    .main-header .select2-container--default .select2-selection--multiple:hover,
    .navbar .select2-container--default .select2-selection--multiple:hover,
    .navbar-nav .select2-container--default .select2-selection--multiple:hover {
        border-color: var(--primary-color) !important;
    }

    .main-header .select2-container--default.select2-container--focus .select2-selection--multiple,
    .navbar .select2-container--default.select2-container--focus .select2-selection--multiple,
    .navbar-nav .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 4px var(--primary-hover-alpha) !important;
    }

    .main-header .select2-container--default .select2-selection--multiple .select2-selection__choice,
    .navbar .select2-container--default .select2-selection--multiple .select2-selection__choice,
    .navbar-nav .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: var(--primary-color) !important;
        border: none !important;
        border-radius: 8px !important;
        color: var(--text-primary-color, #ffffff) !important;
        padding: 4px 10px !important;
        margin: 3px !important;
        font-size: 13px !important;
    }

    .main-header .select2-container--default .select2-selection--multiple .select2-selection__choice__remove,
    .navbar .select2-container--default .select2-selection--multiple .select2-selection__choice__remove,
    .navbar-nav .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: var(--text-primary-color, #ffffff) !important;
        margin-right: 6px !important;
        font-weight: bold !important;
    }

    .main-header .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover,
    .navbar .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover,
    .navbar-nav .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #ffcccc !important;
    }

    /* ============================================
       Width Settings - NAVBAR ONLY
       ============================================ */
    .main-header #area-Manager-select,
    .main-header #country-select,
    .navbar #area-Manager-select,
    .navbar #country-select,
    .navbar-nav #area-Manager-select,
    .navbar-nav #country-select {
        width: 190px !important;
    }

    .main-header .select2-container,
    .navbar .select2-container,
    .navbar-nav .select2-container {
        width: 220px !important;
        margin: 2px !important;
        min-width: 150px !important;
    }

    .main-header .select2-container--default .select2-selection--single,
    .navbar .select2-container--default .select2-selection--single,
    .navbar-nav .select2-container--default .select2-selection--single {
        height: 38px !important;
        border-radius: 10px !important;
    }

    .main-header .select2-container--default .select2-selection--single .select2-selection__rendered,
    .navbar .select2-container--default .select2-selection--single .select2-selection__rendered,
    .navbar-nav .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 34px !important;
        font-size: 13px !important;
    }

    .main-header .select2-container--default .select2-selection--single .select2-selection__arrow,
    .navbar .select2-container--default .select2-selection--single .select2-selection__arrow,
    .navbar-nav .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }

    .main-header .select2-container--default .select2-selection--single .select2-selection__clear,
    .navbar .select2-container--default .select2-selection--single .select2-selection__clear,
    .navbar-nav .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        width: 22px !important;
        height: 22px !important;
        border-radius: 50% !important;
        color: var(--gray-700, #374151) !important;
        font-size: 16px !important;
        font-weight: normal !important;
        line-height: 20px !important;
        text-align: center !important;
        cursor: pointer !important;
        transition: var(--transition) !important;
        margin: 0 !important;
        padding: 0 !important;
        right: 13% !important;
    }

    .main-header .select2-container--default .select2-selection--single .select2-selection__clear:hover,
    .navbar .select2-container--default .select2-selection--single .select2-selection__clear:hover,
    .navbar-nav .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        background: #ef4444 !important;
        color: white !important;
        transform: translateY(-50%) scale(1.1) !important;
    }

    /* ============================================
       RTL Support (Arabic) - NAVBAR ONLY
       ============================================ */
    .rtl .main-header .select2-container--default .select2-selection--single .select2-selection__clear,
    .rtl .navbar .select2-container--default .select2-selection--single .select2-selection__clear,
    .rtl .navbar-nav .select2-container--default .select2-selection--single .select2-selection__clear,
    [dir="rtl"] .main-header .select2-container--default .select2-selection--single .select2-selection__clear,
    [dir="rtl"] .navbar .select2-container--default .select2-selection--single .select2-selection__clear,
    [dir="rtl"] .navbar-nav .select2-container--default .select2-selection--single .select2-selection__clear {
        right: auto !important;
        left: 35px !important;
        float: none !important;
    }

    .rtl .main-header .select2-container--default .select2-selection--single .select2-selection__arrow b,
    .rtl .navbar .select2-container--default .select2-selection--single .select2-selection__arrow b,
    .rtl .navbar-nav .select2-container--default .select2-selection--single .select2-selection__arrow b {
        left: 0 !important;
    }

    .rtl .main-header .select2-container--default .select2-selection--single .select2-selection__rendered,
    .rtl .navbar .select2-container--default .select2-selection--single .select2-selection__rendered,
    .rtl .navbar-nav .select2-container--default .select2-selection--single .select2-selection__rendered,
    [dir="rtl"] .main-header .select2-container--default .select2-selection--single .select2-selection__rendered,
    [dir="rtl"] .navbar .select2-container--default .select2-selection--single .select2-selection__rendered,
    [dir="rtl"] .navbar-nav .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-right: 16px !important;
        padding-left: 60px !important;
    }

    .rtl .main-header .select2-container--default .select2-selection--single .select2-selection__arrow,
    .rtl .navbar .select2-container--default .select2-selection--single .select2-selection__arrow,
    .rtl .navbar-nav .select2-container--default .select2-selection--single .select2-selection__arrow,
    [dir="rtl"] .main-header .select2-container--default .select2-selection--single .select2-selection__arrow,
    [dir="rtl"] .navbar .select2-container--default .select2-selection--single .select2-selection__arrow,
    [dir="rtl"] .navbar-nav .select2-container--default .select2-selection--single .select2-selection__arrow {
        right: auto !important;
        left: 20px !important;
        height: 43px !important;
    }

    .rtl .main-header .select2-container--default .select2-selection--single .select2-selection__clear,
    .rtl .navbar .select2-container--default .select2-selection--single .select2-selection__clear,
    .rtl .navbar-nav .select2-container--default .select2-selection--single .select2-selection__clear,
    [dir="rtl"] .main-header .select2-container--default .select2-selection--single .select2-selection__clear,
    [dir="rtl"] .navbar .select2-container--default .select2-selection--single .select2-selection__clear,
    [dir="rtl"] .navbar-nav .select2-container--default .select2-selection--single .select2-selection__clear {
        cursor: pointer;
        float: left !important;
        left: 30px !important;
        color: var(--primary-color) !important;
    }

    /* ============================================
       Dropdown Styling (Global - needed because
       dropdowns are appended to body)
       Add a specific class to navbar select2 dropdowns
       ============================================ */
    .select2-dropdown.navbar-select2-dropdown {
        border: 2px solid var(--gray-200, #e5e7eb) !important;
        border-radius: var(--border-radius, 12px) !important;
        box-shadow: var(--shadow-lg) !important;
        overflow: hidden !important;
        margin-top: 4px !important;
        animation: fadeInDown 0.2s ease-out;
    }

    .select2-dropdown.navbar-select2-dropdown .select2-search--dropdown .select2-search__field {
        border: 2px solid var(--gray-200, #e5e7eb) !important;
        border-radius: 8px !important;
        padding: 10px 14px !important;
        transition: var(--transition) !important;
    }

    .select2-dropdown.navbar-select2-dropdown .select2-search--dropdown .select2-search__field:focus {
        border-color: var(--primary-color) !important;
        outline: none !important;
        box-shadow: 0 0 0 3px var(--primary-hover-alpha) !important;
    }

    .select2-dropdown.navbar-select2-dropdown .select2-results__options {
        max-height: 300px !important;
    }

    .select2-dropdown.navbar-select2-dropdown .select2-results__option {
        padding: 12px 16px !important;
        transition: all 0.2s ease !important;
        font-size: 14px !important;
        color: var(--gray-700, #374151) !important;
    }

    .select2-dropdown.navbar-select2-dropdown .select2-results__option--highlighted[aria-selected] {
        background: var(--primary-color) !important;
        color: var(--text-primary-color, #ffffff) !important;
    }

    .select2-dropdown.navbar-select2-dropdown .select2-results__option[aria-selected=true] {
        background-color: var(--primary-hover-alpha, rgba(37, 99, 235, 0.1)) !important;
        color: var(--primary-color) !important;
        font-weight: 600 !important;
    }

    .select2-dropdown.navbar-select2-dropdown .select2-results__option[aria-selected=true]::after {
        content: '✓';
        float: left;
        margin-left: 8px;
        color: var(--primary-color);
    }

    .select2-dropdown.navbar-select2-dropdown .select2-results__message {
        color: var(--text-secondary-color, #9ca3af) !important;
        padding: 16px !important;
        text-align: center !important;
    }

    [dir="rtl"] .select2-dropdown.navbar-select2-dropdown .select2-results__option[aria-selected=true]::after {
        float: right;
        margin-left: 0;
        margin-right: 8px;
    }

    .select2-dropdown.navbar-select2-dropdown .select2-results__option.loading-results {
        color: var(--primary-color) !important;
    }

    /* ============================================
       Animations
       ============================================ */
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
