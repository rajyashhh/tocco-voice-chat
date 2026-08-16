

<div >
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-left: 10px;
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
            background-color: #ccc;
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
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #2196F3;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .switch-label {
            margin-left: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }

        .switch-item {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .switch-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            padding-top: 16px;
        }

        /* Responsive Layout */
        @media (max-width: 768px) {
            .switch-container {
                justify-content: space-around;
            }
        }

        @media (max-width: 576px) {
            .switch-item {
                flex-basis: 100%; /* Make items take 45% width on small screens */
                margin-bottom: 10px;
            }
        }
    </style>

    <div class="box-body no-padding">
        <div class="switch-container">

            <div class="switch-item">
                <label for="stopCharge" class="switch-label">{{ __('dashboard.frazeCharge') }}</label>
                <label class="switch">
                    <input type="checkbox" id="stopCharge" {{ $stop_charge == 1 ? 'checked' : '' }}>
                    <span class="slider round"></span>
                </label>
            </div>

            <div class="switch-item">
                <label for="stopInviteCode" class="switch-label">{{ __("dashboard.closeCose") }}</label>
                <label class="switch">
                    <input type="checkbox" id="stopInviteCode" {{ $stop_invite_code == 1 ? 'checked' : '' }}>
                    <span class="slider round"></span>
                </label>
            </div>

            <div class="switch-item">
                <label for="stopTransferSalary" class="switch-label">{{ __("dashboard.transSalary") }}</label>
                <label class="switch">
                    <input type="checkbox" id="stopTransferSalary" {{ $transfer_salary == 1 ? 'checked' : '' }}>
                    <span class="slider round"></span>
                </label>
            </div>

             <div class="switch-item">
                <label for="stopGiftCheckbox" class="switch-label">{{__('Stop sending gifts to everyone')}}   </label>
                <label class="switch">
                    <input type="checkbox" id="stopGiftCheckbox" {{ $make_gift_top == 1 ? 'checked' : '' }}>
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
    </div>
        <div class="nav-tabs-custom">
            <div class="tab-content">
            <div class="tab-pane active" id="tab_2">
                    <div class="box box-warning">
                        <div class="box-header with-border">
                            <h3 class="box-title">{{ __('register accounts') }}</h3>
                        </div>
                        <form action="{{ route('admin.app.settings.update') }}" method="POST" class="form-horizontal">
                            @csrf
                            <div class="box-body">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{ __('register account') }}</label>
                                    <div class="col-sm-8">
                                        <input type="number" name="register_account" class="form-control"
                                                value="{{ $register_account ?? 0 }}"
                                            required>
                                        <span
                                            class="help-block">{{ __('Number of accounts that can be registered with same device') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="box-footer">
                                <button type="submit" class="btn btn-warning pull-right">{{ __('Save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    <script>
        $(document).ready(function() {
            // Setup CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });


            $('#stopGiftCheckbox').on('change', function() {
                var isChecked = $(this).is(':checked');
                $.ajax({
                    url: '/admin/close-open-gift',
                    method: 'POST',
                    data: { make_rooms_top: isChecked },
                    success: function(response) { console.log(response); },
                    error: function(error) { console.error(error); }
                });
            });

            // Handle change event for stopCharge
            $('#stopCharge').on('change', function() {
                var isChecked = $(this).is(':checked');
                $.ajax({
                    url: '/admin/send-request-stop-charge',
                    method: 'POST',
                    data: { stop_charge: isChecked },
                    success: function(response) { console.log(response); },
                    error: function(error) { console.error(error); }
                });
            });

            // Handle change event for stopInviteCode
            $('#stopInviteCode').on('change', function() {
                var isChecked = $(this).is(':checked');
                $.ajax({
                    url: '/admin/send-request-invite-code',
                    method: 'POST',
                    data: { stop_invite_code: isChecked },
                    success: function(response) { console.log(response); },
                    error: function(error) { console.error(error); }
                });
            });

            // Handle change event for stopTransferSalary
            $('#stopTransferSalary').on('change', function() {
                var isChecked = $(this).is(':checked');
                $.ajax({
                    url: '/admin/send-request-transfer-salary',
                    method: 'POST',
                    data: { transfer_salary: isChecked },
                    success: function(response) { console.log(response); },
                    error: function(error) { console.error(error); }
                });
            });
        });
    </script>


</div>
