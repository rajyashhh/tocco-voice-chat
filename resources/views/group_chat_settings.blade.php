<style>
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        margin: 0;
        padding: 0;
        background-color: var(--gray-50);
        color: var(--text-primary-color);
        display: flex;
    }

    /* القائمة الجانبية */
    .settings-sidebar {
        width: 250px;
        background: var(--secondary-color);
        min-height: 400px;
        align-self: flex-start;
        padding: 20px;
        box-shadow: var(--shadow-md);
    }

    .settings-sidebar h2 {
        text-align: center;
        color: var(--primary-color);
    }

    .settings-menu button {
        display: block;
        width: 100%;
        text-align: right;
        padding: 15px;
        background: var(--gray-700);
        color: var(--text-primary-color);
        border: none;
        margin-bottom: 5px;
        cursor: pointer;
        font-size: 16px;
        border-radius: var(--border-radius);
        transition: var(--transition);
    }

    .settings-menu button:hover {
        background: var(--primary-color);
        color: var(--white);
    }

    /* محتوى الصفحة */
    .settings-content {
        flex-grow: 1;
        padding: 20px;
        background: var(--gray-50);
    }

    .settings-section {
        display: none;
    }

    .active {
        display: block;
    }

    /* تنسيق النماذج */
    form {
        background: transparent;
        padding: 20px;
        border-radius: var(--border-radius);
    }

    label {
        display: block;
        margin: 10px 0 5px;
        color: var(--text-primary-color);
    }

    input,
    select {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        background: var(--gray-50);
        border: 1px solid var(--gray-300);
        color: var(--gray-800);
        border-radius: var(--border-radius);
    }

    button {
        padding: 10px;
        border: none;
        cursor: pointer;
        font-weight: bold;
        border-radius: var(--border-radius);
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
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
        background: var(--gray-800);
        padding: 35px 40px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--gray-600);
    }

    .form-title {
        text-align: center;
        font-size: 22px;
        font-weight: 600;
        color: var(--white);
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--primary-color);
    }

    .form-group {
        margin-bottom: 25px;
        position: relative;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-size: 14px;
        font-weight: 600;
        color: var(--white);
        transition: color 0.3s ease;
    }

    .form-group:focus-within label {
        color: var(--primary-color);
    }

    .form-control {
        width: 100%;
        padding: 14px 16px;
        background: var(--white);
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius);
        color: var(--gray-900);
        font-size: 15px;
        font-weight: 500;
        transition: var(--transition);
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        background: var(--white);
        box-shadow: 0 0 0 3px var(--primary-hover-alpha);
    }

    .form-control:hover {
        border-color: var(--primary-color);
    }

    .btn-submit {
        width: 100%;
        padding: 14px 28px;
        background: var(--primary-button);
        color: var(--white);
        border: none;
        border-radius: var(--border-radius);
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        margin-top: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-submit:hover {
        background: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .btn-submit:active {
        transform: translateY(0);
    }

    .input-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-300);
        font-size: 18px;
    }

    .alert {
        padding: 15px 20px;
        border-radius: var(--border-radius);
        margin-bottom: 25px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-danger {
        background: rgba(220, 53, 69, 0.1);
        border: 1px solid rgba(220, 53, 69, 0.2);
        color: #dc3545;
    }

    .alert-success {
        background: rgba(40, 167, 69, 0.1);
        border: 1px solid rgba(40, 167, 69, 0.2);
        color: #28a745;
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
        background-color: var(--gray-900);
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
        color: var(--text-primary-color);
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

    /* Tab styling */
    .tab-buttons {
        display: flex;
        border-bottom: 1px solid var(--gray-300);
        margin-bottom: 20px;
    }

    .tab-button {
        padding: 10px 20px;
        background: var(--gray-700);
        border: none;
        color: var(--text-primary-color);
        cursor: pointer;
        margin-right: 5px;
        border-radius: var(--border-radius) var(--border-radius) 0 0;
        transition: var(--transition);
    }

    .tab-button:hover {
        background: var(--gray-200);
    }

    .tab-button.active {
        background: var(--primary-color);
        color: var(--white);
    }

    .tab-content {
        display: none;
        padding: 20px;
        background: var(--gray-800);
        border-radius: 0 var(--border-radius) var(--border-radius) var(--border-radius);
    }

    .tab-content.active {
        display: block;
    }

    .tab-content form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .tab-content label {
        grid-column: 1;
    }

    .tab-content input[type="file"] {
        grid-column: 2;
    }

    .tab-content button {
        grid-column: 1 / span 2;
        justify-self: center;
    }



    /* Badge Upload Section Specific Styles */
    .badge-upload-container {
        display: grid;
        width: 200%;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .badge-upload-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 15px;
        background: var(--gray-50);
        border-radius: var(--border-radius);
        min-height: 200px;
        border: 1px solid var(--gray-200);
        /* Ensures consistent height */
    }

    .badge-upload-item label {
        font-weight: bold;
        color: var(--primary-color);
        margin-bottom: 5px;
    }

    .badge-upload-item input[type="file"] {
        padding: 8px;
        background: var(--gray-50);
        border: 1px solid var(--gray-300);
        color: var(--gray-800);
        width: 100%;
        border-radius: var(--border-radius);
    }

    .badge-preview {
        margin-top: 10px;
        text-align: center;
        flex-grow: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .badge-preview img {
        max-width: 100%;
        max-height: 100px;
        cursor: pointer;
        border: 2px solid var(--gray-300);
        transition: transform 0.3s;
        border-radius: var(--border-radius);
    }

    .badge-preview img:hover {
        transform: scale(1.05);
        border-color: var(--primary-color);
    }

    .upload-button {
        display: block;
        width: auto;
        margin: 20px auto 0;
        padding: 10px 30px;
        background: var(--primary-button);
        color: var(--white);
        font-weight: bold;
        border-radius: var(--border-radius);
        transition: var(--transition);
        grid-column: 1 / -1;
        /* Span full width */
    }

    .upload-button:hover {
        background: var(--primary-color);
    }
    .swal-wide {
        width: 900px !important;
        font-size: 25px;
    }

    /* Responsive adjustments */
    @media (max-width: 2000px) {
        .badge-upload-container {
            grid-template-columns: 1fr;
        }
    }

    .switch-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-start;
        margin-top: 16px;
    }

    .switch-item {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        margin-right: 20px;
    }

    .switch-label {
        margin-left: 10px;
        font-weight: 500;
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }

    .switch input {
        display: none;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--gray-300);
        transition: .4s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: var(--white);
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: var(--green-color);
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .grid-per-pager .input, select{
        width: auto !important;
    }

    .exportPdfForm {
        background: transparent !important;
    }

    .modal-body input,
    .modal-body select {
        width: auto !important;
    }

    .close {
        right: 0 !important;
        top: 0 !important;
        font-size: 30px !important;
    }

    button.close {
        width: auto !important;
    }

    .btn-sm {
        margin: 0 !important;
    }

    /* للشاشات الصغيرة (موبايل) */
@media (max-width: 768px) {
    body {
        flex-direction: column;
    }

    .all-page {
        flex-direction: column;
        display: flex;
    }

    .settings-sidebar {
        width: 100%;
        padding: 10px;
        box-shadow: none;
        position: relative;
    }

    .settings-sidebar h2 {
        margin-bottom: 10px;
    }

    .settings-menu {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .settings-menu button {
        flex: 1 1 45%;
        font-size: 14px;
        padding: 10px;
        margin: 2px 0;
    }

    .settings-content {
        width: 100% !important;
        padding: 10px;
    }

    .form {
        width: 100% !important;
    }

    .badge-upload-container {
        grid-template-columns: 1fr !important;
        width: 100% !important;
    }
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
                    style="background: var(--primary-color); color: var(--text-secondary-color);">{{ __('group chat setting') }}</button>
                
            </div>
        </div>

        <div class="settings-content">
            <div id="PercentageTarget" class="settings-section active">


                <form id="target-percentage-form" action="{{ url('admin/update-configs-group-chat') }}" method="POST">
                    @csrf
                    @php
                        $errorMessage = $errors ? $errors->first('msg') : null;
                    @endphp
                    @if ($errorMessage)
                        <div class="alert alert-danger">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            {{ $errorMessage }}
                        </div>
                    @endif
                    <div class="form">
                        <div class="form-title">
                            {{ __('Group Chat Settings') }}
                        </div>
                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">

                        <div class="form-group">
                            <label for="group_chat">{{ __('admin.price') }}</label>
                            <input type="number" id="group_chat" name="group_chat" value="{{ $groupChat }}" class="form-control" placeholder="{{ __('Enter price') }}">
                        </div>

                        <div class="form-group">
                            <label for="max_message">{{ __('max message') }}</label>
                            <input type="number" id="max_message" name="max_message" value="{{ $maxMessage }}" class="form-control" placeholder="{{ __('Enter max messages') }}">
                        </div>

                        <button type="submit" class="btn-submit">{{ __('Save Settings') }}</button>
                    </div>
                </form>
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


                if (sectionId === 'Badges') {
                    const EnglishTabBtn = document.querySelector('.tab-button[onclick*="en"]');
                    if (EnglishTabBtn) {
                        EnglishTabBtn.click(); // fire real click event
                    }
                }
                // if (sectionId === 'user_days') {
                //     const EnglishTabBtn = document.querySelector('.tab-button[onclick*="en"]');
                //     if (EnglishTabBtn) {
                //         EnglishTabBtn.click();
                //     }
                // }

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
