<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('admin.roles') }}</h3>

        <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        /* Hide default HTML checkbox */
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        /* The slider */
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
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
            -webkit-transition: .4s;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: #2196F3;
        }

        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(26px);
            -ms-transform: translateX(26px);
            transform: translateX(26px);
        }

        /* Rounded sliders */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }
    </style>

    <div class="row ">
        <div class="col-md-12">
            <div class="row ">
                <div class="col-md-12">


                    <div class="box-body no-padding" style="margin: 10px">
                        @php
                            $role = \Modules\Events\Entities\GeneralRole::where("type","event_period")->first();
                        @endphp
                        @if($role != null)
                            <a href="{{url('admin/general-rols/'.@$role->id.'/edit')}}">
                                {{ auth()->user()->lan == "en" ? @$role->desc_en : @$role->desc_ar }}
                            </a>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- /.box-body -->


    <script>
        $(document).ready(function () {
            // Get the checkbox element
            var stopChargeCheckbox = $('#stopChargeCheckbox');

            // Attach a change event listener to the checkbox
            stopChargeCheckbox.on('change', function () {
                // Get the current state of the checkbox
                var isChecked = stopChargeCheckbox.is(':checked');
                // Make an API call here, for example, using jQuery.ajax
                $.ajax({
                    url: '/admin/send-request-stop-charge',
                    method: 'POST',
                    data: {
                        stop_charge: isChecked
                    },
                    success: function (response) {
                        // Handle the API response if needed
                        console.log(response);
                    },
                    error: function (error) {
                        // Handle errors if the API call fails
                        console.error(error);
                    }
                });
            });


            var chargeYourselfCheckbox = $('#chargeYourselfCheckbox');

            // Attach a change event listener to the checkbox
            chargeYourselfCheckbox.on('change', function () {
                // Get the current state of the checkbox
                var isChecked = chargeYourselfCheckbox.is(':checked');
                // Make an API call here, for example, using jQuery.ajax
                $.ajax({
                    url: '/send-request-user-charge-themselves',
                    method: 'POST',
                    data: {
                        charge_yourself: isChecked
                    },
                    success: function (response) {
                        // Handle the API response if needed
                        console.log(response);
                    },
                    error: function (error) {
                        // Handle errors if the API call fails
                        console.error(error);
                    }
                });
            });
        });
    </script>

</div>
