<style>
    /* Fixed panel theme (owner decision: panel colors are no longer
       admin-configurable — values baked to the previous dark defaults) */
    :root {
        --primary-color: #FF9428;
        --secondary-color: #1A1A1A;
        --green-color: #10b981;
        --text-primary-color: #fdf8f8;
        --text-secondary-color: #c1b9b9;
        --box-background-color: #222222;
        --table-background-color: #c88213;
        --background-image: none;
        --brand_background-image: none;
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
        --off-white: #faf9f6;
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

        --gradient-primary: linear-gradient(90deg, var(--secondary-color) 0%, var(--primary-color) 100%);
        --gradient-vertical-primary: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    }

    /* .col-sm-8 {
    width: 80.66666667%;
} */

    .col-sm-2 {
        width: auto;
    }

    .rtl label {
        margin: 0 !important;
    }


    .sidebar-menu {
        margin: 11% 0px;
    }

    /*.ltr label {*/
    /*    margin: 0 !important;*/
    /*}*/
    /*
        .ltr .fields-group .form-group{

           display: flex !important;
        } */

    .fileinput-remove {
        display: none;

    }

    .rtl .pull-right {
        float: left !important;
    }

    .box-info .btn-group.pull-right {
        float: right !important;
    }

    .rtl .box-info .pull-right {
        float: right !important;
    }

    .rtl .box-info label {
        margin: 5px 10px 0 0 !important;
    }

    /* .box-header {
        background: #f9fafb !important;
        border-bottom: 1px solid #e5e7eb !important;
        padding: 16px 24px !important;
        margin: -24px -24px 24px -24px !important;
        border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
        display: block !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 21px 145px !important;
    } */

    /* Responsive fixes for box-header on mobile */
    @media (max-width: 767px) {
        .box-header {
            padding: 12px 16px !important;
            margin: -16px -16px 16px -16px !important;
            display: block !important;
        }

        .box-header.with-border {
            padding: 12px 16px !important;
            margin: -16px -16px 16px -16px !important;
        }

        .box-header.with-border.filter-box {
            padding: 12px 16px !important;
            margin: -16px -16px 16px -16px !important;
        }

        .box-header .pull-right,
        .box-header .pull-left {
            text-align: center !important;
        }

        .box-header .box-title {
            font-size: 16px !important;
            margin-bottom: 8px !important;
        }

        .box-header form {
            padding-top: 0 !important;
        }
    }

    .box-header form {
        border-radius: 36px;
    }

    .input-group-sm > .form-control, .input-group-sm > .input-group-addon, .input-group-sm > .input-group-btn > .btn {
        height: 35px !important;
    }

    .box-header form .box-footer {
        border-radius: 36px;
    }

    .pagination > .active > a, .pagination > .active > a:focus, .pagination > .active > a:hover, .pagination > .active > span, .pagination > .active > span:focus, .pagination > .active > span:hover {
        z-index: 2;
        color: #fff;
        cursor: default;
        background: var(--secondary-color) !important;
        border-color: #337ab7;
    }

    .skin-black-light .content-header {
        box-shadow: none;
    }

    input:checked + .slider {
        background: green !important;
    }

    .content-header > .breadcrumb > li > a {
        color: var(--primary-color) !important;
        text-decoration: none;
        font-size: var(--bs-breadcrumb-font-size);
        display: inline-block;
    }

    /* Dynamic CSS */
    @keyframes dynamic-sidebar-bg {
        0% {
            background: linear-gradient(180deg, var(--white) 0%, var(--gray-50) 100%) !important;
        }
        50% {
            background: linear-gradient(180deg, var(--gray-50) 0%, var(--white) 50%, var(--gray-200) 100%) !important;
        }
        100% {
            background: linear-gradient(180deg, var(--white) 0%, var(--gray-50) 100%) !important;
        }
    }

    .skin-black-light .main-sidebar,
    .skin-black-light .left-side {
        /* Sidebar surface is defined by css/theme-tokens.blade.php (--sidebar-bg,
           neutral slate in both light & dark). Orange gradient + animation removed. */
        background: var(--sidebar-bg, #0f1b2d) !important;
        box-shadow: var(--shadow-lg) !important;
        border-right: 1px solid var(--border, rgba(255,255,255,.09)) !important;
    }

    .skin-black-light .main-sidebar {
        width: 18%;
        position: fixed !important;
        top: 0 !important;
        left: -var(--sidebar-width) !important;
        height: 100vh !important;
        z-index: 1000 !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        transition: left 0.3s ease !important;
        border-radius: 0 20px 20px 0 !important;
        box-shadow: 0 0 40px rgba(0, 0, 0, 0.1) !important;
    }

    /* Neon animated sidebar scrollbar removed (2026-08 theme unification).
       Quiet, token-driven scrollbars are defined in css/theme-tokens.blade.php. */

    .sidebar-open .main-sidebar {
        left: 0 !important;
    }

    .skin-black-light .sidebar-menu > li.header {
        color: rgba(255, 255, 255, 0.6) !important;
        background: rgba(255, 255, 255, 0.05) !important;
        margin: 16px 16px 8px 16px !important;
        padding: 8px 16px !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        border-radius: 6px !important;
    }

    /* Global Custom Scrollbar for Entire System - Cyberpunk Neon Design */
    *::-webkit-scrollbar {
        width: 14px !important;
        height: 14px !important;
    }

    *::-webkit-scrollbar-track {
        background: linear-gradient(180deg, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.6) 50%, rgba(0, 0, 0, 0.8) 100%) !important;
        border-radius: 0 !important;
        margin: 1px !important;
        position: relative !important;
        border: 1px solid rgba(37, 99, 235, 0.3) !important;
        box-shadow: inset 0 0 20px rgba(37, 99, 235, 0.1), 0 0 10px rgba(16, 185, 129, 0.1) !important;
    }

    *::-webkit-scrollbar-track::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        background: linear-gradient(90deg, transparent 0%, rgba(37, 99, 235, 0.1) 20%, rgba(16, 185, 129, 0.1) 80%, transparent 100%) !important;
        animation: neon-track 4s ease-in-out infinite !important;
    }

    @keyframes neon-track {
        0%, 100% {
            opacity: 0.2;
        }
        50% {
            opacity: 0.5;
        }
    }

    *::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #000000 0%, rgba(37, 99, 235, 0.8) 30%, rgba(16, 185, 129, 0.8) 70%, #000000 100%) !important;
        border-radius: 0 !important;
        border: 1px solid rgba(255, 255, 255, 0.8) !important;
        box-shadow: 0 0 10px rgba(37, 99, 235, 0.8), 0 0 20px rgba(16, 185, 129, 0.6), inset 0 0 10px rgba(0, 0, 0, 0.8) !important;
        transition: all 0.2s ease !important;
        position: relative !important;
        cursor: pointer !important;
        background-size: 400% 400% !important;
        animation: neon-flow 6s linear infinite !important;
        min-height: 40px !important;
        min-width: 40px !important;
    }

    @keyframes neon-flow {
        0% {
            background-position: 0% 50%;
        }
        50% {
            background-position: 100% 50%;
        }
        100% {
            background-position: 0% 50%;
        }
    }

    *::-webkit-scrollbar-thumb::before {
        content: '' !important;
        position: absolute !important;
        top: 2px !important;
        left: 2px !important;
        right: 2px !important;
        bottom: 2px !important;
        background: linear-gradient(45deg, rgba(255, 255, 255, 0.9) 0%, rgba(37, 99, 235, 0.7) 50%, rgba(16, 185, 129, 0.7) 100%) !important;
        border-radius: 0 !important;
        box-shadow: 0 0 8px rgba(255, 255, 255, 0.8), 0 0 15px rgba(37, 99, 235, 0.6) !important;
        animation: neon-core 3s ease-in-out infinite alternate !important;
    }

    @keyframes neon-core {
        0% {
            opacity: 0.7;
            transform: scale(0.9);
        }
        100% {
            opacity: 1;
            transform: scale(1.1);
        }
    }

    *::-webkit-scrollbar-thumb::after {
        content: '' !important;
        position: absolute !important;
        top: -1px !important;
        left: -1px !important;
        right: -1px !important;
        bottom: -1px !important;
        background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.3) 50%, transparent 70%) !important;
        border-radius: 0 !important;
        animation: neon-glow 2s ease-in-out infinite !important;
    }

    @keyframes neon-glow {
        0%, 100% {
            opacity: 0;
        }
        50% {
            opacity: 1;
        }
    }

    *::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, rgba(37, 99, 235, 1) 0%, rgba(16, 185, 129, 1) 50%, rgba(37, 99, 235, 1) 100%) !important;
        box-shadow: 0 0 20px rgba(37, 99, 235, 1.2), 0 0 35px rgba(16, 185, 129, 1), inset 0 0 15px rgba(255, 255, 255, 0.3) !important;
        transform: scale(1.05) !important;
        border: 2px solid rgba(255, 255, 255, 1) !important;
        animation-duration: 3s !important;
    }

    *::-webkit-scrollbar-thumb:hover::before {
        box-shadow: 0 0 15px rgba(255, 255, 255, 1), 0 0 25px rgba(37, 99, 235, 0.8) !important;
        animation-duration: 2s !important;
    }

    *::-webkit-scrollbar-thumb:active {
        background: linear-gradient(135deg, rgba(16, 185, 129, 1) 0%, rgba(37, 99, 235, 1) 100%) !important;
        box-shadow: 0 0 25px rgba(37, 99, 235, 1.5), 0 0 45px rgba(16, 185, 129, 1.2) !important;
        transform: scale(0.95) !important;
        animation-duration: 1.5s !important;
    }

    *::-webkit-scrollbar-corner {
        background: rgba(0, 0, 0, 0.8) !important;
        border: 1px solid rgba(37, 99, 235, 0.3) !important;
        box-shadow: 0 0 10px rgba(37, 99, 235, 0.2) !important;
    }

    body {
        background-color: #f8fafc !important;
        background-image: var(--brand_background-image) !important;
        background-repeat: no-repeat !important;
        background-size: cover !important;
        background-position: center !important;
        background-attachment: fixed !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        line-height: 1.6;
    }

    .skin-black-light .content-wrapper {
        margin-left: 0 !important;
        background: #f8fafc !important;
        min-height: calc(100vh - var(--header-height)) !important;
        padding: 1% !important;
        transition: var(--transition) !important;
    }

    /* Shift content when sidebar is open */
    .sidebar-open .content-wrapper {
        margin-left: var(--sidebar-width) !important;
        padding-left: 0 !important;
    }

    /* Shift content when sidebar is open (RTL) */
    .rtl.sidebar-open .content-wrapper {
        margin-right: var(--sidebar-width) !important;
        padding-right: 0 !important;
    }

    .skin-black-light .wrapper {
        background: #f8fafc !important;
    }

    .rtl .small-box .icon {
        width: 100%;
        text-align: left;
        right: -2px !important;
    }

    .skin-black-light .main-header > .navbar {
        border-bottom: 1px solid rgba(229, 231, 235, 0.3) !important;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important;
        height: var(--header-height) !important;
        display: flex !important;
        align-items: center !important;
        position: relative !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        border-radius: 0 0 var(--border-radius) var(--border-radius) !important;
        z-index: 1030 !important;
    }

    .sidebar-open .skin-black-light .main-header > .navbar {
        margin-left: var(--sidebar-width) !important;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15) !important;
    }

    .content-header {
        padding: 20px 24px !important;
        margin: 0 0 24px 0 !important;
        background: #ffffff !important;
        box-shadow: var(--shadow-sm) !important;
        border-radius: var(--border-radius) !important;
        border: 1px solid #e5e7eb !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
    }

    .content-header > h1 {
        margin: 0 !important;
        color: #111827 !important;
        font-size: 1.5rem !important;
        font-weight: 600 !important;
    }

    .content-header > .breadcrumb {
        background: transparent !important;
        margin: 0 !important;
        padding: 0 !important;
        font-size: 0.875rem !important;
    }

    .content-header > .breadcrumb > li > a {
        color: #6b7280 !important;
        text-decoration: none !important;
        transition: var(--transition) !important;
    }

    .content-header > .breadcrumb > li > a:hover {
        color: var(--primary-color) !important;
    }

    .content-header > .breadcrumb > .active {
        color: #374151 !important;
        font-weight: 500 !important;
    }

    .skin-black-light .sidebar a {
        color: var(--text-secondary-color) !important;
        text-decoration: none !important;
        display: flex !important;
        align-items: center !important;
        transition: var(--transition) !important;
    }

    .settings-menu {
        border-bottom: 3px solid transparent;
        padding-bottom: 10px;
        background: transparent;
    }

    .dark-mode .settings-menu {
        border-bottom: 3px solid var(--white);
    }

    .skin-black-light .sidebar a:hover {
        color: var(--primary-color) !important;
    }

    .skin-black-light .sidebar a i {
        color: var(--text-secondary-color) !important;
        margin-right: 12px !important;
        width: 20px !important;
        text-align: center !important;
        font-size: 1.1rem !important;
        transition: var(--transition) !important;
    }

    .rtl .col-md-1,
    .rtl .col-md-2,
    .rtl .col-md-3,
    .rtl .col-md-4,
    .rtl .col-md-5,
    .rtl .col-md-6,
    .rtl .col-md-7,
    .rtl .col-md-8,
    .rtl .col-md-9,
    .rtl .col-md-10,
    .rtl .col-md-11,
    .rtl .col-md-12 {
        float: right;
    }

    .rtl .box-header .pull-left {
        float: right !important;
    }

    .iti {
        position: relative;
        z-index: 1050 !important;
        width: 100%;
    }

    .iti__country-list {
        z-index: 3000 !important;
    }

    .iti {
        direction: ltr !important;
        text-align: left !important; /* optional, for consistent alignment */
    }

    .iti__country-list, .iti__country {
        direction: ltr !important;
        text-align: left !important;
    }

    .rtl .iti--allow-dropdown .iti__flag-container,
    .rtl .iti--separate-dial-code .iti__flag-container {
        left: auto;
        right: auto;
    }

    .box {
        background: #ffffff !important;
        color: #374151 !important;
        border: 1px solid #e5e7eb !important;
        border-radius: var(--border-radius) !important;
        box-shadow: var(--shadow-md) !important;
        overflow: visible !important;
        transition: var(--transition) !important;
    }

    .box:hover {
        box-shadow: var(--shadow-lg) !important;
        transform: translateY(-2px) !important;
    }

    .table .table {
        color: var(--table-background-color) !important;
    }

    .skin-black-light .sidebar-menu > li > a {
        border-radius: var(--border-radius);
        margin: 4px 16px;
        padding: 12px 16px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: none !important;
        position: relative;
        overflow: hidden;
    }

    .skin-black-light .sidebar-menu > li > a::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background: var(--primary-color);
        transform: scaleY(0);
        transition: var(--transition);
        border-radius: 0 4px 4px 0;
    }

    .ltr .skin-black-light .sidebar-menu > li > a::before {
        left: 85% !important;
    }

    .skin-black-light .sidebar-menu > li:hover > a,
    .skin-black-light .sidebar-menu > li.active > a {
        color: #ffffff !important;
        background: rgba(37, 99, 235, 0.15) !important;
        box-shadow: var(--shadow-md);
        transform: translateX(4px);
    }

    .skin-black-light .sidebar-menu > li:hover > a::before,
    .skin-black-light .sidebar-menu > li.active > a::before {
        transform: scaleY(1);
    }

    .skin-black-light .sidebar-menu > li:hover > a i,
    .skin-black-light .sidebar-menu > li.active > a i {
        color: var(--primary-color) !important;
        transform: scale(1.1);
        transition: var(--transition);
    }

    /* Sidebar Icon Animations */

    /* Best Choice: Glow Pulse Animation - Active */
    .skin-black-light .sidebar-menu > li:hover > a i {
        animation: icon-glow 1s ease-in-out infinite;
        filter: drop-shadow(0 0 4px rgba(37, 99, 235, 0.6));
    }

    @keyframes icon-glow {
        0%, 100% {
            transform: scale(1);
            filter: drop-shadow(0 0 4px rgba(37, 99, 235, 0.6));
        }
        50% {
            transform: scale(1.1);
            filter: drop-shadow(0 0 8px rgba(37, 99, 235, 0.8));
        }
    }

    /* Alternative Options - Uncomment to use */

    /* Option 1: Gentle Pulse Animation */
    /*
    .skin-black-light .sidebar-menu > li:hover > a i {
        animation: icon-pulse 0.6s ease-in-out;
    }

    @keyframes icon-pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.15); }
    }
    */

    /* Option 2: Rotate Animation */
    /*
    .skin-black-light .sidebar-menu > li:hover > a i {
        animation: icon-rotate 0.4s ease-in-out;
    }

    @keyframes icon-rotate {
        0% { transform: rotate(0deg) scale(1); }
        50% { transform: rotate(180deg) scale(1.1); }
        100% { transform: rotate(360deg) scale(1); }
    }
    */

    /* Option 3: Bounce Animation */
    /*
    .skin-black-light .sidebar-menu > li:hover > a i {
        animation: icon-bounce 0.8s ease-in-out;
    }

    @keyframes icon-bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0) scale(1); }
        40% { transform: translateY(-8px) scale(1.1); }
        60% { transform: translateY(-4px) scale(1.05); }
    }
    */

    /* Option 4: Shake Animation */
    /*
    .skin-black-light .sidebar-menu > li:hover > a i {
        animation: icon-shake 0.5s ease-in-out;
    }

    @keyframes icon-shake {
        0%, 100% { transform: translateX(0) scale(1); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-2px) scale(1.05); }
        20%, 40%, 60%, 80% { transform: translateX(2px) scale(1.05); }
    }
    */

    .skin-black-light .treeview-menu > li.active > a,
    .skin-black-light .treeview-menu > li > a:hover {
        color: var(--text-secondary-color) !important;
    }

    /* Animation Option 1: Fast & Smooth (Current) */
    .sidebar-menu .treeview-menu {
        display: none;
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-10px);
        transition: max-height 0.25s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s ease, transform 0.25s ease;
    }

    .sidebar-menu li.active .treeview-menu,
    .sidebar-menu li.menu-open .treeview-menu {
        display: block;
        max-height: 500px; /* Adjust based on content */
        opacity: 1;
        transform: translateY(0);
    }

    .sidebar-collapse .sidebar-menu li:hover .treeview-menu {
        display: block;
        max-height: 500px;
        opacity: 1;
        transform: translateY(0);
    }

    /* Animation Option 2: Ultra Fast (0.15s) - Uncomment to use */
    /*
    .sidebar-menu .treeview-menu {
        display: none;
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-5px);
        transition: max-height 0.15s ease-out, opacity 0.15s ease-out, transform 0.15s ease-out;
    }

    .sidebar-menu li.active .treeview-menu,
    .sidebar-menu li.menu-open .treeview-menu {
        display: block;
        max-height: 500px;
        opacity: 1;
        transform: translateY(0);
    }

    .sidebar-collapse .sidebar-menu li:hover .treeview-menu {
        display: block;
        max-height: 500px;
        opacity: 1;
        transform: translateY(0);
    }
    */

    /* Animation Option 3: Gentle & Slow (0.4s) - Uncomment to use */
    /*
    .sidebar-menu .treeview-menu {
        display: none;
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-15px) scale(0.95);
        transition: max-height 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.4s ease, transform 0.4s ease;
    }

    .sidebar-menu li.active .treeview-menu,
    .sidebar-menu li.menu-open .treeview-menu {
        display: block;
        max-height: 500px;
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    .sidebar-collapse .sidebar-menu li:hover .treeview-menu {
        display: block;
        max-height: 500px;
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    */

    /* Animation Option 4: Bounce Effect - Uncomment to use */
    /*
    .sidebar-menu .treeview-menu {
        display: none;
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-20px);
        transition: max-height 0.3s ease, opacity 0.3s ease, transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .sidebar-menu li.active .treeview-menu,
    .sidebar-menu li.menu-open .treeview-menu {
        display: block;
        max-height: 500px;
        opacity: 1;
        transform: translateY(0);
    }

    .sidebar-collapse .sidebar-menu li:hover .treeview-menu {
        display: block;
        max-height: 500px;
        opacity: 1;
        transform: translateY(0);
    }
    */

    /* Animation Option 5: Fade Only (No Slide) - Uncomment to use */
    /*
    .sidebar-menu .treeview-menu {
        display: none;
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transition: max-height 0.3s ease, opacity 0.3s ease;
    }

    .sidebar-menu li.active .treeview-menu,
    .sidebar-menu li.menu-open .treeview-menu {
        display: block;
        max-height: 500px;
        opacity: 1;
    }

    .sidebar-collapse .sidebar-menu li:hover .treeview-menu {
        display: block;
        max-height: 500px;
        opacity: 1;
    }
    */

    .sidebar-menu > li > .treeview-menu {
        background: var(--gradient-primary) !important;
    }

    .table.table-hover tbody tr:hover {
        color: var(--text-secondary-color) !important;
        background-color: var(--primary-hover-alpha) !important;
    }


    .input-group .input-group-addon {
        color: var(--text-secondary-color) !important;
        background-color: var(--second-alpha) !important;
    }

    .rtl .input-group .form-control {
        float: right;
    }

    .box-footer {
        border-top: 2px solid var(--second-alpha) !important;
    }

    .btn-info {
        background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-color) 100%) !important;
        color: var(--text-secondary-color) !important;
        border-color: var(--secondary-color);
    }

    .btn-primary {
        background: var(--primary-color) !important;
        color: var(--text-secondary-color) !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 10px 20px !important;
        font-weight: 500 !important;
        transition: var(--transition) !important;
        box-shadow: var(--shadow-sm) !important;
    }

    .btn-dropbox,
    .btn-info,
    .btn-twitter,
    .btn-instagram,
    .btn-default,
    .btn-success,
    .btn-danger {
        color: var(--text-secondary-color) !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 10px 20px !important;
        font-weight: 500 !important;
        transition: var(--transition) !important;
        box-shadow: var(--shadow-sm) !important;
    }

    .btn-action {
        background: var(--primary-color) !important;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: var(--text-secondary-color) !important;
        border-radius: 6px;
        padding: 6px 12px;
        transition: all 0.3s ease;
    }

    .btn:hover,
    .btn-success:hover,
    .button:hover {
        transform: translateY(-1px) !important;
        box-shadow: var(--shadow-md) !important;
        color: black !important;
        border-color: var(--secondary-color);
    }

    .btn-default:hover {
        color: black !important;
    }

    .btn-dropbox,
    .btn-info,
    .btn-twitter,
    .btn-instagram,
    .btn-default,
    .btn-danger {
        background: var(--primary-color) !important;
    }

    .btn-danger {
        background: red !important;
    }

    .btn-success {
        background: green !important;
    }

    /*.btn-success {*/
    /*    background: var(--success-button) !important;*/
    /*    color: #ffffff !important;*/
    /*    border: none !important;*/
    /*    border-radius: 8px !important;*/
    /*    padding: 10px 20px !important;*/
    /*    font-weight: 500 !important;*/
    /*    transition: var(--transition) !important;*/
    /*    box-shadow: var(--shadow-sm) !important;*/
    /*}*/

    .bootstrap-switch.bootstrap-switch-on .bootstrap-switch-handle-on {
        background-color: var(--primary-color) !important;
        color: var(--text-secondary-color);
    }

    .rtl .box-header > .fa,
    .rtl .box-header > .glyphicon,
    .rtl .box-header > .ion,
    .rtl .box-header .box-title {
        float: right !important;
    }

    .btn-dropbox,
    .btn-instagram,
    .btn-twitter {
        background-color: var(--primary-color) !important;
        color: var(--text-secondary-color) !important;
    }

    .content-header > .breadcrumb > li > a {
        color: black !important;
    }

    .skin-black-light .main-header > .navbar > .sidebar-toggle {
        background: transparent !important;
        border: none !important;
        color: #374151 !important;
        padding: 8px 12px !important;
        border-radius: 6px !important;
        transition: var(--transition) !important;
        margin-right: 16px !important;
    }

    .navbar-nav {
        margin: 0 !important;
        margin-left: auto !important;
    }

    .skin-black-light .main-header > .navbar .nav > li > a,
    .skin-black-light .main-header > .navbar .nav > li > a:active,
    .skin-black-light .main-header > .navbar .nav > li > a:focus,
    .skin-black-light .main-header > .navbar .nav .open > a,
    .skin-black-light .main-header > .navbar .nav .open > a:hover,
    .skin-black-light .main-header > .navbar .nav .open > a:focus,
    .skin-black-light .main-header > .navbar .nav > .active > a {
        background-color: var(--second-color) !important;
        /*border-left: 1px solid var(--second-alpha) !important;*/
    }

    .skin-black-light .main-header>.navbar .navbar-custom-menu .navbar-nav>li>a,
    .skin-black-light .main-header>.navbar .navbar-right>li>a {
        border-left: unset !important;
    }

    .skin-black-light .main-header > .logo {
        color: #374151 !important;
        border: none !important;
        padding: 0 24px !important;
        display: flex !important;
        align-items: center !important;
        font-weight: 600 !important;
        font-size: 1.25rem !important;
        box-shadow: var(--shadow-sm) !important;
    }

    .cardHome {
        color: var(--inverse-box-color) !important;
        background-color: var(--secondary-color) !important;
    }

    .small-box {
        background-color: var(--secondary-color);
    }

    .bootstrap-switch .bootstrap-switch-handle-off.bootstrap-switch-primary, .bootstrap-switch .bootstrap-switch-handle-on.bootstrap-switch-primary {
        background: green !important;
    }

    .bootstrap-switch .bootstrap-switch-handle-off.bootstrap-switch-success, .bootstrap-switch .bootstrap-switch-handle-on.bootstrap-switch-success {
        color: #fff;
        background: var(--primary-color) !important;
    }

    .bootstrap-switch .bootstrap-switch-label {
        text-align: center;
        margin-top: -1px;
        margin-bottom: -1px;
        z-index: 100;
        color: #333;
        background: var(--second-alpha) !important;
    }

    * {
        scrollbar-color: var(--scroll-first-color) var(--scroll-second-color);
        scrollbar-width: thin;
    }


    .box-header.with-border {
        border-bottom: 1px solid var(--second-alpha) !important;
    }

    .table {
        background: #ffffff !important;
        border-radius: var(--border-radius) !important;
        box-shadow: var(--shadow-sm) !important;
    }

    .table > thead > tr > th {
        background: #f9fafb !important;
        border-bottom: 2px solid #e5e7eb !important;
        color: #374151 !important;
        font-weight: 600 !important;
        /*padding: 12px 16px !important;*/
        text-transform: uppercase !important;
        /* font-size: 0.875rem !important; */ /* يافنان اوعي تفعل دي بتأثر علي كل الداش بورد     ***** امضاء  شامي***** */

        letter-spacing: 0.05em !important;
    }

    .table > tbody > tr {
        transition: var(--transition) !important;
    }

    .table > tbody > tr:hover {
        background: #f9fafb !important;
    }

    .table > tbody > tr > td {
        border-top: 1px solid #f3f4f6 !important;
        /*padding: 12px 16px !important;*/
        color: #374151 !important;
    }


    .table-responsive {
        border: 1px solid var(--second-alpha) !important;
    }

    .nav-tabs > li {
        float: left;
    }

    .rtl .nav-tabs > li {
        float: right;
    }

    .rtl .box-body .fields-group [class*="col-md-12"] {
        float: left !important;
    }

    .rtl [class*="col-md-12"] {
        float: none !important;
    }


    .rtl [class*="col-md-6"] {
        float: right;
    }

    .skin-black-light .main-header > .navbar .sidebar-toggle:hover {
        background: #f3f4f6 !important;
        color: var(--secondary-color) !important;
        transform: scale(1.05) !important;
    }

    body.sidebar-open .main-header .sidebar-toggle:not(#mobileSelectBtn) {
        background-color: var(--primary-color) !important;
        color: #ffffff !important;
    }

    #mobileSelectBtn.active {
        background-color: var(--primary-color) !important;
        color: #ffffff !important;
    }

    #mobileSelectBtn::before,
    #mobileSelectBtn::after,
    #mobileSelectBtn span {
        display: none !important;
    }

    .select2-dropdown {
        background-color: #ffffff !important;
        border: 1px solid #d1d5db !important;
        border-radius: 8px !important;
        box-shadow: var(--shadow-lg) !important;
        margin-top: 4px !important;
        z-index: 10000 !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: rgba(37, 99, 235, 0.1) !important;
        color: #374151 !important;
    }

    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: rgba(37, 99, 235, 0.1) !important;
        color: #374151 !important;
    }

    .dark-mode .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--white) !important;
    }

    .select2-container--default .select2-selection--single {
        border: 1px solid #d1d5db !important;
        border-radius: 8px !important;
        background: #ffffff !important;
        height: 36px !important;
        line-height: 36px !important;
    }


    .input-group .input-group-addon {
        border-radius: 0;
        border-color: var(--primary-hover-alpha) !important;
        background-color: #fff;
    }

    .modal-backdrop {
        position: static !important;
    }

    .modal-content {
        position: relative;
        -webkit-background-clip: padding-box;
        background-clip: padding-box;
        border: 1px solid var(--primary-hover-alpha) !important;

        border-radius: 6px;
        outline: 0;
        -webkit-box-shadow: 0 3px 9px rgba(0, 0, 0, .5);
        box-shadow: 0 3px 9px rgba(0, 0, 0, .5);
    }

    .modal-header {
        min-height: 8%;
        padding: 15px;
        border-bottom: 1px solid var(--primary-hover-alpha) !important;
    }

    .modal-footer {
        padding: 15px;
        text-align: right;
        border-top: 1px solid var(--primary-hover-alpha) !important;
    }

    .main-footer {
        background: transparent !important;
        padding: 15px;
        color: #444;
        border-top: 1px solid var(--primary-hover-alpha) !important;
    }

    .user-type-badges {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .user-type-badges img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        transition: all 0.2s ease;
    }


    .skin-black-light .content-wrapper, .skin-black-light .main-footer {
        background-image: none !important;
    }

    .dropdown-toggle {
        background-color: var(--primary-hover-alpha) !important;
        color: var(--inverse-box-color) !important;
        border: 1px solid transparent !important;
    }

    .btn-default {
        background-color: var(--primary-hover-alpha) !important;
        color: black !important;
        border: 1px solid var(--primary-hover-alpha) !important;
    }

    .rtl .modal-footer {
        text-align: left;
    }

    .bootstrap-switch .bootstrap-switch-handle-off.bootstrap-switch-default, .bootstrap-switch .bootstrap-switch-handle-on.bootstrap-switch-default {
        background: red !important;
        color: var(--text-secondary-color) !important;
    }

    .dark-mode .h1, .dark-mode .h2, .dark-mode .h3, .dark-mode .h4, .dark-mode .h5, .dark-mode .h6,
    .dark-mode h1, .dark-mode h2, .dark-mode h3, .dark-mode h4, .dark-mode h5, .dark-mode h6 {
        color: var(--text-secondary-color);
    }

    .img-thumbnail {
        display: inline-block;
        max-width: 100%;
        height: auto;
        padding: 4px;
        line-height: 1.42857143;
        background-color: transparent !important;
        border: 1px solid var(--primary-hover-alpha) !important;
        border-radius: 4px;
        -webkit-transition: all .2s ease-in-out;
        -o-transition: all .2s ease-in-out;
        transition: all .2s ease-in-out;
    }

    /* Default for web */
    .nprogress-custom-parent {
        overflow: hidden;
        position: relative !important;
    }

    .rtl .pull-right > .dropdown-menu {
        right: auto;
        /*left: auto;*/
    }

    .popover {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 1060;
        display: none;
        max-width: 276px;
        padding: 1px;
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-size: 14px;
        font-style: normal;
        font-weight: 400;
        line-height: 1.42857143;
        text-align: left;
        text-align: start;
        text-decoration: none;
        text-shadow: none;
        text-transform: none;
        letter-spacing: normal;
        word-break: normal;
        word-spacing: normal;
        word-wrap: normal;
        white-space: normal;
        background-color: var(--box-background-color) !important;
        -webkit-background-clip: padding-box;
        background-clip: padding-box;
        border: 1px solid var(--primary-hover-alpha) !important;
        border: 1px solid rgba(0, 0, 0, .2);
        border-radius: 6px;
        -webkit-box-shadow: 0 5px 10px rgba(0, 0, 0, .2);
        box-shadow: 0 5px 10px rgba(0, 0, 0, .2);
        line-break: auto;
    }

    .popover.top > .arrow:after {
        bottom: 1px;
        margin-left: -10px;
        content: " ";
        border-top-color: var(--box-background-color) !important;
        border-bottom-width: 0;
    }

    .dropdown-menu {
        top: 100%;
        z-index: 1000;
        display: none;
        min-width: 160px;
        padding: 5px 0;
        margin: 2px 0 0;
        font-size: 14px;
        list-style: none;
        background: var(--gradient-primary) !important;
        filter: brightness(0.80);
        -webkit-background-clip: padding-box;
        background-clip: padding-box;
        border: 1px solid var(--primary-hover-alpha) !important;
        border-radius: 4px;
        -webkit-box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
        box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
        color: var(--inverse-box-color) !important;
    }

    .grid-dropdown-menu {
        position: fixed;
    }

    .table-responsive .grid-dropdown-menu {
        position: absolute;
    }

    .rtl .dropdown-menu {
        left: 7%;
    }

    .flag-image {
        height: 30px; /* adjust size */
        width: auto;
        margin-top: 10px;
        border-radius: 4px; /* optional */
    }

    /* RTL override */
    html.rtl .dropdown-menu {
        text-align: right;
        right: auto !important;
        /*left: auto;*/
        float: right;
    }

    .colorpicker.colorpicker-horizontal {
        width: 8% !important;
    }

    /* LTR override */
    html.ltr .dropdown-menu {
        text-align: left;
        left: auto;
        /* right: 38px; */
        float: left;
    }

    .navbar-nav > .notifications-menu > .dropdown-menu,
    .navbar-nav > .messages-menu > .dropdown-menu,
    .navbar-nav > .tasks-menu > .dropdown-menu {
        width: auto !important;
    }

    .slimScrollDiv {
        height: auto !important;
    }

    .navbar-nav > .notifications-menu > .dropdown-menu > li .menu,
    .navbar-nav > .messages-menu > .dropdown-menu > li .menu,
    .navbar-nav > .tasks-menu > .dropdown-menu > li .menu {
        height: auto !important;
    }

    .rtl .column-reward .rtlSvga {
        direction: ltr !important;
    }

    select > option {
        background-color: var(--white) !important;
        color: black !important;
    }

    /*.sort-select option:checked,*/
    /*.sort-select option:focus,*/
    /*.sort-select option:active {*/
    /*    background-color: var(--primary-color) !important;*/
    /*    color: white;*/
    /*}*/

    #target_type:focus option:checked {
        background: var(--primary-color) !important;
        color: white !important;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--primary-hover-alpha) !important;
        transition: .4s;
        border-radius: 34px;
    }

    .navbar-nav > .user-menu > .dropdown-menu > .user-footer {
        background-color: var(--box-background-color) !important;
        padding: 10px;
    }

    .skin-black-light .main-header li.user-header {
        background-color: var(--box-background-color) !important;
    }

    .navbar-nav > .user-menu > .dropdown-menu > li.user-header > img {
        z-index: 5;
        height: 90px;
        width: 90px;
        border: 3px solid var(--primary-hover-alpha) !important;

    }

    .navbar-nav > .user-menu > .dropdown-menu > li.user-header > p {
        z-index: 5;
        color: var(--inverse-box-color) !important;
        font-size: 17px;
        margin-top: 10px;
    }

    .inputs_cus_form {
        color: var(--text-secondary-color) !important;
        background-color: var(--box-background-color) !important;
    }

    .dd-handle {
        display: block;
        margin: 1px 0;
        padding: 8px 10px;
        color: var(--text-secondary-color) !important;
        text-decoration: none;
        border: 1px solid #ddd;
        background: var(--box-background-color) !important;
    }

    .dropdown-menu > li > a {
        color: var(--text-secondary-color) !important;
    }

    .ltr .pull-role {
        width: 70px;
        position: relative;
        font-size: 10px;

        position: relative;
        right: -91px;
        top: 6px;
    }

    .rtl {
        direction: rtl;
        text-align: right;
    }

    .rtl .sidebar-menu {
        text-align: right;
    }

    .rtl .main-sidebar {
        right: -var(--sidebar-width);
        left: auto;
        transition: right 0.3s ease;
        border-radius: 20px 0 0 20px !important;
        box-shadow: 0 0 40px rgba(0, 0, 0, 0.1) !important;
    }

    .rtl.sidebar-open .main-sidebar {
        right: 0;
    }

    .rtl .content-wrapper,
    .rtl .main-footer {
        margin-left: 0;
        margin-right: 17.5%;
    }

    .rtl .treeview-menu {
        padding-right: 10px;
    }

    .rtl .fa-angle-left {
        direction: rtl;
        left: 0px;
    }

    .box-header {
        padding: 30px;
        color: #444;
        display: block;
        position: relative;

    }

    .rtl .breadcrumb {
        left: 10px !important;
        right: auto !important;
        direction: rtl;
        display: flex;
        justify-content: flex-start;
    }

    /* .rtl .content-header {
        display: flex ;
         height: 56px;
    }
    .rtl .content-header h1{
        left: 0px;
        position: absolute;
    } */
    .rtl .sidebar-toggle {
        direction: rtl !important;
        float: right !important;
    }

    .rtl .navbar-custom-menu {
        float: right !important;

    }

    .rtl .main-header .logo {
        float: right !important;
    }

    .main-header .logo {
        height: auto;
    }

    .rtl .navbar-static-top {
        margin-left: 0 !important;
        float: left;
        width: 81.5%;
    }

    .main-header .logo {
        width: 17.3% !important;
    }

    .rtl .main-header .logo {
        width: 17.4% !important;
    }

    .sidebar-mini.sidebar-collapse .main-header .logo {
        width: 5.3% !important;
        height: 100% !important;
    }

    .rtl .sidebar-mini.sidebar-collapse .main-header .logo {
        width: 5.4% !important;
    }

    .sidebar-mini.sidebar-collapse .main-sidebar {
        width: 6% !important;
    }

    .rtl .sidebar-mini.sidebar-collapse .content-wrapper, .sidebar-mini.sidebar-collapse .right-side, .sidebar-mini.sidebar-collapse .main-footer {
        margin-left: 0% !important;
    }

    .sidebar-mini.sidebar-collapse .content-wrapper, .sidebar-mini.sidebar-collapse .right-side, .sidebar-mini.sidebar-collapse .main-footer {
        margin-left: 5% !important;
    }

    .sidebar-mini.sidebar-collapse .main-header .navbar {
        width: 93.5% !important;
    }

    .rtl .sidebar-mini.sidebar-collapse .main-header .navbar {
        width: 93.5% !important;
    }

    .ltr .navbar-static-top {
        float: right;
        width: 81.5%;
    }

    .ltr .main-header > .navbar {
        margin-left: 0 !important;
    }

    .rtl .navbar-custom-menu > .navbar-nav > li > .dropdown-menu {
        position: absolute;
        right: 0;
        left: 0;
    }

    /*.rtl .skin-black-light .main-header > .navbar .nav > li {*/
    /*    float: right !important;*/
    /*}*/

    .rtl .skin-black-light .main-header > .navbar .nav > li > a {
        float: right !important;
    }

    /* RTL Header Layout: Select menus (dropdowns) on right, other icons on left */
    /*.rtl .skin-black-light .main-header > .navbar .nav > li.dropdown,*/
    /*.rtl .skin-black-light .main-header > .navbar .nav > li.user-menu {*/
    /*    float: right !important;*/
    /*}*/

    .rtl .skin-black-light .main-header > .navbar .nav > li:not(.dropdown):not(.user-menu) {
        float: left !important;
    }

    .rtl .skin-black-light .main-header > .navbar .nav > li:not(.dropdown):not(.user-menu) > a {
        float: left !important;
    }

    .rtl th {
        text-align: start;
    }

    .rtl .box-header .form-horizontal .row {
        display: block !important;
        direction: rtl !important;
        /*margin-bottom: 10px;*/
    }

    .ltr .form-horizontal .row {
        display: block !important;
    }

    .ltr .main-header .logo {
        float: left !important;
    }

    .rtl .form-horizontal .box-footer .btn-group {
        float: right;
    }

    .rtl .content-wrapper-rtl {
        margin-right: 5.5% !important;
    }

    .ltr .content-wrapper {
        margin-left: 17% !important;
    }

    .rtl .fields-group .form-group {

        display: flex !important;
    }

    .rtl /**.box-header**/ .box-tools {
        float: left;
        top: -8px;
        position: relative;
        left: 107px;

    }

    .rtl .wallet_posation {
        /* position: absolute; */

    }

    .rtl .wallet_div {
        width: 82%;
        margin-right: -14px !important;


    }

    .rtl .column-show_img .rtlSvga {
        direction: ltr;

    }

    .rtl .column-img2 .rtlSvga {
        direction: ltr;

    }

    .rtl .column-img .rtlSvga {
        direction: ltr;

    }

    .rtl .column-image .rtlSvga {
        direction: ltr;

    }

    .rtl .colorpicker-element .color {
        float: right !important;
    }

    .rtl .form-horizontal .control-label {
        padding-top: 7px;
        margin-bottom: 0;
        text-align: center;
    }

    .rtl .asterisk:before {
        content: none !important;
    }

    .rtl .asterisk:after {
        content: "* ";
        color: red;
    }

    .rtl .box-header .box-tools {
        float: left !important;

    }

    .rtl .box-header .pull-right {
        float: left !important;

    }

    /*.rtl .column-__actions__ .grid-dropdown-actions .dropdown-menu{*/
    /*  left: 29px !important;*/
    /*}*/
    .rtl .pull-role {
        width: 70px;
        position: relative;
        top: 7px;
        font-size: 10px;
        left: -30px;
    }

    .rtl .sidebar-menu .treeview-menu > li > a > .fa-angle-left,
    .rtl .sidebar-menu .treeview-menu > li > a > .fa-angle-down {
        transform: rotate(180deg);
        text-align: left;
        top: 13px;
        right: 190px;
    }

    .rtl .sidebar-menu .treeview.active > a > .fa-angle-left,
        /*.rtl .sidebar-menu .treeview.menu-open > a > .fa-angle-left,*/
    .rtl .sidebar-menu .treeview.active > a > .fa-angle-down,
    .rtl .sidebar-menu .treeview.menu-open > a > .fa-angle-down,
    .rtl .sidebar-menu .treeview-menu > li.active > a > .fa-angle-left,
    .rtl .sidebar-menu .treeview-menu > li.active > a > .fa-angle-down {
        transform: rotate(-90deg) !important;
    }

    .navbar.navbar-static-top .fa {
        color: var(--primary-color);
    }

    .navbar.navbar-static-top .slimScrollDiv .menu .fa {
        color: var(--text-secondary-color) !important;
    }

    .tab-buttons {
        display: flex;
        width: 100%;
        margin-bottom: 20px;
        gap: 10px;
    }

    .tab-button {
        background-color: var(--secondary-color);
        flex: 1;
        padding: 5px;
        text-align: center;
        font-size: 18px;
        color: white;
        text-decoration: none;
        transition: background-color 0.3s;
    }

    button {
        background: var(--primary-color);
        color: var(--text-secondary-color);
        padding: 10px;
        border: none;
        cursor: pointer;
        font-weight: bold;
    }

    .settings-menu button.active {
        background: gray !important;
        color: var(--text-secondary-color) !important;
    }

    .btn-warning {
        background-color: var(--primary-color);
        color: var(--text-secondary-color);
        border-color: var(--secondary-color);
        border: none !important;
        border-radius: 8px !important;
        padding: 10px 20px !important;
        font-weight: 500 !important;
        transition: var(--transition) !important;
        box-shadow: var(--shadow-sm) !important;
    }

    /*    tr[data-key="18"] {
            background-color: var(--secondary-color) !important;
            filter: brightness(2);
        }*/

    .tab-button.active {
        background-color: var(--primary-color);
        border: 2px solid #fff;
        opacity: 1;
    }


    .colorpicker.dropdown-menu.colorpicker-visible {
        top: 282.8px;
        right: 500.475px;
        position: absolute;
    }

    /*.small-input {*/
    /*    width: 80px; !* Adjust width as needed *!*/
    /*    padding: 5px;*/
    /*    text-align: center;*/
    /*    border: 1px solid #ccc;*/
    /*    border-radius: 4px;*/
    /*    background-color: #f9f9f9;*/
    /*    margin-bottom: 10px;*/
    /*}*/

    /*.amount-text {*/
    /*    display: block;*/
    /*    margin-top: 5px; !* Adjust spacing as needed *!*/
    /*    font-size: 14px; !* Adjust font size as needed *!*/
    /*    color: #666;*/
    /*    text-align: center;*/
    /*}*/

    .rtl .sidebar-menu > li > a .fa-angle-left {
        transform: rotate(180deg);
    }

    .rtl .sidebar-menu > li.active > a .fa-angle-left {
        transform: rotate(-90deg);
        top: 23px;
        right: 207px;
    }

    .box-footer {
        flex-direction: row-reverse;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 0 !important;
        background: transparent !important;
    }

    .pagination-info {
        margin: 5px 0;
        white-space: nowrap;
        text-align: right;
        width: auto;
        order: 2;
    }

    /* [lang="en"] .form-horizontal .form-group {
     margin-left: -15px;
     margin-right: -1500px;

 }
 /* .col-sm-8 {
     width: 1000px;
 } */

    /* html[dir="ltr"] .col-sm-8 {
        width: 1000px !important;
    } */


    /* [lang="en"] .col-sm-8 {
        width: 1000px !important;
    } */


    .box-footer .pull-right {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        /*margin: 5px 0;*/
        order: 1;
    }

    .box-footer .pull-right .dropdown {
        margin-left: 5px;
    }

    .pagination > li > a,
    .pagination > li > span {
        min-width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 5px;
    }

    .pagination {
        margin: 0;
        padding: 0;
        display: flex;
    }

    .rtl label.control-label.pull-right small:last-of-type {
        margin-left: 50px;
    }

    .small-box h3 {
        font-size: x-large !important;
    }

    .small-box:hover .icon {
        font-size: 80px;
        transform: translateY(-37px);
        transition: all 0.3s ease;
    }

    .small-box .icon {
        font-size: 50px;
        top: 25px;
        right: 2px;
    }

    .preview-superadmin-btn,
    .exit-preview-btn {
        border: none;
        border-radius: 4px;
        padding: 4px 14px;
        font-size: 14px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    #go-superadmin i {
        font-size: 15px;
    }

    .payment-card {
        height: 580px;
    }

    html {
        overflow-x: auto !important;
    }

    body {
        overflow-x: auto !important;
    }

    @media (max-width: 767px) {
        .ltr .content-wrapper {
            margin-left: 0 !important;
        }

        .ltr .main-header .logo {

            display: none !important;
        }

        .rtl .main-header .logo {
            display: none !important;
        }

        .rtl .navbar-static-top {
            margin-left: 1% !important;
            float: left;
            width: 99%;
        }

        .ltr .navbar-static-top {
            margin-left: 1% !important;
            float: left;
            width: 99%;
        }

        #app {
            margin-top: 19% !important;
        }

        .sidebar-open .content-wrapper {
            margin-left: 0 !important;
            padding-left: 24px !important;
        }

        .sidebar-open .skin-black-light .main-header > .navbar {
            margin-left: 0 !important;
        }

        .skin-black-light .main-sidebar {
            width: 100% !important;
            left: -100% !important;
            border-radius: 0 !important;
        }

        .sidebar-open .main-sidebar {
            left: 0 !important;

        }

        .rtl .navbar-custom-menu {
            position: absolute;
            left: 0px;
        }

        .ltr .navbar-custom-menu {
            position: absolute;
            right: 0px;
        }
    }

    .navbar.navbar-static-top {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1030;
        transition: transform 0.3s ease-in-out;
    }

    .navbar.navbar-static-top.navbar-hidden {
        transform: translateY(-100%);
    }

    /* Fix header blocking */
    header.main-header {
        pointer-events: none !important;
    }

    /* But enable clicks on actual navbar content */
    header.main-header .navbar,
    header.main-header .nav,
    header.main-header .nav-pills,
    header.main-header a,
    header.main-header button,
    header.main-header .logo {
        pointer-events: auto !important;
    }

    /* If navbar is hidden */
    .navbar-hidden {
        pointer-events: none !important;
        visibility: hidden !important;
    }

    /* Make sure tabs are clickable */
    .nav-pills,
    .nav-pills li,
    .nav-pills a,
    .charge_action {
        pointer-events: auto !important;
        position: relative;
        z-index: 100;
    }

    /* form inputs */
    .form-horizontal .fields-group > .col-md-12 {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 20px !important;
        width: 100% !important;
        padding: 15px !important;
    }

    .rtl .form-horizontal .fields-group > .col-md-12 {
        direction: rtl !important;
    }

    .form-horizontal .fields-group > .col-md-12 > .form-group {
        margin: 0 !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .form-horizontal .fields-group > .col-md-12 > input[type="hidden"] {
        display: none !important;
    }

    .form-horizontal .fields-group > .col-md-12 > .form-group .control-label {
        width: 100% !important;
        text-align: left !important;
        margin-bottom: 8px !important;
        padding: 0 5px !important;
        font-weight: 600;
        order: 1;
    }

    .rtl .form-horizontal .fields-group > .col-md-12 > .form-group .control-label {
        text-align: right !important;
    }

    .form-horizontal .fields-group > .col-md-12 > .form-group .col-sm-2,
    .form-horizontal .fields-group > .col-md-12 > .form-group .col-sm-8,
    .form-horizontal .fields-group > .col-md-12 > .form-group [class*="col-sm-"] {
        width: 100% !important;
        float: none !important;
        padding: 0 5px !important;
        order: 2;
    }

    .form-horizontal .fields-group > .col-md-12 > .form-group .input-group-addon {
        display: none !important;
    }

    .form-horizontal .fields-group > .col-md-12 > .form-group .input-group {
        display: table !important;
        width: 100% !important;
    }

    .form-horizontal .fields-group > .col-md-12 > .form-group .input-group .form-control,
    .form-horizontal .fields-group > .col-md-12 > .form-group .form-control {
        border-radius: 8px !important;
        width: 100% !important;
    }

    .form-horizontal .box-footer {
        width: 100%;
        clear: both;
    }

    .file-input .input-group.file-caption-main {
        position: relative !important;
        display: block !important;
        direction: ltr !important;
    }

    .rtl .file-input .input-group.file-caption-main .file-caption {
        direction: rtl !important;
    }

    .file-input .input-group.file-caption-main .input-group-btn {
        position: relative !important;
    }

    .rtl .file-input .input-group.file-caption-main .input-group-btn {
        position: absolute !important;
    }

    .file-input .input-group.file-caption-main .btn-file {
        padding: 5px 15px !important;
        border-radius: 6px !important;
        font-size: 12px !important;
        margin-left: -105% !important;
    }

    .rtl .file-input .input-group.file-caption-main .btn-file {
        margin-top: 65% !important;
        margin-left: 100% !important;
    }

    .form-control:focus {
        border-color: var(--secondary-color) !important;
    }

    .form-horizontal + .box-footer,
    .form-horizontal .box-footer {
        display: grid !important;
        direction: rtl !important;
    }

    .rtl .form-horizontal + .box-footer,
    .rtl .form-horizontal .box-footer {
        direction: ltr !important;
    }

    .form-horizontal + .box-footer .col-md-8,
    .form-horizontal .box-footer .col-md-8 {
        /*float: none !important;*/
        width: auto !important;
        display: flex !important;
        align-items: center !important;
        gap: 16px !important;
        margin-right: auto !important;
    }

    .form-horizontal + .box-footer .btn-group.pull-right,
    .form-horizontal .box-footer .btn-group.pull-right {
        order: -2 !important;
        float: none !important;
    }

    .form-horizontal + .box-footer .btn-group.pull-left,
    .form-horizontal .box-footer .btn-group.pull-left {
        order: -1 !important;
        float: none !important;
    }

    .form-horizontal + .box-footer .pull-right:not(.btn-group),
    .form-horizontal .box-footer .pull-right:not(.btn-group) {
        float: none !important;
    }

    .form-horizontal + .box-footer .checkbox,
    .form-horizontal .box-footer .checkbox {
        margin: 0 !important;
    }

    .form-horizontal .fields-group > .col-md-12 > .form-group:has(.full-column-width) {
        grid-column: 1 / -1 !important;
    }

    @media (max-width: 768px) {
        .form-horizontal .fields-group > .col-md-12 {
            grid-template-columns: 1fr !important;
        }
    }

    .iti--separate-dial-code .iti__selected-flag {
        background-color: rgba(0, 0, 0, 0.02) !important;
    }

    .grid-table td .dropdown,
    .grid-table td .dropup,
    .table td .dropdown,
    .table td .dropup {
        position: relative;
    }


    /* ==========================================
   MODERN FILTER DESIGN - LARAVEL ADMIN
   ========================================== */

    .col-md-12 .box-body .fields-group {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .fields-group > .form-group {
        display: flex;
        align-items: center;
        /*gap: 35px;*/
        margin-bottom: 0;
    }

    .fields-group .col-sm-2.control-label {
        font-weight: 500;
        font-size: 14px;
        text-align: right;
        margin-bottom: 0;
    }

    .fields-group .col-sm-8 {
        flex: 1;
    }

    .fields-group .input-group.input-group-sm {
        background: white;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        overflow: hidden;
        position: relative;
        z-index: 1;
        transition: all 0.3s ease;
    }

    .fields-group .input-group.input-group-sm:has(.bootstrap-datetimepicker-widget) {
        position: absolute !important;
    }

    .fields-group .input-group.input-group-sm:has(.bootstrap-datetimepicker-widget) {
        position: absolute !important;
    }

    .fields-group .input-group.input-group-sm:hover {
        border-color: rgba(255, 255, 255, 0.15);
    }

    .fields-group .input-group.input-group-sm:focus-within {
        border-color: rgba(99, 102, 241, 0.5);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .fields-group .input-group-addon {
        display: none !important;
    }

    .fields-group .input-group.input-group-sm .form-control {
        border-radius: 12px !important;
        padding: 12px 16px;
        font-size: 14px;
        height: auto;
        box-shadow: none !important;
    }

    .fields-group .form-control::placeholder {
        color: #64748b;
        opacity: 1;
    }

    .fields-group .form-control:focus {
        outline: none !important;
        box-shadow: none !important;
    }

    .modal-dialog {
        margin: 5% auto !important;
    }

    /* ==========================================
   AGENCY HEADER - RESPONSIVE DESIGN
   ========================================== */

    /* Main Header Container */
    .agency-header {
        display: flex;
        flex-direction: row;
        gap: 24px;
        padding: 24px;
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.5), rgba(15, 23, 42, 0.5));
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Avatar Section */
    .agency-avatar {
        flex-shrink: 0;
    }

    .agency-avatar .logo-img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(255, 255, 255, 0.1);
    }

    /* Info Section */
    .agency-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .agency-name {
        font-size: 24px;
        font-weight: 700;
        color: #f1f5f9;
        margin: 0;
    }

    /* Meta Items */
    .agency-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .meta-label {
        color: #94a3b8;
        font-size: 13px;
        font-weight: 500;
    }

    .meta-value {
        color: #e2e8f0;
        font-size: 13px;
        font-weight: 600;
    }

    .flag-image {
        height: 16px;
        border-radius: 2px;
        margin-right: 4px;
    }

    /* Stats Section */
    .agency-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
    }

    /* ==========================================
       BUTTONS CARD
       ========================================== */

    .card.p-3.bg-danger-subtle {
        border: 1px solid rgba(0, 0, 0, 0.08) !important;
        border-radius: 12px !important;
        padding: 20px !important;
        align-self: flex-start;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        display: flex;
        gap: 12px;
    }

    .card.p-3.bg-danger-subtle .d-flex {
        display: flex !important;
        flex-direction: row !important;
        gap: 10px !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
    }

    /* All Buttons */
    /*.card.p-3.bg-danger-subtle .btn {*/
    /*    border-radius: 10px !important;*/
    /*    padding: 10px 18px !important;*/
    /*    font-size: 13px !important;*/
    /*    font-weight: 500 !important;*/
    /*    transition: all 0.3s ease !important;*/
    /*    white-space: nowrap !important;*/
    /*}*/

    /*!* Back Button *!*/
    /*.card.p-3.bg-danger-subtle .btn-light {*/
    /*    background: rgba(255, 255, 255, 0.08) !important;*/
    /*    color: #e2e8f0 !important;*/
    /*    border: 1px solid rgba(255, 255, 255, 0.1) !important;*/
    /*}*/

    /*.card.p-3.bg-danger-subtle .btn-light:hover {*/
    /*    background: rgba(255, 255, 255, 0.15) !important;*/
    /*}*/

    /*!* Edit Button *!*/
    /*.card.p-3.bg-danger-subtle .btn-success {*/
    /*    background: var(--gradient-primary) !important;*/
    /*    color: #fff !important;*/
    /*    border: none !important;*/
    /*    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3) !important;*/
    /*}*/

    /*.card.p-3.bg-danger-subtle .btn-success:hover {*/
    /*    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%) !important;*/
    /*    transform: translateY(-2px) !important;*/
    /*}*/

    /*!* Remove BD Button *!*/
    /*.card.p-3.bg-danger-subtle .btn-danger {*/
    /*    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;*/
    /*    color: #fff !important;*/
    /*    border: none !important;*/
    /*    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;*/
    /*}*/

    /*.card.p-3.bg-danger-subtle .btn-danger:hover {*/
    /*    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;*/
    /*    transform: translateY(-2px) !important;*/
    /*}*/

    /* ==========================================
       TABLET RESPONSIVE
       ========================================== */

    @media (max-width: 992px) {
        .agency-header {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .agency-info {
            align-items: center;
        }

        .agency-meta {
            justify-content: center;
        }

        .agency-stats {
            justify-content: center;
        }

        .card.p-3.bg-danger-subtle {
            width: 100%;
            align-self: stretch;
        }
    }

    /* ==========================================
       MOBILE RESPONSIVE
       ========================================== */

    @media (max-width: 576px) {
        .agency-header {
            padding: 16px;
            gap: 16px;
        }

        .agency-avatar .logo-img {
            width: 80px;
            height: 80px;
        }

        .agency-name {
            font-size: 18px;
        }

        .meta-label,
        .meta-value {
            font-size: 11px;
        }

        .agency-meta {
            flex-direction: column;
            gap: 8px;
        }

        .meta-item {
            justify-content: center;
        }

        .agency-stats {
            flex-direction: column;
            gap: 8px;
        }

        /* BUTTONS ON MOBILE */
        .card.p-3.bg-danger-subtle {
            padding: 12px !important;
        }

        .card.p-3.bg-danger-subtle .d-flex {
            flex-direction: column !important;
            gap: 8px !important;
        }

        .card.p-3.bg-danger-subtle .btn {
            width: 100% !important;
            padding: 12px 16px !important;
            font-size: 13px !important;
            text-align: center !important;
        }
    }

    /* ==========================================
       EXTRA SMALL SCREENS
       ========================================== */

    @media (max-width: 380px) {
        .agency-header {
            padding: 12px;
        }

        .agency-avatar .logo-img {
            width: 60px;
            height: 60px;
        }

        .agency-name {
            font-size: 16px;
        }

        .meta-label,
        .meta-value {
            font-size: 10px;
        }

        .card.p-3.bg-danger-subtle .btn {
            padding: 10px 12px !important;
            font-size: 12px !important;
        }
    }

    .content-header > .breadcrumb > li > a,
    .content-header > h1,
    .label,
    a {
        color: #000000;
    }

    .navbar-nav > .messages-menu > .dropdown-menu > li .menu > li > a {
        color: var(--text-secondary-color) !important;
    }

    ::placeholder {
        color: #3f3f3f !important;
    }

    .close:focus, .close:hover {
        color: var(--text-secondary-color);
    }

    .agency-header {
        display: flex;
        align-items: flex-start;
        gap: 25px;
        margin-bottom: 30px;
        position: relative;
        padding: 20px;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        border-top: 3px solid var(--primary-color);
        transition: all 0.3s ease;
    }

    .card-header {
        padding: 15px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--off-white);
        border-bottom: 2px solid #e5e7eb;
        border-radius: 12px 12px 0 0;
    }

    .pagination-wrapper {
        padding: 15px 20px;
        display: flex;
        justify-content: center;
        border-top: 1px solid #eee;
    }

    .table tbody tr:nth-child(even) {
        background-color: var(--off-white) !important;
    }

    .stat-card {
        padding: 15px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8f9fa;
        border-bottom: 2px solid #e5e7eb;
        border-radius: 12px 12px 0 0;
    }

    .stat-value {
        font-size: 20px;
        font-weight: 700;
        line-height: 1;
    }

    .performers-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.06);
        transition: all 0.3s ease;
    }

    .performers-card:hover {
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid black;
    }

    .section-badge {
        background: var(--primary-color);
        color: white;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th {
        padding: 12px 15px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }

    .count-badge {
        background: var(--primary-color);
        color: #7f8c8d;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .rtl .btn-back {
        position: absolute;
        top: 5px;
        left: 20px;
        border: none;
        padding: 8px 15px;
        border-radius: 6px;
        color: #7f8c8d;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .btn-back:hover {
        background: #d6e0e3;
        color: #34495e;
    }

    .section-box {
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.06);
        transition: all 0.3s ease;
    }

    .section-box:hover {
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    }

    .table-section {
        width: 100%;
        border-collapse: collapse;
    }

    .nav-pills>li.active>a, .nav-pills>li.active>a:focus, .nav-pills>li.active>a:hover {
        background: var(--primary-color) !important;
    }

    .nav-pills>li.active>a, .nav-pills>li.active>a:hover, .nav-pills>li.active>a:focus {
        border-top-color: var(--secondary-color) !important;
    }

    .data-table tr:hover {
        background: var(--off-white);
    }

    /* Logo Fixes */
    .main-header .logo {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 5px 15px !important;
        height: var(--header-height, 70px) !important;
        overflow: hidden !important;
    }

    .sidebar-collapse .main-header .logo .logo-icon {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: auto !important;
        height: 100% !important;

        border-radius: 0 !important;
        overflow: unset !important;
        border: unset !important;
        margin-top: 0 !important;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .main-header .logo .logo-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        overflow: hidden;
        border: 4px solid #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .sidebar-collapse .main-header .logo .logo-icon img {
        max-height: 70px !important;
        width: auto !important;
        height: auto !important;
        object-fit: contain !important;
        border-radius: 8px !important;
        border: unset !important;
    }

    .main-header .logo .logo-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid rgba(255, 255, 255, 0.1);
    }

    .main-header .logo .logo-icon img.circular-logo {
        width: 45px !important;
        height: 45px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
    }

    .rtl .main-header .logo .logo-icon {
        margin-right: 0;
        margin-left: 8px;
    }

    .main-sidebar, .left-side {
        padding-top: 5% !important;
    }

    .dark-mode .phpdebugbar-settings {
        background: white !important;
    }

    .phpdebugbar[data-theme="dark"] .phpdebugbar-settings {
        background: none !important;
    }

    .bootstrap-datetimepicker-widget {
        display: contents !important;
    }

    .datepicker table tr td.active, .datepicker table tr td.active:hover, .datepicker table tr td.active.disabled, .datepicker table tr td.active.disabled:hover {
        background: var(--primary-color) !important;
    }

    .datepicker table tr td span.active, .datepicker table tr td span.active:hover, .datepicker table tr td span.active.disabled, .datepicker table tr td span.active.disabled:hover {
        background: var(--primary-color) !important;
    }

    .dropdown-menu.show,
    .dropdown-menu[style*="display: block"] {
        position: fixed !important;
        z-index: 99999 !important;
    }

    .input-group:has(input.sort) .input-group-btn > .btn,
    .input-group:has(input.sort_num) .input-group-btn > .btn,
    .input-group:has(input.expire) .input-group-btn > .btn,
    .input-group:has(input.price) .input-group-btn > .btn,
    .input-group:has(input.level) .input-group-btn > .btn,
    .input-group:has(input.num) .input-group-btn > .btn,
    .input-group:has(input.exp) .input-group-btn > .btn,
    .input-group:has(input.t_length) .input-group-btn > .btn,
    .input-group:has(input.priority) .input-group-btn > .btn {
        height: 34px;
        padding: 0 10px !important;
    }

    .navbar-nav>.notifications-menu>.dropdown-menu>li .menu>li>a:hover, .navbar-nav>.messages-menu>.dropdown-menu>li .menu>li>a:hover, .navbar-nav>.tasks-menu>.dropdown-menu>li .menu>li>a:hover {
        background: var(--primary-color);
    }

    .transferModal{
        display: none;
        position: fixed;
        top: 25%;
        left: 50%;
        transform: translate(-50%, -20%);
        background: white;
        border-radius: 36px;
        z-index: 9999;
        width: 520px;
        overflow: hidden;
        height: 60%;
    }

    .dropdown-menu>li>a:focus, .dropdown-menu>li>a:hover {
        background-color: var(--primary-color);
    }

    .nav>li>a:hover, .nav>li>a:active, .nav>li>a:focus {
        background: var(--secondary-color) !important;
        color: var(--text-secondary-color);
    }

    .form-divider {
        position: relative;
        margin: 30px 0 20px;
        border-bottom: 1px solid #e5e5e5;
    }

    .form-divider span {
        position: absolute;
        top: -10px;
        left: 50%;
        background: #fff;
        padding: 0 10px;
        font-weight: 600;
        font-size: 14px;
        color: #555;
    }

    .ltr .box-tools .btn-group.pull-right {
        margin-left: 5px;
        margin-right: 0 !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: var(--primary-color) !important;
    }

    /* Label on input border */
    .box-header .form-group,
    .filter-box .form-group {
        position: relative !important;
        margin-top: 12px !important;
    }

    .box-header .form-group .control-label,
    .box-header .form-group > label,
    .filter-box .form-group .control-label,
    .filter-box .form-group > label {
        position: absolute !important;
        top: -35% !important;
        left: 40px !important;
        background: white !important;
        padding: 0 6px !important;
        font-size: 12px !important;
        z-index: 10 !important;
        margin: 0 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* RTL */
    .rtl .box-header .form-group .control-label,
    .rtl .filter-box .form-group .control-label {
        left: auto !important;
        right: 25px !important;
    }

    .box-header .form-group .form-control,
    .filter-box .form-group .form-control,
    .box-header .form-group .select2-container,
    .filter-box .form-group .select2-container {
        min-width: 280px !important;
        width: 100% !important;
    }

    .box-header .form-group {
        width: 45% !important;
        display: inline-block !important;
        margin-left: 5% !important;
    }

    .rtl .box-header .form-group {
        margin-right: 5% !important;
    }

    /* Responsive fixes for smaller screens */
    @media (max-width: 768px) {
        .box-header .form-group {
            display: block !important;
            margin-right: 0 !important;
            margin-bottom: 20px !important;
        }

        .box-header .form-group .control-label,
        .box-header .form-group > label,
        .filter-box .form-group .control-label,
        .filter-box .form-group > label {
            left: 15px !important;
            top: -10px !important;
            font-size: 11px !important;
            max-width: calc(100% - 30px) !important;
        }

        /* RTL responsive */
        .rtl .box-header .form-group .control-label,
        .rtl .filter-box .form-group .control-label {
            left: auto !important;
            right: 15px !important;
        }

        .box-header .form-group .form-control,
        .filter-box .form-group .form-control,
        .box-header .form-group .select2-container,
        .filter-box .form-group .select2-container {
            min-width: 100% !important;
        }
    }

    @media (max-width: 480px) {
        .box-header .form-group .control-label,
        .box-header .form-group > label,
        .filter-box .form-group .control-label,
        .filter-box .form-group > label {
            font-size: 10px !important;
            top: -8px !important;
        }
    }

    .settings-sidebar {
        width: 250px;
        min-height: 400px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .settings-sidebar h2 {
        text-align: center;
        color: var(--primary-color);
    }
    .new-form {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        border: 1px solid #eaeaea;
    }

    button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    @media (max-width: 768px) {
        .table-responsive td,
        .table-responsive th {
            white-space: nowrap !important;
            word-break: normal !important;
            overflow-wrap: normal !important;
        }
    }
</style>

@include('css.dark_mode')
@include('css.select_menu_style')
