<style>
    /* Dark theme colors matching dashboard */
    :root {
        --dash-dark: #1e2632;
        --dash-darker: #161d27;
        --dash-primary: #3c8dbc;
        --dash-text: #a8b5c4;
        --dash-text-light: #ffffff;
    }
    
    .modern-tabs {
        background: var(--dash-dark);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid rgba(255,255,255,0.05);
    }
    .modern-tabs ul {
        display: flex;
        gap: 12px;
        padding: 0;
        margin: 0;
        list-style: none;
        flex-wrap: wrap;
    }
    .modern-tabs li {
        flex: 1;
        min-width: 114px;
        display: flex;
    }
    .modern-tabs a {
        flex: 1;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
        padding: 16px 20px;
        border-radius: 12px;
        text-decoration: none;
        color: var(--dash-text);
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s ease;
        background: var(--dash-darker);
        border: 2px solid transparent;
        min-height: 56px;
        box-sizing: border-box;
    }
    .modern-tabs a:hover {
        background: rgba(60, 141, 188, 0.15);
        color: var(--dash-text-light);
    }
    .modern-tabs li.active a {
        background: var(--dash-primary) !important;
        color: white !important;
        box-shadow: 0 4px 15px rgba(60, 141, 188, 0.4);
        border-color: rgba(255,255,255,0.15);
    }
    .modern-tabs .tab-icon {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: rgba(255,255,255,0.08);
        font-size: 13px;
    }
    .modern-tabs li.active .tab-icon {
        background: rgba(255,255,255,0.2);
    }
    
    [dir="rtl"] .modern-tabs ul {
        flex-direction: row-reverse;
    }
    [dir="rtl"] .modern-tabs a {
        flex-direction: row-reverse;
    }
    
    @media (max-width: 768px) {
        .modern-tabs li {
            min-width: 100%;
        }
    }
</style>

<div class="modern-tabs" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <ul>
        <li class="{{ request('name') == 'users' || empty(request('name')) ? 'active' : '' }}">
            <a href="?name=users">
                <span class="tab-icon"><i class="fa fa-users"></i></span>
                <span>{{ __('Host reports') }}</span>
            </a>
        </li>
        <li class="{{ request('name') == 'agencies' ? 'active' : '' }}">
            <a href="?name=agencies">
                <span class="tab-icon"><i class="fa fa-building"></i></span>
                <span>{{ __('agencies report') }}</span>
            </a>
        </li>
        <li class="{{ request('name') == 'bd' ? 'active' : '' }}">
            <a href="?name=bd">
                <span class="tab-icon"><i class="fa fa-briefcase"></i></span>
                <span>{{ __('BD report') }}</span>
            </a>
        </li>
    </ul>
</div>
