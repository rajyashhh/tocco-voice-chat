<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('search') }}</h3>

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
                        <form action="">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="">{{__("from date")}}</label><br>
                                    <input type="date" name="from_date" dir="ltr" value="{{request("from_date")}}">
                                </div>

                                <div class="col-md-3">
                                    <label for="">{{__("to date")}}</label><br>
                                    <input type="date" name="to_date" dir="ltr" value="{{request("to_date")}}">
                                </div>
                                <label for=""></label><br>
                                <button class="btn btn-primary">{{__('submit')}}</button>
                            </div>
                        </form>

                    </div>
                    <div class="box-body no-padding" style="margin: 10px">

                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
