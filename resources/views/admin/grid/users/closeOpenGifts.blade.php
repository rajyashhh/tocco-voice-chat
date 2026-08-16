

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
                <label for="stopChargeCheckbox" class="switch-label">ايقاف ارسال الهدايا للجميع</label>
                <label class="switch">
                    <input type="checkbox" id="stopChargeCheckbox" {{ $make_rooms_top == 1 ? 'checked' : '' }}>
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Handle change event for stopChargeCheckbox
            $('#stopChargeCheckbox').on('change', function() {
                var isChecked = $(this).is(':checked');
                $.ajax({
                    url: '/admin/close-open-gift',
                    method: 'POST',
                    data: { make_rooms_top: isChecked },
                    success: function(response) { console.log(response); },
                    error: function(error) { console.error(error); }
                });
            });
        });
    </script>


</div>
