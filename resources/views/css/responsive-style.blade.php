<style>
    @media (max-width: 1400px) {
        .content {
            /* width: 1340px !important; */
        }

        .payment-card {
            height: 795px;
        }

        .wrapper {
            /* min-width: max-content; */
        }
    }

    @media (max-width: 1200px) {
        .content {
            width: 1160px !important;
        }

        .wrapper {
            /* min-width: max-content; */
        }
    }

    @media (max-width: 992px) {
        .app-class {
            margin-top: 10% !important;
        }

        html.ltr .dropdown-menu {
            right: 10%;
        }

        .content {
            width: 100% !important;
        }

        .wrapper {
            /* min-width: max-content; */
        }

        .rtl .navbar-custom-menu > .navbar-nav > li > .dropdown-menu {
            left: 0 !important;
            width: 32% !important;
        }

        .navbar-custom-menu > .navbar-nav > li {
            position: relative !important;
        }
    }

    @media (max-width: 768px) {
        .rtl .dropdown-menu {
            left: 12%;
        }

        html.ltr .dropdown-menu {
            right: 14%;
        }

        .main-sidebar, .left-side {
            padding-top: 20% !important;
        }

        .content {
            width: 100% !important;
        }

        .main-sidebar, .left-side {
            padding-top: 10% !important;
        }

        .rtl .box-body .fields-group [class*="col-md-12"] {
            float: none;
        !important;
        }

        .wrapper {
            /* min-width: max-content; */
        }

        .form-horizontal .fields-group > .col-md-12 {
            grid-template-columns: 1fr !important;
        }

        .box-footer .pull-right label,
        .box-footer label.pull-right {
            display: none !important;
        }

        .nprogress-custom-parent {
            position: absolute !important;
        }

        .grid-table td .dropdown,
        .grid-table td .dropup,
        .table td .dropdown,
        .table td .dropup {
            position: relative !important;
        }

        .rtl .main-sidebar {
            right: 0 !important;
            left: auto !important;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        }

        .active_hide {
            transform: translateX(1%) !important;
        }

        .rtl .main-sidebar.active {
            transform: translateX(0);
        }

        .rtl .sidebar-toggle {
            float: right !important;
            margin-right: 10px;
        }

        .rtl .navbar-custom-menu {
            float: left !important;
        }

        .rtl .navbar-custom-menu > .navbar-nav > li > .dropdown-menu {
            left: 0 !important;
            width: 32% !important;
        }

        .rtl .content-wrapper,
        .rtl .main-footer {
            margin-left: 0;
            margin-right: 0;
        }

        .rtl .content-wrapper-rtl {
            transition: margin-left 0.3s ease-in-out, width 0.3s ease-in-out;
            margin-right: 444px !important;
            width: calc(100% - 0px);
        }

        .rtl.sidebar-open .content-wrapper-rtl {
            margin-right: 444px !important;
            width: calc(100% - 444px);
        }

        /*select header menu*/
        .rtl .select-country-rtl {
            transition: margin-right 0.3s ease-in-out, width 0.3s ease-in-out;
        }

        .rtl .select-country-rtl {
            margin-right: 230px !important;
            top: -20px;
            position: relative;
        }

        .ltr .select-country-rtl {
            transition: margin-left 0.3s ease-in-out, width 0.3s ease-in-out;
        }

        .ltr .select-country-rtl {
            margin-left: 230px !important;
            top: -20px;
            position: relative;
        }

        .sidebar-open .content-header {
            padding: 30px 20px !important;
            margin: 35px 15px !important;
        }

        /*.sidebar-open .content-wrapper {*/
        /*    margin-right: 250px;*/
        /*}*/
        .col-md-3, .col-sm-6 {
            flex: 0 0 100%;
            width: 50%;
        }

        #area-Manager-select,
        #country-select {
            width: 150px !important;
        }

        .select2-container {
            width: 150px !important;
        }

        .mobile-select-toggle {
            position: relative;
        }

        .ltr .mobile-select-menu {
            display: none;
            position: absolute;
            left: 50%;
            top: 55px;
            background: #fff;
            padding: 15px;
            width: 220px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 9999;
        }

        .ltr .mobile-select-menu.show {
            display: block;
        }

        .rtl .mobile-select-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 55px;
            background: #fff;
            padding: 15px;
            width: 220px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 9999;
        }

        .rtl .mobile-select-menu.show {
            display: block;
        }

        .rtl #mobileSelectBtn {
            width: 42px;
            height: 42px;
            display: flex !important;;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
            right: 200%;
            position: relative;
        }

        .ltr #mobileSelectBtn {
            width: 42px;
            height: 42px;
            display: flex !important;;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
            left: 200%;
            position: relative;
        }
    }

    @media (max-width: 576px) {
        .main-sidebar, .left-side {
            padding-top: 20% !important;
        }

        html.ltr .dropdown-menu {
            right: 10%;
        }

        .box-footer {
            flex-direction: column;
            align-items: center;
        }

        .pagination-info,
        .box-footer .pull-right {
            width: fit-content;
            display: flex;
            justify-content: center;
            text-align: center;
        }

        .pagination-info {
            order: 1;
            margin-bottom: 10px;
        }

        .box-footer .pull-right {
            order: 2;
        }

        .wrapper {
            /* min-width: max-content; */
        }

        .grid-table td .dropdown,
        .grid-table td .dropup,
        .table td .dropdown,
        .table td .dropup {
            position: relative !important;
        }

        .rtl .navbar-custom-menu > .navbar-nav > li > .dropdown-menu {
            position: absolute;
            right: -113px;
            width: 60% !important;
        }

        .rtl .navbar-custom-menu > .navbar-nav > li > .dropdown-menu {
            position: absolute;
            left: -113px;
            width: 60% !important;
        }

        .mobile-select-toggle {
            position: relative;
        }

        .mobile-select-toggle {
            display: block !important;

        }

        .ltr .mobile-select-menu {
            display: none;
            position: absolute;
            left: 50%;
            top: 55px;
            background: #fff;
            padding: 15px;
            width: 220px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 9999;
        }

        .ltr .mobile-select-menu.show {
            display: block;
        }

        .rtl .mobile-select-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 55px;
            background: #fff;
            padding: 15px;
            width: 220px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 9999;
        }

        .rtl .mobile-select-menu.show {
            display: block;
        }

        .rtl #mobileSelectBtn {
            width: 42px;
            height: 42px;
            display: flex !important;;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
            right: 200%;
            position: relative;
        }

        .ltr #mobileSelectBtn {
            width: 42px;
            height: 42px;
            display: flex !important;;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
            left: 200%;
            position: relative;
        }
    }

    @media (max-width: 768px) {
        .select-country-wrapper {
            display: none !important;
        }

        .select-country {
            display: none !important;
        }

        .mobile-select-toggle {
            position: relative;
        }

        .ltr .mobile-select-menu {
            display: none;
            position: absolute;
            left: 50%;
            top: 55px;
            background: #fff;
            padding: 15px;
            width: 220px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 9999;
        }

        .mobile-select-toggle {
            display: block !important;

        }

        .ltr .mobile-select-menu.show {
            display: block;
        }

        .rtl .mobile-select-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 55px;
            background: #fff;
            padding: 15px;
            width: 220px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 9999;
        }

        .rtl .mobile-select-menu.show {
            display: block;
        }

        .rtl #mobileSelectBtn {
            width: 42px;
            height: 42px;
            display: flex !important;;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
            right: 10%;
            position: relative;
        }

        .ltr #mobileSelectBtn {
            width: 42px;
            height: 42px;
            display: flex !important;;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
            left: 10%;
            position: relative;
        }

        .fields-group > .form-group {
            display: flex;
            flex-direction: row !important;
            align-items: center;
        }

        .fields-group .col-sm-2.control-label {
            flex: 0 0 auto;
            /*width: 16.66666667%;*/
            /*min-width: 80px;*/
            text-align: right;
            margin-bottom: 0;
            font-size: 12px;
        }

        .fields-group .col-sm-8 {
            flex: 1;
            width: auto;
        }

        .fields-group .input-group.input-group-sm .form-control {
            padding: 10px 12px;
            font-size: 12px;
        }

        .fields-group .form-group:last-child {
            flex-direction: row !important;
            justify-content: flex-start;
        }

        .input-group-addon, .input-group-btn {
            width: 0px;
        }

        .form-horizontal + .box-footer .col-md-8,
        .form-horizontal .box-footer .col-md-8 {
            margin-left: 20px;
        }

        .modal-dialog {
            margin: 25% auto !important;
        }

        .dashboard-container .tab-pane .col-md-12 .stats-container .row {
            display: flex !important;
        }

        .rtl .col-md-3 .card {
            margin-left: 4% !important;
            margin-right: 4% !important;
        }

        .col-md-3 .card {
            margin-right: 4% !important;
            margin-left: 4% !important;
        }

        .rtl .finance-card {
            margin-right: 0px !important;
        }

        .form-horizontal .fields-group > .col-md-12 {
            padding: 0px !important;
            padding-bottom: 15px !important;
        }

        .box-body {
            padding: 0px !important;
        }

        .col-lg-1, .col-lg-10, .col-lg-11, .col-lg-12, .col-lg-2, .col-lg-3, .col-lg-4, .col-lg-5, .col-lg-6, .col-lg-7,
        .col-lg-8, .col-lg-9, .col-md-1, .col-md-10, .col-md-11, .col-md-12, .col-md-2, .col-md-3, .col-md-4, .col-md-5,
        .col-md-6, .col-md-7, .col-md-8, .col-md-9, .col-sm-1, .col-sm-10, .col-sm-11, .col-sm-12, .col-sm-2, .col-sm-3,
        .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-xs-1, .col-xs-10, .col-xs-11, .col-xs-12,
        .col-xs-2, .col-xs-3, .col-xs-4, .col-xs-5, .col-xs-6, .col-xs-7, .col-xs-8, .col-xs-9 {
            padding-right: 0;
            padding-left: 0;
        }
    }
</style>
