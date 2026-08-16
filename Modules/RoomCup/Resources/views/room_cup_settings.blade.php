
<div class="d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-lg" style="width:95%; border-radius:15px;">
        <div class="card-header text-center bg-primary text-white" style="border-top-left-radius:15px; border-top-right-radius:15px;">
            <!-- <h4 class="mb-0">{{ __('Room Cup Settings') }}</h4> -->
        </div>
        <div class="card-body p-4" style="    height: 100%;">

            <form method="POST" action="{{ admin_url('room-cup-settings/save') }}" style="height: 100%;">
                @csrf

                <!-- Toggle Switch Group -->
                <div class="mb-4 d-flex justify-content-between align-items-center flex-row-reverse inp-div">
                    <span id="switch-text" class="fw-bold me-3">{{ $settings['enabled'] ? __('ON') : __('OFF') }}</span>
                    <div class="d-flex align-items-center">
                        <label class="fw-bold mb-0 me-3" for="enabled">{{ __('Enable Room Cup Feature') }}</label>
                        <label class="switch mb-0">
                            <input type="checkbox" name="enabled" id="enabled" {{ $settings['enabled'] ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

                <!-- Type of Schedule -->
                <div class="mb-4 inp-div">
                    <label for="type" class="form-label fw-bold d-block text-end">{{ __('Schedule Type') }}</label>
                    <select name="type" id="type" class="form-control text-end">
                        <option value="daily" {{ $settings['type']=='daily' ? 'selected' : '' }}>{{ __('Daily') }}</option>
                        <!-- <option value="every_x_days" {{ $settings['type']=='every_x_days' ? 'selected' : '' }}>{{ __('Every X Days') }}</option> -->
                        <option value="weekly" {{ $settings['type']=='weekly' ? 'selected' : '' }}>{{ __('Weekly') }}</option>
                        <option value="monthly" {{ $settings['type']=='monthly' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                    </select>
                </div>

                <!-- Time -->
                <!-- <div class="mb-4  inp-div">
                    <label for="time" class="form-label fw-bold d-block text-end">{{ __('Execution Time') }}</label>
                    <div class="input-group justify-content-end ">
                        <input type="time" class="form-control text-end" name="time" id="time" value="{{ $settings['time'] ?? '23:59' }}">
                        <span class="input-group-text bg-light"><i class="bi bi-clock-fill"></i></span>
                    </div>
                </div>

                <div class="mb-4 inp-div" >
                    <label for="day" class="form-label fw-bold d-block text-end">{{ __('Day (0=Sunday,1=Monday,...)') }}</label>
                    <input type="number" class="form-control text-end" name="day" id="day" value="{{ $settings['day'] ?? 0 }}" min="0" max="31">
                </div> -->


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

