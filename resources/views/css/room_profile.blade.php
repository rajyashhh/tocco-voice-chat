<style>
    /* ═══════════════════════════════════════════════════════════
       ROOM PROFILE — CLEAN PROFESSIONAL UI
       Colors come from the central design system (css/theme-tokens):
       one change to the base tokens recolors the whole panel. No local
       :root here — --primary-color and every token below are inherited.
       ═══════════════════════════════════════════════════════════ */

    /* ── Reset ────────────────────────────────────────────── */
    .room-profile-page * { box-sizing: border-box; }

    .room-profile-page {
        max-width: 1300px;
        margin: 0 auto;
        padding: 20px;
        font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: var(--text-primary);
    }

    /* ══════════════════════════════════════════════════════════
       HEADER CARD
       ══════════════════════════════════════════════════════════ */
    .room-header-card {
        background: var(--card-bg);
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 12px rgba(0,0,0,.04);
        padding: 28px 32px;
        margin-bottom: 24px;
        border: 1px solid var(--card-border);
    }

    /* Top row: Avatar + Info + Actions */
    .room-header-top {
        display: flex;
        align-items: flex-start;
        gap: 24px;
        margin-bottom: 20px;
    }

    .room-avatar {
        flex-shrink: 0;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid var(--card-border);
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
    }

    .room-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .room-header-info {
        flex: 1;
        min-width: 0;
    }

    .room-name {
        font-size: 24px;
        font-weight: 800;
        color: var(--text-primary);
        margin: 0 0 10px 0;
        line-height: 1.2;
    }

    /* Meta row (ID, UID, etc.) */
    .room-meta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 12px;
    }

    .room-meta-tag {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: var(--surface-sunken);
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 12.5px;
        color: var(--text-secondary);
        white-space: nowrap;
    }

    .room-meta-tag i {
        font-size: 10px;
        color: var(--text-muted);
    }

    .room-meta-tag strong {
        color: var(--text-primary);
        font-weight: 700;
    }

    /* Owner row */
    .room-owner-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        font-size: 13.5px;
    }

    .room-owner-row .owner-label {
        color: var(--text-secondary);
        font-weight: 500;
    }

    .room-owner-row a {
        color: var(--primary-color);
        font-weight: 700;
        text-decoration: none;
    }

    .room-owner-row a:hover {
        text-decoration: underline;
    }

    /* Status + Features row */
    .room-status-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
    }

    .status-pill.active {
        background: var(--chip-success-bg);
        color: var(--chip-success-fg);
        border: 1px solid color-mix(in srgb, var(--success) 35%, transparent);
    }

    .status-pill.active::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--success);
        animation: blink 1.5s infinite;
    }

    .status-pill.inactive {
        background: var(--chip-danger-bg);
        color: var(--chip-danger-fg);
        border: 1px solid color-mix(in srgb, var(--danger) 35%, transparent);
    }

    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: .3; }
    }

    /* Feature tags — decorative category badges driven by semantic tokens
       so they recolor centrally with the palette. */
    .feature-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 700;
        color: var(--on-color);
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .feature-tag i { font-size: 9px; }

    .feature-tag.popular     { background: var(--warning); }
    .feature-tag.top         { background: var(--info); }
    .feature-tag.recommended { background: var(--accent); color: var(--on-accent); }
    .feature-tag.secret      { background: var(--warning); color: var(--text-primary); }
    .feature-tag.live        { background: var(--danger); }

    /* Header actions */
    .room-header-actions {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary);
        background: var(--surface-sunken);
        border: 1px solid var(--border);
        text-decoration: none;
        cursor: pointer;
        transition: all .2s ease;
        white-space: nowrap;
    }

    .btn-back:hover {
        background: var(--border);
        color: var(--text-primary);
    }

    .btn-edit-room {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--on-accent);
        background: var(--accent);
        border: none;
        cursor: pointer;
        transition: all .2s ease;
        white-space: nowrap;
    }

    .btn-edit-room:hover {
        background: var(--accent-strong);
        box-shadow: 0 2px 8px rgba(0,0,0,.15);
    }

    /* ══════════════════════════════════════════════════════════
       STATS ROW
       ══════════════════════════════════════════════════════════ */
    .room-stats-row {
        display: flex;
        gap: 12px;
        padding-top: 20px;
        border-top: 1px solid var(--surface-sunken);
    }

    .room-stat-item {
        flex: 1;
        text-align: center;
        padding: 14px 8px;
        background: var(--surface-raised);
        border-radius: 10px;
        border: 1px solid var(--surface-sunken);
        transition: all .2s ease;
    }

    .room-stat-item:hover {
        background: var(--surface-sunken);
        border-color: var(--border);
    }

    .room-stat-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        color: var(--on-color);
        margin-bottom: 8px;
    }

    /* Stat markers — semantic tokens (recolor with the palette). */
    .room-stat-icon.c-blue   { background: var(--info); }
    .room-stat-icon.c-green  { background: var(--success); }
    .room-stat-icon.c-amber  { background: var(--warning); }
    .room-stat-icon.c-purple { background: var(--accent); color: var(--on-accent); }

    .room-stat-value {
        font-size: 18px;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.2;
    }

    .room-stat-value img {
        height: 22px;
        vertical-align: middle;
    }

    .room-stat-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-top: 2px;
    }

    /* ══════════════════════════════════════════════════════════
       ERROR ALERT
       ══════════════════════════════════════════════════════════ */
    .room-alert {
        background: var(--chip-danger-bg);
        border: 1px solid color-mix(in srgb, var(--danger) 35%, transparent);
        border-radius: 10px;
        padding: 12px 18px;
        margin-bottom: 16px;
        color: var(--danger);
        font-size: 13px;
    }

    .room-alert ul {
        margin: 0;
        padding-inline-start: 16px;
    }

    /* ══════════════════════════════════════════════════════════
       TABS
       ══════════════════════════════════════════════════════════ */
    .agency-tabs {
        display: flex;
        gap: 4px;
        padding: 5px;
        background: var(--card-bg);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        margin-bottom: 24px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border: 1px solid var(--card-border);
    }

    .tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        background: transparent;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary);
        cursor: pointer;
        white-space: nowrap;
        transition: all .2s ease;
        text-decoration: none;
    }

    .tab-btn i {
        font-size: 13px;
        opacity: .7;
    }

    .tab-btn:hover:not(.active) {
        background: var(--surface-sunken);
        color: var(--text-primary);
    }

    .tab-btn.active {
        background: var(--primary-color);
        color: var(--on-accent);
        box-shadow: 0 1px 4px rgba(0,0,0,.12);
    }

    .tab-btn.active i {
        opacity: 1;
    }

    /* ══════════════════════════════════════════════════════════
       TAB CONTENT
       ══════════════════════════════════════════════════════════ */
    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    /* ══════════════════════════════════════════════════════════
       CARDS
       ══════════════════════════════════════════════════════════ */
    .card {
        background: var(--card-bg);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        margin-bottom: 24px;
        border: 1px solid var(--card-border);
        overflow: hidden;
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 24px;
        border-bottom: 1px solid var(--surface-sunken);
        background: var(--surface-raised);
    }

    .card-title {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .card-title i {
        color: var(--primary-color);
        font-size: 14px;
    }

    .card .p-3 {
        padding: 20px 24px;
    }

    /* ══════════════════════════════════════════════════════════
       TABLES
       ══════════════════════════════════════════════════════════ */
    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table thead th {
        padding: 11px 16px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-secondary);
        background: var(--surface-raised);
        border-bottom: 2px solid var(--card-border);
        white-space: nowrap;
        text-align: start;
    }

    .table tbody td {
        padding: 12px 16px;
        font-size: 13px;
        color: var(--text-primary);
        border-bottom: 1px solid var(--surface-sunken);
        vertical-align: middle;
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    .table tbody tr:hover {
        background: var(--surface-raised);
    }

    /* User cell */
    .user-cell {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: inherit;
    }

    .user-cell img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--card-border);
        flex-shrink: 0;
    }

    .user-cell:hover img {
        border-color: var(--primary-color);
    }

    .user-cell-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .user-cell-name {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 13px;
        line-height: 1.3;
    }

    .user-cell-meta {
        font-size: 11.5px;
        color: var(--text-muted);
        line-height: 1.3;
    }

    /* ══════════════════════════════════════════════════════════
       BADGES
       ══════════════════════════════════════════════════════════ */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 700;
        border: none;
    }

    .badge-primary   { background: var(--chip-info-bg); color: var(--chip-info-fg); }
    .badge-success   { background: var(--chip-success-bg); color: var(--chip-success-fg); }
    .badge-danger    { background: var(--chip-danger-bg); color: var(--chip-danger-fg); }
    .badge-warning   { background: var(--chip-warning-bg); color: var(--chip-warning-fg); }
    .badge-info      { background: var(--chip-info-bg); color: var(--chip-info-fg); }
    .badge-secondary { background: var(--chip-neutral-bg); color: var(--chip-neutral-fg); }

    /* ══════════════════════════════════════════════════════════
       BUTTONS
       ══════════════════════════════════════════════════════════ */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 7px;
        font-weight: 600;
        font-size: 12.5px;
        padding: 7px 14px;
        border: none;
        cursor: pointer;
        transition: all .2s ease;
        text-decoration: none;
    }

    .btn-sm {
        padding: 5px 12px;
        font-size: 12px;
    }

    .btn-danger {
        background: var(--chip-danger-bg);
        color: var(--chip-danger-fg);
        border: 1px solid color-mix(in srgb, var(--danger) 35%, transparent);
    }
    .btn-danger:hover {
        background: var(--danger);
        color: var(--on-color);
    }

    .btn-warning {
        background: var(--chip-warning-bg);
        color: var(--chip-warning-fg);
        border: 1px solid color-mix(in srgb, var(--warning) 35%, transparent);
    }
    .btn-warning:hover {
        background: var(--warning);
        color: var(--on-color);
    }

    .btn-success {
        background: var(--chip-success-bg);
        color: var(--chip-success-fg);
        border: 1px solid color-mix(in srgb, var(--success) 35%, transparent);
    }
    .btn-success:hover {
        background: var(--success);
        color: var(--on-color);
    }

    .btn-info {
        background: var(--primary-color);
        color: var(--on-accent);
        border: none;
    }
    .btn-info:hover {
        opacity: .9;
    }

    .btn-light, .btn-default, .btn-secondary {
        background: var(--surface-sunken);
        color: var(--text-secondary);
        border: 1px solid var(--border);
    }
    .btn-light:hover, .btn-default:hover, .btn-secondary:hover {
        background: var(--border);
    }

    /* ══════════════════════════════════════════════════════════
       FORM CONTROLS
       ══════════════════════════════════════════════════════════ */
    .gift-log-form {
        background-color: transparent !important;
        filter: none !important;
    }

    .form-control, .form-select {
        height: 40px;
        border-radius: 8px;
        border: 1.5px solid var(--border);
        padding: 8px 12px;
        font-size: 13px;
        color: var(--text-primary);
        background: var(--surface-raised);
        transition: all .2s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px var(--accent-soft);
        background: var(--card-bg);
        outline: none;
    }

    .form-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 4px;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    /* ══════════════════════════════════════════════════════════
       DIAMOND SUMMARY
       ══════════════════════════════════════════════════════════ */
    .diamond-summary-container {
        display: flex;
        justify-content: center;
        padding: 16px 0;
    }

    .diamond-summary-box {
        max-width: 400px;
        width: 100%;
        background: var(--gradient-1);
        border-radius: 12px;
        padding: 20px 28px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,.1);
    }

    .diamond-title {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .8px;
        color: rgba(255,255,255,.7);
        margin-bottom: 8px;
    }

    .diamond-count {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 26px;
        font-weight: 800;
        color: var(--on-color);
    }

    .diamond-icon-container {
        background: rgba(255,255,255,.15);
        border-radius: 50%;
        padding: 6px;
        display: inline-flex;
    }

    .diamond-icon {
        width: 24px;
        height: 24px;
        object-fit: contain;
    }

    /* ══════════════════════════════════════════════════════════
       PK
       ══════════════════════════════════════════════════════════ */
    .team-info {
        padding: 10px 12px;
        background: var(--surface-raised);
        border-radius: 8px;
        border: 1px solid var(--surface-sunken);
    }

    .team-title {
        font-weight: 700;
        font-size: 13px;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .team-boss {
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: inherit;
        margin-bottom: 4px;
    }

    .boss-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid var(--border);
    }

    .members-count {
        color: var(--primary-color);
        font-size: 12px;
        font-weight: 600;
    }

    .members-count:hover { text-decoration: underline; }

    .score-info { display: flex; flex-direction: column; gap: 3px; }

    .team-score {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
    }

    .score-label { font-weight: 600; color: var(--text-secondary); }
    .score-value { font-weight: 800; color: var(--danger); }

    .status-container {
        display: flex;
        flex-direction: column;
        gap: 4px;
        align-items: flex-start;
    }

    .winner-badge {
        background: var(--success);
        color: var(--on-color);
        padding: 3px 10px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 700;
    }

    .time-info {
        font-size: 12px;
        color: var(--text-secondary);
        line-height: 1.7;
    }

    /* ══════════════════════════════════════════════════════════
       BOXES / PROGRESS
       ══════════════════════════════════════════════════════════ */
    .progress {
        background: var(--border);
        border-radius: 50px;
        overflow: hidden;
        height: 6px !important;
    }

    .progress-bar {
        border-radius: 50px;
        transition: width .4s ease;
    }

    .progress-info { width: 100%; }

    .show-users-text {
        color: var(--primary-color);
        font-weight: 600;
        font-size: 12px;
    }

    .show-users-text:hover { text-decoration: underline; }

    /* ══════════════════════════════════════════════════════════
       MODAL MEMBERS
       ══════════════════════════════════════════════════════════ */
    .team-members-list {
        max-height: 450px;
        overflow-y: auto;
        padding: 4px;
    }

    .member-item {
        padding: 12px 14px;
        border-bottom: 1px solid var(--surface-sunken);
        transition: background .15s ease;
    }

    .member-item:hover { background: var(--surface-raised); }
    .member-item:last-child { border-bottom: none; }

    .member-info {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: inherit;
    }

    .member-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--card-border);
    }

    .member-details { flex: 1; }

    .member-name {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 2px;
    }

    .member-id {
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.4;
    }

    /* ══════════════════════════════════════════════════════════
       MODAL
       ══════════════════════════════════════════════════════════ */
    .modal-content {
        border-radius: 14px;
        border: none;
        box-shadow: 0 8px 30px rgba(0,0,0,.12);
    }

    .modal-header {
        border-bottom: 1px solid var(--surface-sunken);
        padding: 16px 24px;
    }

    .modal-title {
        font-weight: 700;
        font-size: 16px;
    }

    .modal-body { padding: 24px; }

    .modal-footer {
        border-top: 1px solid var(--surface-sunken);
        padding: 14px 24px;
    }

    /* ══════════════════════════════════════════════════════════
       LOADING — fixed neutral dark scrim (theme-independent toast)
       ══════════════════════════════════════════════════════════ */
    #tab-loading {
        background: rgba(30,41,59,.92) !important;
        color: #fff !important;
        border-radius: 12px !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        padding: 16px 28px !important;
        transform: translate(-50%, -50%);
        box-shadow: 0 8px 24px rgba(0,0,0,.2);
    }

    /* ══════════════════════════════════════════════════════════
       PAGINATION
       ══════════════════════════════════════════════════════════ */
    .pagination {
        gap: 3px;
    }

    .pagination .page-item .page-link {
        border-radius: 6px;
        border: 1px solid var(--border);
        color: var(--text-secondary);
        font-size: 12.5px;
        padding: 6px 12px;
    }

    .pagination .page-item.active .page-link {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: var(--on-accent);
    }

    /* ══════════════════════════════════════════════════════════
       EMPTY STATE
       ══════════════════════════════════════════════════════════ */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 40px 20px;
        color: var(--empty-state-fg);
    }

    .empty-state i { font-size: 32px; opacity: .4; }
    .empty-state p { margin: 0; font-size: 13px; font-weight: 500; }

    /* ══════════════════════════════════════════════════════════
       RESPONSIVE
       ══════════════════════════════════════════════════════════ */
    @media (max-width: 768px) {
        .room-header-top {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .room-meta-row {
            justify-content: center;
        }

        .room-owner-row {
            justify-content: center;
        }

        .room-status-row {
            justify-content: center;
        }

        .room-header-actions {
            justify-content: center;
            width: 100%;
        }

        .room-stats-row {
            flex-wrap: wrap;
        }

        .room-stat-item {
            flex: 1 1 calc(50% - 6px);
            min-width: 0;
        }

        .room-avatar {
            width: 80px;
            height: 80px;
        }

        .room-name {
            font-size: 20px;
        }
    }

    @media (max-width: 480px) {
        .room-profile-page {
            padding: 12px;
        }

        .room-header-card {
            padding: 20px 16px;
        }

        .room-stats-row {
            gap: 8px;
        }

        .room-stat-item {
            flex: 1 1 calc(50% - 4px);
            padding: 10px 6px;
        }

        .tab-btn {
            padding: 8px 14px;
            font-size: 12px;
        }

        .table thead th, .table tbody td {
            padding: 10px 10px;
            font-size: 12px;
        }
    }

    /* ── Utility ───────────────────────────────────────────── */
    .text-center .p-3 { color: var(--text-muted); font-size: 13px; }
    .text-center .p-3 i { margin-inline-end: 5px; }
    .me-2 { margin-inline-end: .5rem; }
    .float-right { float: inline-end; }
    .edit-btn { background: var(--primary-color) !important; }
    .level-form { background-color: transparent !important; filter: none !important; padding: 10px; }
    .level-label { padding: 10px; }
    .ltr .gift-log-form { padding-left: 0; }

    /* Select2 */
    .select2-container--default .select2-selection--single {
        height: 40px;
        border-radius: 8px;
        border: 1.5px solid var(--border);
        padding: 4px 12px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 30px;
        color: var(--text-primary);
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 6px;
    }
</style>
