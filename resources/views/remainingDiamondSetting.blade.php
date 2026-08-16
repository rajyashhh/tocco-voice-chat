<div class="d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-lg" style="width:95%; border-radius:15px;">
        <div class="card-header text-center bg-primary text-white" style="border-top-left-radius:15px; border-top-right-radius:15px;">
            <!-- <h4 class="mb-0">{{ __('remaining diamonds settings') }}</h4> -->
        </div>
        <div class="card-body p-4" style="    height: 100%;">

            <form method="POST" action="{{ admin_url('remaining-diamond-settings/save') }}" style="height: 100%;">
                @csrf


                <!-- Type of Schedule -->
                <div class="mb-4 inp-div">
                    <label for="type" class="form-label fw-bold d-block text-end">{{ __('exchange to') }}</label>
                    <select name="remaining_diamonds" id="remaining_diamonds" class="form-control text-end">
                        <option value="nothing" {{ $settings['remaining_diamonds']=='nothing' ? 'selected' : '' }}>{{ __('Do not make any thing') }}</option>
                        <option value="coins" {{ $settings['remaining_diamonds']=='coins' ? 'selected' : '' }}>{{ __('Coins') }}</option>
                        <option value="diamonds" {{ $settings['remaining_diamonds']=='diamonds' ? 'selected' : '' }}>{{ __('diamonds') }}</option>
                    </select>
                </div>
                 <span class="help-block">{{ __('Remaining diamonds from last month that the host user can convert to coins, keep as diamonds, or leave unchanged.') }}</span>


                <!-- Save Button -->
                <div class="d-flex justify-content-end div-btn-form">
                    <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm btn-form">{{ __('Save') }}</button>
                </div>

            </form>
        </div>
    </div>
</div>


<style>


    .inp-div{
        width: 77%;
        margin: 23px auto;
    }
    .inp{
        width: 100%;
        top: 13px;
        position: relative;
    }

    form{
        height: 243px;
        border-radius: 30px;
        height: 100%;
        padding: 7% 9%;

    }
    .btn-form{
        width: 22% !important;
        position: relative;
    }
    .div-btn-form {
    top: 10px;
    position: relative;
    width: 75%;
    margin: auto;
    }
.switch {
  position: relative;
  display: inline-block;
  width: 70px;
  height: 38px;
}

.switch input { display: none; }

.slider {
  position: absolute;
  cursor: pointer;
  top:0;
  left:0;
  right:0;
  bottom:0;
  background-color: #ccc;
  transition: 0.4s;
  border-radius: 38px;
}

.slider:before {
  position: absolute;
  content: "";
  height:30px;
  width:30px;
  left:4px;
  bottom:4px;
  background-color:white;
  transition:0.4s;
  border-radius:50%;
}

input:checked + .slider {
  background-color: #28a745;
}

input:checked + .slider:before {
  transform: translateX(32px);
}

.slider.round { border-radius: 38px; }
.card {
    margin: auto;
    width: 445px;
    border-radius: 15px;
    height: 495px;
}
</style>

<script>
const toggle = document.getElementById('enabled');
const text = document.getElementById('switch-text');

function updateSwitch() {
    text.innerText = toggle.checked ? 'ON' : 'OFF';
    text.style.color = toggle.checked ? 'green' : 'red';
}

toggle.addEventListener('change', updateSwitch);
updateSwitch();
</script>

