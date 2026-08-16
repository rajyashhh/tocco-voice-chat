@php
    $selectedTimeZone = App\Models\Setting::where('key', 'timezone')->first();
    $settings = App\Models\Setting::pluck('value', 'key')->toArray();
    $user_coinsConfig = App\Models\Config::where ('name', '=','one_usd_value_in_coins')->first();
    $shipping_coins = cache()->get('shipping_coins', $settings['shipping_coins'] ?? '');
    $user_coins = cache()->get('user_coins', $user_coinsConfig['value'] ?? '');
    $superadmin_coins = cache()->get('super_admin_coins', $settings['super_admin_coins'] ?? '');

@endphp

<!-- Add SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    /* Add this CSS to your stylesheet */
    .radio-options-container {
        display: flex;
        gap: 20px;
        /* Space between options */
        align-items: center;
        margin: 15px 0;
    }

    .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
        /* Space between radio and label */
    }

    .radio-input {
        margin: 0;
        /* Remove default margins */
    }

    .radio-label {
        margin: 0;
        /* Remove default margins */
        cursor: pointer;
        user-select: none;
    }

    /* Custom radio button styling */
    .radio-input {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border: 2px solid #ff9800;
        border-radius: 50%;
        outline: none;
        cursor: pointer;
        position: relative;
    }

    .radio-input:checked {
        background-color: #ff9800;
    }

    .radio-input:checked::after {
        content: '';
        position: absolute;
        width: 8px;
        height: 8px;
        background: white;
        border-radius: 50%;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: var(--secondary-color);
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

    /* .settings-menu button {
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
    } */

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

    .settings-section.active {
        display: block;
        /* Show active section */
    }

    /* تنسيق النماذج */
    form {
        background: var(--box-background-color);
        padding: 20px;
        border-radius: 5px;
        width: 100%;
        position: relative;
        margin: auto;

    }

    @media (max-width: 768px) {
        form {
            background: #222;
            padding: 20px;
            border-radius: 5px;
            width: 100%;

            position: relative;
            margin: auto;

        }
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

    button {
        width: 198px;
    }

    /* تصميم النافذة */
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-dialog {
        position: relative;
        width: auto;
        margin: 10% auto;
        max-width: 500px;
    }

    .modal-content {
        position: relative;
        background-color: var(--box-background-color);
        border: 1px solid #888;
        border-radius: 5px;
        box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
        padding: 20px;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .modal-title {
        color: var(--text-secondary-color);
        margin: 0;
    }

    .close {
        color: var(--text-primary-color);
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .modal-body {
        color: var(--text-primary-color);
        margin-bottom: 20px;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }

    .btn-primary {
        background-color: #ff9800;
        color: black;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }

    .btn:hover {
        opacity: 0.9;
    }

    img {
        width: 201px;
        display: block;
        height: 99px;
        margin-bottom: 20px;
    }

    /* button {
        width: 200px;

    } */

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


    .card {
        border-radius: 10px;
        /* Rounded corners */
        border: 1px solid #ddd;
        /* Light border */
        background: "{{ $settings['primary_color'] ?? '#000000' }}";
        /* White background */
        box-shadow: 2px 4px 6px rgba(0, 0, 0, 0.1);
        /* Subtle shadow */
        padding: 20px;
        /* Inner spacing */
        transition: transform 0.2s ease-in-out;
        margin-top: 40px;
        /* Smooth effect */
        position: relative;

    }
    .btn-save {
        position: absolute;
        bottom: 15px;
        left: 15px;
    }

    .card:hover {
        transform: scale(1.02);
        /* Slight zoom effect on hover */
    }

    .card-header {
        background: {{ $settings['primary_color'] ?? '#000000' }};
        /* Light gray background */
        padding: 12px 15px;
        border-bottom: 1px solid #ddd;
        /* Separator */
        border-radius: 8px 8px 0 0;
        /* Rounded top corners */
        text-align: center;
        font-weight: bold;
        font-size: 1.2rem;
        color: #333;

        display: flex;
    justify-content: space-between;
    align-items: center;
    }

    .card-header h4 {
    margin: 0;
}
.d-flex.align-items-center {
    gap: 10px; /* Add spacing between radio and switch */
}


    .custom-radio {
        display: none;
    }

    /* Switch container */
    .switch {
        display: inline-block;
        width: 50px;
        height: 25px;
        background-color: #ccc;
        border-radius: 25px;
        position: relative;
        cursor: pointer;
        transition: background 0.3s;
    }

    /* Circle (toggle) */
    .switch::after {
        content: "";
        width: 20px;
        height: 20px;
        background: white;
        border-radius: 50%;
        position: absolute;
        top: 50%;
        left: 5px;
        transform: translateY(-50%);
        transition: left 0.3s;
    }

    /* Active state */
    .switch.active {
        background: #4caf50;
    }

    .switch.active::after {
        left: 25px;
    }

    /* Custom styles for the confirmation dialog */
    .swal2-popup {
        background: var(--box-background-color) !important;
        color: var(--text-primary-color) !important;
    }

    .swal2-title {
        color: var(--text-primary-color) !important;
    }

    .swal2-content {
        color: var(--text-primary-color) !important;
    }

    .swal2-confirm {
        background-color: #ff9800 !important;
        border: none !important;
    }

    .swal2-cancel {
        background-color: #dc3545 !important;
        border: none !important;
    }
    .soon{

    }
    .rtl .soon::before{
        content: '{{ __("soon") }}';
    top: 37px;
    position: absolute;
    left: 16px;
    background: green;
    width: 19px;
    text-align: center;
    -webkit-writing-mode: vertical-rl;
    height: 31px;
    }
    .ltr .soon::before{
        content: '{{ __("soon") }}';
        top: 36px;
        position: absolute;
        right: 15px;
        background: green;
        width: 19px;
        text-align: center;
        -webkit-writing-mode: vertical-rl;
        height: 31px;

    }
</style>

<!-- Add SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Add Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

</head>
<body>

    <div class="settings-sidebar">
        <div class="settings-menu">
            <button onclick="showSection('chargesSettings')">{{ __('Charges settings') }}</button>
            <button onclick="showSection('withSettings')">{{ __('Withdrawal settings') }}</button>
            <button onclick="showSection('coinExchange')">{{ __('coin exchange') }}</button>
        </div>
    </div>
    <div class="all-page" style="width: 100%;">


        <div class="settings-content">
            <div id="chargesSettings" class="settings-section active">

                <h3> {{ __('Charges settings') }}</h3>

                <form action="{{ route('admin.app.settings.update') }}" method="POST" enctype="multipart/form-data" id="targetSettingsForm">
                    @csrf
                    <div class="form row">
                        <div class="col-md-6">
                            <div class="form-group ">
                                <label>{{ __('Zones') }}</label>
                                <input type="number" name="zones_coins" id="zones_coins"
                                    value="{{ $settings['zones_coins'] ?? '' }}" class="form-control" >
                                    <!-- value="{{ $settings['zones_coins'] ?? '' }}" class="form-control"> -->
                                <small id="zones_coins_hint" class="form-text text-muted mt-1"
                                    data-template="{{ __('1 :dollar = :value :coins', ['dollar' => __('Dollar'), 'coins' => __('Coins')]) }}">
                                    {{ __('1 :dollar = :value  :coins', ['dollar' => __('Dollar'), 'coins' => __('Coins')]) }}
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group ">
                                <label>{{ __('Super Admin') }}</label>
                                <input type="number" name="super_admin_coins" id="super_admin_coins"
                                    value="{{ $superadmin_coins }}" class="form-control">
                                <small id="super_admin_coins_hint" class="form-text text-muted mt-1"
                                    data-template="{{ __('1 :dollar = :value :coins', ['dollar' => __('Dollar'), 'coins' => __('Coins')]) }}">
                                    {{ __('1 :dollar = :value :coins', ['dollar' => __('Dollar'), 'coins' => __('Coins')]) }}
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Agency Charge') }}</label>
                                <input type="number" name="shipping_coins" id="shipping_coins"
                                    value="{{ $shipping_coins }}" class="form-control">
                                <small id="shipping_coins_hint" class="form-text text-muted mt-1"
                                    data-template="{{ __('1 :dollar = :value :coins', ['dollar' => __('Dollar'), 'coins' => __('Coins')]) }}">
                                    {{ __('1 :dollar = :value :coins', ['dollar' => __('Dollar'), 'coins' => __('Coins')]) }}
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('The User') }}</label>
                                <input type="number" name="user_coins" id="user_coins"
                                    value="{{ $user_coins }}" class="form-control">
                                <small id="user_coins_hint" class="form-text text-muted mt-1"
                                    data-template="{{ __('1 :dollar = :value :coins', ['dollar' => __('Dollar'), 'coins' => __('Coins')]) }}">
                                    {{ __('1 :dollar = :value :coins', ['dollar' => __('Dollar'), 'coins' => __('Coins')]) }}
                                </small>
                            </div>
                        </div>

                        <button type="button" onclick="showConfirmationModal()">{{ __('save') }}</button>
                    </div>
                </form>
            </div>


            <div id="withSettings" class="settings-section ">

                <h3> {{ __('Withdrawal settings') }}</h3>

                <form action="{{ route('admin.app.settings.update') }}" method="POST" enctype="multipart/form-data" id="targetSettingsForm">
                    @csrf
                    <div class="form row">


                        <div class="col-md-6">
                            <div class="form-group ">
                                <label>{{ __('Limit per single withdrawal transaction') }}</label>
                                <input type="number" name="limit_daily_withdrawal" id="limit_daily_withdrawal"
                                    value="{{ $settings['limit_daily_withdrawal'] ?? 1 }}" class="form-control" >
        
                            </div>
                        </div>

                    </div>
                    <br>
                    <div class="action-buttons"style="border-top: 1px solid rgba(0,0,0,0.1); padding-top: 20px; display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary btn-save">
                            <i class="fas fa-save"></i> {{ __('Save') }}
                        </button>
                        
                    </div>
                </form>
            </div>

            <div id="coinExchange" class="settings-section ">

                <h3> {{ __('coin exchange') }}</h3>

                <form action="{{ route('admin.exchange-coins') }}" method="POST">
                    @csrf
                    <div class="form row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Exchange Rate') }}</label>
                                <input type="number" name="exchange_coin_percentage" id="coin_exp" step="any" required
                                    value="{{ $settings['exchange_coin_percentage'] ?? 1 }}" class="form-control">
                                <small id="coin_exp_hint" class="form-text text-muted mt-1"
                                    data-template="{{ __('1 :diamond = :value :coins', ['diamond' => __('Diamond'), 'coins' => __('Coins')]) }}">
                                    {{ __('1 :diamond = :value :coins', ['diamond' => __('Diamond'), 'coins' => __('Coins'), 'value' => $settings['exchange_coin_percentage'] ?? 1]) }}
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Calculator') }}</label>
                                <div class="input-with-result">
                                    <input type="number" class="user_coin_input form-control" step="any"
                                        placeholder="{{ __('Enter diamond amount') }}">
                                    <input type="hidden" class="exchange_rate"
                                        value="{{ $settings['exchange_coin_percentage'] ?? 1 }}">
                                    <span class="exp_result result-badge"></span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-save">
                            <i class="fas fa-save"></i> {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bootstrap Modal -->
            <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmationModalLabel">{{ __('Confirm Update') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            {{ __('Are you sure you want to update the target settings? This action cannot be undone.') }}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="button" class="btn btn-primary" onclick="submitForm()">{{ __('Yes, update') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function showConfirmationModal() {
                    $('#confirmationModal').modal('show');
                }

                function submitForm() {
                    $('#confirmationModal').modal('hide');
                    document.getElementById('targetSettingsForm').submit();
                }
            </script>

            <script>
                $(document).ready(function () {
                    function updateLibrary(selectedLibrary) {
                        $.ajax({
                            url: "{{ route('admin.update-agora-zego') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                library: selectedLibrary
                            },
                            success: function (response) {
                                console.log("Library updated via AJAX:", response);
                                toastr.success('Library preference saved!');
                            },
                            error: function (xhr) {
                                console.error("AJAX Error:", xhr.responseText);
                                toastr.error('Failed to update library');
                            }
                        });
                    }

                    function updateSwitches() {
                        $(".custom-radio").each(function () {
                            if ($(this).prop("checked")) {
                                $(this).next(".switch").addClass("active");
                            } else {
                                $(this).next(".switch").removeClass("active");
                            }
                        });
                    }

                    // عند تغيير الراديو، نحدث الواجهة
                    $(document).on("change", ".libraryRealTime", function () {
                        let selectedLibrary = $(this).val();
                        console.log("Selected library:", selectedLibrary);
                        updateLibrary(selectedLibrary);
                        updateSwitches();
                    });

                    $(".switch").click(function () {
                        let radio = $(this).prev(".custom-radio");

                        if (!radio.prop("checked")) {
                            $(".custom-radio").prop("checked", false);
                            $(".switch").removeClass("active");

                            radio.prop("checked", true).trigger("change");
                        }
                    });

                    updateSwitches();
                });

            </script>

            <!-- كود JavaScript -->
            <script>

                function previewImage(event) {
                    let file = event.target.files[0];
                    if (file) {
                        let reader = new FileReader();
                        reader.onload = function(e) {
                            let preview = document.getElementById('imagePreview');
                            preview.src = e.target.result; // Update preview with new image
                            preview.style.display = 'block'; // Show image
                        };
                        reader.readAsDataURL(file);
                    }
                }

                function previewFavIcon(event) {
                    let file = event.target.files[0];
                    if (file) {
                        let reader = new FileReader();
                        reader.onload = function(e) {
                            let preview = document.getElementById('favIconPreview');
                            preview.src = e.target.result; // Update preview with new image
                            preview.style.display = 'block'; // Show image
                        };
                        reader.readAsDataURL(file);
                    }
                }

                document.addEventListener("DOMContentLoaded", function() {
                    // Function to get query parameter by name
                    function getQueryParam(name) {
                        const urlParams = new URLSearchParams(window.location.search);
                        return urlParams.get(name);
                    }

                    // Get the 'firsttab' parameter from URL or default to 'chargesSettings'
                    const activeTab = getQueryParam("firsttab") || "chargesSettings";

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


                function toggleBackgroundInput() {
                    const type = document.getElementById("background_type").value;
                    document.getElementById("background_color_group").style.display = type === "color" ? "block" : "none";
                    document.getElementById("background_image_group").style.display = type === "image" ? "block" : "none";
                }



    document.addEventListener('DOMContentLoaded', function () {
        function updateHint(inputId) {
            const input = document.getElementById(inputId);
            const hint = document.getElementById(inputId + '_hint');
            const template = hint.getAttribute('data-template');

            function update() {
                // نحصل على القيمة المدخلة من المستخدم
                const value = parseFloat(input.value);
                // إذا كانت القيمة فارغة أو NaN نعرض 0
                const finalValue = isNaN(value) || value === '' ? 0 : value;

                // تحديث النص داخل الـ small
                hint.innerText = template.replace(':value', finalValue);
            }

            input.addEventListener('input', update);
            update(); // تشغيل أول مرة
        }

        // تحديث الحقول عند تحميل الصفحة
        updateHint('super_admin_coins');
        updateHint('zones_coins');
        updateHint('user_coins');
        updateHint('shipping_coins');
        updateHint('coin_exp');
    });

    // Coin exchange live calculator (diamond amount × rate)
    document.addEventListener('DOMContentLoaded', function () {
        const rateInput = document.getElementById('coin_exp');
        const diamondInput = document.querySelector('.user_coin_input');
        const rateHidden = document.querySelector('.exchange_rate');
        const resultSpan = document.querySelector('.exp_result');
        if (!rateInput || !diamondInput || !resultSpan) return;

        function calculateExchange() {
            const rate = parseFloat(rateInput.value) || 0;
            const diamonds = parseFloat(diamondInput.value) || 0;
            rateHidden.value = rate;
            if (rate > 0 && diamonds > 0) {
                resultSpan.textContent = (diamonds * rate).toFixed(2) + ' {{ __('Coins') }}';
                resultSpan.style.display = 'inline-block';
            } else {
                resultSpan.textContent = '';
                resultSpan.style.display = 'none';
            }
        }

        rateInput.addEventListener('input', calculateExchange);
        diamondInput.addEventListener('input', calculateExchange);
        calculateExchange();
    });
            </script>



        </div>
</body>
