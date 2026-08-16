<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
    }

    /* القائمة الجانبية */
    .settings-sidebar {
        width: 250px;
        min-height: 400px;
        padding: 20px;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
    }

    .settings-menu button {
        display: block;
        width: 100%;
        text-align: right;
        padding: 15px;
        color: black;
        background: white;
        border: none;
        margin-bottom: 5px;
        cursor: pointer;
        font-size: 16px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    }

    .settings-menu button:hover {
        background: var(--primary-color);
        color: var(--text-secondary-color);
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

    /* Toggle Switch Styling */
    .feature-toggle-container {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }

    .toggle-label {
        margin-left: 10px;
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #2196F3;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

    .feature-description-container {
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .external-content {
        min-height: 200px;
    }

    .loading {
        color: #888;
        font-style: italic;
    }

    @media (max-width: 576px) {
    }

    @media (max-width: 768px) {
        .all-page {
            display: block;
        }

        .settings-content {
            width: 100%;
        }

        .form-control {
            width: auto;
        }
    }

    @media (max-width: 992px) {
    }

    @media (max-width: 1200px) {
    }

    @media (max-width: 1400px) {
    }
</style>
</head>

<body>
    <div class="all-page">
        <div class="settings-sidebar">
            <h2>{{__("setting")}}</h2>
            <div class="settings-menu">
                <button onclick="showSection('PaidRoom')">{{ __('Paid Room') }}</button>
                <button onclick="showSection('custom_background_settings')">{{ __('Custom Background settings') }}</button>
                <button onclick="showSection('additional_settings')">{{ __('Additional settings') }}</button>
                <button onclick="showSection('admin_settings')">{{ __('Admin settings') }}</button>
                <button onclick="showSection('show_room')">{{ __('show rooms') }}</button>
                <button onclick="showSection('microphone_settings')">{{ __('Microphone settings') }}</button>
            </div>
        </div>

    <div class="settings-content">

        <div id="PaidRoom" class="settings-section active">
            <h2>{{ __('Paid Room') }}</h2>
            <form id="paidRoomForm" class="new-form" action="{{ route('admin.room-settings.store') }}" method="POST">
                @csrf
                <div class="form">
                    <div class="feature-toggle-container">
                        <span class="toggle-label">{{ __('Enable Pay Room') }}</span>
                        <label class="switch">
                            <input type="checkbox" id="switch_toggle"
                                   {{ $settings['paid_room'] ?? false ? 'checked' : '' }}
                                   onchange="toggleNumberInput(this);">
                            <span class="slider round"></span>
                        </label>
                        <input type="hidden" name="paid_room" id="switch_value"
                               value="{{ $settings['paid_room'] ?? '0' }}">
                    </div>

                    <div id="numberInputContainer"
                         style="{{ $settings['paid_room'] ?? false ? 'display: block;' : 'display: none;' }}">
                        <label>{{ __('Enter Number') }}</label>
                        <input type="number" name="paid_room_amount" class="form-control"
                               value="{{ $settings['paid_room_amount'] ?? '' }}">
                    </div>

                        <button type="submit">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>

            <div id="custom_background_settings" class="settings-section">
                <h3> {{ __('Custom Background settings') }}</h3>
                <form action="{{ route('admin.room-settings.store') }}" class="new-form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form">
                        <label>{{ __('Cost of background request:') }} </label>
                        <input type="text" name="cost_request_background"
                            value="{{ $settings['cost_request_background'] ?? '' }}" class="form-control">

                    <label>{{ __('Background expiration in days:') }} </label>
                    <input type="text" name="background_expiration"
                           value="{{ $settings['background_expiration'] ?? '' }}" class="form-control">

                    <button type="submit">{{ __('save') }}</button>
                </div>

            </form>
        </div>

        <div id="additional_settings" class="settings-section">
            <h3>{{ __('Additional settings') }}</h3>
            <form action="{{ route('admin.room-settings.store') }}" class="new-form" method="POST" class="new-form" id="roomSettingsForm"
                  novalidate>
                <div class="form">
                    @csrf
                    <label>{{ __('Room Rule:') }}</label>
                    <input class="form-control" type="text" name="room_rule"
                           value="{{ $settings['room_rule'] ?? '' }}" required>
                    <!-- <small class="text-danger d-none">{{ __('This field is required') }}</small> -->

                    <label>{{ __('Room Rule en:') }}</label>
                    <input class="form-control" type="text" name="room_rule_en"
                           value="{{ $settings['room_rule_en'] ?? '' }}" required>
                    <!-- <small class="text-danger d-none">{{ __('This field is required') }}</small> -->

                    <label>{{ __('Youtube Key:') }}</label>
                    <input class="form-control" type="text" name="youtube_key"
                           value="{{ $settings['youtube_key'] ?? '' }}" required>
                    <!-- <small class="text-danger d-none">{{ __('This field is required') }}</small> -->

                    <label>{{ __('Pk Background:') }}</label>
                    <input class="form-control" type="text" name="pk_background"
                           value="{{ $settings['pk_background'] ?? '' }}" required>
                    <!-- <small class="text-danger d-none">{{ __('This field is required') }}</small> -->

                    <label>{{ __('Private Comment Price:') }}</label>
                    <input class="form-control" type="text" name="private_comment_price"
                           value="{{ $settings['private_comment_price'] ?? '' }}" required>
                    <!-- <small class="text-danger d-none">{{ __('This field is required') }}</small> -->

                    <label>{{ __('Total Gift Price:') }}</label>
                    <input class="form-control" type="number" name="total_gift_price"
                           value="{{ $settings['total_gift_price'] ?? '' }}" required>
                    <!-- <small class="text-danger d-none">{{ __('This field is required') }}</small> -->

                    <label>{{ __('special bar coin') }}</label>
                    <input class="form-control" type="number" name="special_bar_coin"
                           value="{{ $settings['special_bar_coin'] ?? '' }}" required>
                    <!-- <small class="text-danger d-none">{{ __('This field is required') }}</small> -->

                    <button type="submit" class="btn btn-primary mt-3">{{ __('save') }}</button>
                </div>
            </form>
            <script>
                document.getElementById('roomSettingsForm').addEventListener('submit', function (e) {
                    let isValid = true;

                    this.querySelectorAll('input[required]').forEach(input => {
                        const hint = input.nextElementSibling;
                        if (!input.value.trim()) {
                            hint.classList.remove('d-none');
                            input.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            hint.classList.add('d-none');
                            input.classList.remove('is-invalid');
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            </script>
            <style>
                .is-invalid {
                    border-color: #dc3545 !important;
                }

                small.text-danger {
                    font-size: 0.85rem;
                }

                input[type=number] {
                    font-variant-numeric: normal;
                    unicode-bidi: plaintext;
                    direction: ltr;
                }
            </style>
        </div>

        <div id="admin_settings" class="settings-section">
            <h3>{{ __('Admin settings') }}</h3>
            <form action="{{ route('admin.room-settings.store') }}" class="new-form" method="POST">
                <div class="form">
                    @csrf
                    <label>{{ __('admins number') }}</label>
                    <input class="form-control" type="number" min="1" name="max_room_admin"
                           value="{{ $settings['max_room_admin'] ?? 4 }}">
                    <small class="form-text text-muted" style="    position: relative; top: -15px;">
                        {{ __('This is the default number of admins allowed per room.') }}
                    </small>
                    <br>
                    <button type="submit">{{ __('save') }}</button>
                </div>
            </form>
        </div>

          <div id="show_room" class="settings-section">
            <h3>{{ __('show rooms') }}</h3>
            <form action="{{ route('admin.room-settings.store') }}" class="new-form" method="POST">
                <div class="form">
                    @csrf
                   <span class="toggle-label">{{ __('room in home screen layer') }}</span>
                         <input type="hidden" name="show_room" value="0">
                            <label class="switch">
                                <input type="checkbox"
                                    name="show_room"
                                    value="1"
                                    @checked((bool) ($settings['show_room'] ?? false))>
                                <span class="slider round"></span>
                            </label>
                     <br><br>
                    <button type="submit">{{ __('save') }}</button>
                </div>
            </form>
        </div>
        <div id="microphone_settings" class="settings-section">
            <h3>{{ __('Microphone settings') }}</h3>
            <form action="{{ route('admin.room-settings.store') }}" class="new-form" method="POST" enctype="multipart/form-data">
                <div class="form">
                    @csrf

                    {{-- Open Mic Image --}}
                    <label>{{ __('Open Mic Image') }}</label>
                    @if(!empty($settings['open_mic_image']))
                        <img src="{{ getImagePath($settings['open_mic_image']) }}" alt="Open Mic"
                             style="width: 120px; height: 120px; object-fit: contain; cursor: pointer; border: 1px solid #444; border-radius: 8px; padding: 5px; background: #333;"
                             onclick="openFullScreen(this)">
                    @endif
                    <input class="form-control" type="file" name="open_mic_image" accept="image/*">
                    <small class="form-text text-muted" style="position: relative; top: -10px; color: #aaa;">
                        {{ __('Upload an image for the open (active) microphone.') }}
                    </small>

                    {{-- Close Mic Image --}}
                    <label>{{ __('Close Mic Image') }}</label>
                    @if(!empty($settings['close_mic_image']))
                        <img src="{{ getImagePath($settings['close_mic_image']) }}" alt="Close Mic"
                             style="width: 120px; height: 120px; object-fit: contain; cursor: pointer; border: 1px solid #444; border-radius: 8px; padding: 5px; background: #333;"
                             onclick="openFullScreen(this)">
                    @endif
                    <input class="form-control" type="file" name="close_mic_image" accept="image/*">
                    <small class="form-text text-muted" style="position: relative; top: -10px; color: #aaa;">
                        {{ __('Upload an image for the closed (muted) microphone.') }}
                    </small>

                    <br>
                    <button type="submit">{{ __('save') }}</button>
                </div>
            </form>
        </div>

    </div>
    <div id="imageModal" class="modal" onclick="closeFullScreen()">
        <span class="close">&times;</span>
        <img class="modal-content" id="fullImage">
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var reelHiddenValue = document.getElementById('host_reel_value').value;
            document.getElementById('use_zego').checked = (reelHiddenValue === '1');
        });

        function toggleNumberInput(checkbox) {
            const numberContainer = document.getElementById('numberInputContainer');
            const switchValue = document.getElementById('switch_value');

            if (checkbox.checked) {
                numberContainer.style.display = 'block';
                switchValue.value = '1';
            } else {
                numberContainer.style.display = 'none';
                switchValue.value = '0';
            }
        }

        // showSection('PaidRoom');
        document.addEventListener("DOMContentLoaded", function () {
            // Function to get query parameter by name
            function getQueryParam(name) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(name);
            }

            // Get the 'firsttab' parameter from URL or default to 'PaidRoom'
            const activeTab = getQueryParam("firsttab") || "PaidRoom";

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
                activeButton.style.setProperty('background-color', 'var(--primary-color)', 'important');
                activeButton.style.setProperty('color', 'var(--text-secondary-color)', 'important');
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
