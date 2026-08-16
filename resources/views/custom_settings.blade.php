<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #121212;
        color: white;
        display: flex;
    }

    /* القائمة الجانبية */
    .settings-sidebar {
        width: 250px;
        background: #222;
        min-height: 400px;

        padding: 20px;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
    }

    .settings-sidebar h2 {
        text-align: center;
        color: #ff9800;
    }

    .settings-menu button {
        display: block;
        width: 100%;
        text-align: right;
        padding: 15px;
        background: #333;
        color: white;
        border: none;
        margin-bottom: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    .settings-menu button:hover {
        background: #ff9800;
    }

    /* محتوى الصفحة */
    .settings-content {
        flex-grow: 1;
        padding: 20px;
    }

    .settings-section {
        display: none;
    }

    .active {
        display: block;
    }

    /* تنسيق النماذج */
    form {
        background: #222;
        padding: 20px;
        border-radius: 5px;
    }

    label {
        display: block;
        margin: 10px 0 5px;
    }

    input,
    select {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        background: #333;
        border: 1px solid #444;
        color: white;
    }

    button {
        padding: 10px;
        border: none;
        cursor: pointer;
        font-weight: bold;
    }

    .all-page {
        display: inline-flex;
    }

    .wrapper {
        width: 100%;

    }

    .settings-content {
        width: 869px;

    }

    .form {
        width: 400px;
        margin: auto;
    }

    /* تصميم النافذة */
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

    /* الصورة داخل النافذة */
    .modal-content {
        margin: auto;
        display: block;
        width: 80%;
        max-width: 700px;
    }

    /* زر الإغلاق */
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
</style>
</head>

<body>
    <div class="all-page">
        <div class="settings-sidebar">
            <h2>{{ __('Settings') }}</h2>
            <div class="settings-menu">
                <button onclick="showSection('CustomSettings')"
                    style="background: var(--primary-color); color: var(--text-secondary-color);">{{ __('Custom Settings') }}
                </button>
                <button onclick="showSection('AndroidSettings')"
                    >{{ __('admin.android') }}
                </button>
                <button onclick="showSection('HuawiSettings')"
                    >{{ __('admin.huawei') }}
                </button>
                <button onclick="showSection('IosSettings')"
                    >{{ __('admin.ios') }}
                </button>
            </div>
        </div>

        <div class="settings-content">
            <div id="CustomSettings" class="settings-section active">
                <h3> {{ __('Custom Settings') }}</h3>

                <form action="{{ route('admin.custom-setting') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form">
                        <label for="show_welcom_enmation"
                            class="control-label">{{ __('admin.show_welcome_animation') }}:</label>
                        <label class="switch">
                            <input type="checkbox" name="show_welcom_enmation"
                                {{ $settings['show_welcom_enmation'] ? 'checked' : '' }}>
                        </label>

                        <label for="chat_status" class="control-label">{{ __('admin.enable_chat') }}:</label>
                        <label class="switch">
                            <input type="checkbox" name="chat_status" {{ $settings['chat_status'] ? 'checked' : '' }}>
                        </label>


                        <label for="invitation_code_date"
                            class="control-label">{{ __('admin.invitation_code_date') }}:</label>
                        <input type="text" name="invitation_code_date"
                            value="{{ $settings['invitation_code_date'] }}" class="inputs_cus_form">



                        <button type="submit">{{ __('Save') }}</button>
                    </div>

                </form>
            </div>



            <div id="AndroidSettings" class="settings-section">
                <h3> {{ __('admin.android') }}</h3>

                <form action="{{ route('admin.android-setting') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form">
                        <h1 class="control-label text-center">{{ __('admin.android') }}</h1>
                        <label for="android_min_version">{{ __('admin.minimum_version') }}:</label>
                        <input type="text" name="android_min_version" value="{{ $settings['android_min_version'] }}"
                            class="inputs_cus_form">

                        <label for="android_current_version">{{ __('admin.current_version') }}:</label>
                        <input type="text" name="android_current_version"
                            value="{{ $settings['android_current_version'] }}" class="inputs_cus_form">

                        <label for="android_update_required">{{ __('admin.update_required') }}:</label>
                        <input type="text" name="android_update_required"
                            value="{{ $settings['android_update_required'] }}" class="inputs_cus_form">


                        <button type="submit">{{ __('Save') }}</button>
                    </div>

                </form>
            </div>




            <div id="HuawiSettings" class="settings-section">
                <h3> {{ __('admin.huawei') }}</h3>

                <form action="{{ route('admin.huawi-setting') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form">
                        <h1 class="control-label text-center">{{ __('admin.huawei') }}</h1>
                        <label for="huawei_min_version">{{ __('admin.minimum_version') }}:</label>
                        <input type="text" name="huawei_min_version" value="{{ $settings['huawei_min_version'] }}"
                            class="inputs_cus_form">

                        <label for="huawei_current_version">{{ __('admin.current_version') }}:</label>
                        <input type="text" name="huawei_current_version"
                            value="{{ $settings['huawei_current_version'] }}" class="inputs_cus_form">

                        <label for="huawei_update_required">{{ __('admin.update_required') }}:</label>
                        <input type="text" name="huawei_update_required"
                            value="{{ $settings['huawei_update_required'] }}" class="inputs_cus_form">
                        <button type="submit">{{ __('Save') }}</button>
                    </div>

                </form>
            </div>



            <div id="IosSettings" class="settings-section">
                <h3> {{ __('admin.ios') }}</h3>

                <form action="{{ route('admin.ios-setting') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form">
                        <h1 class="control-label text-center">{{ __('admin.ios') }}</h1>
                        <label for="ios_min_version">{{ __('admin.minimum_version') }}:</label>
                        <input type="text" name="ios_min_version" value="{{ $settings['ios_min_version'] }}"
                            class="inputs_cus_form">

                        <label for="ios_current_version">{{ __('admin.current_version') }}:</label>
                        <input type="text" name="ios_current_version" value="{{ $settings['ios_current_version'] }}"
                            class="inputs_cus_form">

                        <label for="ios_update_required">{{ __('admin.update_required') }}:</label>
                        <input type="text" name="ios_update_required"
                            value="{{ $settings['ios_update_required'] }}" class="inputs_cus_form">
                            <button type="submit">{{ __('Save') }}</button>

                    </div>

                </form>
            </div>
        </div>
        <div id="imageModal" class="modal" onclick="closeFullScreen()">
            <span class="close">&times;</span>
            <img class="modal-content" id="fullImage">
        </div>
        <!-- كود JavaScript -->
        <script>
document.addEventListener("DOMContentLoaded", function () {
    // Function to get query parameter by name
    function getQueryParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }

    // Get the 'firsttab' parameter from URL or default to 'brandSettings'
    const activeTab = getQueryParam("firsttab") || "CustomSettings";

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

    // Reset button styles
    document.querySelectorAll('.settings-menu button').forEach(button => {
        button.style.backgroundColor = '';
        button.style.color = '';
    });

    // Highlight the active button
    const activeButton = document.querySelector(`.settings-menu button[onclick="showSection('${sectionId}')"]`);
    if (activeButton) {
        activeButton.style.backgroundColor = 'var(--primary-color)';
        activeButton.style.color = 'var(--text-secondary-color)';
    }

    // Update the URL with the selected tab without reloading
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
