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
                <button onclick="showSection('VipSettings')" style="background: var(--primary-color); color: var(--text-secondary-color);">{{ __('vip Settings') }}</button>
            </div>
        </div>

        <div class="settings-content">
            <div id="VipSettings" class="settings-section active">
                <h3> {{ __('vip Settings') }}</h3>

                <form action="{{ route('admin.ovip-config') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    {{-- <div class="form">
                        <label for="buy_aristocracy">{{ __('admin.user_exp') }}</label>
                        <input type="number" id="buy_aristocracy" name="buy_aristocracy" min="1"  value="{{$config['buy_aristocracy'] ?? ''}}"/>
                    </div> --}}

                    <div class="form">
                        <label for="enable_vip_auto">{{ __('Enable Vip Auto') }}</label>
                        <select name="enable_vip_auto" id="enable_vip_auto">
                            <option value="1" {{ (isset($config['enable_vip_auto']) && $config['enable_vip_auto']) ? 'selected' : '' }}>
                                {{ __('True') }}
                            </option>
                            <option value="0" {{ (isset($config['enable_vip_auto']) && !$config['enable_vip_auto']) ? 'selected' : '' }}>
                                {{ __('False') }}
                            </option>
                        </select>
                        <div>
                            <small class="" style="font-weight: 600;">
                                {{ __('Using Vip without presenting') }}
                                <i class="fa fa-exclamation-circle"></i>
                            </small>
                        </div>
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
    const activeTab = getQueryParam("firsttab") || "VipSettings";

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
