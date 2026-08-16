<style>
    .carousel-settings-wrapper {
        display: flex;
        gap: 24px;
        min-height: 500px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Sidebar - reads colors from dashboard menu */
    .cs-sidebar {
        width: 260px;
        flex-shrink: 0;
        background: var(--gradient-vertical-primary, linear-gradient(180deg, #2563eb 0%, #1f2937 100%));
        border-radius: 0 20px 20px 0;
        padding: 28px 20px;
        box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
    }

    .cs-sidebar-title {
        font-size: 20px;
        font-weight: 700;
        color: #ffffff;
        text-align: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid rgba(255, 255, 255, 0.15);
    }

    .cs-sidebar-title i {
        color: #ffffff;
        margin-right: 8px;
    }

    .cs-nav-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 14px 18px;
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.8);
        border: 1px solid transparent;
        border-radius: var(--border-radius, 12px);
        cursor: pointer;
        font-size: 15px;
        font-weight: 500;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 6px;
        text-align: left;
        position: relative;
        overflow: hidden;
    }

    .cs-nav-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.2);
        transform: translateX(4px);
    }

    .cs-nav-btn.active {
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.3);
        font-weight: 600;
    }

    .cs-nav-btn i {
        font-size: 18px;
        width: 22px;
        text-align: center;
        color: rgba(255, 255, 255, 0.9);
    }

    /* Main Content */
    .cs-content {
        flex: 1;
        min-width: 0;
    }

    .cs-section {
        display: none;
    }

    .cs-section.active {
        display: block;
    }

    .cs-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 28px;
    }

    .cs-header-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .cs-header-title i {
        color: #f59e0b;
        font-size: 22px;
    }

    .cs-header-badge {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        font-size: 12px;
        font-weight: 600;
        padding: 5px 14px;
        border-radius: 20px;
        letter-spacing: 0.5px;
    }

    /* Form Card */
    .cs-form-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
    }

    .cs-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }

    .cs-field {
        position: relative;
    }

    .cs-field-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
    }

    .cs-field-label i {
        color: #64748b;
        font-size: 16px;
    }

    .cs-field-input-wrap {
        position: relative;
    }

    .cs-field-input {
        width: 100%;
        padding: 12px 16px;
        padding-right: 50px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        color: #1e293b;
        font-size: 15px;
        font-weight: 500;
        transition: all 0.25s ease;
        box-sizing: border-box;
    }

    .cs-field-input:focus {
        outline: none;
        border-color: #f59e0b;
        background: #fffbeb;
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
    }

    .cs-field-unit {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        font-weight: 600;
        color: #94a3b8;
        background: #e2e8f0;
        padding: 3px 8px;
        border-radius: 6px;
        pointer-events: none;
    }

    .cs-field-hint {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 6px;
        font-size: 12px;
        color: #94a3b8;
    }

    .cs-field-hint i {
        font-size: 12px;
    }

    /* Alert */
    .cs-alert {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        border: 1px solid #fca5a5;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
    }

    .cs-alert ul {
        margin: 0;
        padding: 0 16px;
        color: #dc2626;
        font-size: 14px;
    }

    .cs-alert li {
        margin-bottom: 4px;
    }

    /* Submit */
    .cs-submit-row {
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
    }

    .cs-submit-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 32px;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        width: auto;
    }

    .cs-submit-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
    }

    .cs-submit-btn:active {
        transform: translateY(0);
    }

    .cs-submit-btn i {
        font-size: 16px;
    }

    /* Modal */
    .cs-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(4px);
    }

    .cs-modal-content {
        margin: auto;
        display: block;
        max-width: 700px;
        max-height: 80vh;
        margin-top: 10vh;
        border-radius: 12px;
    }

    .cs-modal-close {
        position: absolute;
        top: 20px;
        right: 30px;
        color: white;
        font-size: 36px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.2s;
        background: none;
        border: none;
        width: auto;
    }

    .cs-modal-close:hover {
        color: #f59e0b;
    }

    /* Reset img in this scope */
    .carousel-settings-wrapper img {
        width: auto;
        height: auto;
        display: inline;
        margin-bottom: 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .carousel-settings-wrapper {
            flex-direction: column;
        }

        .cs-sidebar {
            width: 100%;
            border-radius: 12px;
            padding: 16px;
        }

        .cs-form-grid {
            grid-template-columns: 1fr;
        }

        .cs-form-card {
            padding: 20px;
        }
    }

    @media (max-width: 480px) {
        .cs-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
    }
</style>

<div class="carousel-settings-wrapper">
    <!-- Sidebar -->
    <div class="cs-sidebar">
        <div class="cs-sidebar-title">
            <i class="fa fa-cog"></i> {{ __('Settings') }}
        </div>
        <div class="cs-nav-menu">
            <button class="cs-nav-btn active" onclick="showSection('carouselSetting')" type="button">
                <i class="fa fa-image"></i>
                {{ __('banner Settings') }}
            </button>
        </div>
    </div>

    <!-- Content -->
    <div class="cs-content">
        <div id="carouselSetting" class="cs-section active">
            <!-- Header -->
            <div class="cs-header">
                <div class="cs-header-title">
                    <i class="fa fa-sliders"></i>
                    {{ __('banner Settings') }}
                </div>
                <span class="cs-header-badge">
                    <i class="fa fa-info-circle"></i> {{ __('Price for 1 day') }}
                </span>
            </div>

            <!-- Form -->
            <div class="cs-form-card">
                <form action="{{ route('admin.app.settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Errors --}}
                    @if($errors->any())
                        <div class="cs-alert">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="cs-form-grid">
                        <!-- Party Tab (Top) -->
                        <div class="cs-field">
                            <label class="cs-field-label" for="home_top">
                                <i class="fa fa-arrow-up"></i> {{ __('Party Tab (Top)') }}
                            </label>
                            <div class="cs-field-input-wrap">
                                <input type="number"
                                    id="home_top"
                                    name="home_top"
                                    min="1"
                                    value="{{ $config['home_top'] ?? \Modules\Country\Helper\SuperAdminHelper::getHourlyBannerPrice('display_home_top') }}"
                                    class="cs-field-input"
                                    placeholder="{{ __('Enter value') }}"
                                    required />
                                <span class="cs-field-unit">//hour</span>
                            </div>
                            <div class="cs-field-hint">
                                <i class="fa fa-info-circle"></i> {{ __('Price for 1 day') }}
                            </div>
                        </div>

                        <!-- Party Tab (Middle) -->
                        <div class="cs-field">
                            <label class="cs-field-label" for="home_middle">
                                <i class="fa fa-minus"></i> {{ __('Party Tab (Middle)') }}
                            </label>
                            <div class="cs-field-input-wrap">
                                <input type="number"
                                    id="home_middle"
                                    name="home_middle"
                                    min="1"
                                    value="{{ $config['home_middle'] ?? \Modules\Country\Helper\SuperAdminHelper::getHourlyBannerPrice('display_home_middle') }}"
                                    class="cs-field-input"
                                    placeholder="{{ __('Enter value') }}"
                                    required />
                                <span class="cs-field-unit">/hour</span>
                            </div>
                            <div class="cs-field-hint">
                                <i class="fa fa-info-circle"></i> {{ __('Price for 1 day') }}
                            </div>
                        </div>

                        <!-- Live Tab -->
                        <div class="cs-field">
                            <label class="cs-field-label" for="live">
                                <i class="fa fa-video-camera"></i> {{ __('Live Tab') }}
                            </label>
                            <div class="cs-field-input-wrap">
                                <input type="number"
                                    id="live"
                                    name="live"
                                    min="1"
                                    value="{{ $config['live'] ?? \Modules\Country\Helper\SuperAdminHelper::getHourlyBannerPrice('display_live') }}"
                                    class="cs-field-input"
                                    placeholder="{{ __('Enter value') }}"
                                    required />
                                <span class="cs-field-unit">//hour</span>
                            </div>
                            <div class="cs-field-hint">
                                <i class="fa fa-info-circle"></i> {{ __('Price for 1 day') }}
                            </div>
                        </div>

                        <!-- Discover Tab -->
                        <div class="cs-field">
                            <label class="cs-field-label" for="discover">
                                <i class="fa fa-compass"></i> {{ __('Discover Tab') }}
                            </label>
                            <div class="cs-field-input-wrap">
                                <input type="number"
                                    id="discover"
                                    name="discover"
                                    min="1"
                                    value="{{ $config['discover'] ?? \Modules\Country\Helper\SuperAdminHelper::getHourlyBannerPrice('display_discover') }}"
                                    class="cs-field-input"
                                    placeholder="{{ __('Enter value') }}"
                                    required />
                                <span class="cs-field-unit">/hour</span>
                            </div>
                            <div class="cs-field-hint">
                                <i class="fa fa-info-circle"></i> {{ __('Price for 1 day') }}
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="cs-submit-row">
                        <button type="submit" class="cs-submit-btn">
                            <i class="fa fa-check"></i>
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="cs-modal" onclick="closeFullScreen()">
        <button class="cs-modal-close" type="button">&times;</button>
        <img class="cs-modal-content" id="fullImage">
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    function getQueryParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }

    const activeTab = getQueryParam("firsttab") || "carouselSetting";
    showSection(activeTab);
});

function showSection(sectionId) {
    document.querySelectorAll('.cs-section').forEach(function(section) {
        section.classList.remove('active');
    });

    var target = document.getElementById(sectionId);
    if (target) {
        target.classList.add('active');
    }

    document.querySelectorAll('.cs-nav-btn').forEach(function(button) {
        button.classList.remove('active');
    });

    var activeButton = document.querySelector('.cs-nav-btn[onclick="showSection(\'' + sectionId + '\')"]');
    if (activeButton) {
        activeButton.classList.add('active');
    }

    var url = new URL(window.location);
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
</script>
