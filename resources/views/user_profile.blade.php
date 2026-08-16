@php use App\Helpers\Common;use Carbon\Carbon; @endphp
<link rel="stylesheet" href="{{ admin_asset('vendor/fontawesome6/css/all.min.css') }}">
<link rel="stylesheet" href="{{ admin_asset('vendor/fonts/inter/inter.css') }}">

{{-- Central design system: one source of truth for every color/surface.
     Rendered via $content->view() INSIDE the admin layout, so the tokens,
     jQuery, pjax and SweetAlert2 are already provided globally; the include
     keeps tokens resolving even if this doc is opened standalone. --}}
@include('css.theme-tokens')

<style>
        :root {
            /* Only the brand cover image stays local — it is data-driven from
               the white-label panel (getImagePath), not a palette color.
               Every color / surface / geometry token now lives centrally in
               css/theme-tokens.blade.php. */
            --brand_background-image: url({{ getImagePath(config('themes.brandBackgroundImage')) }});
        }

        * { box-sizing: border-box; }

        .filter-container {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            padding: 20px;
            margin-bottom: 24px;
        }

        .filter-content {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-actions {
            display: flex;
            gap: 12px;
            margin-inline-start: auto;
        }

        .form-floating {
            position: relative;
        }

        .form-select {
            height: 48px;
            border-radius: 8px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            padding: 12px 16px;
            font-size: 14px;
            background-color: var(--surface-raised);
        }

        .wallet-filter-select {
            padding: 0 !important;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--accent-soft);
            background-color: var(--on-color);
        }

        .form-label {
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .btn-filter {
            background-color: var(--primary-color);
            color: var(--on-color);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            height: 48px;
        }

        .btn-filter:hover {
            background-color: var(--accent-strong);
            transform: translateY(-1px);
        }

        .btn-reset {
            background-color: var(--surface-raised);
            color: var(--text-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            height: 48px;
        }

        .btn-reset:hover {
            background-color: var(--surface-raised);
            color: var(--danger);
            border-color: color-mix(in srgb, var(--danger) 40%, transparent);
        }

        @media (max-width: 768px) {
            .filter-content {
                flex-direction: column;
                gap: 16px;
            }

            .filter-group {
                width: 100%;
            }

            .filter-actions {
                width: 100%;
                justify-content: flex-end;
                margin-inline-start: 0;
            }
        }

        @media (max-width: 576px) {
            .filter-actions {
                flex-direction: column;
                gap: 12px;
            }

            .btn-filter, .btn-reset {
                width: 100%;
                justify-content: center;
            }
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-inline-end: 57px;
            color: var(--on-color);
            font-size: 20px;
            margin-bottom: 6px;
        }

        .stat-icon.bg-blue {
            background: var(--gradient-3);
        }

        .stat-icon.bg-green {
            background: var(--gradient-4);
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 5px;
        }

        .section-box {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }


        .section-header h4 {
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-header .text-yellow {
            color: var(--warning);
        }

        .section-header .text-red {
            color: var(--danger);
        }


        .number-badge {
            display: inline-block;
            padding: 4px 10px;
            background: var(--surface-raised);
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
        }

        .number-badge.warning {
            background: var(--chip-warning-bg);
            color: var(--warning);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 0;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }

        .empty-table {
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            background: var(--surface);
            border-radius: 8px;
            margin: 15px 0;
        }

        .empty-table i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .empty-table p {
            margin: 0;
            font-size: 14px;
        }

        .agency-profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-primary);
            /* background: var(--secondary-color); */
            /* filter: brightness(0.85); */

        }

        .agency-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid var(--surface);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .agency-avatar .logo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .agency-info {
            flex: 1;
        }

        .agency-name {
            margin: 0 0 10px 0;
            color: var(--text-primary);
            font-size: 28px;
            font-weight: 700;
        }

        .agency-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }

        .meta-label {
            font-weight: 600;
            color: var(--text-secondary);
        }

        .meta-value {
            color: var(--text-primary);
        }

        .meta-uuid {
            color: var(--text-secondary);
            font-size: 0.9em;
        }

        .agency-stats {
            display: flex;
            gap: 15px;
        }

        .stat-card {
            background: var(--surface);
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            min-width: 200px;

        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--info);
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ltr .btn-back {
            position: absolute;
            top: 5px;
            right: 20px;
            background: var(--surface-raised);
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .rtl .btn-back {
            position: absolute;
            top: 5px;
            left: 20px;
            background: var(--surface-raised);
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-back:hover {
            background: var(--border);
            color: var(--text-primary);
        }

        .notice-section {
            background: var(--chip-warning-bg);
            border-left: 4px solid var(--warning);
            padding: 15px;
            border-radius: 0 6px 6px 0;
            margin-bottom: 25px;
        }

        .notice-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: var(--warning);
            font-weight: 600;
        }

        .notice-content {
            color: var(--text-primary);
            line-height: 1.5;
        }

        .top-performers-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .top-performers-section {
                grid-template-columns: 1fr;
            }
        }

        .performers-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            /* filter: brightness(0.5); */

        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--surface-raised);
        }

        .stats-row {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .section-title {
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-primary);
        }

        .section-badge {
            background: var(--info);
            color: var(--on-color);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 15px;
        }

        .avatar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s;
        }

        .avatar-item:hover {
            transform: translateY(-3px);
        }

        .avatar-img-container {
            position: relative;
            width: 60px;
            height: 60px;
            margin-bottom: 8px;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--surface);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .avatar-badge {
            position: absolute;
            bottom: -5px;
            right: -5px;
            background: var(--danger);
            color: var(--on-color);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            border: 2px solid white;
        }

        .avatar-badge.admin {
            background: var(--success);
        }

        .avatar-name {
            font-size: 12px;
            text-align: center;
            max-width: 80px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-scroll-container {
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .nav-pills {
            display: inline-flex;
            padding: 10px 0;
        }

        .nav-pills li {
            display: inline-block;
        }


        .nav-pills > li.active > a, .nav-pills > li.active > a:hover, .nav-pills > li.active > a:focus {
            border-top-color: var(--primary-color);
        }

        .nav-pills > li.active > a, .nav-pills > li.active > a:focus, .nav-pills > li.active > a:hover {
            color: var(--on-color);
            background-color: var(--primary-color);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 0;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }

        .agency-tabs {
            display: flex;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
            overflow-x: auto;
        }

        .tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 600;

            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .tab-btn.active {
            color: var(--text-secondary-color);
            background-color: var(--primary-color);
            border-bottom-color: transparent;
            border-radius: 4px;
        }

        .tab-btn:hover:not(.active) {
            border-bottom-color: var(--border-strong) !important;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background: var(--surface);
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .card-header h3 {
            margin: 0;
            font-size: 18px;
            color: var(--text-primary);
        }

        .count-badge {
            background: var(--surface-raised);
            color: var(--text-secondary);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
        }


        .table-section {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
        }

        .data-table th {
            /* text-align: start; */
            padding: 12px 15px;
            background: var(--table-head-bg);

            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--surface-raised);
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-info strong {
            font-size: 14px;
        }

        .user-info small {
            font-size: 11px;
            color: var(--text-secondary);
        }

        .role-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            color: var(--on-color);
        }

        .role-badge.owner {
            background: var(--accent);
        }

        .role-badge.admin {
            background: var(--success);
        }

        .btn-action {
            padding: 5px 10px;
            background: var(--info);
            color: var(--on-color);
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-action:hover {
            background: var(--info);
        }

        .empty-table {
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }

        .empty-table i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .empty-table p {
            margin: 0;
            font-size: 14px;
        }

        .user-avatar,
        .supporter-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .supporters-avatars {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .target-card-section-1 {
            /* display: inline-flex; */
            width: 100%;
            padding-top: 26px;
            margin-bottom: 35px;

        }

        .card-target-filter {
            display: inline;
            width: 34%;
            left: 33px;
            position: absolute;
        }

        .card-target-filter .form-group {
            margin-bottom: 16px;
            right: 20px;
            position: relative;
            top: 10px;
        }

        .card-target-filter button {
            position: relative;
            left: -49px;
            bottom: -29px;
        }

        .card-target-filter-phone {
            width: 51%;
            margin-bottom: 27px;
            position: relative;
        }

        .card-target-filter-phone .form-group {
            margin-bottom: 16px;
            right: 20px;
            position: relative;
            top: 10px;
        }

        .card-target-filter-phone button {
            position: relative;
            left: -49px;
            bottom: -29px;
        }

        .target-card-stat {
            width: 50%;
        }

        .filter-form {
            border-radius: 13px;
            height: 165px;

        }

        .card-target-filter-phone {
            /* display: none; */
        }

        .card-target-filter {
            display: none;
        }

        @media (max-width: 768px) {
            .stats-row {
                flex-direction: column;
                width: 108%;

            }

            .avatar-grid {
                grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
            }

            .target-card-section-1 {
                display: grid;
                width: 100%;
                padding-top: 26px;
                margin-bottom: 35px;
            }


            .stat-icon {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-inline-end: 118px;
                color: var(--on-color);
                font-size: 20px;
                margin-bottom: 6px;

            }


            .target-card-stat {
                width: 92%;

            }

            .card-target-filter {
                display: none;
            }

            .card-target-filter-phone {
                display: block;
                width: 100%;
                left: 0px;
                position: relative;
                margin-bottom: 31px;

            }

            .card-target-filter-phone .col-md-7 {
                float: none;
            }

            .card-target-filter-phone .form-control {
                /* display: block; */
                width: 89%;
                padding: 6px 12px;
                font-size: 14px;
                line-height: 1.42857143;
                color: var(--text-secondary-color) !important;
                background-color: var(--on-color);
                background-image: none;
                border: 1px solid var(--accent-soft) !important;
                border-radius: 4px;
            }

            .card-target-filter-phone .filter-form {
                border-radius: 13px;
                height: 238px;
            }

            .card-target-filter-phone .align-items-end {
                display: grid;
            }

            .card-target-filter-phone button {
                left: -224px;

            }
        }

        .date-flex-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .date-flex-row span {
            font-weight: 600;
            white-space: nowrap;
        }

        .date-flex-row input[type="date"] {
            flex-basis: 0;
        }

        .diamond-summary-container {
            display: flex;
            justify-content: center;
            width: 100%;
            padding: 20px;
        }

        .diamond-summary-box {
            max-width: 600px;
            width: 100%;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-color) 100%);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin: 0 auto; /* This also helps with centering */
        }

        .diamond-summary-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .diamond-title {
            font-size: 22px;
            font-weight: bold;
            color: var(--profile-heading);
            margin-bottom: 15px;
        }

        .diamond-count {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--on-color);
        }

        .diamond-count span {
            margin-inline-end: 10px;
        }

        .diamond-icon-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            padding: 8px;
            display: inline-flex;
        }

        .diamond-icon {
            width: 32px;
            height: 32px;
            filter: drop-shadow(0 0 3px rgba(255, 255, 255, 0.5));
        }

        .gift-log-form {
            background-color: transparent !important;
            filter: none !important;
        }

        .level-form {
            background-color: transparent !important;
            filter: none !important;
            padding: 10px;
        }

        .level-label {
            padding: 10px;
        }

        /* .rtl .gift-log-form {
            padding-right: 13%;
        } */
        .ltr .gift-log-form {
            padding-left: 13%;
        }

        .card {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-top: 1px solid var(--border);
        }

        .card-header {
            border-bottom: 1px solid var(--border);
        }

        .card-title {
            color: var(--text-primary);
            font-weight: 500;
        }

        /* ═══════════════════════════════════════════
           MODERN UI POLISH — Enhanced Styling
           ═══════════════════════════════════════════ */

        /* ── Cover Slideshow ── */
        .profile-cover-wrapper {
            position: relative;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            overflow: visible;
            margin-bottom: 0;
        }

        .profile-cover-slideshow {
            overflow: hidden;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .profile-cover-slideshow {
            position: relative;
            height: 220px;
            overflow: hidden;
        }

        .cover-slide {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            z-index: 1;
        }

        .cover-slide.active {
            opacity: 1;
            z-index: 2;
        }

        .cover-slide::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 80px;
            background: linear-gradient(transparent, rgba(0,0,0,0.35));
            z-index: 3;
        }

        /* Navigation arrows */
        .cover-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(4px);
            border: none;
            color: var(--on-color);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s, background 0.3s;
            font-size: 14px;
        }

        .profile-cover-wrapper:hover .cover-nav {
            opacity: 1;
        }

        .cover-nav:hover {
            background: rgba(255,255,255,0.45);
        }

        .cover-prev { left: 14px; }
        .cover-next { right: 14px; }

        /* Dots indicator */
        .cover-dots {
            position: absolute;
            bottom: 14px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            display: flex;
            gap: 8px;
        }

        .cover-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.45);
            cursor: pointer;
            transition: all 0.3s;
        }

        .cover-dot.active {
            background: var(--surface);
            transform: scale(1.3);
            box-shadow: 0 0 6px rgba(255,255,255,0.6);
        }

        .profile-avatar-wrapper {
            position: absolute;
            bottom: -50px;
            left: 40px;
            z-index: 10;
        }

        .profile-avatar-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid var(--surface);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            object-fit: contain;
            background: var(--surface);
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .profile-avatar-img:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 28px rgba(0,0,0,0.25);
        }

        .profile-info-card {
            background: var(--surface);
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            padding: 20px 40px 24px;
            padding-top: 60px;
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
            border: 1px solid rgba(0,0,0,0.06);
            border-top: none;
        }

        .profile-info-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 16px;
        }

        .profile-name-section {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .profile-user-name {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
            letter-spacing: -0.3px;
        }

        .profile-badges-inline {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .profile-actions-top {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .profile-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--surface-sunken);
        }

        .profile-meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--surface-raised);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 6px 16px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .profile-meta-chip i {
            color: var(--text-muted);
            font-size: 12px;
        }

        .meta-sep {
            color: var(--border);
            margin: 0 2px;
        }

        .profile-stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 16px;
        }

        .profile-stat-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--surface-raised);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px 24px;
            min-width: 120px;
            flex: 1;
            text-align: center;
            transition: all 0.2s;
        }

        .profile-stat-box:hover {
            border-color: var(--primary-color);
            background: var(--chip-info-bg);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        .profile-stat-value {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
            word-break: break-all;
            overflow-wrap: break-word;
            text-align: center;
            max-width: 100%;
        }

        .profile-stat-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .profile-badges-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding-top: 12px;
            border-top: 1px solid var(--surface-sunken);
        }

        .flag-image {
            height: 18px;
            border-radius: 3px;
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .profile-cover { height: 140px; }
            .profile-avatar-wrapper { left: 50%; transform: translateX(-50%); bottom: -40px; }
            .profile-avatar-img { width: 90px; height: 90px; }
            .profile-info-card { padding: 56px 16px 20px; text-align: center; }
            .profile-info-header { justify-content: center; }
            .profile-name-section { justify-content: center; }
            .profile-actions-top { justify-content: center; }
            .profile-meta-row { justify-content: center; }
            .profile-stats-row { justify-content: center; gap: 8px; }
            .profile-stat-box { min-width: 80px; padding: 10px 8px; flex: 1 1 calc(33.33% - 8px); }
            .profile-stat-value { font-size: 14px !important; }
            .profile-stat-label { font-size: 9px !important; }
            .profile-badges-row { justify-content: center; }
            .profile-meta-chip { font-size: 11px; padding: 4px 10px; }
        }

        @media (max-width: 480px) {
            .profile-stats-row { gap: 6px; }
            .profile-stat-box { min-width: 70px; padding: 8px 6px; }
            .profile-stat-value { font-size: 12px !important; }
            .profile-stat-label { font-size: 8px !important; }
            .profile-info-card { padding: 56px 10px 16px; }
            .agency-profile-container { padding: 10px; }
        }

        /* ── Tabs Navigation ── */
        .agency-tabs {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: 8px;
            border: none;
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
            gap: 4px;
            scrollbar-width: none;
            color: var(--text-secondary-color);
        }

        .agency-tabs::-webkit-scrollbar {
            display: none;
        }

        .tab-btn {
            padding: 11px 20px;
            font-size: 13px;
            font-weight: 600;
            border-radius: var(--radius-sm);
            border: none;
            border-bottom: none;
            color: var(--text-secondary-color);
            opacity: 0.6;
            text-decoration: none;
            transition: all 0.25s;
        }

        .tab-btn:hover:not(.active) {
            opacity: 0.9;
            background: rgba(255,255,255,0.08);
            border-bottom-color: transparent !important;
            text-decoration: none;
            color: var(--text-secondary-color);
        }

        .tab-btn.active {
            opacity: 1;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            color: var(--text-primary-color);
            background: var(--primary-color);
        }

        /* ── Cards ── */
        .card {
            border: none;
            border-top: none;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            padding: 18px 24px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -0.2px;
        }

        .card-body {
            padding: 20px 24px;
        }

        /* ══════════════════════════════════════════════════════
           PREMIUM TABLE STYLES — Polished & Professional
           ══════════════════════════════════════════════════════ */
        .data-table,
        .table {
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
            width: 100%;
            background: var(--surface) !important;
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        /* ── Table Header ── */
        .data-table th,
        .table > thead > tr > th {
            background: linear-gradient(180deg, var(--surface-raised), var(--surface-sunken)) !important;
            color: var(--text-primary) !important;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 16px 22px !important;
            border-bottom: 2px solid var(--border) !important;
            border-top: none !important;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .data-table th:first-child,
        .table > thead > tr > th:first-child {
            border-radius: var(--radius-md) 0 0 0;
        }

        .data-table th:last-child,
        .table > thead > tr > th:last-child {
            border-radius: 0 var(--radius-md) 0 0;
        }

        /* ── Table Cells ── */
        .data-table td,
        .table > tbody > tr > td {
            padding: 16px 22px !important;
            vertical-align: middle;
            border-bottom: 1px solid var(--border) !important;
            border-top: none !important;
            color: var(--text-primary);
            font-size: 13.5px;
            line-height: 1.7;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        /* ── Row Styles ── */
        .data-table tbody tr,
        .table > tbody > tr {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--surface);
            position: relative;
        }

        .data-table tbody tr:nth-child(even),
        .table > tbody > tr:nth-child(even) {
            background: var(--chip-info-bg);
        }

        .data-table tbody tr:hover,
        .table > tbody > tr:hover {
            background: var(--gradient-3) !important;
            box-shadow: inset 4px 0 0 var(--primary-color, var(--info)), 0 2px 8px rgba(0,0,0,0.12);
            z-index: 1;
        }

        .data-table tbody tr:hover td,
        .table > tbody > tr:hover td {
            color: var(--text-primary);
        }

        .data-table tbody tr:last-child td:first-child {
            border-radius: 0 0 0 var(--radius-md);
        }

        .data-table tbody tr:last-child td:last-child {
            border-radius: 0 0 var(--radius-md) 0;
        }

        /* Override orange text in tbody */
        .data-table tbody,
        .table tbody,
        .data-table tbody tr,
        .table tbody tr,
        .data-table tbody tr td,
        .table tbody tr td,
        .data-table tbody[style] tr td,
        .table tbody[style] tr td {
            color: var(--text-primary) !important;
        }

        /* ── First Column (ID/#) ── */
        .data-table td:first-child,
        .table > tbody > tr > td:first-child {
            font-weight: 800;
            font-size: 12px;
        }

        .data-table td:first-child,
        .table > tbody > tr > td:first-child {
            color: var(--on-color);
        }

        .data-table td:first-child::before,
        .table > tbody > tr > td:first-child::before {
            content: '';
            display: inline-block;
        }

        .data-table td:first-child,
        .table > tbody > tr > td:first-child {
            position: relative;
        }

        .data-table td:first-child span,
        .data-table td:first-child {
            color: var(--info);
        }

        /* ID number badge style */
        .data-table tbody tr td:first-child,
        .table > tbody > tr > td:first-child {
            color: var(--info);
            font-variant-numeric: tabular-nums;
            min-width: 50px;
            text-align: center;
        }

        /* ── Table Wrapper ── */
        .table-responsive {
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04), 0 0 1px rgba(0,0,0,0.06);
        }

        /* Table inside cards - remove double borders */
        .card .table-responsive {
            margin: 0;
            border-left: none;
            border-right: none;
            border-radius: 0;
            box-shadow: none;
            border-top: none;
        }

        .card .data-table,
        .card .table {
            margin-bottom: 0;
        }

        /* ── Table Links ── */
        .data-table a,
        .table a {
            color: var(--info);
            text-decoration: none;
            transition: all 0.25s;
            font-weight: 600;
            position: relative;
        }

        .data-table a:hover,
        .table a:hover {
            color: var(--info);
        }

        .data-table a::after,
        .table td a::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 0;
            height: 1.5px;
            background: var(--info);
            transition: width 0.3s ease;
        }

        .data-table a:hover::after,
        .table td a:hover::after {
            width: 100%;
        }

        /* ── Table Images ── */
        .data-table img,
        .table img {
            border-radius: 10px;
            border: 2px solid var(--border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .data-table img:hover,
        .table img:hover {
            transform: scale(1.12);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            border-color: var(--chip-info-bg);
        }

        /* ── Status Badges ── */
        .table .text-success {
            color: var(--success) !important;
            font-weight: 700;
            background: var(--chip-success-bg);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .table .text-danger {
            color: var(--danger) !important;
            font-weight: 700;
            background: var(--chip-danger-bg);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .table .text-muted {
            color: var(--text-muted) !important;
        }

        /* ── Action Buttons in Tables ── */
        .data-table .btn,
        .table .btn {
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 18px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
            letter-spacing: 0.3px;
            border: none;
        }

        .data-table .btn:hover,
        .table .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .data-table .btn:active,
        .table .btn:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .data-table .btn-info,
        .table .btn-info {
            background: var(--gradient-3);
            color: var(--on-color);
        }

        .data-table .btn-info:hover,
        .table .btn-info:hover {
            background: var(--gradient-3);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }

        .data-table .btn-danger,
        .table .btn-danger {
            background: var(--gradient-danger);
            color: var(--on-color);
        }

        .data-table .btn-danger:hover,
        .table .btn-danger:hover {
            background: var(--gradient-danger);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }

        .data-table .btn-default,
        .table .btn-default {
            background: linear-gradient(135deg, var(--surface-sunken), var(--border));
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }

        .data-table .btn-default:hover,
        .table .btn-default:hover {
            background: linear-gradient(135deg, var(--border), var(--border-strong));
            color: var(--text-primary);
        }

        .data-table .d-flex,
        .table .d-flex {
            gap: 10px;
        }

        /* ── User Cell in Tables ── */
        .data-table .d-flex.align-items-center img,
        .table .d-flex.align-items-center img {
            border-radius: 50% !important;
            border: 2px solid var(--chip-info-bg) !important;
        }

        /* ── Pagination Enhancement ── */
        .pagination-wrapper,
        .pagination-container {
            padding: 20px 24px;
            display: flex;
            justify-content: center;
        }

        .pagination {
            gap: 4px;
        }

        .pagination > li > a,
        .pagination > li > span {
            border-radius: 10px !important;
            margin: 0 2px !important;
            border: 1px solid var(--border) !important;
            font-weight: 700;
            font-size: 13px;
            padding: 8px 16px !important;
            transition: all 0.25s;
            color: var(--text-secondary);
        }

        .pagination > .active > a,
        .pagination > .active > span {
            background: var(--gradient-3) !important;
            border-color: transparent !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            color: var(--on-color) !important;
        }

        .pagination > li > a:hover {
            background: var(--chip-info-bg) !important;
            border-color: var(--chip-info-bg) !important;
            color: var(--info) !important;
            transform: translateY(-1px);
        }

        /* ── Empty Table State ── */
        .data-table tbody tr td[colspan],
        .table tbody tr td[colspan] {
            padding: 48px 24px !important;
            color: var(--text-muted);
            font-size: 14px;
            font-style: italic;
            background: linear-gradient(135deg, var(--surface-raised), var(--surface-sunken)) !important;
        }

        /* ── Buttons ── */
        .btn {
            font-weight: 600;
            border-radius: var(--radius-sm);
            transition: all 0.25s;
            font-size: 13px;
        }

        .btn-info {
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .btn-danger {
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 6px 16px;
            font-size: 12px;
        }

        /* ── Filter Container ── */
        .filter-container {
            background: rgba(0,0,0,0.015);
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: var(--radius-md);
            padding: 20px;
            box-shadow: none;
        }

        .form-control {
            border-radius: var(--radius-sm);
            border: 1.5px solid rgba(0,0,0,0.1);
            padding: 8px 14px;
            font-size: 13px;
            transition: all 0.25s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0,0,0,0.04);
        }

        .form-select {
            border-radius: var(--radius-sm);
        }

        /* ── Nav Pills (sub-tabs) ── */
        .nav-pills {
            gap: 4px;
            padding: 8px 0;
        }

        .nav-pills > li > a {
            border-radius: var(--radius-sm);
            padding: 8px 18px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
        }

        .nav-pills > li.active > a {
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }

        /* ── Pagination ── */
        .pagination-wrapper,
        .pagination-container {
            padding: 16px 24px;
        }

        .pagination > li > a,
        .pagination > li > span {
            border-radius: var(--radius-sm) !important;
            margin: 0 3px !important;
            border: 1px solid rgba(0,0,0,0.08) !important;
            font-weight: 600;
            font-size: 13px;
            padding: 6px 14px !important;
            transition: all 0.2s;
        }

        .pagination > .active > a,
        .pagination > .active > span {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .pagination > li > a:hover {
            background: rgba(0,0,0,0.04) !important;
            transform: translateY(-1px);
        }

        /* ── Diamond Summary ── */
        .diamond-summary-box {
            border-radius: var(--radius-md);
            padding: 24px;
            box-shadow: var(--shadow-md);
        }

        .diamond-title {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -0.2px;
        }

        .diamond-count {
            font-size: 28px;
            font-weight: 800;
        }

        /* ── Loading Indicator ── */
        #tab-loading {
            border-radius: var(--radius-md) !important;
            box-shadow: var(--shadow-lg) !important;
            font-size: 16px !important;
            padding: 20px 32px !important;
            transform: translate(-50%, -50%) !important;
        }

        /* ── Modals ── */
        .modal-content {
            border: none !important;
            border-radius: var(--radius-lg) !important;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25) !important;
            overflow: hidden;
        }

        .modal-header {
            padding: 20px 24px !important;
        }

        .modal-body {
            padding: 24px !important;
        }

        .modal-footer {
            padding: 16px 24px !important;
            border-top: 1px solid rgba(0,0,0,0.06) !important;
            gap: 8px;
        }

        /* ── Date Filter Row ── */
        .date-flex-row {
            gap: 10px;
            background: rgba(0,0,0,0.015);
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(0,0,0,0.06);
        }

        .date-flex-row span {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* ── Empty States ── */
        .empty-state,
        .empty-table {
            padding: 48px;
            border-radius: var(--radius-md);
        }

        .empty-state i,
        .empty-table i {
            font-size: 48px;
            opacity: 0.4;
            margin-bottom: 16px;
        }

        .empty-state p,
        .empty-table p {
            font-size: 14px;
            opacity: 0.6;
        }

        /* ── Scrollbar ── */
        .table-responsive::-webkit-scrollbar,
        .nav-scroll-container::-webkit-scrollbar {
            height: 4px;
        }

        .table-responsive::-webkit-scrollbar-track,
        .nav-scroll-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .table-responsive::-webkit-scrollbar-thumb,
        .nav-scroll-container::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.12);
            border-radius: 4px;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .agency-header {
                padding: 20px;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .agency-meta {
                justify-content: center;
            }

            .agency-stats {
                flex-direction: column;
                width: 100%;
            }

            .agency-tabs {
                border-radius: var(--radius-sm);
                padding: 4px;
            }

            .tab-btn {
                padding: 8px 14px;
                font-size: 12px;
            }

            .card-header {
                padding: 14px 18px;
            }

            .card-body {
                padding: 16px 18px;
            }

            .data-table th,
            .data-table td,
            .table > thead > tr > th,
            .table > tbody > tr > td {
                padding: 10px 12px !important;
                font-size: 12px;
            }
        }

        /* ── Animations ── */
        .tab-content {
            animation: fadeInTab 0.3s ease;
        }

        @keyframes fadeInTab {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Box Body Spacing ── */
        .box-body {
            padding: 16px;
        }

        .box-body.p-3 {
            padding: 20px !important;
        }

        /* ── Profile Actions Dropdown ── */
        .profile-dropdown-menu.show-dropdown {
            display: block !important;
            animation: dropdownFadeIn 0.2s ease;
        }

        @keyframes dropdownFadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
{{-- FontAwesome is loaded locally in the head above; jQuery + pjax come from
     the admin layout. No external CDN. --}}
</head>

<body>
<div class="agency-profile-container" id="pjax-container">
    {{-- Error Messages --}}
    @if ($errors->any())
        <div class="alert alert-danger mx-3 mt-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($errors->has('msg'))
        <div class="alert alert-danger mx-3 mt-3">
            {{ $errors->first('msg') }}
        </div>
    @endif
    <!-- Header Section - Cover + Profile Card -->
    <div class="profile-cover-wrapper">
        @php
            $coverImages = $covers->count() ? $covers : collect();
        @endphp
        <div class="profile-cover-slideshow">
            @if($coverImages->count() > 0)
                @foreach($coverImages as $i => $cover)
                    <div class="cover-slide {{ $i === 0 ? 'active' : '' }}" style="background-image: url('{{ getImagePath($cover->img) }}');"></div>
                @endforeach
                @if($coverImages->count() > 1)
                    <div class="cover-dots">
                        @foreach($coverImages as $i => $cover)
                            <span class="cover-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                        @endforeach
                    </div>
                    <button class="cover-nav cover-prev" onclick="coverSlide(-1)"><i class="fas fa-chevron-left"></i></button>
                    <button class="cover-nav cover-next" onclick="coverSlide(1)"><i class="fas fa-chevron-right"></i></button>
                @endif
            @else
                <div class="cover-slide active" style="background-image: var(--brand_background-image); background-color: var(--surface-sunken);"></div>
            @endif
        </div>
        <div class="profile-avatar-wrapper">
            <img src="{{ $user->display_image }}" alt="Avatar" class="profile-avatar-img" onclick="document.getElementById('avatarFullModal').style.display='flex'">
        </div>
    </div>

    <!-- Full Image Modal -->
    <div id="avatarFullModal" onclick="this.style.display='none'" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.75);z-index:9999;justify-content:center;align-items:center;cursor:pointer;">
        <img src="{{ $user->display_image }}" alt="Full Avatar" onclick="event.stopPropagation()" style="max-width:90vw;max-height:85vh;border-radius:12px;box-shadow:0 8px 40px rgba(0,0,0,0.4);object-fit:contain;background: var(--surface);">
    </div>

    <div class="profile-info-card">
        <div class="profile-info-header">
            <div class="profile-name-section">
                <h1 class="profile-user-name">{{ @$user?->name ?? '' }}</h1>
                
            </div>
            <div class="profile-actions-top" style="display: flex; align-items: center; gap: 8px;">
                <a href="{{ url('admin/users/') }}" class="btn btn-outline-secondary btn-sm"
                   style="border-radius: 8px; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fas fa-arrow-left"></i> {{ __('Go Back') }}
                </a>

                {{-- Actions Dropdown Menu --}}
                <div class="dropdown" style="position: relative;">
                    <button class="btn btn-sm" type="button" id="profileActionsDropdown"
                            onclick="document.getElementById('profileDropdownMenu').classList.toggle('show-dropdown')"
                            style="background: var(--gradient-1); color: var(--on-color); border: none; border-radius: 10px; padding: 8px 18px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: all 0.2s;"
                            onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.25)'"
                            onmouseout="this.style.transform='none'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.15)'">
                        <i class="fas fa-ellipsis-v" style="font-size: 14px;"></i>
                        {{ __('Actions') }}
                    </button>

                    <div id="profileDropdownMenu" class="profile-dropdown-menu"
                         style="display: none; position: absolute; top: calc(100% + 8px); right: 0; min-width: 260px; background: linear-gradient(180deg, var(--sidebar-bg), color-mix(in srgb, var(--sidebar-bg) 82%, #000)); border-radius: 14px; box-shadow: 0 20px 50px rgba(0,0,0,0.35); z-index: 1000; overflow: hidden; border: 1px solid rgba(255,255,255,0.08);">

                        {{-- Edit & Remove BD Section --}}
                        <div style="padding: 8px;">
                            @if (\Encore\Admin\Facades\Admin::user()->can('edit-' . 'users') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                <a href="javascript:void(0)" class="edit_user_item_model_btn"
                                   style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; text-decoration: none; color: var(--border); font-size: 13px; font-weight: 600; transition: all 0.15s;"
                                   onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">
                                    <i class="fas fa-edit" style="width: 18px; text-align: center; color: var(--info);"></i>
                                    {{ __('edit') }}
                                </a>
                            @endif

                            @if (($user->is_bd == 1) && (\Encore\Admin\Facades\Admin::user()->can('edit-users') || \Encore\Admin\Facades\Admin::user()->can('*')))
                                <a href="javascript:void(0)" class="remove-bd-btn" data-id="{{ $user->id }}" data-url="{{ route('users.remove', $user->id) }}"
                                   style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; text-decoration: none; color: var(--chip-danger-bg); font-size: 13px; font-weight: 600; transition: all 0.15s;"
                                   onmouseover="this.style.background='rgba(0,0,0,0.12)'" onmouseout="this.style.background='transparent'">
                                    <i class="fas fa-trash-alt" style="width: 18px; text-align: center; color: var(--danger);"></i>
                                    {{ __('Remove BD') }}
                                </a>
                            @endif
                        </div>

                        {{-- Divider --}}
                        <div style="height: 1px; background: rgba(255,255,255,0.08); margin: 0 14px;"></div>

                        {{-- Toggle Actions Section --}}
                        <div style="padding: 8px;">
                            @if (\Encore\Admin\Facades\Admin::user()->can('charge-switch-' . $permission) || \Encore\Admin\Facades\Admin::user()->can('*'))
                                <a href="javascript:void(0)" onclick="profileAction('{{ route('users.toggle-transfer-salary', $user->id) }}', this)"
                                   style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; text-decoration: none; color: var(--border); font-size: 13px; font-weight: 600; transition: all 0.15s;"
                                   onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">
                                    <i class="fa {{ $user->transfer_salary ? 'fa-toggle-off' : 'fa-toggle-on' }}" style="width: 18px; text-align: center; color: {{ $user->transfer_salary ? 'var(--danger)' : 'var(--success)' }};"></i>
                                    {{ $user->transfer_salary ? __('Disable Transfer Salary') : __('Enable Transfer Salary') }}
                                </a>
                            @endif

                            @if (\Encore\Admin\Facades\Admin::user()->can('invite-switch-' . $permission) || \Encore\Admin\Facades\Admin::user()->can('*'))
                                <a href="javascript:void(0)" onclick="profileAction('{{ route('users.toggle-invite-code', $user->id) }}', this)"
                                   style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; text-decoration: none; color: var(--border); font-size: 13px; font-weight: 600; transition: all 0.15s;"
                                   onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">
                                    <i class="fa {{ @$user->userSetting->show_invite_code ? 'fa-toggle-on' : 'fa-toggle-off' }}" style="width: 18px; text-align: center; color: {{ @$user->userSetting->show_invite_code ? 'var(--success)' : 'var(--danger)' }};"></i>
                                    {{ @$user->userSetting->show_invite_code ? __('Disable Show Invite Code') : __('Enable Show Invite Code') }}
                                </a>
                            @endif

                            @if (\Encore\Admin\Facades\Admin::user()->can('can-Play-switch-' . $permission) || \Encore\Admin\Facades\Admin::user()->can('*'))
                                <a href="javascript:void(0)" onclick="profileAction('{{ route('users.toggle-can-play', $user->id) }}', this)"
                                   style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; text-decoration: none; color: var(--border); font-size: 13px; font-weight: 600; transition: all 0.15s;"
                                   onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">
                                    <i class="fa {{ $user->can_play == 2 ? 'fa-toggle-on' : 'fa-toggle-off' }}" style="width: 18px; text-align: center; color: {{ $user->can_play == 2 ? 'var(--success)' : 'var(--danger)' }};"></i>
                                    {{ $user->can_play == 2 ? __('Disable Can Play') : __('Enable Can Play') }}
                                </a>
                            @endif
                        </div>

                        {{-- Kick & Change Agency Section --}}
                        @if (($user->agency_id >= 1) || ($user->family_id >= 1))
                            <div style="height: 1px; background: rgba(255,255,255,0.08); margin: 0 14px;"></div>
                            <div style="padding: 8px;">
                                @if ($user->agency_id >= 1 && (\Encore\Admin\Facades\Admin::user()->can('kick-agency-switch-' . $permission) || \Encore\Admin\Facades\Admin::user()->can('*')))
                                    <a href="javascript:void(0)" onclick="profileActionConfirm('{{ route('users.kick-agency', $user->id) }}', '{{ __('dashboard.chickKickAgency') }}')"
                                       style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; text-decoration: none; color: var(--chip-danger-bg); font-size: 13px; font-weight: 600; transition: all 0.15s;"
                                       onmouseover="this.style.background='rgba(0,0,0,0.12)'" onmouseout="this.style.background='transparent'">
                                        <i class="fa fa-sign-out" style="width: 18px; text-align: center; color: var(--danger);"></i>
                                        {{ __('dashboard.kickAgency') }}
                                    </a>
                                @endif

                                @if ($user->family_id >= 1 && (\Encore\Admin\Facades\Admin::user()->can('kick-family-switch-' . $permission) || \Encore\Admin\Facades\Admin::user()->can('*')))
                                    <a href="javascript:void(0)" onclick="profileActionConfirm('{{ route('users.kick-family', $user->id) }}', '{{ __('dashboard.chickKick') }}')"
                                       style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; text-decoration: none; color: var(--chip-danger-bg); font-size: 13px; font-weight: 600; transition: all 0.15s;"
                                       onmouseover="this.style.background='rgba(0,0,0,0.12)'" onmouseout="this.style.background='transparent'">
                                        <i class="fa fa-sign-out" style="width: 18px; text-align: center; color: var(--danger);"></i>
                                        {{ __('dashboard.kickFamily') }}
                                    </a>
                                @endif

                                @if ($user->agency_id >= 1 && (\Encore\Admin\Facades\Admin::user()->can('chang-agency-switch-' . $permission) || \Encore\Admin\Facades\Admin::user()->can('*')))
                                    <a href="javascript:void(0)" onclick="$('#changeAgencyModal').modal('show'); document.getElementById('profileDropdownMenu').classList.remove('show-dropdown');"
                                       style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; text-decoration: none; color: var(--info); font-size: 13px; font-weight: 600; transition: all 0.15s;"
                                       onmouseover="this.style.background='rgba(0,0,0,0.12)'" onmouseout="this.style.background='transparent'">
                                        <i class="fa fa-exchange" style="width: 18px; text-align: center; color: var(--info);"></i>
                                        {{ __('dashboard.changeAgency') }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-meta-row">
            <div class="profile-meta-chip" style="cursor: pointer; transition: all 0.2s;" title="Click to copy"
                 onclick="var t='{{ @$user->original_uuid }}'; navigator.clipboard.writeText(t); var el=this; el.style.background='var(--chip-success-bg)'; el.style.borderColor='var(--chip-success-bg)'; setTimeout(function(){el.style.background=''; el.style.borderColor='';},1000);">
                <i class="fas fa-id-badge"></i>
                @if (@$user->uuid == @$user->original_uuid)
                    <span>{{ @$user->original_uuid }}</span>
                @else
                    <span>{{ @$user->original_uuid }}</span>
                    <span class="meta-sep">|</span>
                    <span style="opacity:0.7">{{ @$user->uuid_v3 }}</span>
                @endif
                <i class="fas fa-copy" style="font-size: 10px; color: var(--text-muted); margin-inline-start: 4px;"></i>
            </div>
            <div class="profile-meta-chip">
                <i class="fas fa-phone"></i>
                <span>{{ @$user->phone ?? 'N/A' }}</span>
            </div>
            <div class="profile-meta-chip">
                <img src="{{ getImagePath(@$user->country->flag) }}" class="flag-image" alt="flag"
                     title="{{ app()->getLocale() === 'ar' ? @$user->country->name : @$user->country->e_name }}">
                <span>{{ app()->getLocale() === 'ar' ? @$user->country->name : @$user->country->e_name }}</span>
            </div>

            {{-- Agency --}}
            @if(@$user->agency)
                @php
                    $agencyImg = getImagePath(@$user->agency->img) ?? asset('images/icon-agency.jpg');
                    if (!isImageExists($agencyImg)) $agencyImg = asset('images/icon-agency.jpg');
                    $agencyProfileUrl = route('admin.agency.profile', ['id' => @$user->agency->id ?? 0]);
                @endphp
                <a href="{{ $agencyProfileUrl }}" class="profile-meta-chip" style="text-decoration: none; background: var(--chip-info-bg); border-color: var(--chip-info-bg); cursor: pointer; transition: all 0.2s;"
                   onmouseover="this.style.borderColor='var(--info)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.12)'"
                   onmouseout="this.style.borderColor='var(--chip-info-bg)'; this.style.boxShadow='none'">
                    <img src="{{ $agencyImg }}" style="width: 22px; height: 22px; border-radius: 6px; object-fit: cover; border: 1px solid var(--chip-info-bg);">
                    <span style="font-weight: 600; color: var(--info);">{{ @$user->agency->name }}</span>
                    <span style="font-size: 10px; color: var(--text-muted); background: var(--surface-sunken); padding: 1px 6px; border-radius: 4px;">{{ __('agency') }}</span>
                </a>
            @endif

            {{-- Shipping Agency --}}
            @if(@$user->shippingAgency)
                @php
                    $shipImg = getImagePath(@$user->shippingAgency->img) ?? asset('images/icon-agency.jpg');
                    if (!isImageExists($shipImg)) $shipImg = asset('images/icon-agency.jpg');
                @endphp
                <div class="profile-meta-chip" style="background: var(--chip-warning-bg); border-color: var(--chip-warning-bg);">
                    <img src="{{ $shipImg }}" style="width: 22px; height: 22px; border-radius: 6px; object-fit: cover; border: 1px solid var(--chip-warning-bg);">
                    <span style="font-weight: 600; color: var(--warning);">{{ @$user->shippingAgency->name }}</span>
                    <span style="font-size: 10px; color: var(--text-muted); background: var(--surface-sunken); padding: 1px 6px; border-radius: 4px;">{{ __('shipping') }}</span>
                </div>
            @endif
        </div>

        @php
            $user_diamonds = (in_array($user->type_user, [0,3])) ? $user->exchange_diamonds : $user->monthly_diamond_received;
        @endphp

        <div class="profile-stats-row">
            <div class="profile-stat-box">
                <span class="profile-stat-value">{{ number_format((float)(@$user->salary ?? 0), 2) }}</span>
                <span class="profile-stat-label">{{ __('Balance') }}</span>
            </div>
            <div class="profile-stat-box">
                <span class="profile-stat-value">{{ number_format((float)(@$user_diamonds ?? 0)) }}</span>
                <span class="profile-stat-label">{{ __('diamonds') }}</span>
            </div>
            <div class="profile-stat-box">
                <span class="profile-stat-value">{{ number_format((float)(@$user->di ?? 0)) }}</span>
                <span class="profile-stat-label">{{ __('coins') }}</span>
            </div>
        </div>

        <div class="profile-stats-row">
            
            <div class="profile-stat-box">
                <img src="{{ getImagePath(@$user->senderLevel->img) }}" style="height: 28px;">
                <span class="profile-stat-label">{{ __('level') }}</span>
            </div>
            <div class="profile-stat-box">
                <img src="{{ getImagePath(@$user->receiverLevel->img) }}" style="height: 28px;">
                <span class="profile-stat-label">{{ __('Receiver Level') }}</span>
            </div>
           
            <div class="profile-stat-box">
                 <img src="{{ getImagePath(@$user->chargeLevel->img) }}" style="height: 28px;">
                <span class="profile-stat-label">{{ __('charge level') }}</span>
            </div>
        </div>

        <div class="profile-badges-row">
            <span class="meta-label">{{__('badges')}}:</span>
            {!! @$user->userBadge() !!}
        </div>
        <div class="profile-badges-row">
            <span class="meta-label">{{__('type')}}:</span>
            {!! @$user->userBadgeTop() !!}
        </div>
    </div>


    @php
        $activeTab = request('tab', 'packs');

    @endphp
        <!-- Navigation Tabs -->
    <div class="agency-tabs">

        <a href="?tab=packs"
           class="tab-btn {{ $activeTab === 'packs' ? 'active' : '' }}"
           data-target="packs-tab">
            {{ __('packs') }}
        </a>

        <a href="?tab=vips" class="tab-btn {{ $activeTab == 'vips' ? 'active' : '' }}"
           data-target="vips-tab">{{ __('vips') }}</a>

        @if (\Encore\Admin\Facades\Admin::user()->can('level-switch-' . 'users') || \Encore\Admin\Facades\Admin::user()->can('*'))
            <a href="?tab=level" class="tab-btn" data-target="level-tab">{{ __('level') }}</a>
        @endif
        @if (\Encore\Admin\Facades\Admin::user()->can('salary-switch-' . 'users') || \Encore\Admin\Facades\Admin::user()->can('*'))
            <a href="?tab=salary" class="tab-btn {{ $activeTab == 'salary' ? 'active' : '' }}"
               data-target="salary-tab">{{ __('prof_reports') }}</a>
        @endif
        <a href="?tab=charge" class="tab-btn {{ $activeTab == 'charge' ? 'active' : '' }}"
           data-target="charge-tab">{{ __('Charge Reports') }}</a>

        <a href="?tab=gift-log" class="tab-btn {{ $activeTab == 'gift-log' ? 'active' : '' }}"
           data-target="gift-log-tab">{{ __('gifts') }}</a>
        <a href="?tab=user-agency" class="tab-btn {{ request('tab') == 'user-agency' ? 'active' : '' }}"
           data-target="user-agency-tab">{{ __('Agency join logs') }}</a>
        <a href="?tab=user-coins" class="tab-btn {{ request('tab') == 'user-coins' ? 'active' : '' }}"
           data-target="user-coins-tab">{{ __('User Coins') }}</a>
        <a href="?tab=badges" class="tab-btn {{ $activeTab == 'badges' ? 'active' : '' }}"
           data-target="badges-tab">{{ __('badges') }}</a>

        <a href="?tab=wallet_logs" class="tab-btn {{ $activeTab == 'wallet_logs' ? 'active' : '' }}"
           data-target="wallet-logs-tab">  {{ __('wallet-transactions') }} </a>
    </div>
    {{-- tab-loading removed - tabs now switch client-side without reload --}}

    <!-- packs Section -->

    <div class="tab-content {{ $activeTab === 'packs' ? '' : 'd-none' }}" id="packs-tab">
        <div class="card" style="border: none; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); overflow: hidden;">
            <div class="card-header" style="background: var(--gradient-3); padding: 18px 24px; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                            <i class="fas fa-box-open" style="font-size: 18px; color: var(--on-color);"></i>
                        </div>
                        <h4 class="card-title" style="text-align: start; margin: 0; color: var(--on-color); font-weight: 700; font-size: 17px; letter-spacing: 0.3px;">{{ __('pack') }}</h4>
                    </div>
                    @if($packs && $packs->count())
                        <span style="background: rgba(255,255,255,0.2); color: var(--on-color); padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; backdrop-filter: blur(4px);">
                            {{ $packs->total() }} {{ __('total') }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="box-body" style="padding: 12px 20px; background: var(--surface-raised); border-bottom: 1px solid var(--border);">
                <div class="nav-scroll-container">
                    <ul class="nav nav-pills" style="gap: 6px;">
                        @foreach($types as $id => $name)
                            <li class="{{ $type == $id ? 'active' : '' }}">
                                <a href="{{ request()->fullUrlWithQuery(['type' => $id, 'pack_page' => 1]) }}"
                                   class="charge_action" style="border-radius: 8px; padding: 7px 16px; font-size: 12px; font-weight: 600;">
                                    {{ __($name) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="table-responsive">
                <div class="box-body" style="padding: 0;">
                    <table class="table table-hover align-middle data-table" id="pack" style="margin-bottom: 0; border-collapse: separate; border-spacing: 0;">
                        <thead>
                        <tr style="background: var(--chip-info-bg);">
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                <i class="fas fa-hashtag" style="margin-inline-end: 4px; opacity: 0.5;"></i>#
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                <i class="fas fa-user-edit" style="margin-inline-end: 4px; color: var(--info);"></i>{{ __('created by') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                <i class="fas fa-layer-group" style="margin-inline-end: 4px; color: var(--accent);"></i>{{ __('get type') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                <i class="fas fa-tag" style="margin-inline-end: 4px; color: var(--accent);"></i>{{ __('type') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                <i class="fas fa-image" style="margin-inline-end: 4px; color: var(--danger);"></i>{{ __('img') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                <i class="fas fa-clock" style="margin-inline-end: 4px; color: var(--danger);"></i>{{ __('expire') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                <i class="fas fa-exchange-alt" style="margin-inline-end: 4px; color: var(--info);"></i>{{ __('receive_type') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                <i class="fas fa-cog" style="margin-inline-end: 4px; opacity: 0.5;"></i>{{ __('action') }}
                            </th>
                        </tr>
                        </thead>
                        @if($packs && $packs->count())

                            <tbody>
                            @foreach($packs as $index => $pack)
                                @php
                                    $path = @$pack->ware?->show_img ?? '';

                                    $admin = null;

                                    if ($pack->vip_user_id && optional($pack->userVip)->admin) {
                                        $admin = $pack->userVip->admin;
                                    } elseif ($pack->dash_user_id && optional($pack)->admin) {
                                        $admin = $pack->admin;
                                    }

                                    $image = optional($admin)->avatar ?? '';
                                    $defaultImage = asset("images/businessman-icon.jpg");
                                    $imagePath = getImagePath($image);
                                    $image = isImageExists($imagePath) ? $imagePath : $defaultImage;

                                    $nameRaw = optional($admin)->name;
                                    $name = is_array($nameRaw) ? reset($nameRaw) : (string) $nameRaw;

                                    $uid = optional($admin)->id ?? 0;
                                    $url = $admin ? url("admin/auth/users/" . $uid) : '#';
                                @endphp
                                <tr style="transition: all 0.2s ease; border-bottom: 1px solid var(--surface-raised);"
                                    onmouseover="this.style.backgroundColor='var(--chip-info-bg)'" onmouseout="this.style.backgroundColor='transparent'">
                                    <td style="padding: 14px 16px; font-weight: 600; color: var(--text-secondary); font-size: 13px;">
                                        <span style="background: var(--surface-raised); padding: 3px 10px; border-radius: 6px;">
                                            {{ $packs->firstItem() + $index }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        @if ($admin && @$pack->receive_type == 'wares-dash-dedicate')
                                            <a href="{{ $url ?? '#' }}" target="_blank"
                                               style="display: inline-flex; align-items: center; text-decoration: none; gap: 8px; background: var(--surface-raised); padding: 4px 10px; border-radius: 8px; border: 1px solid var(--border);">
                                                <img src="{{ $image }}" width="30" height="30"
                                                     style="object-fit: cover; border-radius: 50%; border: 2px solid var(--chip-info-bg);">
                                                <span style="font-weight: 600; color: var(--info); font-size: 12px;">{{ $name }} <small style="color:var(--text-muted);">(id:{{ $uid }})</small></span>
                                            </a>
                                        @else
                                            <span style="color: var(--border); font-size: 12px;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--chip-success-bg); color: var(--success); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                            <i class="fas fa-inbox" style="font-size: 10px;"></i>
                                            {{ $pack->getTypeGet() }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--accent-soft); color: var(--accent); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                            <i class="fas fa-cube" style="font-size: 10px;"></i>
                                            {{ $pack->getType() }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <img src="{{ getImagePath(@$path) }}" width="38" height="38"
                                             style="object-fit: cover; border-radius: 10px; border: 2px solid var(--border); box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        @php
                                            $expireDisplay = (!empty($pack->expire) && $pack->expire !== '0') ? \Carbon\Carbon::parse($pack->expire)->format('Y-m-d H:i:s') : $pack->days;
                                            $isExpired = (!empty($pack->expire) && $pack->expire !== '0' && \Carbon\Carbon::parse($pack->expire)->isPast());
                                        @endphp
                                        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
                                            {{ $isExpired ? 'background: var(--chip-danger-bg); color: var(--danger);' : 'background: var(--chip-success-bg); color: var(--success);' }}">
                                            <i class="fas {{ $isExpired ? 'fa-times-circle' : 'fa-check-circle' }}" style="font-size: 11px;"></i>
                                            {{ $expireDisplay }}
                                        </div>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <div style="display: flex; flex-direction: column; gap: 6px;">
                                            @php
                                                $rType = @$pack->receive_type ?? '';
                                                $typeBg = 'var(--chip-info-bg)'; $typeColor = 'var(--info)'; $typeIcon = 'fa-tag';
                                                if (Str::contains($rType, 'send')) { $typeBg = 'var(--chip-info-bg)'; $typeColor = 'var(--info)'; $typeIcon = 'fa-paper-plane'; }
                                                elseif ($rType === 'wares-dash-dedicate') { $typeBg = 'var(--chip-success-bg)'; $typeColor = 'var(--success)'; $typeIcon = 'fa-user-shield'; }
                                                elseif ($rType === 'purchase') { $typeBg = 'var(--chip-warning-bg)'; $typeColor = 'var(--warning)'; $typeIcon = 'fa-shopping-cart'; }
                                            @endphp
                                            <span style="display: inline-flex; align-items: center; gap: 5px; background: {{ $typeBg }}; color: {{ $typeColor }}; padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px; width: fit-content;">
                                                <i class="fas {{ $typeIcon }}" style="font-size: 10px;"></i>
                                                {{ $rType }}
                                            </span>

                                            @if(@$pack->sender && Str::contains(@$pack->receive_type, 'send'))
                                                @php
                                                    $name = @$pack->sender->name ?? 'Unknown User';
                                                    $showUrl = url("admin/users/" . @$pack->sender->id);
                                                @endphp
                                                <a href="{{ $showUrl }}"
                                                   style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; background: var(--chip-info-bg); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--chip-info-bg); transition: all 0.2s;">
                                                    <i class="fas fa-user" style="font-size: 10px; color: var(--info);"></i>
                                                    <span style="font-size: 11px; color: var(--text-secondary); font-weight: 600;">sender:</span>
                                                    <span style="font-size: 12px; color: var(--info); font-weight: 700;">{{ $name }}</span>
                                                </a>
                                            @elseif($pack->receive_type == 'wares-dash-dedicate' && $pack->admin)
                                                @php
                                                    $name = @$pack->admin->name ?? 'Unknown User';
                                                    $showUrl = url("admin/auth/users/" . @$pack->admin->id);
                                                @endphp
                                                <a href="{{ $showUrl }}"
                                                   style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; background: var(--chip-success-bg); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--chip-success-bg); transition: all 0.2s;">
                                                    <i class="fas fa-user-shield" style="font-size: 10px; color: var(--success);"></i>
                                                    <span style="font-size: 11px; color: var(--text-secondary); font-weight: 600;">admin:</span>
                                                    <span style="font-size: 12px; color: var(--success); font-weight: 700;">{{ $name }}</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>

                                    <td style="padding: 14px 16px;">
                                        <div style="display: inline-flex; gap: 6px; align-items: center;">
                                            <button class="btn btn-sm edit_item_model_btn"
                                                    data-id="{{ @$pack->id }}"
                                                    style="background: var(--chip-info-bg); color: var(--info); border: 1px solid var(--chip-info-bg); border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px;"
                                                    onmouseover="this.style.background='var(--info)'; this.style.color='var(--surface)'; this.style.borderColor='var(--info)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)'"
                                                    onmouseout="this.style.background='var(--chip-info-bg)'; this.style.color='var(--info)'; this.style.borderColor='var(--chip-info-bg)'; this.style.transform='none'; this.style.boxShadow='none'">
                                                <i class="fas fa-clock" style="font-size: 11px;"></i> {{ __('dashboard.free') }}
                                            </button>
                                            <button class="btn btn-sm delete-btn" data-id="{{ @$pack->id }}"
                                                    style="background: var(--chip-danger-bg); color: var(--danger); border: 1px solid var(--chip-danger-bg); border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px;"
                                                    onmouseover="this.style.background='var(--danger)'; this.style.color='var(--surface)'; this.style.borderColor='var(--danger)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)'"
                                                    onmouseout="this.style.background='var(--chip-danger-bg)'; this.style.color='var(--danger)'; this.style.borderColor='var(--chip-danger-bg)'; this.style.transform='none'; this.style.boxShadow='none'">
                                                <i class="fas fa-trash-alt" style="font-size: 11px;"></i> {{ __('dashboard.delete') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        @else
                            <tbody>
                                <tr>
                                    <td colspan="8" style="padding: 48px 24px; text-align: center; border: none;">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                            <div style="width: 64px; height: 64px; background: var(--chip-info-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-box-open" style="font-size: 28px; color: var(--info); opacity: 0.5;"></i>
                                            </div>
                                            <p style="margin: 0; color: var(--text-muted); font-size: 14px; font-weight: 500;">{{ __('No packs found') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @endif

                    </table>
                </div>
            </div>

            @if($packs && $packs->count())
                <div class="pagination-wrapper" style="padding: 16px 24px; border-top: 1px solid var(--surface-raised);">
                    {{ $packs?->appends([
                         'type'        => $type,
                        'vip_page' => $userVips?->currentPage(),
                        'salary_page' => $salaries?->currentPage(),
                        'gift_page' => $giftSLogs?->currentPage(),
                    ])->links('vendor.pagination.default') }}
                </div>
            @endif


        </div>

    </div>

    <!-- vips Section -->

    <div class="tab-content {{ $activeTab == 'vips' ? '' : 'd-none' }}" id="vips-tab">
        <div class="card" style="border: none; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); overflow: hidden;">
            <div class="card-header" style="background: var(--gradient-warning); padding: 18px 24px; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.25); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                            <i class="fas fa-crown" style="font-size: 18px; color: var(--on-color);"></i>
                        </div>
                        <h4 class="card-title" style="text-align: start; margin: 0; color: var(--on-color); font-weight: 700; font-size: 17px; letter-spacing: 0.3px;">{{ __('vips') }}</h4>
                    </div>
                    @if($userVips && $userVips->count())
                        <span style="background: rgba(255,255,255,0.25); color: var(--on-color); padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; backdrop-filter: blur(4px);">
                            {{ $userVips->total() }} {{ __('total') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <div class="box-body" style="padding: 0;">
                    <table class="table table-hover align-middle data-table" id="vip" style="margin-bottom: 0; border-collapse: separate; border-spacing: 0;">
                        <thead>
                        <tr style="background: var(--chip-warning-bg);">
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                <i class="fas fa-hashtag" style="margin-inline-end: 4px; opacity: 0.5;"></i>#
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                <i class="fas fa-star" style="margin-inline-end: 4px; color: var(--warning);"></i>{{ __('level') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                <i class="fas fa-clock" style="margin-inline-end: 4px; color: var(--danger);"></i>{{ __('expire') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                <i class="fas fa-cubes" style="margin-inline-end: 4px; color: var(--accent);"></i>{{ __('qty') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                <i class="fas fa-coins" style="margin-inline-end: 4px; color: var(--success);"></i>{{ __('total Price') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                <i class="fas fa-exchange-alt" style="margin-inline-end: 4px; color: var(--info);"></i>{{ __('receive_type') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                <i class="fas fa-cog" style="margin-inline-end: 4px; opacity: 0.5;"></i>{{ __('action') }}
                            </th>
                        </tr>
                        </thead>
                        @if($userVips && $userVips->count())
                            <tbody>
                            @foreach($userVips as $index => $userVip)
                                <tr style="transition: all 0.2s ease; border-bottom: 1px solid var(--surface-raised);"
                                    onmouseover="this.style.backgroundColor='var(--chip-warning-bg)'" onmouseout="this.style.backgroundColor='transparent'">
                                    <td style="padding: 14px 16px; font-weight: 600; color: var(--text-secondary); font-size: 13px;">
                                        <span style="background: var(--surface-raised); padding: 3px 10px; border-radius: 6px;">
                                            {{ $index + 1 + (($userVips->currentPage() - 1) * $userVips->perPage()) }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <div style="display: inline-flex; align-items: center; gap: 6px; background: var(--chip-warning-bg); padding: 5px 14px; border-radius: 20px; font-weight: 700; color: var(--warning); font-size: 13px;">
                                            <i class="fas fa-crown" style="font-size: 11px; color: var(--warning);"></i>
                                            {{ $userVip->level }}
                                        </div>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        @php
                                            $expireDisplay = (!empty($userVip->expire) && $userVip->expire != '0')
                                                ? \Carbon\Carbon::parse($userVip->expire)->format('Y-m-d H:i:s')
                                                : $userVip->days;
                                            $isExpired = (!empty($userVip->expire) && $userVip->expire != '0' && \Carbon\Carbon::parse($userVip->expire)->isPast());
                                        @endphp
                                        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
                                            {{ $isExpired ? 'background: var(--chip-danger-bg); color: var(--danger);' : 'background: var(--chip-success-bg); color: var(--success);' }}">
                                            <i class="fas {{ $isExpired ? 'fa-times-circle' : 'fa-check-circle' }}" style="font-size: 11px;"></i>
                                            {{ $expireDisplay }}
                                        </div>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span style="font-weight: 700; color: var(--text-primary); font-size: 14px;">{{ @$userVip->qty ?? 0 }}</span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <div style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700; color: var(--success); font-size: 14px;">
                                            <i class="fas fa-coins" style="font-size: 11px; color: var(--warning);"></i>
                                            {{ @$userVip->total ?? 0 }}
                                        </div>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <div style="display: flex; flex-direction: column; gap: 6px;">
                                            {{-- Receive type badge --}}
                                            @php
                                                $rType = @$userVip->receive_type ?? '';
                                                $typeBg = 'var(--chip-info-bg)'; $typeColor = 'var(--info)'; $typeIcon = 'fa-tag';
                                                if ($rType === 'send-vip') { $typeBg = 'var(--chip-info-bg)'; $typeColor = 'var(--info)'; $typeIcon = 'fa-paper-plane'; }
                                                elseif ($rType === 'admin-dedicate') { $typeBg = 'var(--chip-success-bg)'; $typeColor = 'var(--success)'; $typeIcon = 'fa-user-shield'; }
                                                elseif ($rType === 'purchase') { $typeBg = 'var(--chip-warning-bg)'; $typeColor = 'var(--warning)'; $typeIcon = 'fa-shopping-cart'; }
                                            @endphp
                                            <span style="display: inline-flex; align-items: center; gap: 5px; background: {{ $typeBg }}; color: {{ $typeColor }}; padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px; width: fit-content;">
                                                <i class="fas {{ $typeIcon }}" style="font-size: 10px;"></i>
                                                {{ $rType }}
                                            </span>

                                            {{-- If send-vip, show sender below --}}
                                            @if(@$userVip->receive_type === 'send-vip' && @$userVip->sender)
                                                @php
                                                    $name = @$userVip->sender->name ?? 'Unknown User';
                                                    $showUrl = url("admin/users/" . @$userVip->sender->id);
                                                @endphp
                                                <a href="{{ $showUrl }}"
                                                   style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; background: var(--chip-info-bg); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--chip-info-bg); transition: all 0.2s;">
                                                    <i class="fas fa-user" style="font-size: 10px; color: var(--info);"></i>
                                                    <span style="font-size: 11px; color: var(--text-secondary); font-weight: 600;">sender:</span>
                                                    <span style="font-size: 12px; color: var(--info); font-weight: 700;">{{ $name }}</span>
                                                </a>
                                            @endif
                                            @if(@$userVip->receive_type === 'admin-dedicate' && @$userVip->admin)
                                                @php
                                                    $name = @$userVip->admin->name ?? 'Unknown Admin';
                                                    $showUrl = url("admin/auth/users/" . @$userVip->admin->id);
                                                @endphp
                                                <a href="{{ $showUrl }}"
                                                   style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; background: var(--chip-success-bg); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--chip-success-bg); transition: all 0.2s;">
                                                    <i class="fas fa-user-shield" style="font-size: 10px; color: var(--success);"></i>
                                                    <span style="font-size: 11px; color: var(--text-secondary); font-weight: 600;">admin:</span>
                                                    <span style="font-size: 12px; color: var(--success); font-weight: 700;">{{ $name }}</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>

                                    <td style="padding: 14px 16px;">
                                        <button class="btn btn-sm delete-vip-btn" data-id="{{ @$userVip->id }}"
                                                style="background: var(--chip-danger-bg); color: var(--danger); border: 1px solid var(--chip-danger-bg); border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px;"
                                                onmouseover="this.style.background='var(--danger)'; this.style.color='var(--surface)'; this.style.borderColor='var(--danger)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)'"
                                                onmouseout="this.style.background='var(--chip-danger-bg)'; this.style.color='var(--danger)'; this.style.borderColor='var(--chip-danger-bg)'; this.style.transform='none'; this.style.boxShadow='none'">
                                            <i class="fas fa-trash-alt" style="font-size: 11px;"></i> {{ __('dashboard.delete') }}
                                        </button>
                                    </td>

                                </tr>
                            @endforeach
                            </tbody>
                        @else
                            <tbody>
                                <tr>
                                    <td colspan="7" style="padding: 48px 24px; text-align: center; border: none;">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                            <div style="width: 64px; height: 64px; background: var(--chip-warning-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-crown" style="font-size: 28px; color: var(--warning); opacity: 0.5;"></i>
                                            </div>
                                            <p style="margin: 0; color: var(--text-muted); font-size: 14px; font-weight: 500;">{{ __('No VIP records found') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @endif
                    </table>

                    @if($userVips && $userVips->count())
                        <div class="pagination-container" style="padding: 16px 24px; border-top: 1px solid var(--surface-raised);">
                            {{ $userVips->appends([
                                'tab' => 'vips',
                                'vip_page' => $userVips->currentPage(),
                            ])->links('vendor.pagination.bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </div>


        </div>
    </div>

    <div class="tab-content" id="salary-tab">
        <div class="card" style="border: none; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); overflow: hidden;">
            <div class="card-header" style="background: var(--gradient-4); padding: 18px 24px; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                            <i class="fas fa-wallet" style="font-size: 18px; color: var(--on-color);"></i>
                        </div>
                        <h4 class="card-title" style="text-align: start; margin: 0; color: var(--on-color); font-weight: 700; font-size: 17px; letter-spacing: 0.3px;">{{ __('user wallet') }}</h4>
                    </div>
                    @if($salaries && $salaries->count())
                        <span style="background: rgba(255,255,255,0.2); color: var(--on-color); padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; backdrop-filter: blur(4px);">
                            {{ $salaries->total() }} {{ __('total') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="box-body p-3">
                    <div class="card mb-4" style="border: 1px solid var(--chip-success-bg); border-radius: 10px; box-shadow: none;">
                        <div class="card-body">
                            <form method="GET" action="{{ url('admin/users/' . $user->id) }}"
                              class="form-horizontal">
                                <input type="hidden" name="tab" value="salary">
                                <div class="filter-container" style="background: var(--chip-success-bg); border: 1px solid var(--chip-success-bg); border-radius: 10px;">
                                    <div class="filter-content">
                                        <div class="filter-group">
                                            <label class="form-label" style="font-weight: 600; font-size: 12px; color: var(--success);">
                                                <i class="fas fa-calendar-alt" style="margin-inline-end: 4px; color: var(--success);"></i>{{ __('year') }}
                                            </label>
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-addon" style="background: var(--chip-success-bg); border-color: var(--chip-success-bg);">
                                                    <i class="fa fa-calendar" style="color: var(--success);"></i>
                                                </div>
                                                <select class="form-control form-select year" name="year">
                                                    <option value="">{{ __('Select Year') }}</option>
                                                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                                                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div class="filter-group">
                                            <label class="form-label" style="font-weight: 600; font-size: 12px; color: var(--success);">
                                                <i class="fas fa-calendar-day" style="margin-inline-end: 4px; color: var(--success);"></i>{{ __('month') }}
                                            </label>
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-addon" style="background: var(--chip-success-bg); border-color: var(--chip-success-bg);">
                                                    <i class="fa fa-calendar" style="color: var(--success);"></i>
                                                </div>
                                                <select class="form-control form-select month" name="month">
                                                    <option value="">{{ __('Select Month') }}</option>
                                                    @for($m = 1; $m <= 12; $m++)
                                                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ $m }} - {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                        <div class="filter-actions">
                                            <button class="btn btn-sm" style="background: var(--gradient-4); color: var(--on-color); border: none; border-radius: 8px; padding: 8px 18px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;"
                                                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)'"
                                                    onmouseout="this.style.transform='none'; this.style.boxShadow='none'">
                                                <i class="fa fa-search"></i> {{__('Search')}}
                                            </button>
                                            <a href="{{ url('admin/users/' . $user->id. '?'.'tab=salary') }}"
                                               class="btn btn-default btn-sm" style="border-radius: 8px; padding: 8px 18px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                                <i class="fa fa-undo"></i> {{__('Reset')}}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                <div class="table-responsive">
                    <div class="box-body" style="padding: 0;">
                        <table class="table table-hover align-middle data-table" style="margin-bottom: 0; border-collapse: separate; border-spacing: 0;">
                            <thead>
                            <tr style="background: var(--chip-success-bg);">
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                    <i class="fas fa-hashtag" style="margin-inline-end: 4px; opacity: 0.5;"></i>#
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                    <i class="fas fa-building" style="margin-inline-end: 4px; color: var(--success);"></i>{{ __('agency') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                    <i class="fas fa-money-bill-wave" style="margin-inline-end: 4px; color: var(--success);"></i>{{ __('salary') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                    <i class="fas fa-hand-holding-usd" style="margin-inline-end: 4px; color: var(--danger);"></i>{{ __('Withdraw') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                    <i class="fas fa-wallet" style="margin-inline-end: 4px; color: var(--accent);"></i>{{ __('net salary') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                    <i class="fas fa-calendar-check" style="margin-inline-end: 4px; color: var(--info);"></i>{{ __('days') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                    <i class="fas fa-clock" style="margin-inline-end: 4px; color: var(--warning);"></i>{{ __('hours') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                    <i class="fas fa-camera" style="margin-inline-end: 4px; color: var(--danger);"></i>{{ __('Moments') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                    <i class="fas fa-film" style="margin-inline-end: 4px; color: var(--danger);"></i>{{ __('Reels') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                    <i class="fas fa-gem" style="margin-inline-end: 4px; color: var(--info);"></i>{{ __('diamonds') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                    <i class="fas fa-calendar" style="margin-inline-end: 4px; color: var(--text-secondary);"></i>{{ __('date') }}
                                </th>
                            </tr>
                            </thead>
                            @if($salaries && $salaries->count())
                                <tbody>
                                @foreach($salaries as $index => $salary)
                                    @php
                                        $agency = $salary->agency;
                                        $name = $agency->name ?? '';
                                        $path = @$agency->img;
                                        $defaultImage = asset("images/icon-agency.jpg");
                                        $url = getImagePath($path) ?? $defaultImage;
                                        if (!isImageExists($url)) { $url = $defaultImage; }
                                        $image = handleShowImageWithTypes($user->id, $url, 40, 40);
                                        $profileUrl = route('admin.agency.profile', ['id' => @$agency->id ?? 0]);
                                        $extras = json_decode($salary->extras, true);
                                        $moment = $extras['moment'] ?? [];
                                        $reel = $extras['reel'] ?? [];
                                        $momentUpload = $moment['upload'] ?? '0/0';
                                        $momentLikes = $moment['likes'] ?? '0/0';
                                        $momentComments = $moment['comments'] ?? '0/0';
                                        $reelUpload = $reel['upload'] ?? '0/0';
                                        $reelLikes = $reel['likes'] ?? '0/0';
                                        $reelComments = $reel['comments'] ?? '0/0';
                                    @endphp
                                    <tr style="transition: all 0.2s ease; border-bottom: 1px solid var(--surface-raised);"
                                        onmouseover="this.style.backgroundColor='var(--chip-success-bg)'" onmouseout="this.style.backgroundColor='transparent'">
                                        <td style="padding: 14px 16px;">
                                            <span style="background: var(--surface-raised); padding: 3px 10px; border-radius: 6px; font-weight: 600; color: var(--text-secondary); font-size: 13px;">
                                                {{ $index + 1 + (($salaries->currentPage() - 1) * $salaries->perPage()) }}
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <a href="{{ $profileUrl }}" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: var(--surface-raised); padding: 4px 10px; border-radius: 8px; border: 1px solid var(--border);">
                                                {!! $image !!}
                                                <div style="display: flex; flex-direction: column;">
                                                    <span style="font-weight: 600; color: var(--success); font-size: 13px;">{{ $name }}</span>
                                                    <small style="color: var(--text-muted); font-size: 11px;">ID: {{ @$agency->id ?? 0 }}</small>
                                                </div>
                                            </a>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--chip-success-bg); color: var(--success); padding: 4px 12px; border-radius: 6px; font-weight: 700; font-size: 13px;">
                                                <i class="fas fa-dollar-sign" style="font-size: 10px;"></i>
                                                {{ number_format(truncateAndTrim($salary->sallary)) }}
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--chip-danger-bg); color: var(--danger); padding: 4px 12px; border-radius: 6px; font-weight: 700; font-size: 13px;">
                                                <i class="fas fa-minus-circle" style="font-size: 10px;"></i>
                                                {{ number_format($salary->cut_amount) }}
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--chip-success-bg); color: var(--success); padding: 5px 14px; border-radius: 20px; font-weight: 800; font-size: 13px; border: 1px solid var(--chip-success-bg);">
                                                <i class="fas fa-check-circle" style="font-size: 11px;"></i>
                                                {{ number_format(truncateAndTrim($salary->sallary - $salary->cut_amount)) }}
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="font-weight: 700; color: var(--text-primary); font-size: 14px;">{{ $salary->days }}</span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; font-weight: 600; color: var(--warning); font-size: 13px;">
                                                <i class="fas fa-hourglass-half" style="font-size: 10px;"></i>
                                                {{ $salary->hours }}
                                            </span>
                                        </td>
                                        <td style="padding: 14px 12px;">
                                            <div style="background: var(--chip-danger-bg); border: 1px solid var(--chip-danger-bg); border-radius: 8px; padding: 8px 10px; font-size: 11px; line-height: 1.8; min-width: 90px;">
                                                <div><i class="fas fa-upload" style="width: 14px; color: var(--danger);"></i> <b>{{ $momentUpload }}</b></div>
                                                <div><i class="fas fa-heart" style="width: 14px; color: var(--danger);"></i> <b>{{ $momentLikes }}</b></div>
                                                <div><i class="fas fa-comment" style="width: 14px; color: var(--accent);"></i> <b>{{ $momentComments }}</b></div>
                                            </div>
                                        </td>
                                        <td style="padding: 14px 12px;">
                                            <div style="background: var(--chip-danger-bg); border: 1px solid var(--chip-danger-bg); border-radius: 8px; padding: 8px 10px; font-size: 11px; line-height: 1.8; min-width: 90px;">
                                                <div><i class="fas fa-upload" style="width: 14px; color: var(--danger);"></i> <b>{{ $reelUpload }}</b></div>
                                                <div><i class="fas fa-heart" style="width: 14px; color: var(--danger);"></i> <b>{{ $reelLikes }}</b></div>
                                                <div><i class="fas fa-comment" style="width: 14px; color: var(--danger);"></i> <b>{{ $reelComments }}</b></div>
                                            </div>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <div style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700; color: var(--info); font-size: 14px;">
                                                <i class="fas fa-gem" style="font-size: 11px; color: var(--info);"></i>
                                                {{ $salary->achieved_diamond }}
                                            </div>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--surface-sunken); color: var(--text-secondary); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                                <i class="fas fa-calendar-alt" style="font-size: 10px; color: var(--text-muted);"></i>
                                                {{ $salary->month .'/'. $salary->year }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            @else
                                <tbody>
                                    <tr>
                                        <td colspan="11" style="padding: 48px 24px; text-align: center; border: none;">
                                            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                                <div style="width: 64px; height: 64px; background: var(--chip-success-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-wallet" style="font-size: 28px; color: var(--success); opacity: 0.5;"></i>
                                                </div>
                                                <p style="margin: 0; color: var(--text-muted); font-size: 14px; font-weight: 500;">{{ __('No salary records found') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>

                        @if($salaries && $salaries->count())
                            <div class="pagination-container" style="padding: 16px 24px; border-top: 1px solid var(--surface-raised);">
                                {{ $salaries->appends([
                                    'tab' => 'salary',
                                    'year' => request('year'),
                                    'month' => request('month'),
                                ])->links('vendor.pagination.bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="tab-content" id="level-tab">
        <div class="card" style="border: none; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); overflow: hidden;">
            <div class="card-header" style="background: var(--gradient-3); padding: 18px 24px; border: none;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                        <i class="fas fa-layer-group" style="font-size: 18px; color: var(--on-color);"></i>
                    </div>
                    <h4 class="card-title" style="text-align: start; margin: 0; color: var(--on-color); font-weight: 700; font-size: 17px; letter-spacing: 0.3px;">{{ __('level') }}</h4>
                </div>
            </div>
            <div class="box-body p-3">
                <div class="card mb-4" style="border: 1px solid var(--chip-info-bg); border-radius: 12px; box-shadow: none; overflow: hidden;">
                    <div class="card-body" style="padding: 28px;">
                        <form action="{{ url('/admin/edit-level') }}" id="user_level_update_form" method="POST"
                              enctype="multipart/form-data" class="level-form" style="padding: 0;">
                            @csrf
                            <input type="hidden" name="id" class="item_id" value="{{ $user->id }}">
                            <div class="row" style="gap: 0;">
                                <div class="col-lg-6 mb-4">
                                    <div style="background: var(--chip-info-bg); border: 1px solid var(--chip-info-bg); border-radius: 12px; padding: 20px; transition: all 0.2s;"
                                         onmouseover="this.style.borderColor='var(--info)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.12)'"
                                         onmouseout="this.style.borderColor='var(--chip-info-bg)'; this.style.boxShadow='none'">
                                        <label class="form-label" style="font-weight: 700; font-size: 13px; color: var(--info); display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                            <div style="width: 32px; height: 32px; background: var(--gradient-3); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-arrow-up" style="color: var(--on-color); font-size: 14px;"></i>
                                            </div>
                                            {{ __('Sender Level') }}
                                        </label>
                                        <input type="number" min="0" value="{{ $user->total_sender_level }}"
                                               class="form-control" id="total_sender_level" name="total_sender_level" required
                                               style="border: 2px solid var(--chip-info-bg); border-radius: 10px; padding: 12px 16px; font-size: 16px; font-weight: 700; color: var(--info); background: var(--surface); transition: all 0.2s;"
                                               onfocus="this.style.borderColor='var(--info)'; this.style.boxShadow='0 0 0 3px rgba(0,0,0,0.12)'"
                                               onblur="this.style.borderColor='var(--chip-info-bg)'; this.style.boxShadow='none'">
                                    </div>
                                </div>

                                <div class="col-lg-6 mb-4">
                                    <div style="background: var(--accent-soft); border: 1px solid var(--accent-soft); border-radius: 12px; padding: 20px; transition: all 0.2s;"
                                         onmouseover="this.style.borderColor='var(--accent)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.12)'"
                                         onmouseout="this.style.borderColor='var(--accent-soft)'; this.style.boxShadow='none'">
                                        <label class="form-label" style="font-weight: 700; font-size: 13px; color: var(--accent); display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                            <div style="width: 32px; height: 32px; background: var(--gradient-1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-arrow-down" style="color: var(--on-color); font-size: 14px;"></i>
                                            </div>
                                            {{ __('Received Level') }}
                                        </label>
                                        <input type="number" min="0" value="{{ $user->total_received_level }}"
                                               class="form-control" id="total_received_level" name="total_received_level" required
                                               style="border: 2px solid var(--accent-soft); border-radius: 10px; padding: 12px 16px; font-size: 16px; font-weight: 700; color: var(--accent); background: var(--surface); transition: all 0.2s;"
                                               onfocus="this.style.borderColor='var(--accent)'; this.style.boxShadow='0 0 0 3px rgba(0,0,0,0.12)'"
                                               onblur="this.style.borderColor='var(--accent-soft)'; this.style.boxShadow='none'">
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 8px; border-top: 1px solid var(--surface-sunken); margin-top: 8px;">
                                <button class="btn btn-sm" type="button" data-bs-dismiss="modal"
                                        style="background: var(--surface-sunken); color: var(--text-secondary); border: 1px solid var(--border); border-radius: 8px; padding: 10px 24px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;"
                                        onmouseover="this.style.background='var(--border)'" onmouseout="this.style.background='var(--surface-sunken)'">
                                    <i class="fas fa-times" style="font-size: 11px;"></i> {{ __('cancel') }}
                                </button>
                                <button class="btn btn-sm" type="submit"
                                        style="background: var(--gradient-3); color: var(--on-color); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.12);"
                                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'"
                                        onmouseout="this.style.transform='none'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.12)'">
                                    <i class="fas fa-save" style="font-size: 12px;"></i> {{ __('save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-content {{ $activeTab == 'wallet_logs' ? 'active show' : 'd-none' }}" id="wallet-logs-tab">
        <div class="card" style="border: none; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); overflow: hidden;">
            <div class="card-header" style="background: var(--gradient-4); padding: 18px 24px; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                            <i class="fas fa-receipt" style="font-size: 18px; color: var(--on-color);"></i>
                        </div>
                        <h4 class="card-title" style="text-align: start; margin: 0; color: var(--on-color); font-weight: 700; font-size: 17px; letter-spacing: 0.3px;">{{ __('wallet-transactions') }}</h4>
                    </div>
                    @if($walletLogs && $walletLogs->count())
                        <span style="background: rgba(255,255,255,0.2); color: var(--on-color); padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; backdrop-filter: blur(4px);">
                            {{ $walletLogs->total() }} {{ __('total') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="box-body p-3">
                <div class="card mb-4" style="border: 1px solid var(--chip-success-bg); border-radius: 10px; box-shadow: none;">
                    <div class="card-body">
                        <form method="GET" action="{{ url('admin/users/' . $user->id) }}"
                              class="form-horizontal gift-log-form">
                            <input type="hidden" name="tab" value="wallet_logs">
                            <div class="filter-container" style="background: var(--chip-success-bg); border: 1px solid var(--chip-success-bg); border-radius: 10px;">
                                <div class="filter-content">
                                    <div class="filter-group">
                                        <label class="form-label" style="font-weight: 600; font-size: 12px; color: var(--success);">
                                            <i class="fas fa-calendar-alt" style="margin-inline-end: 4px; color: var(--success);"></i>{{__("year")}}
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-addon" style="background: var(--chip-success-bg); border-color: var(--chip-success-bg);">
                                                <i class="fa fa-calendar" style="color: var(--success);"></i>
                                            </div>
                                            <select class="form-control year wallet-filter-select" name="year">
                                                <option value="">{{ __('Select Year') }}</option>
                                                @for($y = now()->year; $y >= now()->year - 5; $y--)
                                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="filter-group">
                                        <label class="form-label" style="font-weight: 600; font-size: 12px; color: var(--success);">
                                            <i class="fas fa-calendar-day" style="margin-inline-end: 4px; color: var(--success);"></i>{{__('month')}}
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-addon" style="background: var(--chip-success-bg); border-color: var(--chip-success-bg);">
                                                <i class="fa fa-calendar" style="color: var(--success);"></i>
                                            </div>
                                            <select class="form-control month wallet-filter-select" name="month">
                                                <option value="">{{ __('Select Month') }}</option>
                                                @for($m = 1; $m <= 12; $m++)
                                                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ $m }} - {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="filter-actions">
                                        <button class="btn btn-sm" style="background: var(--gradient-4); color: var(--on-color); border: none; border-radius: 8px; padding: 8px 18px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fa fa-search"></i> {{__('Search')}}
                                        </button>
                                        <a href="{{ url('admin/users/' . $user->id. '?'.'tab=wallet_logs') }}"
                                           class="btn btn-default btn-sm" style="border-radius: 8px; padding: 8px 18px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fa fa-undo"></i> {{__('Reset')}}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle data-table" id="walletLogs" style="margin-bottom: 0; border-collapse: separate; border-spacing: 0;">
                        <thead>
                        <tr style="background: var(--chip-success-bg);">
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                <i class="fas fa-hashtag" style="margin-inline-end: 4px; opacity: 0.5;"></i>#
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                <i class="fas fa-exchange-alt" style="margin-inline-end: 4px; color: var(--success);"></i>{{ __('Type') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                <i class="fas fa-coins" style="margin-inline-end: 4px; color: var(--warning);"></i>{{ __('Amount') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                <i class="fas fa-wallet" style="margin-inline-end: 4px; color: var(--info);"></i>{{ __('amount before') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                <i class="fas fa-balance-scale" style="margin-inline-end: 4px; color: var(--success);"></i>{{ __('amount after') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--success); border-bottom: 2px solid var(--chip-success-bg);">
                                <i class="fas fa-calendar" style="margin-inline-end: 4px; color: var(--text-secondary);"></i>{{ __('Created at') }}
                            </th>
                        </tr>
                        </thead>
                        @if($walletLogs && $walletLogs->count())
                            <tbody>
                            @foreach($walletLogs as $index => $log)
                                <tr style="transition: all 0.2s ease; border-bottom: 1px solid var(--surface-raised);"
                                    onmouseover="this.style.backgroundColor='var(--chip-success-bg)'" onmouseout="this.style.backgroundColor='transparent'">
                                    <td style="padding: 14px 16px;">
                                        <span style="background: var(--surface-raised); padding: 3px 10px; border-radius: 6px; font-weight: 600; color: var(--text-secondary); font-size: 13px;">
                                            {{ $walletLogs->firstItem() + $index }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--chip-success-bg); color: var(--success); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                            <i class="fas fa-tag" style="font-size: 10px;"></i>{{ __("wallet." . $log->operation) }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        @php $wAmt = $log->amount; @endphp
                                        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 5px 14px; border-radius: 20px; font-weight: 800; font-size: 13px; border: 1px solid {{ $wAmt < 0 ? 'var(--chip-danger-bg)' : 'var(--chip-success-bg)' }}; background: {{ $wAmt < 0 ? 'var(--chip-danger-bg)' : 'var(--chip-success-bg)' }}; color: {{ $wAmt < 0 ? 'var(--danger)' : 'var(--success)' }};">
                                            <i class="fas {{ $wAmt < 0 ? 'fa-arrow-down' : 'fa-arrow-up' }}" style="font-size: 10px;"></i>
                                            {{ number_format($wAmt, 2) }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span style="font-weight: 700; color: var(--info); font-size: 13px;">{{ number_format($log->before_amount, 2) }}</span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--chip-info-bg); color: var(--info); padding: 4px 12px; border-radius: 6px; font-weight: 700; font-size: 13px;">
                                            <i class="fas fa-coins" style="font-size: 10px;"></i>{{ number_format($log->after_amount, 2) }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--surface-sunken); color: var(--text-secondary); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                            <i class="fas fa-calendar-alt" style="font-size: 10px; color: var(--text-muted);"></i>{{ $log->created_at }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        @else
                            <tbody>
                                <tr>
                                    <td colspan="6" style="padding: 48px 24px; text-align: center; border: none;">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                            <div style="width: 64px; height: 64px; background: var(--chip-success-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-receipt" style="font-size: 28px; color: var(--success); opacity: 0.5;"></i>
                                            </div>
                                            <p style="margin: 0; color: var(--text-muted); font-size: 14px; font-weight: 500;">{{ __('No wallet transactions found') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @endif
                    </table>
                </div>
            </div>

            @if($walletLogs && $walletLogs->count())
                <div class="pagination-wrapper" style="padding: 16px 24px; border-top: 1px solid var(--surface-raised);">
                    {{ $walletLogs?->appends([
                        'tab'         => 'wallet_logs',
                        'wallet_logs_page' => $walletLogs?->currentPage(),
                    ])->links('vendor.pagination.default') }}
                </div>
            @endif
        </div>
    </div>

    <div class="tab-content {{ $activeTab == 'badges' ? 'active show' : 'd-none' }}" id="badges-tab">
        <div class="card" style="border: none; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); overflow: hidden;">
            <div class="card-header" style="background: var(--gradient-danger); padding: 18px 24px; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                            <i class="fas fa-award" style="font-size: 18px; color: var(--on-color);"></i>
                        </div>
                        <h4 class="card-title" style="text-align: start; margin: 0; color: var(--on-color); font-weight: 700; font-size: 17px; letter-spacing: 0.3px;">{{ __('badges') }}</h4>
                    </div>
                    @if($badges && $badges->count())
                        <span style="background: rgba(255,255,255,0.2); color: var(--on-color); padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; backdrop-filter: blur(4px);">
                            {{ $badges->total() }} {{ __('total') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <div class="box-body" style="padding: 0;">
                    <table class="table table-hover align-middle data-table" id="badge" style="margin-bottom: 0; border-collapse: separate; border-spacing: 0;">
                        <thead>
                        <tr style="background: var(--chip-danger-bg);">
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--danger); border-bottom: 2px solid var(--chip-danger-bg);">
                                <i class="fas fa-hashtag" style="margin-inline-end: 4px; opacity: 0.5;"></i>#
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--danger); border-bottom: 2px solid var(--chip-danger-bg);">
                                <i class="fas fa-user-edit" style="margin-inline-end: 4px; color: var(--danger);"></i>{{ __('created by') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--danger); border-bottom: 2px solid var(--chip-danger-bg);">
                                <i class="fas fa-clock" style="margin-inline-end: 4px; color: var(--danger);"></i>{{ __('expire') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--danger); border-bottom: 2px solid var(--chip-danger-bg);">
                                <i class="fas fa-exchange-alt" style="margin-inline-end: 4px; color: var(--accent);"></i>{{ __('receive_type') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--danger); border-bottom: 2px solid var(--chip-danger-bg);">
                                <i class="fas fa-calendar" style="margin-inline-end: 4px; color: var(--text-secondary);"></i>{{ __('created_at') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--danger); border-bottom: 2px solid var(--chip-danger-bg);">
                                <i class="fas fa-cog" style="margin-inline-end: 4px; opacity: 0.5;"></i>{{ __('action') }}
                            </th>
                        </tr>
                        </thead>
                        @if($badges && $badges->count())
                            <tbody>
                            @foreach($badges as $index => $badge)
                                @php
                                    $admin = $badge->admin;
                                    $image = $admin->avatar ?? '';
                                    $defaultImage = asset("images/businessman-icon.jpg");
                                    $imagePath = getImagePath($image);
                                    $image = isImageExists($imagePath) ? $imagePath : $defaultImage;
                                    $nameRaw = optional($admin)->name ?? @$admin->username;
                                    $name = is_array($nameRaw) ? reset($nameRaw) : (string) $nameRaw;
                                    $uid = optional($admin)->id ?? 0;
                                    $url = $admin ? url("admin/auth/users/" . $uid) : '#';
                                @endphp
                                <tr style="transition: all 0.2s ease; border-bottom: 1px solid var(--surface-raised);"
                                    onmouseover="this.style.backgroundColor='var(--chip-danger-bg)'" onmouseout="this.style.backgroundColor='transparent'">
                                    <td style="padding: 14px 16px;">
                                        <span style="background: var(--surface-raised); padding: 3px 10px; border-radius: 6px; font-weight: 600; color: var(--text-secondary); font-size: 13px;">
                                            {{ $badges->firstItem() + $index }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        @if ($admin)
                                            <a href="{{ $url ?? '#' }}" target="_blank"
                                               style="display: inline-flex; align-items: center; text-decoration: none; gap: 8px; background: var(--surface-raised); padding: 4px 10px; border-radius: 8px; border: 1px solid var(--border);">
                                                <img src="{{ $image }}" width="30" height="30"
                                                     style="object-fit: cover; border-radius: 50%; border: 2px solid var(--chip-danger-bg);">
                                                <span style="font-weight: 600; color: var(--danger); font-size: 12px;">{{ $name }} <small style="color:var(--text-muted);">({{ $uid }})</small></span>
                                            </a>
                                        @else
                                            <span style="color: var(--border); font-size: 12px;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        @php
                                            $expireDisplay = (!empty($badge->expire) && $badge->expire !== '0') ? \Carbon\Carbon::parse($badge->expire)->format('Y-m-d H:i:s') : $badge->days;
                                            $isExpired = (!empty($badge->expire) && $badge->expire !== '0' && \Carbon\Carbon::parse($badge->expire)->isPast());
                                        @endphp
                                        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
                                            {{ $isExpired ? 'background: var(--chip-danger-bg); color: var(--danger);' : 'background: var(--chip-success-bg); color: var(--success);' }}">
                                            <i class="fas {{ $isExpired ? 'fa-times-circle' : 'fa-check-circle' }}" style="font-size: 11px;"></i>
                                            {{ $expireDisplay }}
                                        </div>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--accent-soft); color: var(--accent); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                            <i class="fas fa-tag" style="font-size: 10px;"></i>{{ $badge->receive_type }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--surface-sunken); color: var(--text-secondary); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                            <i class="fas fa-calendar-alt" style="font-size: 10px; color: var(--text-muted);"></i>{{ $badge->created_at }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        @if (($badge->expire == 0) || ($badge->expire >= now()->timestamp))
                                            <button class="btn btn-sm delete-badge-btn" data-id="{{ @$badge->id }}"
                                                    style="background: var(--chip-danger-bg); color: var(--danger); border: 1px solid var(--chip-danger-bg); border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px;"
                                                    onmouseover="this.style.background='var(--danger)'; this.style.color='var(--surface)'; this.style.borderColor='var(--danger)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)'"
                                                    onmouseout="this.style.background='var(--chip-danger-bg)'; this.style.color='var(--danger)'; this.style.borderColor='var(--chip-danger-bg)'; this.style.transform='none'; this.style.boxShadow='none'">
                                                <i class="fas fa-trash-alt" style="font-size: 11px;"></i> {{ __('dashboard.delete') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        @else
                            <tbody>
                                <tr>
                                    <td colspan="6" style="padding: 48px 24px; text-align: center; border: none;">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                            <div style="width: 64px; height: 64px; background: var(--chip-danger-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-award" style="font-size: 28px; color: var(--danger); opacity: 0.5;"></i>
                                            </div>
                                            <p style="margin: 0; color: var(--text-muted); font-size: 14px; font-weight: 500;">{{ __('No badges found') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @endif
                    </table>
                </div>
            </div>

            @if($badges && $badges->count())
                <div class="pagination-wrapper" style="padding: 16px 24px; border-top: 1px solid var(--surface-raised);">
                    {{ $badges?->appends([
                        'tab'         => 'badges',
                         'type'        => $type,
                        'badges_page' => $badges?->currentPage(),
                    ])->links('vendor.pagination.default') }}
                </div>
            @endif
        </div>
    </div>

    <div class="tab-content" id="user-agency-tab"
         style="{{ request('tab') == 'user-agency' ? 'display: block;' : 'display: none;' }}">
        <div class="card" style="border: none; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); overflow: hidden;">
            <div class="card-header" style="background: var(--gradient-3); padding: 18px 24px; border: none;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                        <i class="fas fa-history" style="font-size: 18px; color: var(--on-color);"></i>
                    </div>
                    <h4 class="card-title" style="text-align: start; margin: 0; color: var(--on-color); font-weight: 700; font-size: 17px;">{{ __('Agency join logs') }}</h4>
                </div>
            </div>
            <div class="box-body p-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="{{ url('admin/users/' . $user->id) }}" class="form-horizontal user-agency-form"
                              method="GET">
                            <input type="hidden" name="tab" value="user-agency">

                            <input type="hidden" name="user_agency_page"
                                   value="{{ request()->get('user_agency_page', 1) }}">

                            <div class="row mb-3" style="align-items: flex-end;">
                                <!-- From Date -->
                                <div class="col-md-4">
                                    <div class="date-flex-row">
                                        <i class="fa fa-calendar"></i>
                                        <span>{{ __('Join date') }}</span>
                                        <input type="date" class="form-control" id="from_date" name="join_date"
                                               value="{{ request('join_date') }}">
                                    </div>
                                </div>

                                <!-- Buttons -->
                                <div class="col-md-4 d-flex align-items-end justify-content-end" style="gap: 8px;">
                                    <button type="submit" class="btn btn-info btn-sm me-2">
                                        <i class="fa fa-search"></i> {{__('Search')}}
                                    </button>
                                    <a href="{{ url('admin/users/' . $user->id. '?tab=user-agency') }}"
                                       class="btn btn-default btn-sm">
                                        <i class="fa fa-undo"></i> {{__('Reset')}}
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <div class="box-body ">
                        <table class="table table-hover align-middle data-table" id="user-agency" style="margin-bottom: 0; border-collapse: separate; border-spacing: 0;">
                            <thead>
                            <tr style="background: var(--chip-info-bg);">
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                    <i class="fas fa-hashtag" style="margin-inline-end: 4px; opacity: 0.5;"></i>#
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                    <i class="fas fa-building" style="margin-inline-end: 4px; color: var(--info);"></i>{{ __('agency') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                    <i class="fas fa-info-circle" style="margin-inline-end: 4px; color: var(--accent);"></i>{{ __('status') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                    <i class="fas fa-user-slash" style="margin-inline-end: 4px; color: var(--danger);"></i>{{ __('kicked By') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                    <i class="fas fa-shield-alt" style="margin-inline-end: 4px; color: var(--warning);"></i>{{ __('kicked By status') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                    <i class="fas fa-sign-in-alt" style="margin-inline-end: 4px; color: var(--success);"></i>{{ __('Join date') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--info); border-bottom: 2px solid var(--chip-info-bg);">
                                    <i class="fas fa-sign-out-alt" style="margin-inline-end: 4px; color: var(--danger);"></i>{{ __('Leave date') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($userJoinAgencies && $userJoinAgencies->count())
                                @foreach($userJoinAgencies as $index => $userJoinAgency)
                                    @php
                                        $kickedBy = null;
                                        $kickedByName = '';
                                        $kickedByUuid = '';
                                        $kickedByImage = '';
                                        $kickedByUrl = '';
                                        $status = '';
                                    @endphp

                                    @php
                                        $agency = $userJoinAgency->agency;
                                        $name = $agency->name ?? '';
                                        $path = @$agency->img;
                                        $defaultImage = asset("images/icon-agency.jpg");
                                        $url = getImagePath($path) ?? $defaultImage;
                                        if (!isImageExists($url)) {
                                            $url = $defaultImage;
                                        }
                                        $image = "<img src='" . e($url) . "' width='40' height='40' style='object-fit: cover; border-radius: 6px;'>";
                                        $profileUrl = route('admin.agency.profile', ['id' => @$agency->id ?? 0]);
                                    @endphp

                                    @php
                                        if ($userJoinAgency->status == 'kick off'){
                                            if ($userJoinAgency->kicked_by_app){
                                             $status = 'app';
                                             $kickedBy = $userJoinAgency['kickedByApp'];
                                             $kickedByName = $kickedBy->name ?? '';
                                             $kickedByUuid = $kickedBy->uuid ?? '';
                                             $kickedByPath = @$kickedBy->profile?->avatar;
                                             $defaultImage = asset("images/businessman-icon.jpg");
                                             $url = getImagePath($kickedByPath) ?? $defaultImage;
                                             if (!isImageExists($url)) {
                                                 $url = $defaultImage;
                                             }
                                             $kickedByImage = "<img src='" . e($url) . "' width='40' height='40' style='object-fit: cover; border-radius: 6px;'>";
                                             $kickedByUrl = url("admin/users/" . ($kickedBy->id) ?? 0);
                                         }

                                         if ($userJoinAgency->kicked_by_admin){
                                             $status = 'admin';
                                             $kickedBy = $userJoinAgency['kickedByAdmin'];
                                             $kickedByName = $kickedBy->name ?? '';
                                             $kickedByUuid = $kickedBy->id ?? '';
                                             $kickedByPath = @$kickedBy?->avatar;
                                             $defaultImage = asset("images/businessman-icon.jpg");
                                             $url = getImagePath($kickedByPath) ?? $defaultImage;
                                             if (!isImageExists($url)) {
                                                 $url = $defaultImage;
                                             }
                                             $kickedByImage = "<img src='" . e($url) . "' width='40' height='40' style='object-fit: cover; border-radius: 6px;'>";
                                             $kickedByUrl = url("admin/auth/users/".($kickedBy->id ?? 0));
                                         }
                                     }
                                    @endphp

                                    <tr>
                                        <td>{{ $index + 1 + (($userJoinAgencies->currentPage() - 1) * $userJoinAgencies->perPage()) }}</td>
                                        <td>
                                            <a href="{{ $profileUrl }}" style="text-decoration: none; color: inherit;">
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    {!! $image !!}
                                                    <div style="display: flex; flex-direction: column;">
                                                        <span
                                                            style="text-decoration: underline; cursor: pointer;">{{ $name }}</span>
                                                        <span
                                                            style="font-size: smaller;">ID: {{ @$agency->id ?? 0 }}</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </td>
                                        <td>
                                            @php
                                                $st = $userJoinAgency->status;
                                                $stBg = 'var(--chip-success-bg)'; $stColor = 'var(--success)'; $stIcon = 'fa-check-circle';
                                                if ($st === 'kick off') { $stBg = 'var(--chip-danger-bg)'; $stColor = 'var(--danger)'; $stIcon = 'fa-times-circle'; }
                                                elseif ($st === 'leave') { $stBg = 'var(--chip-warning-bg)'; $stColor = 'var(--warning)'; $stIcon = 'fa-sign-out-alt'; }
                                            @endphp
                                            <span style="display:inline-flex;align-items:center;gap:4px;background:{{ $stBg }};color:{{ $stColor }};padding:4px 12px;border-radius:6px;font-weight:600;font-size:12px;">
                                                <i class="fas {{ $stIcon }}" style="font-size:10px;"></i>{{ $st }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(!empty($kickedBy) && !empty($kickedBy->id))
                                                <a href="{{ $kickedByUrl ?? '#' }}" target="_blank"
                                                   style="display: inline-flex; align-items: center; text-decoration: none;">
                                                    {!! $kickedByImage !!}
                                                    <span>{{ $kickedByName }} ({{ $kickedByUuid }})</span>
                                                </a>
                                            @endif
                                        </td>
                                        <td>{{ @$status }}</td>
                                        <td>{{ $userJoinAgency->join_date }}</td>
                                        <td>{{ $userJoinAgency->leave_date }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="text-center">{{ __('No agency join logs found') }}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>

                        @if($userJoinAgencies && $userJoinAgencies->count())
                            <div class="pagination-container">
                                {{ $userJoinAgencies->appends([
                                    'tab' => 'user-agency',
                                    'user_agency_page' => $userJoinAgencies?->currentPage(),
                                ])->links('vendor.pagination.bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-content {{ $activeTab == 'user-coins' ? '' : 'd-none' }}" id="user-coins-tab">
        <div class="card" style="border: none; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); overflow: hidden;">
            <div class="card-header" style="background: var(--gradient-warning); padding: 18px 24px; border: none;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                        <i class="fas fa-coins" style="font-size: 18px; color: var(--on-color);"></i>
                    </div>
                    <h4 class="card-title" style="text-align: start; margin: 0; color: var(--on-color); font-weight: 700; font-size: 17px;">{{ __('Users Coins Logs') }}</h4>
                </div>
            </div>
            <div class="box-body p-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="{{ url('admin/users/' . $user->id) }}" class="form-horizontal user-agency-form"
                              method="GET">
                            <input type="hidden" name="tab" value="user-coins">
                            <input type="hidden" name="coins_page" value="{{ request()->get('coins_page', 1) }}">

                            <div class="row mb-3" style="align-items: flex-end;">
                                <!-- From Date -->
                                <div class="col-md-3">
                                    <label>{{ __('From Date') }}</label>
                                    <input type="date" class="form-control" name="from_date"
                                           value="{{ request('from_date') }}">
                                </div>

                                <!-- To Date -->
                                <div class="col-md-3">
                                    <label>{{ __('To Date') }}</label>
                                    <input type="date" class="form-control" name="to_date"
                                           value="{{ request('to_date') }}">
                                </div>

                                <!-- Sub Type -->
                                <div class="col-md-3">
                                    <label>{{ __('Sub Type') }}</label>
                                    <select name="sub_type" class="form-control">
                                        <option value="">{{ __('All') }}</option>
                                        @foreach(\App\Helpers\Common::getCoinSubTypes() as $type)
                                            <option
                                                value="{{ $type }}" {{ request('sub_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Search/Reset Buttons -->
                                <div class="col-md-3 d-flex align-items-end justify-content-end"
                                     style="gap: 8px;top: 23px;">
                                    <button type="submit" class="btn btn-info btn-sm me-2">
                                        <i class="fa fa-search"></i> {{ __('Search') }}
                                    </button>
                                    <a href="{{ url('admin/users/' . $user->id. '?tab=user-coins') }}"
                                       class="btn btn-default btn-sm">
                                        <i class="fa fa-undo"></i> {{ __('Reset') }}
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="table-responsive">
                    <div class="box-body" style="padding: 0;">
                        <table class="table table-hover align-middle data-table" style="margin-bottom: 0; border-collapse: separate; border-spacing: 0;">
                            <thead>
                            <tr style="background: var(--chip-warning-bg);">
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                    <i class="fas fa-hashtag" style="margin-inline-end: 4px; opacity: 0.5;"></i>#
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                    <i class="fas fa-tag" style="margin-inline-end: 4px; color: var(--warning);"></i>{{ __('type') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                    <i class="fas fa-layer-group" style="margin-inline-end: 4px; color: var(--warning);"></i>{{ __('sub type') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                    <i class="fas fa-cube" style="margin-inline-end: 4px; color: var(--accent);"></i>{{ __('Item Name') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                    <i class="fas fa-wallet" style="margin-inline-end: 4px; color: var(--info);"></i>{{ __('balance before') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                    <i class="fas fa-coins" style="margin-inline-end: 4px; color: var(--success);"></i>{{ __('amount') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                    <i class="fas fa-balance-scale" style="margin-inline-end: 4px; color: var(--info);"></i>{{ __('balance yet') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                    <i class="fas fa-calendar-alt" style="margin-inline-end: 4px; color: var(--success);"></i>{{ __('from date') }}
                                </th>
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--warning); border-bottom: 2px solid var(--chip-warning-bg);">
                                    <i class="fas fa-calendar-check" style="margin-inline-end: 4px; color: var(--danger);"></i>{{ __('to date') }}
                                </th>
                            </tr>
                            </thead>
                            @if($usersCoins && $usersCoins->count())
                                <tbody>
                                @foreach($usersCoins as $index => $coin)
                                    <tr style="transition: all 0.2s ease; border-bottom: 1px solid var(--surface-raised);"
                                        onmouseover="this.style.backgroundColor='var(--chip-warning-bg)'" onmouseout="this.style.backgroundColor='transparent'">
                                        <td style="padding: 14px 16px;">
                                            <span style="background: var(--surface-raised); padding: 3px 10px; border-radius: 6px; font-weight: 600; color: var(--text-secondary); font-size: 13px;">
                                                {{ ($usersCoins->currentPage() - 1) * $usersCoins->perPage() + $index + 1 }}
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--chip-warning-bg); color: var(--warning); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                                <i class="fas fa-tag" style="font-size: 10px;"></i>{{ $coin->type }}
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--accent-soft); color: var(--accent); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                                <i class="fas fa-layer-group" style="font-size: 10px;"></i>{{ @$coin->sub_type ?? 0 }}
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px; font-weight: 600; color: var(--text-primary); font-size: 13px;">
                                            {{ @$coin->item_name ?? '' }}
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="font-weight: 700; color: var(--info); font-size: 13px;">{{ number_format(@$coin->amount_before ?? 0) }}</span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            @php $amt = $coin->amount ?? 0; @endphp
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 5px 14px; border-radius: 20px; font-weight: 800; font-size: 13px; border: 1px solid {{ $amt < 0 ? 'var(--chip-danger-bg)' : 'var(--chip-success-bg)' }}; background: {{ $amt < 0 ? 'var(--chip-danger-bg)' : 'var(--chip-success-bg)' }}; color: {{ $amt < 0 ? 'var(--danger)' : 'var(--success)' }};">
                                                <i class="fas {{ $amt < 0 ? 'fa-arrow-down' : 'fa-arrow-up' }}" style="font-size: 10px;"></i>
                                                {{ number_format($amt) }}
                                            </span>
                                            @if($coin->sub_type == 'coin_game_users')
                                                <div style="margin-top: 4px; display: flex; flex-direction: column; gap: 2px;">
                                                    <small style="display: inline-flex; align-items: center; gap: 3px; color: var(--success); font-weight: 600; font-size: 11px;">
                                                        <i class="fas fa-plus-circle" style="font-size: 9px;"></i> {{ $coin->helper_amount }} {{ __('profit') }}
                                                    </small>
                                                    <small style="display: inline-flex; align-items: center; gap: 3px; color: var(--danger); font-weight: 600; font-size: 11px;">
                                                        <i class="fas fa-minus-circle" style="font-size: 9px;"></i> {{ $coin->amount - $coin->helper_amount }} {{ __('loss') }}
                                                    </small>
                                                </div>
                                            @endif
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--chip-info-bg); color: var(--info); padding: 4px 12px; border-radius: 6px; font-weight: 700; font-size: 13px;">
                                                <i class="fas fa-coins" style="font-size: 10px;"></i>
                                                {{ number_format(($coin->amount_before ?? 0) + ($coin->amount ?? 0)) }}
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--surface-sunken); color: var(--text-secondary); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                                <i class="fas fa-calendar-alt" style="font-size: 10px; color: var(--text-muted);"></i>
                                                {{ \Carbon\Carbon::parse($coin->from_date)->format('Y-m-d H:i:s') ?? '0' }}
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--surface-sunken); color: var(--text-secondary); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                                <i class="fas fa-calendar-check" style="font-size: 10px; color: var(--text-muted);"></i>
                                                {{ \Carbon\Carbon::parse($coin->to_date)->format('Y-m-d H:i:s') ?? '0' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            @else
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="padding: 48px 24px; text-align: center; border: none;">
                                            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                                <div style="width: 64px; height: 64px; background: var(--chip-warning-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-coins" style="font-size: 28px; color: var(--warning); opacity: 0.5;"></i>
                                                </div>
                                                <p style="margin: 0; color: var(--text-muted); font-size: 14px; font-weight: 500;">{{ __('No coins logs found') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>

                        @if($usersCoins)
                            <div class="pagination-container">
                                {{ $usersCoins->appends(array_filter([
                                    'tab' => 'user-coins',
                                    'from_date' => request('from_date'),
                                    'to_date' => request('to_date'),
                                    'sub_type' => request('sub_type'),
                                ]))->links('vendor.pagination.bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-content {{ $activeTab == 'charge' ? '' : 'd-none' }}" id="charge-tab">
        <div class="card" style="border: none; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); overflow: hidden;">
            <div class="card-header" style="background: var(--gradient-danger); padding: 18px 24px; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                            <i class="fas fa-bolt" style="font-size: 18px; color: var(--on-color);"></i>
                        </div>
                        <h4 class="card-title" style="text-align: start; margin: 0; color: var(--on-color); font-weight: 700; font-size: 17px; letter-spacing: 0.3px;">{{ __('Charge Reports') }}</h4>
                    </div>
                    @if($charges instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <span style="background: rgba(255,255,255,0.2); color: var(--on-color); padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; backdrop-filter: blur(4px);">
                            {{ $charges->total() }} {{ __('total') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="box-body" style="padding: 12px 20px; background: var(--chip-danger-bg); border-bottom: 1px solid var(--chip-danger-bg);">
                <div class="nav-scroll-container">
                    <ul class="nav nav-pills" style="gap: 6px;">
                        <li class="{{ $chargeTabType == 'receiver' ? 'active' : '' }}">
                            <a class="nav-link @if($chargeTabType == 'receiver') active @endif"
                               href="?tab=charge&type=receiver" role="tab"
                               style="border-radius: 8px; padding: 7px 16px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-download" style="font-size: 10px;"></i> {{ __('Receiver') }}
                            </a>
                        </li>
                        <li class="{{ $chargeTabType == 'charger' ? 'active' : '' }}">
                            <a class="nav-link @if($chargeTabType == 'charger') active @endif"
                               href="?tab=charge&type=charger" role="tab"
                               style="border-radius: 8px; padding: 7px 16px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-upload" style="font-size: 10px;"></i> {{ __('Charger') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle" style="margin-bottom: 0; border-collapse: separate; border-spacing: 0;">
                        <thead>
                        <tr style="background: var(--chip-danger-bg);">
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--danger); border-bottom: 2px solid var(--chip-danger-bg);">
                                <i class="fas fa-hashtag" style="margin-inline-end: 4px; opacity: 0.5;"></i>#
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--danger); border-bottom: 2px solid var(--chip-danger-bg);">
                                <i class="fas fa-user" style="margin-inline-end: 4px; color: var(--danger);"></i>
                                @if($chargeTabType == 'receiver')
                                    {{ __('Charger') }}
                                @else
                                    {{ __('Receiver') }}
                                @endif
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--danger); border-bottom: 2px solid var(--chip-danger-bg);">
                                <i class="fas fa-tag" style="margin-inline-end: 4px; color: var(--accent);"></i>{{ __('Type') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--danger); border-bottom: 2px solid var(--chip-danger-bg);">
                                <i class="fas fa-coins" style="margin-inline-end: 4px; color: var(--warning);"></i>{{ __('Amount') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--danger); border-bottom: 2px solid var(--chip-danger-bg);">
                                <i class="fas fa-dollar-sign" style="margin-inline-end: 4px; color: var(--success);"></i>{{ __('usd') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--danger); border-bottom: 2px solid var(--chip-danger-bg);">
                                <i class="fas fa-calendar" style="margin-inline-end: 4px; color: var(--text-secondary);"></i>{{ __('Created at') }}
                            </th>
                        </tr>
                        </thead>
                        <tbody>
@forelse($charges ?? [] as $index => $charge)
                            @php
                                if($chargeTabType == 'receiver') {
                                    $userCharges = Common::getChargerInfo($charge);
                                  } else {
                                    $userCharges = Common::getReceiverInfo($charge);
                                  }
                                  $name = $userCharges['name'] ?? '-';
                                  $uid = $userCharges['uuid'] ?? '-';
                                  $type = $userCharges['type'] ?? '-';
                                  $image = $userCharges['image'] ?? asset('images/businessman-icon.jpg');
                            @endphp
                            <tr style="transition: all 0.2s ease; border-bottom: 1px solid var(--surface-raised);"
                                onmouseover="this.style.backgroundColor='var(--chip-danger-bg)'" onmouseout="this.style.backgroundColor='transparent'">
                                <td style="padding: 14px 16px;">
                                    <span style="background: var(--surface-raised); padding: 3px 10px; border-radius: 6px; font-weight: 600; color: var(--text-secondary); font-size: 13px;">
                                        {{ @$charge->id ?? 0 }}
                                    </span>
                                </td>
                                <td style="padding: 14px 16px;">
                                    <a href="{{ $userCharges['url'] ?? '#' }}" target="_blank"
                                       style="display: inline-flex; align-items: center; text-decoration: none; gap: 8px; background: var(--surface-raised); padding: 4px 10px; border-radius: 8px; border: 1px solid var(--border);">
                                        <img src="{{ getImagePath($image) }}" width="32" height="32"
                                             style="object-fit: cover; border-radius: 50%; border: 2px solid var(--chip-danger-bg);">
                                        <div style="display: flex; flex-direction: column;">
                                            <span style="font-weight: 600; color: var(--danger); font-size: 12px;">{{ $name }}</span>
                                            <small style="color: var(--text-muted); font-size: 10px;">{{ $uid }}</small>
                                        </div>
                                    </a>
                                </td>
                                <td style="padding: 14px 16px;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--accent-soft); color: var(--accent); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                        <i class="fas fa-cube" style="font-size: 10px;"></i>
                                        {{ $type }}
                                    </span>
                                </td>
                                <td style="padding: 14px 16px;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--chip-warning-bg); color: var(--warning); padding: 5px 14px; border-radius: 20px; font-weight: 800; font-size: 13px; border: 1px solid var(--chip-warning-bg);">
                                        <i class="fas fa-coins" style="font-size: 11px;"></i>
                                        {{ number_format($charge->amount) }}
                                    </span>
                                </td>
                                <td style="padding: 14px 16px;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--chip-success-bg); color: var(--success); padding: 4px 12px; border-radius: 6px; font-weight: 700; font-size: 13px;">
                                        <i class="fas fa-dollar-sign" style="font-size: 10px;"></i>
                                        {{ number_format((float)$charge->usd, 2) }}
                                    </span>
                                </td>
                                <td style="padding: 14px 16px;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--surface-sunken); color: var(--text-secondary); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                        <i class="fas fa-calendar-alt" style="font-size: 10px; color: var(--text-muted);"></i>
                                        {{ \Carbon\Carbon::parse($charge->created_at)->format('Y-m-d H:i') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 48px 24px; text-align: center; border: none;">
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                        <div style="width: 64px; height: 64px; background: var(--chip-danger-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-bolt" style="font-size: 28px; color: var(--danger); opacity: 0.5;"></i>
                                        </div>
                                        <p style="margin: 0; color: var(--text-muted); font-size: 14px; font-weight: 500;">{{ __('No charge records found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

            {{-- Pagination --}}
            @if($charges instanceof \Illuminate\Pagination\LengthAwarePaginator && $charges->count())
                <div class="pagination-container" style="padding: 16px 24px; border-top: 1px solid var(--surface-raised);">
                    {{ $charges->appends([
                        'tab' => 'charge',
                        'type' => $chargeTabType,
                        'receiver_page' => request('receiver_page'),
                        'charger_page' => request('charger_page'),
                    ])->links('vendor.pagination.bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>

    <div class="tab-content {{ $activeTab == 'gift-log' ? '' : 'd-none' }}" id="gift-log-tab">
        <div class="card" style="border: none; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); overflow: hidden;">
            <div class="card-header" style="background: var(--gradient-1); padding: 18px 24px; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                            <i class="fas fa-gift" style="font-size: 18px; color: var(--on-color);"></i>
                        </div>
                        <h4 class="card-title" style="text-align: start; margin: 0; color: var(--on-color); font-weight: 700; font-size: 17px; letter-spacing: 0.3px;">{{ __('gift Reports') }}</h4>
                    </div>
                    @if($giftSLogs instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <span style="background: rgba(255,255,255,0.2); color: var(--on-color); padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; backdrop-filter: blur(4px);">
                            {{ $giftSLogs->total() }} {{ __('total') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="box-body p-3">
                <div class="nav-scroll-container mb-3" style="background: var(--accent-soft); padding: 8px 16px; border-radius: 10px; border: 1px solid var(--accent-soft);">
                    <ul class="nav nav-pills" style="gap: 6px;">
                        <li class="{{ $giftType == 'receiver' ? 'active' : '' }}">
                            <a class="nav-link @if($giftType == 'receiver') active @endif"
                               href="?tab=gift-log&gift_type=receiver" role="tab"
                               style="border-radius: 8px; padding: 7px 16px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-download" style="font-size: 10px;"></i> {{ __('received gift') }}
                            </a>
                        </li>
                        <li class="{{ $giftType == 'sender' ? 'active' : '' }}">
                            <a class="nav-link @if($giftType == 'sender') active @endif"
                               href="?tab=gift-log&gift_type=sender" role="tab"
                               style="border-radius: 8px; padding: 7px 16px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-upload" style="font-size: 10px;"></i> {{ __('sent gift') }}
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <form action="{{ url('admin/users/' . $user->id) }}" class="form-horizontal gift-log-form"
                              method="GET">
                            <input type="hidden" name="tab" value="gift-log">
                            <input type="hidden" name="gift_type" value="{{ $giftType }}">
                            <input type="hidden" name="gift_page" value="{{ request()->get('gift_page', 1) }}">

                            <div class="row mb-2" style="align-items: flex-end;">
                                <!-- From Date -->
                                <div class="col-md-4">
                                    <div class="date-flex-row">
                                        <i class="fa fa-calendar"></i>
                                        <span>{{ __('From Date') }}</span>
                                        <input type="date" class="form-control" id="from_date" name="start_at"
                                               value="{{ request('start_at') }}">
                                    </div>
                                </div>
                                <!-- To Date -->
                                <div class="col-md-4">
                                    <div class="date-flex-row">
                                        <i class="fa fa-calendar"></i>
                                        <span>{{ __('To Date') }}</span>
                                        <input type="date" class="form-control" id="to_date" name="end_at"
                                               value="{{ request('end_at') }}">
                                    </div>
                                </div>
                                @if ($giftType == 'receiver')
                                    <div class="col-md-4">
                                        <div class="date-flex-row">
                                            <label for="agency_id">{{ __('Agency') }}</label>
                                            <select class="form-control" id="agency_id" name="agency_id">
                                                @if(request('agency_id'))
                                                    <option value="{{ request('agency_id') }}" selected>
                                                        {{ \App\Models\Agency::find(request('agency_id'))?->name . ' - ' . request('agency_id') }}
                                                    </option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                @endif
                                <!-- Buttons -->
                                <div class="col-md-4 d-flex align-items-end justify-content-end" style="gap: 8px;">
                                    <button type="submit" class="btn btn-info btn-sm me-2">
                                        <i class="fa fa-search"></i> {{__('Search')}}
                                    </button>
                                    <a href="{{ url('admin/users/' . $user->id. '?'.'tab=gift-log&gift_type=' . $giftType) }}"
                                       class="btn btn-default btn-sm">
                                        <i class="fa fa-undo"></i> {{__('Reset')}}
                                    </a>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
                <!-- Summary Box -->
                <div class="diamond-summary-container">
                    <div class="diamond-summary-box">
                        <div class="diamond-title">
                            {{ $giftType == 'receiver' ? __('total diamonds received') : __('total diamonds sent') }}
                        </div>
                        <div class="diamond-count">
                            <span>{{ number_format(@$diamonds) }}</span>
                            <div class="diamond-icon-container">
                                <img src="{{ asset('images/diamond.jpg') }}" alt="Diamond" class="diamond-icon">
                            </div>
                        </div>
                    </div>
                </div>

                @if($giftType == 'sender')
                <!-- Sender Gift Summary Cards - 2 per row -->
                <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; padding: 20px;">

                    <!-- Total Diamond Send from Users Table Card -->
                    {{-- <div style="flex: 1 1 calc(50% - 20px); min-width: 280px; max-width: 500px;">
                        <div class="diamond-summary-box" style="background: var(--gradient-danger); margin: 0;">
                            <div class="diamond-title">
                                {{ __('Total Diamond Send') }}
                            </div>
                            <div class="diamond-count">
                                <span>{{ number_format(@$user->total_diamond_send ?? 0) }}</span>
                                <div class="diamond-icon-container">
                                    <img src="{{ asset('images/diamond.jpg') }}" alt="Diamond" class="diamond-icon">
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    <div style="flex: 1 1 calc(50% - 20px); min-width: 280px; max-width: 500px;">
                        <div class="diamond-summary-box" style="background: var(--gradient-4); margin: 0;">
                            <div class="diamond-title">
                                {{ __('total_diamonds_sent') }}
                            </div>
                            <div class="diamond-count">
                                <span>{{ number_format(@$totalGiftCoins ?? 0) }}</span>
                                <div class="diamond-icon-container">
                                    <img src="{{ asset('images/diamond.jpg') }}" alt="Diamond" class="diamond-icon">
                                </div>
                            </div>
                        </div>
                    </div>

                  

                </div>
                @endif
                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle" style="margin-bottom: 0; border-collapse: separate; border-spacing: 0;">
                        <thead>
                        <tr style="background: var(--accent-soft);">
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--accent); border-bottom: 2px solid var(--accent-soft);">
                                <i class="fas fa-hashtag" style="margin-inline-end: 4px; opacity: 0.5;"></i>#
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--accent); border-bottom: 2px solid var(--accent-soft);">
                                <i class="fas fa-user" style="margin-inline-end: 4px; color: var(--accent);"></i>{{ $giftType == 'receiver' ? __('Sender') : __('Receiver') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--accent); border-bottom: 2px solid var(--accent-soft);">
                                <i class="fas fa-door-open" style="margin-inline-end: 4px; color: var(--info);"></i>{{ __('room') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--accent); border-bottom: 2px solid var(--accent-soft);">
                                <i class="fas fa-camera" style="margin-inline-end: 4px; color: var(--danger);"></i>{{ __('moment') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--accent); border-bottom: 2px solid var(--accent-soft);">
                                <i class="fas fa-gift" style="margin-inline-end: 4px; color: var(--accent);"></i>{{ __('gift') }}
                            </th>
                            @if($giftType == 'receiver')
                                <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--accent); border-bottom: 2px solid var(--accent-soft);">
                                    <i class="fas fa-building" style="margin-inline-end: 4px; color: var(--success);"></i>{{ __('agency') }}
                                </th>
                            @endif
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--accent); border-bottom: 2px solid var(--accent-soft);">
                                <i class="fas fa-sort-numeric-up" style="margin-inline-end: 4px; color: var(--info);"></i>{{ __('quantity') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--accent); border-bottom: 2px solid var(--accent-soft);">
                                <i class="fas fa-coins" style="margin-inline-end: 4px; color: var(--warning);"></i>{{ __('price') }}
                            </th>
                            <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--accent); border-bottom: 2px solid var(--accent-soft);">
                                <i class="fas fa-calendar" style="margin-inline-end: 4px; color: var(--text-secondary);"></i>{{ __('Created at') }}
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($giftSLogs ?? [] as $index => $giftSLog)
                            @php
                                $userImageDefault = asset('images/businessman-icon.jpg');
                                $defaultImage = asset("images/background_room.jpg");

                                $userCharges = $giftType === 'receiver' ? $giftSLog->sender : $giftSLog->receiver;
                                $name = @$userCharges->name ?? '';
                                $uid = @$userCharges->uuid ?? '';
                                $id = @$userCharges->id ?? 0;

                                $avatar = @$userCharges->profile->avatar;
                                $image = getImagePath($avatar) ?? $userImageDefault;
                                if (!isImageExists($image)) {
                                    $image = $userImageDefault;
                                }

                                $roomName = @$giftSLog->room->room_name ?? '-';
                                $path = @$giftSLog->room->room_cover;
                                $ownerRoom = @$giftSLog->room->uid ?? 0;
                                $url = getImagePath($path) ?? $defaultImage;
                                if (!isImageExists($url)) {
                                    $url = $defaultImage;
                                }

                                $giftName = app()->getLocale() == 'ar'
                                    ? (@$giftSLog->gift->name ?? '')
                                    : (@$giftSLog->gift->e_name ?? '');

                                // Get category name
                                $giftCategoryName = '';
                                $gift = @$giftSLog->gift;
                                if ($gift) {
                                    if (!$gift->relationLoaded('category')) {
                                        $gift->load('category');
                                    }

                                    $giftCategory = $gift->category;
                                    if ($giftCategory && $giftCategory->title) {
                                        $categoryTitle = $giftCategory->title;
                                        if (is_array($categoryTitle)) {
                                            $locale = app()->getLocale();
                                            $giftCategoryName = trim($categoryTitle[$locale] ?? $categoryTitle['en'] ?? '');
                                        } else {
                                            $giftCategoryName = trim($categoryTitle);
                                        }
                                    }
                                }

                                    $agency =$giftSLog->agency;
                                    $agencyName = $agency->name ?? '';
                                    $agencyId = $agency->id ?? 0;
                                    $agencyDefaultImage = asset("images/icon-agency.jpg");
                                    $agencyImage =getImagePath(@$agency->img) ?? $agencyDefaultImage;
                                    if (!isImageExists($agencyImage)) {
                                    $agencyImage = $agencyDefaultImage;
                                }
                            @endphp
                            @php
                                $moment = $giftSLog->moment;
                                $galleries = @$moment->images;
                            @endphp
                            <tr>
                                <td>{{ @$giftSLog->id ?? 0 }}</td>
                                <td>
                                    @if ($giftType === 'receiver' &&@$giftSLog->giftId ==0)

                                        <h5>{{__('remaining diamond')}}</h5>
                                    @else
                                        <a href="{{ url('admin/users/' . $id) }}" target="_blank"
                                           class="d-flex align-items-center text-decoration-none">
                                            <img src="{{ $image }}" width="40" height="40"
                                                 style="object-fit: cover; border-radius: 50%; margin-inline-end: 10px;">
                                            <div>
                                                <strong style="font-size: 14px;">{{ $name }}</strong><br>
                                                <small class="text-muted">UUID: {{ $uid }}</small>
                                            </div>
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($giftSLog->room))
                                        <a href="{{ url('admin/rooms/' .  $giftSLog->room->id) }}" target="_blank"
                                           class="d-flex align-items-center text-decoration-none">
                                            <img src="{{ $url }}"
                                                 width="30" height="30"
                                                 style="object-fit: cover; border-radius: 50%; margin-inline-end: 10px;">
                                            <div>
                                                <span>{{ $roomName }}</span><br>
                                                <small
                                                    class="text-muted">Type: {{ $giftSLog->room->type ?? '-' }}</small>
                                            </div>
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    @if($galleries && $galleries->count() > 0)
                                        <div id="image-gallery-{{ $moment->id }}" style="display: none;">
                                            @foreach($galleries as $image)
                                                @php
                                                    $imgUrl = getDriverUrl() . '/' . $image->image;
                                                @endphp
                                                <img src="{{ $imgUrl }}"
                                                     style="width: 100%; height: 200px; object-fit: cover;"
                                                     data-original="{{ $imgUrl }}"
                                                     loading="lazy"
                                                     class="gallery-image">
                                            @endforeach
                                        </div>

                                        {{-- Show first image as thumbnail --}}
                                        @php
                                            $firstImageUrl = getDriverUrl() . '/' . $galleries->first()->image;
                                        @endphp
                                        <img src="{{ $firstImageUrl }}"
                                             style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"
                                             onclick="document.querySelector('#image-gallery-{{ $moment->id }} img').click()">

                                        @push('scripts')
                                            <script>
                                                new Viewer(document.getElementById('image-gallery-{{ $moment->id }}'));
                                            </script>
                                        @endpush
                                    @else
                                        No Image
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ url('admin/gifts/' .  @$giftSLog->gift->id) }}" target="_blank"
                                       class="d-flex align-items-center text-decoration-none">
                                        {!! handleShowImageWithTypes($giftSLog->gift->id ?? 0, getImagePath($giftSLog->gift->img ?? ''), 30, 30) !!}
                                        <div>
                                            <span>{{ $giftName }}</span><br>
                                            <span>{{ __('Gift ID') }}: {{ $giftSLog->gift->id ?? 0 }}</span><br>
                                            <small class="text-muted">
                                                {{ __('Type') }}: {{ $giftCategoryName ?: '-' }}<br>
                                                @if(@$giftSLog->gift->pk)
                                                    <br>{{ __('PK') }}
                                                @endif
                                            </small>
                                        </div>
                                    </a>
                                </td>
                                @if($giftType == 'receiver')
                                    <td>
                                        @if ($giftSLog->agency_id)
                                            <a href="{{ url('admin/agencies/' . $agencyId) }}" target="_blank"
                                               class="d-flex align-items-center text-decoration-none">
                                                <img src="{{ $agencyImage }}"
                                                     width="50" height="30"
                                                     style="object-fit: cover; border-radius: 4px; border: 1px solid var(--border); padding: 2px; margin-inline-end: 10px;">
                                                <div>
                                                    <span>{{ $agencyName }}</span><br>
                                                    <small class="text-muted">id: {{ $agencyId ?? 0 }}</small>
                                                </div>
                                            </a>
                                        @else
                                            <span class="text-danger">{{__('not join to agency')}}</span>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--chip-info-bg); color: var(--info); padding: 4px 12px; border-radius: 6px; font-weight: 700; font-size: 13px;">
                                        <i class="fas fa-times" style="font-size: 9px;"></i>{{ $giftSLog->giftNum }}
                                    </span>
                                </td>
                                <td>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--chip-warning-bg); color: var(--warning); padding: 5px 14px; border-radius: 20px; font-weight: 800; font-size: 13px; border: 1px solid var(--chip-warning-bg);">
                                        <i class="fas fa-coins" style="font-size: 11px;"></i>{{ number_format($giftSLog->giftPrice) }}
                                    </span>
                                </td>
                                <td>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: var(--surface-sunken); color: var(--text-secondary); padding: 4px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                        <i class="fas fa-calendar-alt" style="font-size: 10px; color: var(--text-muted);"></i>{{ \Carbon\Carbon::parse($giftSLog->created_at)->timezone(getTimezone())->format('Y-m-d H:i') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                @if($giftSLogs instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="d-flex justify-content-center mt-3">
                        {{ $giftSLogs->appends([
                            'tab' => 'gift-log',
                            'gift_type' => $giftType,
                            'start_at' => request('start_at'),
                            'end_at' => request('end_at'),
                            'gift_page' => $giftSLogs->currentPage()
                        ])->links('vendor.pagination.bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>
    </div>


<div class="modal fade" id="Add_model" tabindex="-1" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg mt-6" role="document">
        <div class="modal-content border-0">
            <div class="modal-content position-relative">
                <div class="position-absolute top-0 end-0 mt-2 me-2 z-index-1">
                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.pack.free') }}" method="POST" id="add_form">
                    @csrf
                    <div class="modal-body p-0">
                        <div class="rounded-top-lg py-3 ps-4 pe-6 bg-light">
                            <h4 class="mb-1" id="modalExampleDemoLabel"> {{ __('dashboard.free') }}</h4>
                        </div>
                        <div class="p-4">

                            <div class="row" style="justify-content:space-evenly">

                                <input type="hidden" name=id class="item_id">
                                <div class="mb-3 col-md-12">
                                    <label for="type" class="form-label">{{ __('type') }}</label>
                                    <select name="type" id="type" class="form-select">
                                        <option value="0">{{ __('dashboard.raise') }}</option>
                                        <option value="1">{{ __('dashboard.lower') }}</option>
                                    </select>
                                </div>

                                <!-- Days Input -->
                                <div class="mb-3 col-md-6">
                                    <label for="days" class="form-label">{{ __('days') }}</label>
                                    <input type="number" class="form-control" id="days" name="days"
                                           placeholder="{{ __('days') }}">
                                </div>

                            </div>

                        </div>
                    </div>
                    <div class="modal-footer mt-3">
                        <button class="btn btn-secondary close-modal-btn" type="button"
                                data-bs-dismiss="modal">{{ __('Cancel') }} </button>
                        <button class="btn btn-primary add_country" type="submit">{{ __('save') }} </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="item_modal_update" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg mt-6" role="document">
        <div class="modal-content" style="border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.25); overflow: hidden;">
            <div class="position-relative">
                {{-- Modal Header --}}
                <div style="background: var(--gradient-3); padding: 20px 28px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 42px; height: 42px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                            <i class="fas fa-user-edit" style="font-size: 18px; color: var(--on-color);"></i>
                        </div>
                        <h4 style="margin: 0; color: var(--on-color); font-weight: 700; font-size: 17px; letter-spacing: 0.3px;">{{ __('Edit User') }}</h4>
                    </div>
                </div>

                <form action="{{ url('/admin/update-user') }}" id="country_update_form" method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body" style="padding: 28px;">
                        <input type="hidden" name="id" value="{{ old('id', $user->id) }}">
                        <div class="row">
                            <div class="col-lg-6 mb-4 form-group">
                                <label class="form-label" style="font-weight: 600; font-size: 12px; color: var(--info); display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                    <i class="fas fa-user" style="font-size: 11px; color: var(--info);"></i> {{ __('Name') }}
                                </label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}"
                                       style="border: 2px solid var(--chip-info-bg); border-radius: 10px; padding: 10px 14px; font-size: 14px; transition: all 0.2s;"
                                       onfocus="this.style.borderColor='var(--info)'; this.style.boxShadow='0 0 0 3px rgba(0,0,0,0.12)'"
                                       onblur="this.style.borderColor='var(--chip-info-bg)'; this.style.boxShadow='none'">
                            </div>

                            <div class="col-lg-6 mb-4 form-group">
                                <label class="form-label" style="font-weight: 600; font-size: 12px; color: var(--info); display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                    <i class="fas fa-id-badge" style="font-size: 11px; color: var(--info);"></i> {{ __('uuid') }}
                                </label>
                                <input type="text" name="uuid" class="form-control" value="{{ old('uuid', $user->uuid ?? '') }}"
                                       style="border: 2px solid var(--chip-info-bg); border-radius: 10px; padding: 10px 14px; font-size: 14px; transition: all 0.2s;"
                                       onfocus="this.style.borderColor='var(--info)'; this.style.boxShadow='0 0 0 3px rgba(0,0,0,0.12)'"
                                       onblur="this.style.borderColor='var(--chip-info-bg)'; this.style.boxShadow='none'">
                            </div>

                            <div class="col-lg-6 mb-4 form-group">
                                <label class="form-label" style="font-weight: 600; font-size: 12px; color: var(--info); display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                    <i class="fas fa-envelope" style="font-size: 11px; color: var(--info);"></i> {{ __('email') }}
                                </label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}"
                                       style="border: 2px solid var(--chip-info-bg); border-radius: 10px; padding: 10px 14px; font-size: 14px; transition: all 0.2s;"
                                       onfocus="this.style.borderColor='var(--info)'; this.style.boxShadow='0 0 0 3px rgba(0,0,0,0.12)'"
                                       onblur="this.style.borderColor='var(--chip-info-bg)'; this.style.boxShadow='none'">
                            </div>

                            <div class="col-lg-6 mb-4 form-group">
                                <label class="form-label" style="font-weight: 600; font-size: 12px; color: var(--info); display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                    <i class="fas fa-phone" style="font-size: 11px; color: var(--info);"></i> {{ __('phone') }}
                                </label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone ?? '') }}"
                                       style="border: 2px solid var(--chip-info-bg); border-radius: 10px; padding: 10px 14px; font-size: 14px; transition: all 0.2s;"
                                       onfocus="this.style.borderColor='var(--info)'; this.style.boxShadow='0 0 0 3px rgba(0,0,0,0.12)'"
                                       onblur="this.style.borderColor='var(--chip-info-bg)'; this.style.boxShadow='none'">
                            </div>

                            <div class="col-lg-12 mb-4 form-group">
                                <label class="form-label" style="font-weight: 600; font-size: 12px; color: var(--info); display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                    <i class="fas fa-venus-mars" style="font-size: 11px; color: var(--danger);"></i> {{ __('Gender') }}
                                </label>
                                <select class="form-select" name="gender"
                                        style="border: 2px solid var(--chip-info-bg); border-radius: 10px; padding: 10px 14px; font-size: 14px;">
                                    <option value="">{{ __('Choose gender') }}</option>
                                    <option value="0" {{ old('gender', $user->profile->gender ?? '') == '0' ? 'selected' : '' }}>
                                        {{ __('female') }}
                                    </option>
                                    <option value="1" {{ old('gender', $user->profile->gender ?? '') == '1' ? 'selected' : '' }}>
                                        {{ __('male') }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-lg-6 mb-4 form-group">
                                <label class="form-label" style="font-weight: 600; font-size: 12px; color: var(--info); display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                    <i class="fas fa-camera" style="font-size: 11px; color: var(--accent);"></i> {{ __('image') }}
                                </label>
                                <input class="form-control" name="image" accept="image/*" type="file"
                                       style="border: 2px solid var(--chip-info-bg); border-radius: 10px; padding: 8px 14px; font-size: 13px;">
                                <div style="margin-top: 12px; display: inline-block;">
                                    <img src="{{ getImagePath($user->profile->avatar ?? '') ?? asset('images/default-avatar.png') }}"
                                         class="rounded" id="img_edit" alt="{{ $user->name ?? '' }}"
                                         style="width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 3px solid var(--chip-info-bg); box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                                </div>
                            </div>

                            <div class="col-lg-12 mb-4 form-group">
                                <label class="form-label" style="font-weight: 600; font-size: 12px; color: var(--info); display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                    <i class="fas fa-globe" style="font-size: 11px; color: var(--info);"></i> {{ __('Country') }}
                                </label>
                                <select class="form-select" name="country_id" id="country_id"
                                        style="border: 2px solid var(--chip-info-bg); border-radius: 10px; padding: 10px 14px; font-size: 14px;">
                                    @foreach($countries as $id => $name)
                                        <option value="{{ $id }}" {{ old('country_id', $user->country_id ?? null) == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-12 mb-3 form-group">
                                <label class="form-label" style="font-weight: 600; font-size: 12px; color: var(--info); display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                    <i class="fas fa-pen-fancy" style="font-size: 11px; color: var(--warning);"></i> {{ __('bio') }}
                                </label>
                                <textarea class="form-control" cols="10" name="bio" rows="2"
                                          style="border: 2px solid var(--chip-info-bg); border-radius: 10px; padding: 10px 14px; font-size: 14px; resize: vertical; transition: all 0.2s;"
                                          onfocus="this.style.borderColor='var(--info)'; this.style.boxShadow='0 0 0 3px rgba(0,0,0,0.12)'"
                                          onblur="this.style.borderColor='var(--chip-info-bg)'; this.style.boxShadow='none'">{{ old('bio', $user->bio ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; padding: 16px 28px; border-top: 1px solid var(--surface-sunken); background: var(--surface-raised);">
                        <button class="btn cancel_user_item_model_btn" type="button" data-bs-dismiss="modal"
                                style="background: var(--surface-sunken); color: var(--text-secondary); border: 1px solid var(--border); border-radius: 10px; padding: 10px 24px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;"
                                onmouseover="this.style.background='var(--border)'" onmouseout="this.style.background='var(--surface-sunken)'">
                            <i class="fas fa-times" style="font-size: 11px;"></i> {{ __('Cancel') }}
                        </button>
                        <button class="btn" type="submit"
                                style="background: var(--gradient-3); color: var(--on-color); border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.12);"
                                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'"
                                onmouseout="this.style.transform='none'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.12)'">
                            <i class="fas fa-save" style="font-size: 12px;"></i> {{ __('edit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- jQuery and SweetAlert2 are provided globally by the admin layout; no CDN. --}}

<script>


    // ── Close Profile Dropdown on Outside Click ──
    document.addEventListener('click', function(e) {
        var dropdown = document.getElementById('profileDropdownMenu');
        var btn = document.getElementById('profileActionsDropdown');
        if (dropdown && btn && !btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show-dropdown');
        }
    });

    // ── Cover Slideshow Auto-Rotation ──
    var coverCurrentIndex = 0;
    var coverSlides = document.querySelectorAll('.cover-slide');
    var coverDots = document.querySelectorAll('.cover-dot');
    var coverTimer = null;

    function coverGoTo(index) {
        if (coverSlides.length <= 1) return;
        coverSlides[coverCurrentIndex].classList.remove('active');
        if (coverDots.length) coverDots[coverCurrentIndex].classList.remove('active');
        coverCurrentIndex = (index + coverSlides.length) % coverSlides.length;
        coverSlides[coverCurrentIndex].classList.add('active');
        if (coverDots.length) coverDots[coverCurrentIndex].classList.add('active');
    }

    function coverSlide(direction) {
        coverGoTo(coverCurrentIndex + direction);
        coverResetTimer();
    }

    function coverResetTimer() {
        if (coverTimer) clearInterval(coverTimer);
        if (coverSlides.length > 1) {
            coverTimer = setInterval(function() { coverGoTo(coverCurrentIndex + 1); }, 4000);
        }
    }

    // Click on dots
    coverDots.forEach(function(dot) {
        dot.addEventListener('click', function() {
            coverGoTo(parseInt(this.getAttribute('data-index')));
            coverResetTimer();
        });
    });

    // Start auto-rotation
    coverResetTimer();

    $(document).ready(function () {

        $(document).on('click', '.edit_user_item_model_btn', function () {
            $('#item_modal_update').modal('show');
        });

        // Loading spinner on user edit form submit
        $('#country_update_form').on('submit', function () {
            var btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true);
            btn.html('<i class="fas fa-spinner fa-spin" style="font-size: 12px;"></i> {{ __("Saving...") }}');
        });

        // Loading spinner on level update form submit
        $('#user_level_update_form').on('submit', function () {
            var btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true);
            btn.html('<i class="fas fa-spinner fa-spin" style="font-size: 12px;"></i> {{ __("Saving...") }}');
        });

        $(document).on('click', '.cancel_user_item_model_btn', function () {
            $('#item_modal_update').modal('hide');
        });
        $('#add_form').on('submit', function (e) {
            e.preventDefault(); // prevent default form submit

            let form = $(this);
            let formData = form.serialize();

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                success: function (response) {
                    // ✅ close modal
                    $('#Add_model').modal('hide');

                    $('#Add_model').modal('hide');

                    // ✅ Reload the page
                    location.reload();

                },
                error: function (xhr) {
                    // show error message
                    let errors = xhr.responseJSON.errors;
                    let msg = '';
                    for (let key in errors) {
                        msg += errors[key][0] + '\n';
                    }
                    alert(msg || 'Something went wrong!');
                }
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedTab = urlParams.get('tab') || 'packs';

        const allTabs = document.querySelectorAll('.tab-btn');
        let targetElement = null;

        allTabs.forEach(tab => {
            const target = tab.getAttribute('data-target');
            const content = document.getElementById(target);

            if (!content) return; // ✅ prevent null error

            if (target === selectedTab + '-tab') {
                tab.classList.add('active');
                content.classList.remove('d-none');
                content.style.display = 'block';
                targetElement = content;
            } else {
                tab.classList.remove('active');
                content.classList.add('d-none');
                content.style.display = 'none';
            }

            // Remove the click handler that prevents default and reloads
        });

        if (targetElement) {
            setTimeout(() => {
                targetElement.scrollIntoView({behavior: 'smooth'});
            }, 300);
        }
    });

    // Function to handle tab switching
    function handleTabSwitching() {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedTab = urlParams.get('tab') || 'packs';

        const allTabs = document.querySelectorAll('.tab-btn');
        let targetElement = null;

        allTabs.forEach(tab => {
            const target = tab.getAttribute('data-target');
            const content = document.getElementById(target);

            if (!content) return; // ✅ prevent null error

            if (target === selectedTab + '-tab') {
                tab.classList.add('active');
                content.classList.remove('d-none');
                content.style.display = 'block';
                targetElement = content;
            } else {
                tab.classList.remove('active');
                content.classList.add('d-none');
                content.style.display = 'none';
            }
        });

        if (targetElement) {
            setTimeout(() => {
                targetElement.scrollIntoView({behavior: 'smooth'});
            }, 300);
        }
    }

    // Initialize PJAX — exclude filter buttons from pjax interception
    $(document).pjax('a[data-pjax]:not(.btn)', '#pjax-container');

    // Force all .btn links inside forms/tabs to bypass pjax (full page reload)
    // BUT exclude .nav-link inside gift-log and charge tabs (handled by AJAX sub-tab switcher below)
    $(document).on('click', '.btn-default, .btn-info, .charge_action', function(e) {
        if ($(this).is('a') && $(this).attr('href') && $(this).attr('href') !== '#' && !$(this).closest('.tab-btn').length) {
            e.stopImmediatePropagation();
            window.location.href = $(this).attr('href');
            return false;
        }
    });

    // ── AJAX Sub-Tab Switcher (sender/receiver in gift-log, receiver/charger in charge) ──
    // When clicking sub-tabs (.nav-link) inside gift-log-tab or charge-tab, reload just the tab content via AJAX
    $(document).on('click', '#gift-log-tab .nav-link, #charge-tab .nav-link', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var $link = $(this);
        var href = $link.attr('href');
        if (!href || href === '#') return;

        // Determine which parent tab container to reload
        var $tabContent = $link.closest('.tab-content');
        var tabId = $tabContent.attr('id');

        // Update active state on sub-tab pills
        $link.closest('ul.nav-pills').find('li').removeClass('active');
        $link.closest('ul.nav-pills').find('.nav-link').removeClass('active');
        $link.closest('li').addClass('active');
        $link.addClass('active');

        // Show loading spinner inside the tab
        var $cardBody = $tabContent.find('.box-body.p-3, .box-body').first().parent();
        var originalContent = $tabContent.html();
        $tabContent.prepend('<div id="subtab-loading" style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.8);z-index:100;display:flex;align-items:center;justify-content:center;border-radius:14px;"><i class="fa fa-spinner fa-spin fa-2x" style="color:var(--primary-color);"></i></div>');
        $tabContent.css('position', 'relative');

        // Update URL without reload
        history.pushState(null, '', window.location.pathname + href);

        // Fetch new content via AJAX
        $.ajax({
            url: window.location.pathname + href,
            type: 'GET',
            success: function(html) {
                var $parsed = $('<div>').append($.parseHTML(html, document, true));
                var $newContent = $parsed.find('#' + tabId);
                if ($newContent.length) {
                    $tabContent.html($newContent.html());
                    $tabContent.css('position', '');
                    // Re-initialize select2 inside loaded tab if needed
                    $tabContent.find('#agency_id').each(function() {
                        if (!$(this).data('select2')) {
                            $(this).select2({
                                placeholder: 'Select agency',
                                allowClear: true,
                                ajax: {
                                    url: '/api/search/host-agency',
                                    dataType: 'json',
                                    delay: 250,
                                    data: function(params) { return { q: params.term, page: params.page || 1 }; },
                                    processResults: function(data) {
                                        return {
                                            results: data.data.map(function(item) { return { id: item.id, text: item.name }; }),
                                            pagination: { more: data.next_page_url !== null }
                                        };
                                    },
                                    cache: true
                                }
                            });
                        }
                    });
                } else {
                    // Fallback: remove loading overlay
                    $tabContent.find('#subtab-loading').remove();
                    $tabContent.css('position', '');
                }
            },
            error: function() {
                // Remove loading and show error
                $tabContent.find('#subtab-loading').remove();
                $tabContent.css('position', '');
                Swal.fire({ icon: 'error', title: '{{ __("Failed to load content") }}' });
            }
        });
    });

    // PJAX event listeners for loading indicator
    $(document).on('pjax:start', function() {
        $('#tab-loading').show();
    });

    $(document).on('pjax:end', function() {
        $('#tab-loading').hide();
        handleTabSwitching(); // Update tabs after PJAX load
    });

    // Handle tab switching on initial load
    document.addEventListener("DOMContentLoaded", function () {
        handleTabSwitching();
    });
    document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedTab = urlParams.get('tab') || 'packs';

        const allTabs = document.querySelectorAll('.tab-btn');
        let targetElement = null;

        allTabs.forEach(tab => {
            const target = tab.getAttribute('data-target');
            const content = document.getElementById(target);

            if (!content) return; // ✅ prevent null error

            if (target === selectedTab + '-tab') {
                tab.classList.add('active');
                content.classList.remove('d-none');
                content.style.display = 'block';
                targetElement = content;
            } else {
                tab.classList.remove('active');
                content.classList.add('d-none');
                content.style.display = 'none';
            }

            // Remove the click handler that prevents default and reloads
        });

        if (targetElement) {
            setTimeout(() => {
                targetElement.scrollIntoView({behavior: 'smooth'});
            }, 300);
        }
    });


    // Mark the initially loaded tab as already loaded (data is in the page)
    (function() {
        var initialTab = '{{ $activeTab }}';
        var initialEl = document.getElementById(initialTab + '-tab');
        if (initialEl) initialEl.dataset.loaded = 'true';
        // Level tab is always fully rendered (no server data needed)
        var levelEl = document.getElementById('level-tab');
        if (levelEl) levelEl.dataset.loaded = 'true';
    })();

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();

            // Switch tabs visually
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => {
                c.classList.remove('active');
                c.classList.add('d-none');
                c.style.display = 'none';
            });
            btn.classList.add('active');

            var target = btn.getAttribute('data-target');
            var targetEl = document.getElementById(target);
            var href = btn.getAttribute('href');

            if (targetEl) {
                targetEl.classList.add('active');
                targetEl.classList.remove('d-none');
                targetEl.style.display = 'block';
            }

            // Update URL without reload
            if (href) {
                history.pushState(null, '', window.location.pathname + href);
            }

            // If tab content not loaded yet, fetch via AJAX
            if (targetEl && !targetEl.dataset.loaded) {
                targetEl.innerHTML = '<div style="text-align:center;padding:60px;"><i class="fa fa-spinner fa-spin fa-2x" style="color:var(--primary-color);"></i><p style="margin-top:12px;color:var(--text-muted);font-size:14px;">{{ __("Loading") }}...</p></div>';

                $.ajax({
                    url: window.location.pathname + href,
                    type: 'GET',
                    success: function(html) {
                        var $parsed = $('<div>').append($.parseHTML(html, document, true));
                        var $newContent = $parsed.find('#' + target);
                        if ($newContent.length) {
                            $(targetEl).html($newContent.html());
                            targetEl.dataset.loaded = 'true';
                            // Re-initialize select2 inside loaded tab if needed
                            $(targetEl).find('#agency_id').each(function() {
                                if (!$(this).data('select2')) {
                                    $(this).select2({
                                        placeholder: 'Select agency',
                                        allowClear: true,
                                        ajax: {
                                            url: '/api/search/host-agency',
                                            dataType: 'json',
                                            delay: 250,
                                            data: function(params) { return { q: params.term, page: params.page || 1 }; },
                                            processResults: function(data) {
                                                return {
                                                    results: data.data.map(function(item) { return { id: item.id, text: item.name }; }),
                                                    pagination: { more: data.next_page_url !== null }
                                                };
                                            },
                                            cache: true
                                        }
                                    });
                                }
                            });
                        } else {
                            targetEl.innerHTML = '<div style="text-align:center;padding:60px;color:var(--danger);"><i class="fa fa-exclamation-triangle fa-2x"></i><p style="margin-top:12px;">{{ __("Failed to load content") }}</p></div>';
                        }
                    },
                    error: function() {
                        targetEl.innerHTML = '<div style="text-align:center;padding:60px;color:var(--danger);"><i class="fa fa-exclamation-triangle fa-2x"></i><p style="margin-top:12px;">{{ __("Failed to load content") }}. {{ __("Please try again") }}.</p></div>';
                    }
                });
            }
        });
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        var urlParams = new URLSearchParams(window.location.search);
        var selectedTab = urlParams.get('tab') || 'packs';
        var tabBtn = document.querySelector('.tab-btn[data-target="' + selectedTab + '-tab"]');
        if (tabBtn) tabBtn.click();
    });


    $(document).ready(function () {

        $(document).on('click', '.remove-bd-btn', function (e) {
            e.preventDefault();
            let btn = $(this);
            let url = btn.data('url');

            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: "لن تستطيع التراجع بعد الحذف!",
                showCancelButton: true,
                confirmButtonColor: 'var(--danger)',
                cancelButtonColor: 'var(--info)',
                confirmButtonText: 'نعم، احذف',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.value) {
                    let form = $('<form>', {
                        'method': 'POST',
                        'action': url
                    }).append($('<input>', {
                        'type': 'hidden',
                        'name': '_token',
                        'value': LA.token
                    })).append($('<input>', {
                        'type': 'hidden',
                        'name': '_method',
                        'value': 'POST'
                    }));
                    form.appendTo('body').submit();
                }
            });
        });

        $('#agency_id').select2({
            placeholder: 'Select agency',
            allowClear: true,
            ajax: {
                url: '/api/search/host-agency', // ✅ make sure this matches your route
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        page: params.page || 1
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.data.map(item => ({
                            id: item.id,
                            text: item.name
                        })),
                        pagination: {
                            more: data.next_page_url !== null
                        }
                    };
                },
                cache: true
            }
        });

        console.log("Document ready");

        function showLoader() {
            console.log("Showing loader");
            Swal.fire({
                title: 'Loading...',  // تغيير النص ليوضح الرسالة
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        function showSuccess(message, callback = null) {
            console.log("Showing success:", message);
            Swal.fire({
                icon: 'success',  // استبدال type بـ icon
                title: message,
                confirmButtonText: 'OK'
            }).then(() => {
                console.log("Success confirmed");
                if (callback) {
                    console.log("Running success callback");
                    callback();
                }
            });
        }

        function showError(message) {
            console.log("Showing error:", message);
            Swal.fire({
                icon: 'error',  // استبدال type بـ icon
                title: message,
                confirmButtonText: 'OK'
            });
        }

        function confirmAction(message, onConfirm) {
            console.log("Confirm action:", message);
            Swal.fire({
                title: message,
                icon: 'question',  // استبدال type بـ icon
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel'
            }).then(result => {
                console.log("Confirmation result:", result);
                console.log("isConfirmed:", result.isConfirmed);

                if (result.value) {
                    console.log("User confirmed action");
                    onConfirm();
                } else {
                    console.log("User cancelled action");
                }
            });
        }


        // قبول الطلب
        $('.accept-btn').click(function () {
            const id = $(this).data('id');
            console.log("Accept clicked, ID:", id);
            confirmAction('{{ __("are_you_sure_accept") }}', () => {
                showLoader();
                $.post(`/admin/agencies/accept_join/${id}`, {
                    _token: '{{ csrf_token() }}'
                }, function (response) {
                    Swal.close();
                    console.log("Accept response:", response);
                    if (response.status) {
                        showSuccess(response.message, () => {
                            const url = new URL(window.location.href);
                            url.searchParams.set('tab', 'requests');
                            window.location.href = url.toString();
                        });
                    } else {
                        showError(response.message);
                    }
                }).fail(function (xhr) {
                    Swal.close();
                    console.error("Accept failed", xhr);
                    const res = xhr.responseJSON;
                    showError(res?.message ?? '{{ __("failed_accept_request") }}');
                });
            });
        });

        $(document).on('click', '.edit_item_model_btn', function () {
            let itemId = $(this).data('id');

            // Clear the form
            $('#add_form')[0].reset();

            // Set the hidden ID field
            $('.item_id').val(itemId);

            // Open the modal
            $('#Add_model').modal('show');
        });
        $(document).on('click', '.close-modal-btn', function () {
            $('#Add_model').modal('hide');
        });

        $(document).on('click', '.delete-btn', function () {
            let itemId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: '/admin/delete-pack/' + itemId,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'An error occurred.'
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.delete-badge-btn', function () {
            let itemId = $(this).data('id');

            Swal.fire({
                title: "{{ __('Are you sure?') }}",
                text: "{{ __('This action cannot be undone!') }}",
                showCancelButton: true,
                confirmButtonText: "{{ __('Yes, delete it!') }}",
                cancelButtonText: "{{ __('Cancel') }}",
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: '/admin/delete-badge/' + itemId,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'An error occurred.'
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.delete-vip-btn', function () {
            let itemId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: '/admin/delete-user-vip/' + itemId,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'An error occurred.'
                            });
                        }
                    });
                }
            });
        });


        // رفض الطلب
        $('.reject-btn').click(function () {
            const id = $(this).data('id');
            console.log("Reject clicked, ID:", id);
            confirmAction('{{ __("are_you_sure_reject") }}', () => {
                showLoader();
                $.post(`/admin/agencies/reject_join/${id}`, {
                    _token: '{{ csrf_token() }}'
                }, function (response) {
                    Swal.close();
                    console.log("Reject response:", response);
                    if (response.status) {
                        showSuccess(response.message, () => {
                            const url = new URL(window.location.href);
                            url.searchParams.set('tab', 'requests');
                            window.location.href = url.toString();
                        });
                    } else {
                        showError(response.message);
                    }
                }).fail(function (xhr) {
                    Swal.close();
                    console.error("Reject failed", xhr);
                    const res = xhr.responseJSON;
                    showError(res?.message ?? '{{ __("failed_reject_request") }}');
                });
            });
        });

        // ترقية إلى Admin
        $('.make-admin-btn').click(function () {
            const id = $(this).data('id');
            console.log("Make admin clicked, ID:", id);
            confirmAction('{{ __("are_you_sure_make_admin") }}', () => {
                showLoader();
                $.post(`/admin/agencies/admin/${id}`, {
                    _token: '{{ csrf_token() }}'
                }, function (response) {
                    Swal.close();
                    console.log("Make admin response:", response);
                    if (response.status) {
                        showSuccess(response.message, () => {
                            location.reload();
                        });
                    } else {
                        showError(response.message);
                    }
                }).fail(function (xhr) {
                    Swal.close();
                    console.error("Make admin failed", xhr);
                    const res = xhr.responseJSON;
                    showError(res?.message ?? '{{ __("failed_make_admin") }}');
                });
            });
        });


    });

    // ── Profile Action Buttons (Toggle/Kick) ──
    function profileAction(url, btn) {
        Swal.fire({
            title: '{{ __("Are you sure?") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes") }}',
            cancelButtonText: '{{ __("Cancel") }}'
        }).then(function(result) {
            if (result.value) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || LA.token },
                    success: function(res) {
                        // Toggle the icon between on/off
                        var $btn = $(btn);
                        var $icon = $btn.find('i.fa');
                        if ($icon.hasClass('fa-toggle-on')) {
                            $icon.removeClass('fa-toggle-on').addClass('fa-toggle-off');
                            $icon.css('color', 'var(--danger)');
                        } else {
                            $icon.removeClass('fa-toggle-off').addClass('fa-toggle-on');
                            $icon.css('color', 'var(--success)');
                        }
                        Swal.fire({ icon: 'success', title: res.message });
                    },
                    error: function(xhr) {
                        Swal.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Error' });
                    }
                });
            }
        });
    }

    function profileActionConfirm(url, msg) {
        Swal.fire({
            title: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            confirmButtonColor: 'var(--danger)'
        }).then(function(result) {
            if (result.value) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || LA.token },
                    success: function(res) {
                        Swal.fire({ icon: 'success', title: res.message }).then(function() { location.reload(); });
                    },
                    error: function(xhr) {
                        Swal.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Error' });
                    }
                });
            }
        });
    }

    // ── Change Agency Modal Submit ──
    $(document).on('click', '#submitChangeAgency', function() {
        var agencyId = $('#changeAgencySelect').val();
        if (!agencyId) {
            Swal.fire({ icon: 'warning', title: '{{ __("Please select an agency") }}' });
            return;
        }
        $.ajax({
            url: '{{ route("users.change-agency", $user->id) }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || LA.token },
            data: { agency_id: agencyId },
            success: function(res) {
                $('#changeAgencyModal').modal('hide');
                Swal.fire({ icon: 'success', title: res.message }).then(function() { location.reload(); });
            },
            error: function(xhr) {
                Swal.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Error' });
            }
        });
    });

    // Init select2 for change agency modal
    $(document).on('shown.bs.modal', '#changeAgencyModal', function() {
        $('#changeAgencySelect').select2({
            placeholder: '{{ __("Select Agency") }}',
            allowClear: true,
            dropdownParent: $('#changeAgencyModal'),
            ajax: {
                url: '/admin/search/host-agency',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) {
                    var items = data.data || data;
                    return { results: items.map(function(item) { return { id: item.id, text: item.name + ' (' + item.id + ')' }; }) };
                },
                cache: true
            }
        });
    });

</script>

{{-- Change Agency Modal --}}
<div class="modal fade" id="changeAgencyModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--gradient-1);color: var(--on-color);">
                <h5 class="modal-title"><i class="fa fa-exchange"></i> {{ __('dashboard.changeAgency') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: var(--on-color);opacity:0.9;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>{{ __('Select Agency') }}</label>
                    <select id="changeAgencySelect" class="form-control" style="width:100%;"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="submitChangeAgency">{{ __('save') }}</button>
            </div>
        </div>
    </div>
</div>

