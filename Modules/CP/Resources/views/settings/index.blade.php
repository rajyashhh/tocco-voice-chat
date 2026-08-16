<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: #f0f2f5;
        color: #1a1a2e;
        line-height: 1.6;
        min-height: 100vh;
    }

    .all-page {
        display: flex;
        min-height: 100vh;
        gap: 24px;
        padding: 24px;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* ── Sidebar ── */
    .settings-sidebar {
        width: 260px;
        flex-shrink: 0;
        background: #fff;
        border-radius: 14px;
        padding: 24px 16px;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        border: 1px solid #e5e7eb;
        position: sticky;
        top: 24px;
        height: fit-content;
    }

    .settings-sidebar h2 {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 2px solid #6366f1;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .settings-sidebar h2::before { content: '⚙️'; font-size: 1.1rem; }

    .settings-menu { display: flex; flex-direction: column; gap: 6px; }

    .settings-menu button {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        color: #4b5563;
        padding: 12px 16px;
        border-radius: 10px;
        text-align: left;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all .2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .settings-menu button::before { content: '›'; font-size: 1.2rem; color: #9ca3af; transition: all .2s; }
    .settings-menu button:hover { background: #eef2ff; border-color: #6366f1; color: #1a1a2e; }
    .settings-menu button:hover::before { color: #6366f1; transform: translateX(3px); }

    .settings-menu button.active {
        background: #6366f1 !important;
        border-color: #6366f1 !important;
        color: #fff !important;
        font-weight: 600;
        box-shadow: 0 4px 14px rgba(99,102,241,.35);
    }
    .settings-menu button.active::before { content: '✓'; color: #fff; }

    /* ── Content ── */
    .settings-content {
        flex: 1;
        background: #fff;
        border-radius: 14px;
        padding: 32px;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        border: 1px solid #e5e7eb;
        min-height: 500px;
    }

    .settings-section { display: none; animation: fadeUp .35s ease; }
    .settings-section.active { display: block; }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Section title ── */
    .settings-section h2 {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 8px;
        padding-bottom: 12px;
        position: relative;
        text-align: center;
    }
    .settings-section h2::after {
        content: '';
        position: absolute;
        bottom: 0; left: 50%; transform: translateX(-50%);
        width: 48px; height: 3px;
        background: #6366f1;
        border-radius: 3px;
    }

    .section-desc {
        color: #6b7280;
        font-size: .9rem;
        margin-bottom: 24px;
        text-align: center;
    }

    /* ── Form card ── */
    form {
        background: #fafbfc;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 28px;
    }

    .form { max-width: 520px; margin: 0 auto; }

    /* ── Labels ── */
    label {
        display: block;
        margin-bottom: 6px;
        color: #374151;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    /* ── Inputs ── */
    input[type="text"],
    input[type="number"],
    input[type="email"],
    input[type="password"],
    select {
        width: 100%;
        padding: 11px 14px;
        background: #fff;
        border: 1.5px solid #d1d5db;
        border-radius: 10px;
        color: #111827;
        font-size: .95rem;
        font-weight: 500;
        transition: all .2s ease;
        margin-bottom: 18px;
    }
    input:focus, select:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99,102,241,.12);
    }

    /* ── Submit button ── */
    button[type="submit"], button.save-btn, .btn-form {
        background: #6366f1 !important;
        color: #fff !important;
        border: none;
        padding: 12px 32px;
        border-radius: 10px;
        font-size: .95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 2px 10px rgba(99,102,241,.25);
        width: auto !important;
        min-width: 160px;
    }
    button[type="submit"]:hover, button.save-btn:hover, .btn-form:hover {
        background: #4f46e5 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(99,102,241,.35);
    }

    /* ── Feature Description ── */
    .feature-description-container {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        margin-top: 24px;
    }
    .feature-description-container h4 {
        font-size: 1rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .feature-description-container h4::before { content: '📋'; }
    .feature-description-container .external-content {
        min-height: 80px;
        color: #6b7280;
        font-size: .9rem;
        line-height: 1.7;
    }

    /* ── Field Card ── */
    .field-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        transition: all .2s ease;
        margin-bottom: 20px;
    }
    .field-card:hover {
        border-color: #6366f1;
        box-shadow: 0 4px 12px rgba(99,102,241,.08);
    }
    .field-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }
    .field-card-icon {
        width: 44px; height: 44px;
        background: #eef2ff;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .field-card-header label {
        margin-bottom: 0;
        font-size: .9rem;
    }

    /* ── Alerts ── */
    .alert {
        padding: 12px 18px;
        border-radius: 10px;
        margin: 12px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: .9rem;
        font-weight: 500;
    }
    .alert-danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .alert-info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
    .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }

    /* ── Modal ── */
    .modal {
        display: none; position: fixed; z-index: 1000;
        left: 0; top: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,.7); backdrop-filter: blur(4px);
    }
    .modal-content {
        margin: auto; display: block;
        max-width: 90%; max-height: 90vh;
        border-radius: 12px;
    }
    .close {
        position: absolute; top: 20px; right: 20px;
        color: #fff; font-size: 2rem; cursor: pointer;
        width: 40px; height: 40px; display: flex;
        align-items: center; justify-content: center;
        border-radius: 50%; background: rgba(255,255,255,.15);
    }

    /* ── Toggle Switch ── */
    .switch { position: relative; display: inline-block; width: 52px; height: 28px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #d1d5db; transition: .3s; border-radius: 28px; }
    .slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 3px; bottom: 3px; background: white; transition: .3s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.15); }
    input:checked + .slider { background: #6366f1; }
    input:checked + .slider:before { transform: translateX(24px); }

    /* ── Scrollbar ── */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f3f4f6; }
    ::-webkit-scrollbar-thumb { background: #c7c7cc; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #6366f1; }

    /* ── Responsive ── */
    @media (max-width: 1024px) {
        .all-page { flex-direction: column; padding: 16px; }
        .settings-sidebar { width: 100%; position: static; }
        .settings-menu { flex-direction: row; flex-wrap: wrap; }
        .settings-menu button { flex: 1; min-width: 180px; }
    }
    @media (max-width: 768px) {
        .settings-content { padding: 20px; }
        .form { max-width: 100%; }
    }
</style>

<body>
<div class="all-page">
    {{-- <div class="settings-sidebar">
        <h2>{{ __('Settings') }}</h2>
        <div class="settings-menu">
            <button onclick="showSection('AppFeature')" class="active">
                {{ __('CP Settings') }}
            </button>
        </div>
    </div> --}}

    <div class="settings-content">
        <div id="AppFeature" class="settings-section active">
            <h2>{{ __('CP Settings') }}</h2>
            <p class="section-desc">{{ __('Configure CP module gift type preferences.') }}</p>

            <form id="agencyFeatureForm" action="{{ url("admin/cp-settings/update") }}" method="POST" enctype="multipart/form-data">
                @csrf
                @php
                    $errorMessage = $errors ? $errors->first('msg') : null;
                @endphp
                @if ($errorMessage)
                    <div class="alert alert-danger text-center" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                @endif

                <div class="form">
                    <div class="field-card">
                        <div class="field-card-header">
                            <div class="field-card-icon">🎁</div>
                            <label for="cp_enable_all_gifts">{{ __('Gifts Type') }}</label>
                        </div>
                        <select name="cp_enable_all_gifts" id="cp_enable_all_gifts">
                            <option value="true" {{ $enableGifts === true ? 'selected' : '' }}>
                                {{ __('all gifts') }}
                            </option>
                            <option value="false" {{ $enableGifts === false ? 'selected' : '' }}>
                                {{ __('cp gifts') }}
                            </option>
                        </select>
                    </div>

                    <div style="text-align:center; margin: 20px 0;">
                        <button type="submit" class="btn-form">💾 {{ __('Save') }}</button>
                    </div>

                    <div class="feature-description-container">
                        <h4>{{ __('Feature Description') }}</h4>
                        <div id="feature-description-content" class="external-content">
                          <h5>  {{ __('admin.feature_description') }}</h5>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="imageModal" class="modal" onclick="closeFullScreen()">
    <span class="close">&times;</span>
    <img class="modal-content" id="fullImage">
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        function getQueryParam(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }
        const activeTab = getQueryParam("firsttab") || "AppFeature";
        showSection(activeTab);
    });

    function showSection(sectionId) {
        document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
        document.getElementById(sectionId)?.classList.add('active');

        document.querySelectorAll('.settings-menu button').forEach(b => b.classList.remove('active'));
        const btn = document.querySelector(`.settings-menu button[onclick="showSection('${sectionId}')"]`);
        if (btn) btn.classList.add('active');

        const url = new URL(window.location);
        url.searchParams.set("firsttab", sectionId);
        window.history.pushState({}, "", url);
    }

    function openFullScreen(imgElement) {
        document.getElementById("imageModal").style.display = "block";
        document.getElementById("fullImage").src = imgElement.src;
    }

    function closeFullScreen() {
        document.getElementById("imageModal").style.display = "none";
    }
</script>
</body>
