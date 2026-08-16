<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #121212;
        /* color: white; */
        display: flex;
        color: var(--text-secondary-color);

    }

    /* القائمة الجانبية */
   




    /* محتوى الصفحة */
    .settings-content {
        flex-grow: 1;
        padding: 20px;
        background: #ffffff !important;
        filter: none;
        color: #333333 !important;
        border-top: 3px solid #e0e0e0 !important;
        border-radius: 20px !important;
        padding: 15px;
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
        min-height: 200px; /* Ensures consistent height */
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
        grid-column: 1 / -1; /* Span full width */
    }
    .settings-sidebar {

        background-color: var(--table-background-color);
        display: inline;
        justify-content: center;
        align-items: center;
        padding: 10px 0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        color: var(--text-secondary-color);
        overflow-x: auto;
        /* يجعل الشريط قابلاً للتمرير عند الحاجة */
        white-space: nowrap;
        /* يمنع العناصر من النزول لسطر جديد */
        scrollbar-width: thin;
        /* تقليل عرض شريط التمرير */
    }
    .settings-menu {
        display: flex;
        gap: 4px;
        color: var(--text-secondary-color);
        overflow-x: auto;
        /* يجعل الشريط قابلاً للتمرير عند الحاجة */
        white-space: nowrap;
        /* يمنع العناصر من النزول لسطر جديد */
        scrollbar-width: thin;
        /* تقليل عرض شريط التمرير */

    }
     .settings-menu button {
        background-color: var(--box-background-color);
        border: none;
        padding: 10px 15px;
        font-size: 16px;
        cursor: pointer;
        transition: color 0.3s ease-in-out;
        color: var(--text-secondary-color) !important;

    }
     .settings-menu button:hover {
        background: #ff9800;
    }


    .upload-button:hover {
        background: #ffab40;
    }

    .ltr .box-header .btn{
        right: 18px;
        position: absolute;
        color: var(--text-secondary-color) !important;


    }


    .rtl .box-header .btn{
        left: 18px;
        position: absolute;
        color: var(--text-secondary-color) !important;

    }
    .grid-table{
        background: var(--secondary-color) !important;

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

    <div class="settings-sidebar">
      
    </div>
    <div class="all-page" style="width: 100%;">
       

        <div class="settings-content box grid-box"">

            <div id="PaymentGateways" class="settings-section active">
                <!-- <h3>{{ __('Payment Gateways') }}</h3> -->

                <div class="table-responsive">
                    <div class="box-header with-border" style="display: flex;">
                    {{-- <div class="settings-menu">
                        <button class="" onclick="showSection('PaymentGateways')">{{ __('Payment Gateways') }}</button>
                    </div> --}}
                  @if (Admin::user()->can('*') || Admin::user()->can('create-Payment-methods-for-shipping-agencies'))

                        <a  href="{{ route('admin.create-payment-gateway') }}" class="btn btn-success">
                            {{ __('Add') }}
                        </a>
                    @endif 

                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; padding: 15px;">
                        @foreach($payment_gateways as $gateway)
                        <div style="border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s; background: #fafafa;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 24px rgba(0,0,0,0.12)'" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.08)'">
                            {{-- Image --}}
                            <div style="width: 100%; height: 140px; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden; border-bottom: 1px solid #eee;" onclick="openFullScreen(this.querySelector('img'))">
                                <img src="{{ getImagePath($gateway->photo) }}" alt="{{ $gateway->title }}" style="max-width: 90%; max-height: 120px; object-fit: contain; display: block; margin: 0; width: auto; height: auto;">
                            </div>
                            {{-- Info --}}
                            <div style="padding: 14px 18px; background: #fafafa;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                    <span style="font-size: 11px; color: #999;">#{{ $gateway->id }}</span>
                                    <span style="font-size: 16px; font-weight: 700; color: #333;">{{ $gateway->title }}</span>
                                </div>
                                <div style="display: flex; gap: 10px; justify-content: center;">
                                    @if (Admin::user()->can('*') || Admin::user()->can('edit-Payment-methods-for-shipping-agencies'))
                                        <a href="{{ route('admin.edit-payment-gateway', $gateway->id) }}" style="padding: 7px 20px; border-radius: 6px; background: #3b82f6; color: #fff; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; transition: background 0.2s; width: auto; border: none;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                                            <i class="fa fa-pencil"></i> {{ __('Edit') }}
                                        </a>
                                    @endif
                                    @if (Admin::user()->can('*') || Admin::user()->can('delete-Payment-methods-for-shipping-agencies'))
                                        <a href="{{ route('admin.delete-payment-gateway', $gateway->id) }}" onclick="return confirm('Are you sure?')" style="padding: 7px 20px; border-radius: 6px; background: #ef4444; color: #fff; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; transition: background 0.2s; width: auto; border: none;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                            <i class="fa fa-trash"></i> {{ __('Delete') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>


            </div>

        </div>
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
            // function showSection(sectionId) {
            //     //     document.querySelectorAll('.settings-section').forEach(section => {
            //     //         section.classList.remove('active');
            //     //     });

            //     //     document.getElementById(sectionId).classList.add('active');
            //     // }

            //     document.querySelectorAll('.settings-menu button').forEach(button => {
            //         button.style.backgroundColor = ''; // إرجاع اللون الافتراضي
            //         button.style.color = ''; // إرجاع لون النص الافتراضي
            //     });

            //     // إخفاء جميع الأقسام
            //     document.querySelectorAll('.settings-section').forEach(section => {
            //         section.classList.remove('active');
            //     });

            //     // إظهار القسم المطلوب
            //     document.getElementById(sectionId).classList.add('active');

            //     // تحديد الزر المضغوط عليه
            //     const activeButton = document.querySelector(`.settings-menu button[onclick="showSection('${sectionId}')"]`);

            //     // تعيين لون الزر من متغير CSS
            //     activeButton.style.backgroundColor = 'var(--primary-color)';
            //     activeButton.style.color = 'var(--text-secondary-color)';


            // }

            document.addEventListener("DOMContentLoaded", function() {
                    // Function to get query parameter by name
                    function getQueryParam(name) {
                        const urlParams = new URLSearchParams(window.location.search);
                        return urlParams.get(name);
                    }

                    // Get the 'firsttab' parameter from URL or default to 'chargesSettings'
                    const activeTab = getQueryParam("firsttab") || "PaymentGateways";

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
