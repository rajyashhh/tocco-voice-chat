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
        padding: 20px;
        border-radius: 5px;
        background-color: var(--box-background-color) !important;
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

    /* Tab styling */
    .tab-buttons {
        display: flex;
        border-bottom: 1px solid #444;
        margin-bottom: 20px;
    }

    .tab-button {
        padding: 10px 20px;
        background: #333;
        border: none;
        color: white;
        cursor: pointer;
        margin-right: 5px;
        border-radius: 5px 5px 0 0;
    }

    .tab-button:hover {
        background: #555;
    }

    .tab-button.active {
        background: #ff9800;
        color: #121212;
    }

    .tab-content {
        display: none;
        padding: 20px;
        background: #222;
        border-radius: 0 5px 5px 5px;
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
        background: #333;
        border-radius: 5px;
        min-height: 200px;
        /* Ensures consistent height */
    }

    .badge-upload-item label {
        font-weight: bold;
        color: #ff9800;
        margin-bottom: 5px;
    }

    .badge-upload-item input[type="file"] {
        padding: 8px;
        background: #444;
        border: 1px solid #555;
        color: white;
        width: 100%;
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
        border: 2px solid #555;
        transition: transform 0.3s;
    }

    .badge-preview img:hover {
        transform: scale(1.05);
        border-color: #ff9800;
    }

    .upload-button {
        display: block;
        width: auto;
        margin: 20px auto 0;
        padding: 10px 30px;
        background: #ff9800;
        color: #121212;
        font-weight: bold;
        border-radius: 5px;
        transition: background 0.3s;
        grid-column: 1 / -1;
        /* Span full width */
    }

    .upload-button:hover {
        background: #ffab40;
    }

    /* Responsive adjustments */
    @media (max-width: 2000px) {
        .badge-upload-container {
            grid-template-columns: 1fr;
        }
    }
</style>
</head>

<body>
    <div class="all-page">
        <div class="settings-sidebar">
            <h2>{{ __('Settings') }}</h2>
            <div class="settings-menu">
                <button onclick="showSection('PaymentGateways')"
                    style="background: var(--primary-color); color: var(--text-secondary-color);">
                    {{ __('Payment Gateways') }}
                </button>
            </div>
        </div>
        <div class="settings-content">

            <div id="PaymentGateways" class="settings-section active">
                <h3>{{ __('Payment Gateways') }}</h3>

                <a href="{{ URL::previous() }}" class="btn btn-primary">{{ __('back') }}</a>
                <form action="{{ route('admin.update-payment-gateway', $gateway->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="">{{ __('title') }}</label>
                        <input type="text" name="title" class="form-control" value="{{ $gateway->title }}"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Photo') }}</label>
                        <input type="file" name="photo" class="form-group">
                    </div>

                    <button class="btn btn-primary" type="submit">{{ __('Submit') }}</button>
                </form>

            </div>

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
        function showSection(sectionId) {
            //     document.querySelectorAll('.settings-section').forEach(section => {
            //         section.classList.remove('active');
            //     });

            //     document.getElementById(sectionId).classList.add('active');
            // }

            document.querySelectorAll('.settings-menu button').forEach(button => {
                button.style.backgroundColor = ''; // إرجاع اللون الافتراضي
                button.style.color = ''; // إرجاع لون النص الافتراضي
            });

            // إخفاء جميع الأقسام
            document.querySelectorAll('.settings-section').forEach(section => {
                section.classList.remove('active');
            });

            // إظهار القسم المطلوب
            document.getElementById(sectionId).classList.add('active');

            // تحديد الزر المضغوط عليه
            const activeButton = document.querySelector(`.settings-menu button[onclick="showSection('${sectionId}')"]`);

            // تعيين لون الزر من متغير CSS
            activeButton.style.backgroundColor = 'var(--primary-color)';
            activeButton.style.color = 'var(--text-secondary-color)';


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
