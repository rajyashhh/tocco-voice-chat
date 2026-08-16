<style>
    .dark-mode {
        /* Background and text colors ONLY */
        --white: #152038;
        --gray-50: #0d1b2a;
        --gray-200: #1a2d4d;
        --gray-300: #233d5a;
        --box-background-color: #152038;
        --table-background-color: #0d1b2a;
        --text-primary-color: #d1d5db;
        --text-secondary-color: #9ca3af;
        --dark-primary-color: rgb(15, 23, 42);
        --dark-secondry-color: rgb(30, 41, 59);
    }

    body.dark-mode, .dark-mode body {
        background-color: #0d1b2a !important;
        color: var(--text-primary-color) !important;
    }

    .dark-mode .content,
    .dark-mode .box,
    .dark-mode .card,
    .dark-mode .table,
    .dark-mode .modal-content,
    .dark-mode .skin-black-light .main-header > .navbar,
    .dark-mode .nav-tabs-custom,
    .dark-mode .form-col,
    .dark-mode .settings-menu button,
    .dark-mode .swal2-popup,
    .dark-mode .mobile-select-menu,
    .dark-mode .form-horizontal {
        background-color: var(--dark-secondry-color) !important;
        color: var(--text-primary-color) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
    }

    .dark-mode .pagination > .disabled > a,
    .dark-mode .pagination > .disabled > a:focus,
    .dark-mode .pagination > .disabled > a:hover,
    .dark-mode .pagination > .disabled > span,
    .dark-mode .pagination > .disabled > span:focus,
    .dark-mode .pagination>li>a,
    .dark-mode .pagination > .disabled > span:hover,
    .dark-mode .stat-card,
    .dark-mode .table-section,
    .dark-mode .empty-table,
    .dark-mode .notification-item.unread,
    .dark-mode .notification-item.read,
    .dark-mode .fields-group .input-group.input-group-sm,
    .dark-mode .select2-container--default .select2-selection--multiple .select2-selection__choice,
    .dark-mode .sm\:text-base,
    .dark-mode .user-card,
    .dark-mode .received-message .message-bubble,
    .dark-mode .action-menu,
    .dark-mode .more-btn:hover,
    .dark-mode .filter-toggle,
    .dark-mode .filter-panel,
    .dark-mode .active-filters,
    .dark-mode .select2-dropdown {
        background-color: var(--dark-secondry-color) !important;
    }

    .dark-mode .skin-black-light .content-wrapper,
    .dark-mode .skin-black-light .wrapper,
    .dark-mode .select2-container--default .select2-selection--single,
    .dark-mode form,
    .dark-mode .nav-tabs,
    .dark-mode .tab-content,
    .dark-mode .settings-sidebar,
    .dark-mode #landPageSettings,
    .dark-mode .performers-card,
    .dark-mode .section-box,
    .dark-mode .bootstrap-datetimepicker-widget table thead tr:first-child th:hover,
    .dark-mode .datepicker table tr td.day:hover,
    .dark-mode .datepicker table tr td.active,
    .dark-mode .datepicker table tr td.active:hover,
    .dark-mode .datepicker table tr td.active.disabled,
    .dark-mode .datepicker table tr td.active.disabled:hover,
    .dark-mode .datepicker table tr td span.active,
    .dark-mode .datepicker table tr td span.active:hover,
    .dark-mode .datepicker table tr td span.active.disabled,
    .dark-mode .datepicker table tr td span.active.disabled:hover,
    .dark-mode .datepicker table tr td span:hover,
    .dark-mode .select2-container--default .select2-selection--multiple,
    .dark-mode .modal-body2,
    .dark-mode .modal-no,
    .dark-mode .interactions-panel,
    .dark-mode .reels-sidebar,
    .dark-mode .chat-input,
    .dark-mode .chat-messages,
    .dark-mode .edit-btn,
    .dark-mode .reply-btn,
    .dark-mode .delete-btn,
    .dark-mode .chat-header,
    .dark-mode .refresh-btn,
    .dark-mode .action-menu-item:hover,
    .dark-mode select > option,
    .dark-mode .viewer-header,
    .dark-mode .moment-post,
    .dark-mode .side-modal-content,
    .dark-mode .users-sidebar,
        /*.dark-mode .box-footer {*/
    .dark-mode .sidebarContainer {
        background: var(--dark-primary-color) !important;
    }

    .dark-mode .nav-tabs-custom>.nav-tabs>li.active>a,
    .dark-mode .nav-tabs>li.active>a, .nav-tabs>li.active>a:focus, .nav-tabs>li.active>a:hover,
    .dark-mode .table-striped>tbody>tr:nth-of-type(odd),
    .dark-mode .pagination-wrapper,
    .dark-mode .box-title,
    .dark-mode .modal-footer,
    .dark-mode .box-header > .fa, .dark-mode .box-header > .glyphicon,
    .dark-mode .box-header > .ion, .dark-mode .box-header .box-title,
    .dark-mode .nav-tabs-custom>.nav-tabs>li.active:hover>a {
        background: none !important;
        color: unset !important;
    }

    .dark-mode .top-loading-indicator,
    .dark-mode .reply-preview,
    .dark-mode .skeleton {
        background: var(--dark-secondry-color);
    }

    .dark-mode .box-header.with-border,
    .dark-mode .section-header {
        border-bottom: 1px solid var(--white) !important;
    }

    .dark-mode .box-footer {
        border-top: 2px solid var(--white) !important;
    }

    .dark-mode .table > thead > tr > th {
        background-color: var(--dark-secondry-color) !important;
        color: var(--text-primary-color) !important;
        border-bottom-color: rgba(255, 255, 255, 0.08) !important;
    }

    /* Root + breadcrumb/title: establish the dark text default from the
       token (was hardcoded #ffffff). */
    .dark-mode,
    .dark-mode .content-header > .breadcrumb > li > a,
    .dark-mode .content-header > h1 {
        color: var(--text-primary-color) !important;
    }

    /* Targeted generic-text net for DARK.
       REPLACES the former blanket `.dark-mode *{color:#fff!important}` that
       matched every node and overrode every design token — which painted the
       standalone profile pages white-on-white and blanked the "Go Back" button.
       These rules carry NO `!important`, so element-level colors win:
       inline accent colors (e.g. the green/red stat glyphs) and token-driven
       text keep their intended color, while otherwise-unstyled generic text
       still inherits a readable value on dark surfaces. */
    .dark-mode p,
    .dark-mode span,
    .dark-mode li,
    .dark-mode td,
    .dark-mode th,
    .dark-mode dt,
    .dark-mode dd,
    .dark-mode label,
    .dark-mode strong,
    .dark-mode b,
    .dark-mode small,
    .dark-mode h1, .dark-mode h2, .dark-mode h3,
    .dark-mode h4, .dark-mode h5, .dark-mode h6 {
        color: var(--text-primary-color);
    }

    .dark-mode .box-header .form-group .control-label,
    .dark-mode .box-header .form-group > label,
    .dark-mode .filter-box .form-group .control-label,
    .dark-mode .filter-box .form-group > label {
        background: linear-gradient(to bottom, var(--dark-secondry-color) 70%, var(--dark-primary-color) 30%) !important;
    }

    .dark-mode .datepicker table tr td.old,
    .dark-mode .datepicker table tr td.new {
        color: #515151 !important;
    }

    .dark-mode ::placeholder {
        color: #aaaaaa !important;
    }

    .dark-mode .table > tbody > tr > td {
        color: var(--text-primary-color) !important;
        border-top-color: rgba(255, 255, 255, 0.05) !important;
    }

    .dark-mode,
    .dark-mode .box-body,
    .dark-mode .table-responsive,
    .dark-mode .box-body.table-responsive,
    .dark-mode .content-wrapper,
    .dark-mode .CardwalletLogsTable,
    .dark-mode .chat-messages,
    .dark-mode .viewer-container,
    .dark-mode .users-list-container,
    .dark-mode .main-sidebar {
        scrollbar-color: var(--dark-secondry-color) var(--dark-primary-color) !important;
        scrollbar-width: thin;
    }

    .dark-mode .content-header {
        background-color: var(--dark-secondry-color) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        color: var(--text-primary-color) !important;
    }

    .dark-mode .settings-menu button:hover {
        background: var(--primary-color) !important;
    }

    .dark-mode input,
    .dark-mode textarea,
    .dark-mode .agency-header,
    .dark-mode .card-header,
    .dark-mode select {
        background-color: var(--dark-primary-color) !important;
        color: var(--text-primary-color) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    .dark-mode .select2-container--default .select2-results__option[aria-selected=true],
    .dark-mode .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: rgba(255, 148, 40, 0.18) !important;
        color: #ffffff !important;
    }

    .dark-mode .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--text-primary-color) !important;
    }

    .dark-mode .form-control {
        background-color: var(--dark-primary-color) !important;
        color: var(--text-primary-color) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    .dark-mode .form-control:focus {
        background-color: var(--dark-primary-color) !important;
        color: var(--text-primary-color) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
    }

    .dark-mode .btn-default,
    .dark-mode .btn:hover,
    .dark-mode .btn-success:hover,
    .dark-mode .button:hover {
        color: var(--text-secondary-color) !important;
    }

    /* ═══════════════════════════════════════════════════════════
       UNIFIED DARK SURFACES — 2026-08 admin UI audit
       Kills every light-mode leftover under the permanent dark
       panel: striped tables, hovers, cards, chips, modals, filters.
       ═══════════════════════════════════════════════════════════ */
    .dark-mode {
        --dm-row-odd: #1E2430;
        --dm-row-even: #232A38;
        --dm-row-hover: #2C3547;
        --dm-surface: #1E2430;
        --dm-surface-2: #273040;
        --dm-chip: #2A3242;
        --dm-border: rgba(255, 255, 255, 0.08);
    }

    /* ── Tables: two close dark shades, no black/white alternation ── */
    .dark-mode .table,
    .dark-mode .grid-table,
    .dark-mode .data-table,
    .dark-mode .table-section,
    .dark-mode .gift-detail-table {
        background: var(--dm-row-odd) !important;
        color: var(--text-primary-color) !important;
        box-shadow: none !important;
    }

    .dark-mode .table tbody tr:nth-child(odd),
    .dark-mode .table tbody tr:nth-child(odd) > td,
    .dark-mode .data-table tbody tr:nth-child(odd),
    .dark-mode .data-table tbody tr:nth-child(odd) > td,
    .dark-mode .table-section tbody tr:nth-child(odd),
    .dark-mode .gift-detail-table tr:nth-child(odd) {
        background: var(--dm-row-odd) !important;
    }

    .dark-mode .table tbody tr:nth-child(even),
    .dark-mode .table tbody tr:nth-child(even) > td,
    .dark-mode .data-table tbody tr:nth-child(even),
    .dark-mode .data-table tbody tr:nth-child(even) > td,
    .dark-mode .table-section tbody tr:nth-child(even),
    .dark-mode .gift-detail-table tr:nth-child(even) {
        background: var(--dm-row-even) !important;
    }

    .dark-mode .table tbody tr:hover,
    .dark-mode .table tbody tr:hover > td,
    .dark-mode .table.table-hover tbody tr:hover,
    .dark-mode .table.table-hover tbody tr:hover > td,
    .dark-mode .grid-table tbody tr:hover > td,
    .dark-mode .data-table tbody tr:hover,
    .dark-mode .data-table tbody tr:hover > td,
    .dark-mode .gift-detail-table tr:hover {
        background: var(--dm-row-hover) !important;
        color: var(--text-primary-color) !important;
    }

    .dark-mode .table thead th,
    .dark-mode .grid-table thead th,
    .dark-mode .data-table thead th,
    .dark-mode .table-section thead th {
        background: var(--dm-surface-2) !important;
        color: var(--text-primary-color) !important;
        border-bottom-color: var(--dm-border) !important;
    }

    .dark-mode .table tbody td,
    .dark-mode .grid-table tbody td {
        border-color: var(--dm-border) !important;
    }

    /* ── Grid cell links & inline-editable values (admin/configs etc.) ──
       dynamic-style paints bare <a> black (#000); inside dark grid rows the
       x-editable value anchors and any cell links became unreadable. Plain td
       text is already covered above — this covers the anchors. */
    .dark-mode .table tbody td a,
    .dark-mode .grid-table tbody td a,
    .dark-mode .table tbody td a.editable-click,
    .dark-mode .table tbody td a[class*="grid-editable-"] {
        color: var(--text-primary-color) !important;
    }

    .dark-mode .table tbody td a.editable-empty {
        color: #f87171 !important;
    }

    .dark-mode .table-responsive {
        border-color: var(--dm-border) !important;
    }

    /* ── User / agency cards inside grids (rooms, users, charges…) ── */
    .dark-mode .auc-card,
    .dark-mode .ug-agency-card {
        background: var(--dm-surface-2) !important;
        border-color: var(--dm-border) !important;
    }

    .dark-mode .auc-avatar-img,
    .dark-mode .ug-agency-avatar,
    .dark-mode .image-wrapper img {
        border-color: rgba(255, 255, 255, 0.2) !important;
    }

    .dark-mode .auc-uid,
    .dark-mode .ug-version-chip,
    .dark-mode .permissions-counter {
        background: var(--dm-chip) !important;
        border-color: var(--dm-border) !important;
    }

    .dark-mode .ug-id-badge,
    .dark-mode .rwg-id {
        background: rgba(99, 102, 241, 0.18) !important;
        border-color: rgba(99, 102, 241, 0.4) !important;
    }

    .dark-mode .ug-coins,
    .dark-mode .rwg-coins {
        background: rgba(245, 158, 11, 0.12) !important;
        border-color: rgba(245, 158, 11, 0.35) !important;
    }

    .dark-mode .ug-device-ok {
        background: rgba(16, 185, 129, 0.18) !important;
    }

    .dark-mode .ug-device-warn {
        background: rgba(245, 158, 11, 0.2) !important;
    }

    /* ── Grid filter box + quick search ── */
    .dark-mode .box.grid-filter,
    .dark-mode .box.grid-filter .box-header {
        background: var(--dm-surface) !important;
        border-color: var(--dm-border) !important;
    }

    .dark-mode .box.grid-filter .form-control,
    .dark-mode .quick-search .form-control {
        background: var(--dark-primary-color) !important;
        border-color: var(--dm-border) !important;
        color: var(--text-primary-color) !important;
    }

    /* ── Pagination ── */
    .dark-mode .box-footer .pagination > li > a,
    .dark-mode .box-footer .pagination > li > span {
        background: var(--dm-surface-2) !important;
        border-color: var(--dm-border) !important;
        color: var(--text-primary-color) !important;
    }

    .dark-mode .box-footer .pagination > li > a:hover {
        background: var(--dm-row-hover) !important;
        color: #ffffff !important;
    }

    /* ── Boxes / content header (dynamic-style paints them white) ── */
    .dark-mode .box {
        background: var(--dark-secondry-color) !important;
        border-color: var(--dm-border) !important;
        color: var(--text-primary-color) !important;
    }

    /* ── Room profile page (room_profile.blade.php) ── */
    .dark-mode .room-header-card,
    .dark-mode .agency-tabs,
    .dark-mode .diamond-summary-box,
    .dark-mode .main-chart-container {
        background: var(--dm-surface) !important;
        border-color: var(--dm-border) !important;
    }

    .dark-mode .room-meta-tag,
    .dark-mode .btn-back,
    .dark-mode .room-stat-item,
    .dark-mode .tab-btn:hover:not(.active),
    .dark-mode .gift-timestamp-icon,
    .dark-mode .gift-action-btn.back,
    .dark-mode .file-upload-label,
    .dark-mode .wallet-button {
        background: var(--dm-chip) !important;
        border-color: var(--dm-border) !important;
        color: var(--text-primary-color) !important;
    }

    .dark-mode .room-stat-item:hover,
    .dark-mode .btn-back:hover,
    .dark-mode .wallet-button:hover,
    .dark-mode .member-item:hover,
    .dark-mode .file-upload-label:hover {
        background: var(--dm-row-hover) !important;
        color: var(--text-primary-color) !important;
    }

    .dark-mode .progress {
        background: var(--dm-chip) !important;
    }

    .dark-mode .member-item {
        border-bottom-color: var(--dm-border) !important;
    }

    .dark-mode .badge-primary { background: rgba(37, 99, 235, 0.25) !important; }
    .dark-mode .badge-success { background: rgba(22, 163, 74, 0.25) !important; }
    .dark-mode .badge-danger { background: rgba(220, 38, 38, 0.25) !important; }
    .dark-mode .badge-warning { background: rgba(180, 83, 9, 0.3) !important; }
    .dark-mode .badge-info { background: rgba(79, 70, 229, 0.3) !important; }
    .dark-mode .badge-secondary { background: rgba(100, 116, 139, 0.3) !important; }

    .dark-mode .status-pill.inactive {
        background: rgba(220, 38, 38, 0.2) !important;
        border-color: rgba(220, 38, 38, 0.4) !important;
    }

    .dark-mode .room-alert {
        background: rgba(220, 38, 38, 0.15) !important;
        border-color: rgba(220, 38, 38, 0.35) !important;
    }

    /* ── Gift detail page ── */
    .dark-mode .gift-stat-card,
    .dark-mode .gift-detail-card,
    .dark-mode .gift-media-card,
    .dark-mode .gift-timestamp-item,
    .dark-mode .gift-music-badge {
        background: var(--dm-surface) !important;
        border-color: var(--dm-border) !important;
    }

    .dark-mode .gift-type-badge {
        background: var(--dm-chip) !important;
    }

    /* ── Wallet / transfer popups (bd, area manager, superadmin) ── */
    .dark-mode .wallet-modal,
    .dark-mode .transferModal,
    .dark-mode .wallet-overlay,
    .dark-mode .achievement-container,
    .dark-mode .settings-content,
    .dark-mode .new-form {
        background: var(--dm-surface) !important;
        border-color: var(--dm-border) !important;
        color: var(--text-primary-color) !important;
    }

    .dark-mode .form-section {
        background: var(--dm-surface-2) !important;
    }

    .dark-mode .form-divider {
        border-bottom-color: var(--dm-border) !important;
    }

    .dark-mode .form-divider span,
    .dark-mode .divider-text {
        background: var(--dm-surface) !important;
        color: var(--text-secondary-color) !important;
    }

    .dark-mode .all-page .btn {
        background-color: var(--dm-chip) !important;
        color: var(--text-primary-color) !important;
    }

    /* ── Import games modal ── */
    .dark-mode #importJsonModal .modal-body,
    .dark-mode #importJsonModal .or-divider span {
        background: var(--dm-surface) !important;
    }

    .dark-mode #importJsonModal .form-section,
    .dark-mode #importJsonModal .modal-footer,
    .dark-mode #importJsonModal textarea.form-control,
    .dark-mode #importJsonModal textarea.form-control:focus,
    .dark-mode #importJsonModal .btn-cancel {
        background: var(--dm-surface-2) !important;
        border-color: var(--dm-border) !important;
        color: var(--text-primary-color) !important;
    }

    .dark-mode #importJsonModal .validation-msg.success { background: rgba(16, 185, 129, 0.15) !important; border-color: rgba(16, 185, 129, 0.4) !important; }
    .dark-mode #importJsonModal .validation-msg.error { background: rgba(220, 38, 38, 0.15) !important; border-color: rgba(220, 38, 38, 0.4) !important; }
    .dark-mode #importJsonModal .validation-msg.warning { background: rgba(245, 158, 11, 0.15) !important; border-color: rgba(245, 158, 11, 0.4) !important; }

    /* ── Dropdown menus + datepicker panels (were plain white) ── */
    .dark-mode .dropdown-menu,
    .dark-mode .bootstrap-datetimepicker-widget.dropdown-menu,
    .dark-mode .datepicker.dropdown-menu,
    .dark-mode .datepicker-dropdown {
        background: var(--dm-surface-2) !important;
        border: 1px solid var(--dm-border) !important;
        color: var(--text-primary-color) !important;
    }

    .dark-mode .dropdown-menu > li > a {
        color: var(--text-primary-color) !important;
    }

    .dark-mode .dropdown-menu .divider {
        background-color: var(--dm-border) !important;
    }

    .dark-mode .datepicker table tr td,
    .dark-mode .datepicker table tr th {
        background: transparent;
    }
</style>

{{-- Dark mode is now user-toggleable and applied by css/theme-tokens.blade.php
     (reads localStorage 'admin-theme', default dark). The old permanent-dark
     script was removed so the toggle can switch to light. --}}
