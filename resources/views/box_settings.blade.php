<style>
    :root {
        --primary-color: {{ config('themes.primaryColor', '#FF9428') }};
        --secondary-color: {{ config('themes.secondaryColor', '#1A1A1A') }};
        --text-primary-color: {{ config('themes.textPrimaryColor', '#fdf8f8') }};
        --text-secondary-color: {{ config('themes.textSecondaryColor', '#c1b9b9') }};
        --box-background-color: {{ config('themes.boxBackgroundColor', '#222222') }};
    }

    * { box-sizing: border-box; }

    .all-page {
        display: flex;
        min-height: 400px;
    }

    /* ═══════ CONTENT ═══════ */
    .settings-content {
        flex-grow: 1;
        padding: 20px;
    }

    .settings-section {
        display: none;
    }

    .settings-section.active {
        display: block;
    }

    /* ═══════ FORM ═══════ */
    form {
        background: var(--text-secondary-color);
        padding: 24px;
        border-radius: 10px;
    }

    .form {
        max-width: 480px;
        margin: 0 auto;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #000000;
        margin-bottom: 6px;
    }

    input:focus,
    select:focus {
        border-color: var(--primary-color);
    }

    input::placeholder {
        color: var(--text-secondary-color);
        opacity: 0.5;
    }

    /* ═══════ BUTTON ═══════ */
    .form-group .btn-primary,
    form button[type="submit"] {
        padding: 12px 28px;
        background: var(--primary-color);
        color: var(--text-primary-color);
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: opacity 0.2s;
        width: auto;
    }

    .form-group .btn-primary:hover,
    form button[type="submit"]:hover {
        opacity: 0.85;
    }

    /* ═══════ MODAL ═══════ */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        padding-top: 50px;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
    }

    .modal-content {
        margin: auto;
        display: block;
        width: 80%;
        max-width: 700px;
    }

    .close {
        position: absolute;
        top: 15px;
        right: 35px;
        color: white;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
    }

    img {
        width: 201px;
        display: block;
        height: 99px;
        margin-bottom: 20px;
    }

    button {
        width: 200px;
    }

    /* ═══════ RESPONSIVE ═══════ */
    @media (max-width: 768px) {
        .all-page {
            display: block;
        }

        .settings-content {
            width: 100%;
            padding: 20px 16px;
        }

        .form {
            width: auto;
        }
    }
</style>
</head>

<body>
    <div class="all-page">
        <div class="settings-sidebar">
            <h2>{{ __('Settings') }}</h2>
            <div class="settings-menu">
                <button onclick="showSection('VipSettings')" style="background: var(--primary-color); color: var(--text-secondary-color);">{{ __('Luck Box Settings') }}</button>
            </div>
        </div>

        <div class="settings-content">
            <div id="VipSettings" class="settings-section active">
                <h3>{{ __('Luck Box Settings') }}</h3>

                <form action="{{ route('admin.ovip-config') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form">
                        <!-- Wallet Lucky Box -->
                        <div class="form-group">
                            <label for="app_wallet_lucky_box">{{ __('application wallet percentage from the super box') }}</label>
                            <input type="number"
                                id="app_wallet_lucky_box"
                                name="app_wallet_lucky_box"
                                min="1"
                                value="{{ $config['app_wallet_lucky_box'] ?? 0 }}"
                                class="form-control"
                                placeholder="{{ __('Enter the wallet lucky box value') }}" />
                        </div>

                        <!-- Normal Box Duration -->
                        <div class="form-group">
                            <label for="normal_box_duration">{{ __('Time period until the normal box expires') }}</label>
                            <input type="number"
                                id="normal_box_duration"
                                name="normal_box_duration"
                                min="1"
                                value="{{ $config['normal_box_duration'] ?? 0 }}"
                                class="form-control"
                                placeholder="{{ __('Enter duration in minutes') }}" />
                            <span class="help-block">{{ __('duration in hours') }}</span>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="imageModal" class="modal" onclick="closeFullScreen()">
            <span class="close">&times;</span>
            <img class="modal-content" id="fullImage">
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                function getQueryParam(name) {
                    const urlParams = new URLSearchParams(window.location.search);
                    return urlParams.get(name);
                }

                const activeTab = getQueryParam("firsttab") || "VipSettings";
                showSection(activeTab);
            });

            function showSection(sectionId) {
                document.querySelectorAll('.settings-section').forEach(section => {
                    section.classList.remove('active');
                });

                document.getElementById(sectionId).classList.add('active');

                document.querySelectorAll('.settings-menu button').forEach(button => {
                    button.style.backgroundColor = '';
                    button.style.color = '';
                });

                const activeButton = document.querySelector(`.settings-menu button[onclick="showSection('${sectionId}')"]`);
                if (activeButton) {
                    activeButton.style.backgroundColor = 'var(--primary-color)';
                    activeButton.style.color = 'var(--text-secondary-color)';
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
        </script>
    </div>
</body>
