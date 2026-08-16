/* Moment Viewer - Premium Modern Design */
<style>
    :root {
        --primary-color: {{ config('themes.primaryColor') }};
        --secondary-color: {{ config('themes.secondaryColor') }};
        --text-primary-color: {{ config('themes.textPrimaryColor') }};
        --text-secondary-color: {{ config('themes.textSecondaryColor') }};
        --box-background-color: {{ config('themes.boxBackgroundColor') }};
        --table-background-color: {{ config('themes.tableBackGroundColor') }};
        --background-image: {{ config('themes.backgroundImage') }};
        --brand-background-image: url({{ getImagePath(config('themes.brandBackgroundImage')) }});
        --second-alpha: {{ adjustColor(config('themes.boxBackgroundColor'), -30, -30, -30) }}55;
        --primary-hover-alpha: {{ config('themes.primaryColor')}}33;
        --scroll-second-color: {{ config('themes.boxBackgroundColor') }}cc;
        --scroll-first-color: {{ adjustColor(config('themes.primaryColor'), 40, 40, 40) }}33;
        --inverse-color: {{getLighterColor(config('themes.primaryColor'))}};
        --inverse-box-color: {{adjustTextColor(config('themes.boxBackgroundColor'))}};
        --success-button: linear-gradient(90deg, {{adjustColor(config('themes.primaryColor'))}} 0%, {{config('themes.primaryColor')}} 100%);
        --primary-button: linear-gradient(90deg, {{adjustColor(config('themes.primaryColor'))}} 0%, {{config('themes.primaryColor')}} 100%);

        --primary: {{ config('themes.primaryColor') }};
        --primary-hover: {{ adjustColor(config('themes.primaryColor'), -10, -10, -10) }};
        --primary-light: {{ config('themes.primaryColor') }}18;
        --primary-glow: {{ config('themes.primaryColor') }}40;
        --bg-primary: {{ config('themes.backgroundImage') }};
        --bg-secondary: {{ config('themes.boxBackgroundColor') }};
        --bg-card: #ffffff;
        --text-primary: {{ config('themes.textPrimaryColor') }};
        --text-secondary: {{ config('themes.textSecondaryColor') }};
        --border-color: {{ adjustColor(config('themes.boxBackgroundColor'), 20, 20, 20) }};
        --border-light: rgba(0, 0, 0, 0.06);
        --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.04);
        --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
        --shadow-lg: 0 10px 25px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        --shadow-xl: 0 20px 40px -5px rgba(0, 0, 0, 0.1), 0 8px 16px -4px rgba(0, 0, 0, 0.04);
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --radius-xl: 20px;
        --radius-full: 9999px;
        --transition-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        --transition-normal: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        --transition-slow: 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        --gradient-primary: linear-gradient(135deg, {{ config('themes.primaryColor') }}, {{ adjustColor(config('themes.primaryColor'), 30, 10, -20) }});
        --gradient-danger: linear-gradient(135deg, #ff6b6b, #ee5a24);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: var(--bg-primary) !important;
        line-height: 1.5;
        direction: ltr;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    body[dir="rtl"], html[dir="rtl"] body {
        direction: rtl;
    }

    .content-header { display: none !important; }

    .content-wrapper {
        background: var(--bg-primary) !important;
        min-height: 100vh !important;
        padding-bottom: 40px !important;
        -webkit-overflow-scrolling: touch;
    }

    .content {
        height: auto !important;
        min-height: 100vh !important;
        overflow: visible !important;
    }

    /* ═══════════════════════════════════════════
       LAYOUT
    ═══════════════════════════════════════════ */
    .viewer-layout {
        display: flex;
        min-height: 100vh;
        gap: 0;
    }

    .viewer-main-content {
        flex: 1;
        min-width: 0;
    }

    .viewer-container {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        min-height: 100vh !important;
        height: auto !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        display: block !important;
        -webkit-overflow-scrolling: touch;
        padding: 20px 0;
    }

    .viewer-container img {
        display: block;
        height: auto;
        max-height: none !important;
        max-width: none !important;
        min-height: 0 !important;
        min-width: 0 !important;
    }

    /* ═══════════════════════════════════════════
       HEADER - Glassmorphism Design
    ═══════════════════════════════════════════ */
    .viewer-header {
        background: var(--gradient-primary);
        border-radius: var(--radius-xl);
        padding: 28px 32px;
        box-shadow: 0 8px 32px var(--primary-glow);
        border: none;
        margin-bottom: 24px;
        max-width: 680px;
        margin-left: 7%;
        margin-right: 0;
        position: relative;
        overflow: hidden;
        color: white;
    }

    .viewer-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 200px;
        height: 200px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
    }

    .viewer-header::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -10%;
        width: 150px;
        height: 150px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }

    .rtl .viewer-header {
        margin-left: auto;
        margin-right: 7%;
    }

    .viewer-title {
        font-size: 24px;
        font-weight: 800;
        color: white;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        letter-spacing: -0.3px;
        position: relative;
        z-index: 1;
    }

    .viewer-title i {
        color: white;
        font-size: 22px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        width: 46px;
        height: 46px;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255,255,255,0.15);
    }

    /* ═══════════════════════════════════════════
       CONTROLS - Modern Inputs & Buttons
    ═══════════════════════════════════════════ */
    .viewer-controls {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    .filter-input, .search-input {
        flex: 1;
        min-width: 160px;
        padding: 10px 16px;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: var(--radius-full);
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        color: white;
        transition: all var(--transition-fast);
        outline: none;
    }

    .filter-input:focus, .search-input:focus {
        border-color: rgba(255,255,255,0.6);
        box-shadow: 0 0 0 3px rgba(255,255,255,0.15);
        background: rgba(255,255,255,0.3);
    }

    .filter-input::placeholder, .search-input::placeholder {
        color: rgba(255,255,255,0.7);
    }

    .sort-select {
        padding: 10px 16px;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: var(--radius-full);
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        color: white;
        cursor: pointer;
        transition: all var(--transition-fast);
        outline: none;
        -webkit-appearance: none;
        appearance: none;
        padding-right: 32px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M6 8L1 3h10z' fill='white'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
    }

    .sort-select option {
        background: var(--bg-card);
        color: var(--text-primary);
    }

    [dir="rtl"] .sort-select {
        padding-right: 16px;
        padding-left: 32px;
        background-position: left 12px center;
    }

    .sort-select:focus {
        border-color: rgba(255,255,255,0.6);
        box-shadow: 0 0 0 3px rgba(255,255,255,0.15);
    }

    .refresh-btn {
        padding: 10px 22px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        color: white;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: var(--radius-full);
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all var(--transition-normal);
    }

    .refresh-btn:hover {
        background: rgba(255,255,255,0.35);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .refresh-btn:active {
        transform: translateY(0);
    }

    /* ═══════════════════════════════════════════
       FEED CONTAINER
    ═══════════════════════════════════════════ */
    .moments-feed {
        width: 100%;
        max-width: 680px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        position: relative;
        z-index: auto;
        margin-left: 7%;
        margin-right: 0;
    }

    .rtl .moments-feed {
        margin-left: auto;
        margin-right: 7%;
    }

    /* ═══════════════════════════════════════════
       POST CARD - Modern Card Design
    ═══════════════════════════════════════════ */
    .moment-post {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-light);
        transition: all var(--transition-normal);
        overflow: visible;
        will-change: transform;
        transform: translateZ(0);
        position: relative;
        z-index: 1;
    }

    .moment-post:hover {
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px) translateZ(0);
    }

    .moment-post:has(.post-dropdown:not([style*="display: none"])) {
        z-index: 100;
    }

    /* ═══════════════════════════════════════════
       POST HEADER
    ═══════════════════════════════════════════ */
    .post-header {
        padding: 16px 20px 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
        z-index: 1;
    }

    .user-avatar {
        width: 44px !important;
        height: 44px !important;
        border-radius: 50%;
        object-fit: cover;
        cursor: pointer;
        border: 2px solid var(--primary-light);
        flex-shrink: 0;
        transition: all var(--transition-fast);
    }

    .user-avatar:hover {
        border-color: var(--primary);
        transform: scale(1.05);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }

    .user-info {
        flex: 1;
        min-width: 0;
    }

    .user-name {
        font-size: 15px;
        font-weight: 700;
        margin: 0;
        cursor: pointer;
        line-height: 1.3;
        display: block;
        text-align: left !important;
        color: var(--text-primary);
        transition: color var(--transition-fast);
    }

    .user-name:hover {
        color: var(--primary);
    }

    .user-meta {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        margin-top: 3px;
        line-height: 1.2;
        flex-wrap: wrap;
        color: var(--text-secondary);
    }

    .user-uuid { font-size: 12px; }
    .post-time { font-size: 12px; }

    /* ═══════════════════════════════════════════
       MENU & DROPDOWN
    ═══════════════════════════════════════════ */
    .menu-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: transparent;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        font-size: 18px;
        transition: all var(--transition-fast);
        position: relative;
        z-index: 10;
    }

    .menu-btn:hover {
        background: var(--primary-light);
        color: var(--primary);
    }

    .post-menu {
        position: relative;
        z-index: 10;
    }

    .dropdown-menu, .post-dropdown {
        display: none;
        position: absolute;
        top: 42px;
        left: 0;
        right: auto;
        background: var(--bg-card);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-xl);
        min-width: 220px;
        z-index: 9999;
        padding: 6px;
        border: 1px solid var(--border-light);
        white-space: nowrap;
        animation: dropdownFadeIn 0.2s ease;
    }

    @keyframes dropdownFadeIn {
        from { opacity: 0; transform: translateY(-8px) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    html.rtl .post-dropdown, html[dir="rtl"] .post-dropdown, [dir="rtl"] .post-dropdown {
        text-align: right;
        right: 0% !important;
        left: auto;
        float: right;
    }

    .dropdown-menu.show { display: block; }

    @media (max-width: 768px) {
        .dropdown-menu, .post-dropdown { right: 0; left: auto; }
    }

    .dropdown-item, .post-dropdown-item {
        padding: 10px 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--text-primary);
        border-radius: var(--radius-sm);
        transition: all var(--transition-fast);
        font-size: 14px;
        font-weight: 500;
        background: none;
        border: none;
        width: 100%;
        text-align: left;
        text-decoration: none;
    }

    html.rtl .post-dropdown-item, html[dir="rtl"] .post-dropdown-item, [dir="rtl"] .post-dropdown-item {
        text-align: right;
        flex-direction: row-reverse;
    }

    .dropdown-item:hover, .post-dropdown-item:hover {
        background: var(--primary-light);
        color: var(--primary);
    }

    .dropdown-item.delete-item, .post-dropdown-item.delete-item { color: #ef4444; }
    .post-dropdown-item.delete-item:hover { background: #fef2f2; color: #dc2626; }

    .dropdown-item i, .post-dropdown-item i {
        width: 20px;
        text-align: center;
        font-size: 15px;
        opacity: 0.8;
    }

    /* ═══════════════════════════════════════════
       POST CONTENT & DESCRIPTION
    ═══════════════════════════════════════════ */
    .post-content {
        padding: 0 20px 14px;
    }

    .post-description {
        font-size: 15px;
        line-height: 1.6;
        white-space: pre-wrap;
        word-wrap: break-word;
        margin-bottom: 8px;
        unicode-bidi: plaintext;
        transition: max-height var(--transition-normal);
        color: var(--text-primary);
    }

    .post-description[dir="rtl"] { text-align: right !important; direction: rtl; }
    .post-description[dir="ltr"] { text-align: left !important; direction: ltr; }

    .post-description.collapsible {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .see-more-btn {
        color: var(--primary);
        font-weight: 600;
        cursor: pointer;
        background: none;
        border: none;
        padding: 4px 0;
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        margin-top: 4px;
        transition: opacity var(--transition-fast);
    }

    .see-more-btn:hover {
        opacity: 0.7;
        text-decoration: underline;
    }

    /* ═══════════════════════════════════════════
       POST MEDIA - Modern Grid
    ═══════════════════════════════════════════ */
    .post-media {
        width: 100%;
        background: #0a0a0a;
        position: relative;
        overflow: hidden;
        contain: layout style paint;
        display: grid;
        gap: 3px;
        cursor: pointer;
        max-height: 400px !important;
    }

    .media-grid.grid-1 { grid-template-columns: 1fr; max-height: 520px; }
    .media-grid.grid-2 { grid-template-columns: 1fr 1fr; max-height: 400px; }
    .media-grid.grid-3 { grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; max-height: 400px; }
    .media-grid.grid-3 .media-item:first-child { grid-row: 1 / 3; }
    .media-grid.grid-4 { grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; max-height: 400px; }
    .media-grid.grid-5-plus { grid-template-columns: repeat(3, 1fr); grid-template-rows: 1fr 1fr; max-height: 400px; }
    .media-grid.grid-5-plus .media-item:first-child { grid-column: 1 / 3; grid-row: 1 / 3; }

    .media-item {
        position: relative;
        overflow: hidden;
        background: #0a0a0a;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 160px;
    }

    .post-media img, .post-media video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block !important;
        transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .media-item:hover img, .media-item:hover video {
        transform: scale(1.04);
    }

    .media-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(2px);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 42px;
        font-weight: 800;
        pointer-events: none;
        letter-spacing: -1px;
    }

    /* ═══════════════════════════════════════════
       LIGHTBOX - Full Screen Media Viewer
    ═══════════════════════════════════════════ */
    .media-lightbox {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .media-lightbox.active { display: flex; }

    .lightbox-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.92);
        backdrop-filter: blur(20px);
        cursor: pointer;
    }

    .lightbox-content {
        position: relative;
        z-index: 10001;
        max-width: 90vw;
        max-height: 90vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .lightbox-media {
        max-width: 100%;
        max-height: 90vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .lightbox-media img, .lightbox-media video {
        max-width: 100%;
        max-height: 90vh;
        object-fit: contain;
        display: block;
        border-radius: var(--radius-sm);
    }

    .lightbox-close {
        position: absolute;
        top: 20px; right: 20px;
        width: 48px; height: 48px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        font-size: 22px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all var(--transition-fast);
        z-index: 10002;
    }

    .lightbox-close:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.1);
    }

    .lightbox-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 52px; height: 52px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: white;
        font-size: 22px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all var(--transition-fast);
        z-index: 10002;
    }

    .lightbox-nav:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-50%) scale(1.1);
    }

    .lightbox-prev { left: 24px; }
    .lightbox-next { right: 24px; }

    /* ═══════════════════════════════════════════
       POST STATS - Interactive Stats Bar
    ═══════════════════════════════════════════ */
    .post-stats {
        padding: 10px 20px 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
    }

    .stats-left, .stats-right {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        padding: 6px 12px;
        border-radius: var(--radius-full);
        transition: all var(--transition-fast);
        font-weight: 500;
        color: var(--text-secondary);
    }

    .stat-item:hover {
        background: var(--primary-light);
        color: var(--primary);
        transform: scale(1.02);
    }

    .stat-item i { font-size: 16px; }

    .stat-item:first-child i { color: #ef4444; }
    .stat-item:first-child:hover { background: #fef2f2; }

    .stat-item:nth-child(2) i { color: #8b5cf6; }
    .stat-item:nth-child(2):hover { background: #f5f3ff; }

    .stats-right .stat-item i { color: var(--primary); }
    .stats-right .stat-item:hover { background: var(--primary-light); }

    .stat-item span {
        font-weight: 600;
        min-width: 16px;
        text-align: left;
        font-size: 13px;
    }

    /* ═══════════════════════════════════════════
       SECTIONS (Hidden - Using Side Modal)
    ═══════════════════════════════════════════ */
    .comments-section, .likes-section, .gifts-section {
        display: none !important;
    }

    /* ═══════════════════════════════════════════
       LOADING & EMPTY STATES
    ═══════════════════════════════════════════ */
    .loading-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 50px;
        min-height: 200px;
    }

    .modal-loading {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 24px;
        width: 100%;
    }

    .modal-loading .spinner {
        width: 28px;
        height: 28px;
        border-width: 3px;
    }

    .spinner {
        width: 38px;
        height: 38px;
        border: 3px solid var(--border-color);
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .empty-state {
        text-align: center;
        padding: 70px 24px;
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-light);
        box-shadow: var(--shadow-sm);
    }

    .empty-state i {
        font-size: 56px;
        margin-bottom: 20px;
        color: var(--text-secondary);
        opacity: 0.4;
    }

    .empty-state h3 {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .empty-state p {
        font-size: 15px;
        color: var(--text-secondary);
        margin-bottom: 20px;
    }

    /* ═══════════════════════════════════════════
       SCROLL TO TOP - Floating Action Button
    ═══════════════════════════════════════════ */
    .scroll-top {
        position: fixed;
        bottom: 28px;
        right: 28px;
        width: 52px;
        height: 52px;
        background: var(--gradient-primary);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 16px var(--primary-glow);
        transition: all var(--transition-normal);
        z-index: 1000;
    }

    .scroll-top:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px var(--primary-glow);
    }

    .scroll-top.visible {
        display: flex;
        animation: fadeInUp 0.3s ease;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ═══════════════════════════════════════════
       SCROLLBAR
    ═══════════════════════════════════════════ */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--text-secondary); }

    /* ═══════════════════════════════════════════
       SIDE MODAL - Slide-in Panel
    ═══════════════════════════════════════════ */
    .side-modal {
        position: fixed;
        top: 0; right: -100%;
        width: 100%; height: 100%;
        z-index: 9999;
        transition: right var(--transition-slow);
        pointer-events: none;
    }

    [dir="rtl"] .side-modal {
        right: auto;
        left: -100%;
        transition: left var(--transition-slow);
    }

    .side-modal.active {
        right: 0;
        pointer-events: all;
    }

    [dir="rtl"] .side-modal.active {
        right: auto;
        left: 0;
    }

    .side-modal-overlay {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        opacity: 0;
        transition: opacity var(--transition-normal);
    }

    .side-modal.active .side-modal-overlay { opacity: 1; }

    .side-modal-content {
        position: absolute;
        top: 0; right: -100%;
        width: 100%;
        max-width: 480px;
        height: 100%;
        background: var(--bg-card);
        box-shadow: -8px 0 32px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        transition: right var(--transition-slow);
        overflow: hidden;
    }

    [dir="rtl"] .side-modal-content {
        right: auto;
        left: -100%;
        box-shadow: 8px 0 32px rgba(0, 0, 0, 0.15);
        transition: left var(--transition-slow);
    }

    .side-modal.active .side-modal-content { right: 0; }
    [dir="rtl"] .side-modal.active .side-modal-content { right: auto; left: 0; }

    .side-modal-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--gradient-primary);
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .side-modal-title {
        font-size: 18px;
        font-weight: 700;
        color: white;
        margin: 0;
    }

    .side-modal-close {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: white;
        transition: all var(--transition-fast);
    }

    .side-modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    .side-modal-body {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
    }

    body.modal-open { overflow: hidden; }

    /* ═══════════════════════════════════════════
       MODAL LIST ITEMS
    ═══════════════════════════════════════════ */
    .modal-list { padding: 8px 0; }

    .modal-user-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        transition: all var(--transition-fast);
        cursor: pointer;
        gap: 12px;
        min-height: 60px;
        border-bottom: 1px solid var(--border-light);
    }

    .modal-user-item:has(.modal-comment-text) {
        align-items: flex-start;
        flex-wrap: wrap;
        min-height: 70px;
    }

    .modal-user-item:hover {
        background: var(--primary-light);
    }

    .modal-user-item:last-child { border-bottom: none; }

    .modal-user-header {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        position: relative;
    }

    .modal-user-avatar {
        width: 40px !important;
        height: 40px !important;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary-light);
        flex-shrink: 0;
        min-width: 40px;
    }

    .modal-list .modal-user-item:has(.modal-comment-text) .modal-user-avatar {
        width: 44px !important;
        height: 44px !important;
        border-radius: 50%;
        min-width: 44px;
    }

    .modal-user-info {
        min-width: 0;
        max-width: 200px;
        overflow: hidden;
    }

    .modal-user-name {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--text-primary);
    }

    .modal-user-meta {
        font-size: 11px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--text-secondary);
    }

    .modal-like-info {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
        margin-left: auto;
        min-width: 60px;
    }

    .modal-like-icon {
        color: #ef4444;
        font-size: 16px;
        flex-shrink: 0;
    }

    .modal-like-time {
        font-size: 12px;
        white-space: nowrap;
        color: var(--text-secondary);
    }

    /* Gift Items */
    .modal-gift-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        border-bottom: 1px solid var(--border-light);
        transition: all var(--transition-fast);
        min-height: 60px;
    }

    .modal-gift-item:hover { background: var(--primary-light); }
    .modal-gift-item:last-child { border-bottom: none; }

    .gift-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .gift-img {
        width: 42px; height: 42px;
        object-fit: contain;
        border-radius: var(--radius-sm);
        flex-shrink: 0;
        min-width: 42px;
        background: var(--primary-light);
        padding: 4px;
    }

    .gift-icon {
        font-size: 28px;
        color: #8b5cf6;
        min-width: 42px;
    }

    .gift-details {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
        flex: 1;
    }

    .gift-name {
        font-weight: 600;
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--text-primary);
    }

    .gift-value {
        font-size: 13px;
        color: #f59e0b;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .gift-value i { font-size: 12px; }

    .gift-time {
        font-size: 11px;
        margin-top: 2px;
        color: var(--text-secondary);
    }

    .modal-delete-btn {
        width: 34px; height: 34px;
        border-radius: 50%;
        background: transparent;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ef4444;
        font-size: 14px;
        transition: all var(--transition-fast);
        flex-shrink: 0;
    }

    .modal-delete-btn:hover {
        background: #fef2f2;
        transform: scale(1.1);
    }

    .modal-comment-text {
        margin-top: 10px;
        font-size: 14px;
        line-height: 1.5;
        word-wrap: break-word;
        white-space: pre-wrap;
        unicode-bidi: plaintext;
        color: var(--text-primary);
        background: var(--bg-primary);
        padding: 10px 14px;
        border-radius: var(--radius-md);
        width: 100%;
    }

    .modal-comment-text[dir="rtl"] { text-align: right !important; direction: rtl; }
    .modal-comment-text[dir="ltr"] { text-align: left !important; direction: ltr; }

    .modal-comment-time {
        margin-top: 6px;
        margin-left: 70px;
        font-size: 11px;
        color: var(--text-secondary);
    }

    [dir="rtl"] .modal-comment-time { margin-left: 0; margin-right: 70px; }

    .modal-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 70px 24px;
        text-align: center;
    }

    .modal-empty i {
        font-size: 56px;
        margin-bottom: 16px;
        opacity: 0.3;
        color: var(--text-secondary);
    }

    .modal-empty p {
        font-size: 15px;
        opacity: 0.6;
        margin: 0;
        color: var(--text-secondary);
    }

    /* ═══════════════════════════════════════════
       USERS SIDEBAR - Modern Panel
    ═══════════════════════════════════════════ */
    .users-sidebar {
        width: 30%;
        background: var(--bg-card);
        border-left: 1px solid var(--border-light);
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: sticky;
        top: 0;
    }

    [dir="rtl"] .users-sidebar {
        border-left: none;
        border-right: 1px solid var(--border-light);
    }

    .users-sidebar-header {
        padding: 18px 20px;
        background: var(--gradient-primary);
        color: #fff;
    }

    .users-sidebar-header h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .users-search-box {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-light);
    }

    .users-list-search {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-full);
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        box-sizing: border-box;
        outline: none;
        transition: all var(--transition-fast);
        background: var(--bg-primary);
        color: var(--text-primary);
    }

    .users-list-search:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px var(--primary-glow);
    }

    .users-filter-info {
        padding: 0 16px 12px;
        display: none;
    }

    .users-filter-info.visible { display: block; }

    .clear-filter-btn {
        width: 100%;
        padding: 10px;
        background: var(--gradient-danger);
        color: #fff;
        border: none;
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        transition: all var(--transition-fast);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .clear-filter-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .users-list-container {
        flex: 1;
        overflow-y: auto;
        padding: 8px;
    }

    .user-list-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: var(--radius-md);
        cursor: pointer;
        border: 2px solid transparent;
        margin-bottom: 4px;
        transition: all var(--transition-fast);
    }

    .user-list-item:hover {
        background: var(--primary-light);
        border-color: transparent;
    }

    .user-list-item.active {
        background: var(--primary-light);
        border-color: var(--primary);
    }

    .user-list-avatar {
        width: 38px; height: 38px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid var(--border-light);
    }

    .user-list-info {
        flex: 1;
        min-width: 0;
        overflow: hidden;
    }

    .user-list-name {
        font-weight: 600;
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--text-primary);
    }

    .user-list-meta {
        font-size: 11px;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-list-count {
        background: var(--gradient-primary);
        color: #fff;
        padding: 3px 10px;
        border-radius: var(--radius-full);
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
        min-width: 28px;
        text-align: center;
    }

    .users-load-more {
        padding: 12px;
        border-top: 1px solid var(--border-light);
        display: none;
    }

    .users-load-more.visible { display: block; }

    .load-more-users-btn {
        width: 100%;
        padding: 10px;
        border: 2px solid var(--primary);
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        background: transparent;
        color: var(--primary);
        transition: all var(--transition-fast);
    }

    .load-more-users-btn:hover {
        background: var(--primary);
        color: white;
    }

    .users-loading-more {
        padding: 15px;
        text-align: center;
        display: none;
    }

    .users-loading-more.visible { display: block; }

    .spinner-small {
        width: 22px; height: 22px;
        border: 3px solid var(--border-color);
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
        margin: 0 auto;
    }

    /* ═══════════════════════════════════════════
       PERFORMANCE OPTIMIZATIONS
    ═══════════════════════════════════════════ */
    .side-modal-body, .moments-feed, .viewer-container {
        will-change: scroll-position;
        -webkit-overflow-scrolling: touch;
    }

    .moment-post, .user-avatar, .post-media img, .post-media video,
    .modal-user-avatar, .menu-btn, .post-dropdown, .side-modal-content {
        transform: translateZ(0);
        backface-visibility: hidden;
    }

    .user-avatar, .modal-user-avatar, .post-media img {
        image-rendering: -webkit-optimize-contrast;
    }

    /* ═══════════════════════════════════════════
       RESPONSIVE DESIGN
    ═══════════════════════════════════════════ */
    @media (max-width: 992px) {
        .viewer-layout { flex-direction: column; }
        .users-sidebar {
            width: 100%;
            min-width: 100%;
            height: auto;
            max-height: 250px;
            position: relative;
        }
        .users-list-container { max-height: 150px; }
    }

    @media (max-width: 768px) {
        .users-sidebar { display: none !important; }
        .viewer-main-content { width: 100% !important; flex: 1 !important; }

        body { -webkit-overflow-scrolling: touch; overflow-x: hidden; }

        .viewer-container { padding: 12px 0; }

        .viewer-header {
            margin: 0 10px 14px !important;
            padding: 16px 18px !important;
            border-radius: var(--radius-md);
        }

        .viewer-header::before { height: 3px; }

        .moments-feed {
            margin: 0 10px !important;
            max-width: 100%;
            gap: 14px;
        }

        .viewer-title { font-size: 18px; }

        .viewer-controls {
            flex-direction: column;
            width: 100%;
        }

        .filter-input, .search-input, .sort-select { width: 100%; }

        .moment-post { border-radius: var(--radius-md); }

        .post-header { padding: 12px 14px; }
        .post-content { padding: 0 14px 10px; }
        .post-stats { padding: 8px 14px 10px; }

        .user-avatar { width: 40px !important; height: 40px !important; }
        .user-name { font-size: 14px; }
        .user-meta { font-size: 11px; }
        .post-description { font-size: 14px; }

        .side-modal-content { max-width: 100%; }

        .lightbox-nav { width: 42px; height: 42px; font-size: 18px; }
        .lightbox-prev { left: 12px; }
        .lightbox-next { right: 12px; }
        .lightbox-close { width: 40px; height: 40px; top: 12px; right: 12px; }

        .scroll-top {
            bottom: 18px; right: 18px;
            width: 46px; height: 46px;
        }
    }

    @media (max-width: 480px) {
        .viewer-container { padding: 8px 0; }

        .viewer-header {
            margin: 0 6px 10px !important;
            padding: 14px !important;
        }

        .moments-feed { margin: 0 6px !important; gap: 10px; }

        .viewer-title {
            font-size: 16px;
            gap: 8px;
        }

        .viewer-title i {
            width: 34px; height: 34px;
            font-size: 16px;
        }

        .filter-input, .search-input, .sort-select, .refresh-btn {
            font-size: 13px;
            padding: 8px 12px;
        }

        .post-header { padding: 10px 12px; gap: 10px; }
        .user-avatar { width: 36px !important; height: 36px !important; }
        .user-name { font-size: 13px; }
        .user-meta { font-size: 10px; }

        .menu-btn { width: 32px; height: 32px; font-size: 16px; }

        .post-content { padding: 0 12px 8px; }
        .post-description { font-size: 13px; }

        .post-stats { font-size: 12px; padding: 6px 12px 8px; }
        .stat-item { padding: 4px 8px; }

        .scroll-top {
            bottom: 14px; right: 14px;
            width: 42px; height: 42px;
            font-size: 16px;
        }

        .empty-state { padding: 50px 16px; }
        .empty-state i { font-size: 44px; }
        .empty-state h3 { font-size: 17px; }
        .empty-state p { font-size: 13px; }

        .side-modal-header { padding: 14px 18px; }
        .side-modal-title { font-size: 16px; }
    }

    @media (max-width: 360px) {
        .viewer-title { font-size: 15px; }
        .user-avatar { width: 32px !important; height: 32px !important; }
        .user-name { font-size: 12px; }
        .user-meta { font-size: 9px; }
        .post-description { font-size: 12px; }
        .menu-btn { width: 28px; height: 28px; font-size: 14px; }
    }

    /* ═══════════════════════════════════════════
       DARK MODE SUPPORT
    ═══════════════════════════════════════════ */
    @media (prefers-color-scheme: dark) {
        :root {
            --primary-color: {{ config('themes.primaryColor') }};
            --secondary-color: {{ config('themes.secondaryColor') }};
            --text-primary-color: {{ config('themes.textPrimaryColor') }};
            --text-secondary-color: {{ config('themes.textSecondaryColor') }};
            --box-background-color: {{ config('themes.boxBackgroundColor') }};
            --primary: {{ config('themes.primaryColor') }};
            --primary-hover: {{ adjustColor(config('themes.primaryColor'), -10, -10, -10) }};
            --bg-primary: {{ config('themes.backgroundImage') }};
            --bg-secondary: {{ config('themes.boxBackgroundColor') }};
            --text-primary: {{ config('themes.textPrimaryColor') }};
            --text-secondary: {{ config('themes.textSecondaryColor') }};
            --border-color: {{ adjustColor(config('themes.boxBackgroundColor'), 20, 20, 20) }};
        }
    }

    /* ═══════════════════════════════════════════
       SWEETALERT Z-INDEX FIX
    ═══════════════════════════════════════════ */
    .swal2-container { z-index: 99999 !important; }
    .swal2-popup { z-index: 99999 !important; }
    .swal2-container.swal2-backdrop-show { z-index: 99999 !important; }

    /* ═══════════════════════════════════════════
       UTILITIES
    ═══════════════════════════════════════════ */
    .text-muted { color: var(--text-secondary) !important; }
    .d-none { display: none !important; }
    .d-block { display: block !important; }
    .d-flex { display: flex !important; }
</style>
