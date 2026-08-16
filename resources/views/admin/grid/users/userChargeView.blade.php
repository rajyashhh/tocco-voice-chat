<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('admin.Actions') }}</h3>

        <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="box-body no-padding">
        <style>
            input[type=text],
            select {
                padding: 12px 20px;
                margin: 8px 0;
                display: inline-block;
                border: 1px solid #ccc;
                border-radius: 4px;
                box-sizing: border-box;
            }

            input[type=submit] {
                background-color: #4CAF50;
                color: white;
                padding: 14px 20px;
                margin: 8px 0;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                line-height: 8px;
            }

            input[type=submit]:hover {
                background-color: #45a049;
            }
        </style>
        <ul class="nav nav-pills nav-stacked">
            <li>
                <div style="padding-left: 10px;">
                    <form>
                        <label for="fname">User Uuid</label>
                        <input type="text" id="fname" name="user" value="{{ request('user') ?? '' }}"
                            placeholder="User Uuid..">
                        <input type="submit" value="Submit">
                    </form>
                </div>
            </li>
        </ul>
    </div>
    <!-- /.box-body -->

    <script>
        $(document).ready(function() {
            // Get the checkbox element
            var stopChargeCheckbox = $('#stopChargeCheckbox');

            // Attach a change event listener to the checkbox
            stopChargeCheckbox.on('change', function() {
                // Get the current state of the checkbox
                var isChecked = stopChargeCheckbox.is(':checked');
                // Make an API call here, for example, using jQuery.ajax
                $.ajax({
                    url: '/admin/send-request-stop-charge',
                    method: 'POST',
                    data: {
                        stop_charge: isChecked
                    },
                    success: function(response) {
                        // Handle the API response if needed
                        console.log(response);
                    },
                    error: function(error) {
                        // Handle errors if the API call fails
                        console.error(error);
                    }
                });
            });
        });
    </script>

</div>
