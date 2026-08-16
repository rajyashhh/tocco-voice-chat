


<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: var(--app-bg, #f0f2f5);
        color: var(--text-primary, #1a1a2e);
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
        background: var(--surface, #fff);
        border-radius: 14px;
        padding: 24px 16px;
        box-shadow: var(--shadow-card, 0 1px 4px rgba(0,0,0,.06));
        border: 1px solid var(--border, #e5e7eb);
        position: sticky;
        top: 24px;
        height: fit-content;
    }

    .settings-sidebar h2 {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-primary, #1a1a2e);
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 2px solid #6366f1;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .settings-sidebar h2::before { content: '⚙️'; font-size: 1.1rem; }

    .settings-menu { display: flex; flex-direction: column; gap: 6px; }

    .settings-menu button {
        background: var(--surface-raised, #f9fafb);
        border: 1px solid var(--border, #e5e7eb);
        color: var(--text-secondary, #4b5563);
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
    }
    .settings-menu button::before { content: '›'; font-size: 1.2rem; color: #9ca3af; transition: all .2s; }
    .settings-menu button:hover { background: var(--row-hover, #eef2ff); border-color: #6366f1; color: var(--text-primary, #1a1a2e); }
    .settings-menu button:hover::before { color: #6366f1; transform: translateX(3px); }

    .settings-menu button.active {
        background: #6366f1;
        border-color: #6366f1;
        color: #fff;
        font-weight: 600;
        box-shadow: 0 4px 14px rgba(99,102,241,.35);
    }
    .settings-menu button.active::before { content: '✓'; color: #fff; }

    /* ── Content ── */
    .settings-content {
        flex: 1;
        background: var(--surface, #fff);
        border-radius: 14px;
        padding: 32px;
        box-shadow: var(--shadow-card, 0 1px 4px rgba(0,0,0,.06));
        border: 1px solid var(--border, #e5e7eb);
        min-height: 500px;
    }

    .settings-section { display: none; animation: fadeUp .35s ease; }
    .settings-section.active { display: block; }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Section title ── */
    .settings-section h3 {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--text-primary, #1a1a2e);
        margin-bottom: 24px;
        padding-bottom: 12px;
        position: relative;
    }
    .settings-section h3::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0;
        width: 48px; height: 3px;
        background: #6366f1;
        border-radius: 3px;
    }

    /* ── Form card ── */
    form {
        background: var(--surface-raised, #fafbfc);
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 12px;
        padding: 28px;
    }

    .form { max-width: 480px; }

    /* ── Labels ── */
    label {
        display: block;
        margin-bottom: 6px;
        color: var(--text-secondary, #374151);
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: .6px;
    }

    /* ── Inputs ── */
    input[type="text"],
    input[type="number"],
    input[type="email"],
    input[type="password"],
    select {
        width: 100%;
        padding: 11px 14px;
        background: var(--input-bg, #fff);
        border: 1.5px solid var(--input-border, #d1d5db);
        border-radius: 10px;
        color: var(--input-text, #111827);
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
    input::placeholder { color: #9ca3af; }

    /* ── Submit button ── */
    button[type="submit"], button.save-btn {
        background: #6366f1;
        color: #fff;
        border: none;
        padding: 12px 28px;
        border-radius: 10px;
        font-size: .95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 10px rgba(99,102,241,.25);
        margin-top: 4px;
    }
    button[type="submit"]:hover, button.save-btn:hover {
        background: #4f46e5;
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(99,102,241,.35);
    }
    button[type="submit"]::before, button.save-btn::before { content: '💾'; }

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
    .alert-danger::before  { content: '⚠️'; }
    .alert-info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
    .alert-info::before    { content: 'ℹ️'; }
    .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
    .alert-success::before { content: '✅'; }

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
        box-shadow: 0 20px 60px rgba(0,0,0,.3);
    }
    .close {
        position: absolute; top: 20px; right: 20px;
        color: #fff; font-size: 2rem; font-weight: 300;
        cursor: pointer; width: 40px; height: 40px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%; background: rgba(255,255,255,.15);
        transition: all .2s;
    }
    .close:hover { background: rgba(255,255,255,.3); }

    /* ── Misc ── */
    .badge-upload-container { display: grid; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); gap: 16px; margin: 20px 0; }
    .badge-upload-item { background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 12px; padding: 20px; transition: all .2s; }
    .badge-upload-item:hover { box-shadow: var(--shadow-card, 0 4px 12px rgba(0,0,0,.08)); border-color: #6366f1; }
    .badge-preview { width: 100%; height: 140px; background: var(--surface-raised, #f9fafb); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin: 10px 0; border: 2px dashed var(--border-strong, #d1d5db); }
    .badge-preview img { max-width: 80%; max-height: 80%; object-fit: contain; }

    .switch-container { display: grid; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); gap: 10px; margin: 16px 0; }
    .switch-item { display: flex; align-items: center; justify-content: space-between; background: var(--surface-raised, #f9fafb); padding: 14px 18px; border-radius: 10px; border: 1px solid var(--border, #e5e7eb); transition: all .2s; }
    .switch-item:hover { border-color: #6366f1; }

    .grid-per-pager { background: var(--surface, #fff); border-radius: 12px; overflow: hidden; border: 1px solid var(--border, #e5e7eb); }
    .grid-per-pager table { width: 100%; border-collapse: collapse; }
    .grid-per-pager th { background: #6366f1; color: #fff; padding: 12px 16px; text-align: left; font-weight: 600; font-size: .85rem; }
    .grid-per-pager td { padding: 12px 16px; border-bottom: 1px solid var(--border, #f3f4f6); color: var(--text-secondary, #374151); font-size: .9rem; }
    .grid-per-pager tr:hover { background: var(--row-hover, #f5f3ff); }

    .swal-wide { font-family: 'Inter', sans-serif !important; }

    /* ── Field Cards ── */
    .field-card {
        background: var(--surface, #fff);
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: all .2s ease;
    }
    .field-card:hover {
        border-color: #6366f1;
        box-shadow: 0 4px 12px rgba(99,102,241,.1);
    }
    .field-card-icon {
        font-size: 1.8rem;
        margin-bottom: 8px;
    }
    .field-card label {
        text-align: center;
        margin-bottom: 12px;
        color: var(--text-secondary, #374151);
    }
    .field-card input {
        text-align: center;
        margin-bottom: 0 !important;
        font-size: 1.1rem;
        font-weight: 600;
    }
    .input-wrap {
        position: relative;
    }
    .input-wrap input {
        padding-right: 36px;
    }
    .input-suffix {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-weight: 600;
        font-size: .9rem;
        pointer-events: none;
    }

    /* ── Total bar states ── */
    #total-bar.is-valid { background: #f0fdf4 !important; border: 1px solid #bbf7d0; }
    #total-bar.is-valid #total-value { color: #16a34a !important; }
    #total-bar.is-invalid { background: #fef2f2 !important; border: 1px solid #fecaca; }
    #total-bar.is-invalid #total-value { color: #dc2626 !important; }

    /* ── Token-safety for utility classes / inline blocks on this page ── */
    .settings-content .bg-white { background: var(--surface, #fff) !important; color: var(--text-primary, #1a1a2e); }
    #total-bar { background: var(--surface-sunken, #f3f4f6) !important; }
    #total-bar span:first-child { color: var(--text-secondary, #374151) !important; }

    /* ── Responsive ── */
    @media (max-width: 1024px) {
        .all-page { flex-direction: column; padding: 16px; }
        .settings-sidebar { width: 100%; position: static; }
        .settings-menu { flex-direction: row; flex-wrap: wrap; }
        .settings-menu button { flex: 1; min-width: 180px; }
    }
    @media (max-width: 768px) {
        .settings-content { padding: 20px; }
        button[type="submit"] { width: 100%; }
    }
    @media (max-width: 480px) {
        .settings-menu button { min-width: 100%; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
    <div class="all-page">
        <div class="settings-sidebar">
            <h2 onclick="toggleMenu()">☰ {{ __('Settings') }}</h2>
            <div class="settings-menu">
                <button onclick="showSection('PercentageTarget')"
                    style="background: var(--primary-color); color: var(--text-secondary-color);">{{ __('Percentage target') }}</button>
                <button onclick="showSection('user_days')">{{ __('user days') }}</button>

                {{-- <button
                    onclick="showSection('targets_table')"
                    class="{{ $tab == 'targets_table' ? 'active' : '' }}">
                    {{ __('Targets') }}
                </button> --}}
                @if ($remaining_diamonds_action)
                    <button onclick="showSection('remaining_diamonds')">{{ __('remaining diamonds') }}</button>
                @endif
                {{-- <button onclick="showSection('convert_diamonds')">{{ __('convert diamonds') }}</button> --}}

            </div>
        </div>

        <div class="settings-content">
            <div id="PercentageTarget" class="settings-section active">

                <h3> {{ __('Percentage target') }}</h3>
                <p style="color:#6b7280; margin:-16px 0 24px; font-size:.9rem;">{{ __('Set the weight of each metric. Total must equal 100%.') }}</p>

                <form id="target-percentage-form" action="{{ route('admin.target-percentage') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @php
                        $errorMessage = $errors ? $errors->first('msg') : null;
                    @endphp
                    @if ($errorMessage)
                        <div class="alert alert-danger text-center" style="margin-bottom: 20px;"> {{ $errorMessage }}
                        </div>
                    @endif

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px;">
                        <div class="field-card">
                            <div class="field-card-icon">🕐</div>
                            <label>{{ __('Hours') }}</label>
                            <div class="input-wrap">
                                <input type="number" name="hours" value="{{ $hours }}" class="form-control">
                                <span class="input-suffix">%</span>
                            </div>
                        </div>
                        <div class="field-card">
                            <div class="field-card-icon">📅</div>
                            <label>{{ __('Days') }}</label>
                            <div class="input-wrap">
                                <input type="number" name="days" value="{{ $days }}" class="form-control">
                                <span class="input-suffix">%</span>
                            </div>
                        </div>
                        <div class="field-card">
                            <div class="field-card-icon">📸</div>
                            <label>{{ __('Moments') }}</label>
                            <div class="input-wrap">
                                <input type="number" name="moments" value="{{ $moments }}" class="form-control">
                                <span class="input-suffix">%</span>
                            </div>
                        </div>
                        <div class="field-card">
                            <div class="field-card-icon">🎬</div>
                            <label>{{ __('Reels') }}</label>
                            <div class="input-wrap">
                                <input type="number" name="reels" value="{{ $reels }}" class="form-control">
                                <span class="input-suffix">%</span>
                            </div>
                        </div>
                        <div class="field-card">
                            <div class="field-card-icon">💎</div>
                            <label>{{ __('Diamonds') }}</label>
                            <div class="input-wrap">
                                <input type="number" name="diamonds" value="{{ $diamonds }}" class="form-control">
                                <span class="input-suffix">%</span>
                            </div>
                        </div>
                    </div>

                    <div id="total-bar" style="background:#f3f4f6; border-radius:10px; padding:12px 20px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-weight:600; color:#374151;">Total</span>
                        <span id="total-value" style="font-weight:700; font-size:1.1rem; color:#6366f1;">0%</span>
                    </div>

                    <button type="submit">{{ __('Save') }}</button>
                </form>
            </div>

            <div id="user_days" class="settings-section">

                <h3>{{ __('User Days') }}</h3>
                <p style="color:#6b7280; margin:-16px 0 24px; font-size:.9rem;">{{ __('Configure the minimum hours per day for user activity.') }}</p>

                <form id="user-days-form" action="{{ route('admin.app.settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @php
                        $errorMessage = $errors ? $errors->first('msg') : null;
                    @endphp
                    @if ($errorMessage)
                        <div class="alert alert-danger text-center" style="margin-bottom: 20px;"> {{ $errorMessage }}
                        </div>
                    @endif
                    <div style="max-width:360px;">
                        <div class="field-card">
                            <div class="field-card-icon">⏱️</div>
                            <label>{{ __('Hours per Day') }}</label>
                            <input type="number" name="hours_days" value="{{ $hoursDays }}" class="form-control" style="margin-bottom:0;">
                        </div>
                    </div>
                    <button type="submit" style="margin-top:20px;">{{ __('Save') }}</button>
                </form>
            </div>
                @if ($remaining_diamonds_action)
                    <div id="remaining_diamonds" class="settings-section p-4 shadow-sm rounded bg-white">

                        <!-- Header Row -->
                        <div class="d-flex justify-content-between align-items-center mb-4">

                            <a href="{{ admin_url('remaining-diamonds') }}" class="btn btn-outline-primary fw-bold px-4 py-2">
                                <i class="fa fa-history me-1"></i> {{ __('History') }}
                            </a>
                        </div>

                        <form method="POST" action="{{ admin_url('remaining-diamond-settings/save') }}">
                            @csrf

                            <!-- Exchange Type -->
                            <div class="mb-3">
                                <label for="remaining_diamonds" class="form-label fw-semibold">{{ __('exchange to') }}</label>

                                <select name="remaining_diamonds" id="remaining_diamonds" class="form-select form-select-lg">
                                    <option value="nothing" {{ $settings['remaining_diamonds']=='nothing' ? 'selected' : '' }}>
                                        {{ __('Do not make any thing') }}
                                    </option>
                                    <option value="coins" {{ $settings['remaining_diamonds']=='coins' ? 'selected' : '' }}>
                                        {{ __('Coins') }}
                                    </option>
                                    <option value="diamonds" {{ $settings['remaining_diamonds']=='diamonds' ? 'selected' : '' }}>
                                        {{ __('Diamonds') }}
                                    </option>
                                </select>
                            </div>

                            <!-- Context Text -->
                            <div class="alert alert-info mt-3">
                                <i class="fa fa-info-circle me-1"></i>
                                {{ __('Remaining diamonds from last month that the host user can convert to coins, keep as diamonds, or leave unchanged.') }}
                            </div>

                            <!-- Save Button -->
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-success btn-lg px-4 fw-bold shadow">
                                    <i class="fa fa-check-circle me-1"></i> {{ __('Save') }}
                                </button>
                            </div>

                        </form>
                    </div>

                @endif

{{-- 
                 <div id="convert_diamonds" class="settings-section p-4 shadow-sm rounded bg-white">

                        <form method="POST" action="{{ route('admin.app.settings.update') }}">
                            @csrf

                            <!-- Exchange Type -->
                            <div class="mb-3">
                                <label for="convert_diamonds" class="form-label fw-semibold">{{ __('convert diamonds to coins according') }}</label>

                                <select name="convert_diamonds" id="convert_diamonds" class="form-select form-select-lg">
                                    <option value="zones_coins" {{ $settings['convert_diamonds']=='zones_coins' ? 'selected' : '' }}>
                                        {{ __('Zones') }}
                                    </option>
                                    <option value="super_admin_coins" {{ $settings['convert_diamonds']=='super_admin_coins' ? 'selected' : '' }}>
                                        {{ __('Super Admin') }}
                                    </option>
                                    <option value="shipping_coins" {{ $settings['convert_diamonds']=='shipping_coins' ? 'selected' : '' }}>
                                        {{ __('Agency Charge') }}
                                    </option>
                                     <option value="user_coins" {{ $settings['convert_diamonds']=='user_coins' ? 'selected' : '' }}>
                                        {{ __('The User') }}
                                    </option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-success btn-lg px-4 fw-bold shadow">
                                    <i class="fa fa-check-circle me-1"></i> {{ __('Save') }}
                                </button>
                            </div>

                        </form>
                    </div> --}}



            {{-- <div id="targets_table" class="settings-section">
                <h3>{{ __('Targets table') }}</h3>

                {!! $targetGrid !!}
            </div> --}}

        <div id="imageModal" class="modal" onclick="closeFullScreen()">
            <span class="close">&times;</span>
            <img class="modal-content" id="fullImage">
        </div>




        <script>


            function previewImage(input, previewId) {
                const preview = document.getElementById(previewId);
                const file = input.files[0];

                if (file) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }

                    reader.readAsDataURL(file);
                }
            }

            function openFullScreen(imgElement) {
                var modal = document.getElementById("imageModal");
                var modalImg = document.getElementById("fullImage");

                modal.style.display = "block";
                modalImg.src = imgElement.src;
            }

            function openLanguageTab(evt, languageCode) {
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });

                // Remove active class from all buttons
                document.querySelectorAll('.tab-button').forEach(button => {
                    button.classList.remove('active');
                });

                // Show the current tab and mark button as active
                document.getElementById(languageCode).classList.add('active');
                evt.currentTarget.classList.add('active');
            }

        </script>

        <!-- كود JavaScript -->
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Function to get query parameter by name
                function getQueryParam(name) {
                    const urlParams = new URLSearchParams(window.location.search);
                    return urlParams.get(name);
                }

                // Get the 'firsttab' parameter from URL or default to 'brandSettings'
                const activeTab = getQueryParam("firsttab") || "PercentageTarget";

                // Show the selected tab
                showSection(activeTab);
            });

            function showSection(sectionId) {
                // Remove active class from all sections
                document.querySelectorAll('.settings-section').forEach(section => {
                    section.classList.remove('active');
                });

                // Add active class to the selected section
                document.getElementById(sectionId).classList.add('active');

                // ✅ Reset ALL buttons
                document.querySelectorAll('.settings-menu button').forEach(button => {
                    button.classList.remove('active');
                    button.style.backgroundColor = '';
                    button.style.color = '';
                });

                // ✅ Find button by checking onclick content
                document.querySelectorAll('.settings-menu button').forEach(button => {
                    const onclick = button.getAttribute('onclick');
                    if (onclick && onclick.includes(sectionId)) {
                        button.classList.add('active');
                        button.style.backgroundColor = 'var(--primary-color)';
                        button.style.color = 'var(--text-secondary-color)';
                    }
                });

                if (sectionId === 'Badges') {
                    const EnglishTabBtn = document.querySelector('.tab-button[onclick*="en"]');
                    if (EnglishTabBtn) {
                        EnglishTabBtn.click();
                    }
                }

                const url = new URL(window.location);
                url.searchParams.set("firsttab", sectionId);
                window.history.pushState({}, "", url);
            }

            function openFullScreen(imgElement) {
                var modal = document.getElementById("imageModal");
                var modalImg = document.getElementById("fullImage");

                modal.style.display = "block";
                modalImg.src = imgElement.src;
            }

            function closeFullScreen() {
                document.getElementById("imageModal").style.display = "none";
            }

            // ── Live total % counter ──
            function updateTotal() {
                const fields = ['hours','days','moments','reels','diamonds'];
                let total = 0;
                fields.forEach(f => { total += parseFloat(document.querySelector('input[name="'+f+'"]')?.value) || 0; });
                const el = document.getElementById('total-value');
                const bar = document.getElementById('total-bar');
                if (el) el.textContent = total + '%';
                if (bar) {
                    bar.classList.remove('is-valid','is-invalid');
                    if (total === 100) bar.classList.add('is-valid');
                    else if (total > 0) bar.classList.add('is-invalid');
                }
            }
            document.querySelectorAll('#target-percentage-form input[type="text"]').forEach(inp => {
                inp.addEventListener('input', updateTotal);
            });
            updateTotal(); // initial calc

            document.getElementById('target-percentage-form').addEventListener('submit', function(e) {
                    e.preventDefault();

                    const hours = parseFloat(document.querySelector('input[name="hours"]').value) || 0;
                    const days = parseFloat(document.querySelector('input[name="days"]').value) || 0;
                    const moments = parseFloat(document.querySelector('input[name="moments"]').value) || 0;
                    const reels = parseFloat(document.querySelector('input[name="reels"]').value) || 0;
                    const diamonds = parseFloat(document.querySelector('input[name="diamonds"]').value) || 0;

                    const total = hours + days + moments + reels + diamonds;

                    if (total !== 100) {
                        Swal.fire({
                            icon: 'error',
                            title: 'تحذير',
                            text: "{{ __('total_percentage_must_be_100') }}",
                            confirmButtonText: 'حسنًا'
                        });
                        return;
                    }

                    e.target.submit();
                });

            $(document).on('change', '#stopCharge,#stopInviteCode,#stopTransferSalary,#stopGiftCheckbox', function () {

                const id        = this.id;
                const isChecked = $(this).is(':checked');

                const map = {
                    stopCharge:         ['/admin/send-request-stop-charge',  'stop_charge'],
                    stopInviteCode:     ['/admin/send-request-invite-code',        'stop_invite_code'],
                    stopTransferSalary: ['/admin/send-request-transfer-salary','transfer_salary'],
                    stopGiftCheckbox:   ['/admin/close-open-gift',           'close_open_gifts'],
                };

                const [url, key] = map[id];

                $.ajax({
                    url,
                    type: 'POST',
                    data: { [key]: isChecked },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                })
                    .done(()   => toastr.success('Saved'))
                    .fail(err => toastr.error('Error'));
            });

        </script>
    </div>
</body>
