/* Moment Viewer - Facebook-like Design */
/* استخدام ألوان من config/themes.php */
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

    /* تعيين المتغيرات المستخدمة في التصميم */
    --primary: {{ config('themes.primaryColor') }};
    --primary-hover: {{ adjustColor(config('themes.primaryColor'), -10, -10, -10) }};
    --bg-primary: {{ config('themes.backgroundImage') }};
    --bg-secondary: {{ config('themes.boxBackgroundColor') }};
    --text-primary: {{ config('themes.textPrimaryColor') }};
    --text-secondary: {{ config('themes.textSecondaryColor') }};
    --border-color: {{ adjustColor(config('themes.boxBackgroundColor'), 20, 20, 20) }};
    --shadow-1: 0 1px 2px rgba(0, 0, 0, 0.1);
    --shadow-2: 0 2px 4px rgba(0, 0, 0, 0.1);
    --shadow-3: 0 8px 16px rgba(0, 0, 0, 0.15);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', 'Helvetica Neue', Helvetica, Arial, sans-serif;
    background: var(--bg-primary) !important;
    color: var(--text-primary);
    line-height: 1.34;
    overflow-y: auto !important;
    height: auto !important;
}

.content-wrapper {
    background: var(--bg-primary) !important;
    overflow-y: auto !important;
    min-height: 100vh !important;
    padding-bottom: 40px !important;
}

.viewer-container {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    padding: 20px 0;
    min-height: 100vh;
}

/* إعادة تعيين أحجام الصور */
.viewer-container img {
    display: block;
    height: auto;
    max-height: none !important;
    max-width: none !important;
    min-height: 0 !important;
    min-width: 0 !important;
}

/* Header */
.viewer-header {
    background: var(--bg-secondary);
    padding: 16px 24px;
    margin-bottom: 16px;
    box-shadow: var(--shadow-1);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    max-width: 680px;
    margin-left: auto;
    margin-right: auto;
}

.viewer-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.viewer-title i {
    color: var(--primary);
}

.viewer-controls {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-input,
.search-input {
    flex: 1;
    min-width: 180px;
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    font-size: 15px;
    background: var(--bg-primary);
    color: var(--text-primary);
    transition: all 0.2s;
}

.filter-input:focus,
.search-input:focus {
    outline: none;
    background: var(--bg-secondary);
    border-color: var(--primary);
}

.sort-select {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    font-size: 15px;
    background: var(--bg-secondary);
    cursor: pointer;
    transition: all 0.2s;
}

.sort-select:hover {
    background: var(--bg-primary);
}

.refresh-btn {
    padding: 8px 16px;
    background: var(--primary-button);
    color: var(--inverse-color);
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.refresh-btn:hover {
    background: var(--primary-hover);
}

/* Feed Container */
.moments-feed {
    width: 100%;
    max-width: 680px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
    overflow-y: visible !important;
    height: auto !important;
}

/* Post Card - Facebook Style */
.moment-post {
    background: var(--box-background-color);
    border-radius: 8px;
    box-shadow: var(--shadow-1);
    border: 1px solid var(--border-color);
    overflow: visible;
    transition: box-shadow 0.2s;
}

.moment-post:hover {
    box-shadow: var(--shadow-2);
}

/* Post Header */
.post-header {
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
}

.user-avatar {
    width: 40px !important;
    height: 40px !important;
    border-radius: 50%;
    object-fit: cover;
    cursor: pointer;
    border: 1px solid var(--border-color);
    flex-shrink: 0;
}

.user-info {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.user-name {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    cursor: pointer;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-name:hover {
    text-decoration: underline;
}

.user-meta {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 4px;
    line-height: 1.2;
}

.user-uuid {
    font-size: 12px;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.post-time {
    font-size: 12px;
    color: var(--text-secondary);
    white-space: nowrap;
}

/* Menu Button - Facebook Style */
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
    font-size: 20px;
    transition: all 0.2s;
    position: relative;
}

.menu-btn:hover {
    background: var(--bg-primary);
}

/* Dropdown Menu */
.dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
    background: var(--box-background-color);
    border-radius: 8px;
    box-shadow: 0 12px 28px 0 rgba(0, 0, 0, 0.2), 0 2px 4px 0 rgba(0, 0, 0, 0.1);
    min-width: 200px;
    z-index: 1000;
    padding: 8px;
    border: 1px solid var(--border-color);
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    padding: 8px 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--text-primary);
    border-radius: 6px;
    transition: all 0.2s;
    font-size: 15px;
    font-weight: 500;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
}

.dropdown-item:hover {
    background: var(--bg-primary);
}

.dropdown-item.delete-item {
    color: #e4405f;
}

.dropdown-item i {
    width: 20px;
    text-align: center;
    font-size: 16px;
}

/* Post Content */
.post-content {
    padding: 0 16px 12px;
}

.post-description {
    font-size: 15px;
    color: var(--text-primary);
    line-height: 1.3333;
    white-space: pre-wrap;
    word-wrap: break-word;
    margin-bottom: 12px;
}

.post-description.collapsed {
    max-height: 80px;
    overflow: hidden;
}

.see-more-btn {
    color: var(--text-secondary);
    font-weight: 600;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    font-size: 15px;
}

.see-more-btn:hover {
    text-decoration: underline;
}

/* Post Media */
.post-media {
    width: 100%;
    background: #000;
    position: relative;
    overflow: hidden;
}

.post-media img,
.post-media video {
    width: 100% !important;
    height: auto !important;
    display: block !important;
    max-height: 600px !important;
    max-width: 100% !important;
    min-height: auto !important;
    min-width: auto !important;
    object-fit: contain !important;
}

.media-navigation {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 100%;
    display: flex;
    justify-content: space-between;
    padding: 0 16px;
    pointer-events: none;
}

.nav-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #000;
    pointer-events: all;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.nav-btn:hover {
    background: rgba(255, 255, 255, 1);
    transform: scale(1.1);
}

.media-counter {
    position: absolute;
    top: 16px;
    right: 16px;
    background: rgba(0, 0, 0, 0.75);
    color: white;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 600;
}

.media-item {
    display: none;
}

.media-item.active {
    display: block;
}

/* Post Stats */
.post-stats {
    padding: 12px 16px 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 15px;
    color: var(--text-secondary);
}

.stats-left {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.stats-left:hover {
    text-decoration: underline;
}

.like-icon {
    display: flex;
    align-items: center;
    gap: 4px;
}

.like-icon i {
    color: #e4405f;
}

.stats-right {
    display: flex;
    gap: 12px;
}

.stats-right span {
    cursor: pointer;
}

.stats-right span:hover {
    text-decoration: underline;
}

/* Post Actions */
.post-actions {
    padding: 4px 16px;
    display: flex;
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
}

.action-btn {
    flex: 1;
    padding: 8px;
    background: none;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}

.action-btn:hover {
    background: var(--bg-primary);
}

.action-btn i {
    font-size: 18px;
}

/* Comments Section */
.comments-section {
    padding: 12px 16px;
    max-height: 400px;
    overflow-y: auto;
    display: none;
}

.comments-section.show {
    display: block;
}

.comment-item {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.comment-avatar {
    width: 32px !important;
    height: 32px !important;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.comment-content {
    flex: 1;
    min-width: 0;
}

.comment-bubble {
    background: var(--bg-primary);
    padding: 8px 12px;
    border-radius: 18px;
    display: inline-block;
    max-width: 100%;
}

.comment-author {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 2px;
}

.comment-text {
    font-size: 15px;
    color: var(--text-primary);
    word-wrap: break-word;
}

.comment-actions {
    padding: 0 12px;
    margin-top: 4px;
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: var(--text-secondary);
    font-weight: 600;
}

.comment-actions span {
    cursor: pointer;
}

.comment-actions span:hover {
    text-decoration: underline;
}

.comment-delete {
    color: #e4405f;
}

/* Likes Section */
.likes-section {
    padding: 12px 16px;
    max-height: 300px;
    overflow-y: auto;
    display: none;
    border-top: 1px solid var(--border-color);
}

.likes-section.show {
    display: block;
}

.like-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.like-item:hover {
    background: var(--bg-primary);
}

.like-avatar {
    width: 36px !important;
    height: 36px !important;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.like-user-info {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.like-name {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.like-uuid {
    font-size: 13px;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Loading States */
.loading-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
    min-height: 200px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--border-color);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--box-background-color);
    border-radius: 8px;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-1);
}

.empty-state i {
    font-size: 64px;
    color: var(--text-secondary);
    margin-bottom: 16px;
}

.empty-state h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 15px;
    color: var(--text-secondary);
    margin-bottom: 20px;
}

/* Load More */
.load-more-container {
    text-align: center;
    padding: 20px;
}

.load-more-btn {
    padding: 10px 24px;
    background: var(--box-background-color);
    color: var(--text-primary-color);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.load-more-btn:hover {
    background: var(--bg-primary);
}

/* Scroll to Top Button */
.scroll-top {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 56px;
    height: 56px;
    background: var(--primary-color);
    color: var(--inverse-color);
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s;
    z-index: 1000;
}

.scroll-top:hover {
    background: var(--primary-hover);
    transform: translateY(-4px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.scroll-top.visible {
    display: flex;
}

/* Scrollbar Styling */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--bg-primary);
}

::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--text-secondary);
}

/* Responsive Design */
@media (max-width: 768px) {
    .viewer-container {
        padding: 12px 0;
    }

    .viewer-header {
        margin: 0 8px 12px;
        padding: 12px 16px;
    }

    .viewer-title {
        font-size: 18px;
    }

    .viewer-controls {
        flex-direction: column;
        width: 100%;
    }

    .filter-input,
    .search-input,
    .sort-select {
        width: 100%;
    }

    .moments-feed {
        max-width: 100%;
        gap: 12px;
        padding: 0 8px;
    }

    .moment-post {
        border-radius: 0;
        border-left: none;
        border-right: none;
    }

    .post-media img,
    .post-media video {
        max-height: 400px;
    }

    .scroll-top {
        bottom: 16px;
        right: 16px;
        width: 48px;
        height: 48px;
    }
}

/* Dark Mode Support */
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

/* Utilities */
.text-muted {
    color: var(--text-secondary) !important;
}

.d-none {
    display: none !important;
}

.d-block {
    display: block !important;
}

.d-flex {
    display: flex !important;
}
