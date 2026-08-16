<div class="box box-solid">
   
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



<div class="container mt-4">
    @php
        $name = ['app_wallet', 'owner_wallet', 'game_wallet', 'lucky_box'];
        $icons = [
            'app_wallet' => 'fas fa-wallet', 
            'owner_wallet' => 'fas fa-user-shield', 
            'game_wallet' => 'fas fa-gamepad', 
            'lucky_box' => 'fas fa-gift'
        ];
        $coreWallets = \App\Models\CoreWallets::whereIn('name', $name)->get();
    @endphp

    <div class="row justify-content-center">
        @foreach($coreWallets as $wallet)
            <div class="col-md-6 col-lg-6 mb-4 wallet_posation">
                <div class="card shadow-lg position-relative border-0" style="border-radius: 15px; overflow: hidden;">
                    <!-- Edit Button -->
                    <a href="{{ admin_url('core-wallets/' . $wallet->id . '/edit') }}" 
                       class="btn btn-sm  position-absolute top-0 end-0 m-2">
                        <i class="fas fa-edit"></i>
                    </a>

                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <!-- Left: Icon -->
                        <i class="{{ $icons[$wallet->name] ?? 'fas fa-wallet' }} text-primary fs-2"></i>
                        
                        <!-- Center: Title & Coins -->
                        <div class="text-center">
                            <h5 class="fw-bold mb-2">{{ ucfirst($wallet->name) }}</h5>
                            <p class="text-dark fs-5 fw-semibold">Coins: {{ number_format($wallet->coins) }}</p>
                        </div>
                        
                        <!-- Right: Updated At -->
                        <small class="text-muted position-absolute bottom-0 end-0 p-2">
                            {{ $wallet->update_for_human }}
                        </small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>



    <!-- /.box-body -->


    <script>
       
    </script>

</div>
