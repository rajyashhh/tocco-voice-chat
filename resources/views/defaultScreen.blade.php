
<div class="d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-lg" style="width:95%; border-radius:15px;">
        <div class="card-header text-center bg-primary text-white" style="border-top-left-radius:15px; border-top-right-radius:15px;">
        </div>
        <div class="card-body p-4" style="height: 100%;">

            <form method="POST" action="{{ admin_url('default-app-screen') }}" style="height: 100%;">
                @csrf

                <div class="mb-4 inp-div">
                    <label for="screen" class="form-label fw-bold d-block text-end">{{ __('Default App Screen') }}</label>
                    <select name="screen" id="screen" class="form-control text-end">
                        @if(!array_key_exists($screen, $screenOptions))
                            <option value="" selected>-- {{ __('Select Default App Screen') }} --</option>
                        @endif

                        @foreach($screenOptions as $value => $label)
                            <option value="{{ $value }}" {{ $screen == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4 inp-div" id="home-section-wrap">
                    <label for="home_section" class="form-label fw-bold d-block text-end">{{ __('Default Home Section') }}</label>
                    <select name="home_section" id="home_section" class="form-control text-end">
                        @foreach($sectionOptions as $value => $label)
                            <option value="{{ $value }}" {{ $homeSection == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

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

    form{
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
    .card {
        margin: auto;
        width: 445px;
        border-radius: 15px;
        min-height: 495px;
    }
</style>

<script>
(function () {
    var screenSelect = document.getElementById('screen');
    var sectionWrap = document.getElementById('home-section-wrap');

    function toggleSection() {
        sectionWrap.style.display = screenSelect.value === 'home' ? 'block' : 'none';
    }

    screenSelect.addEventListener('change', toggleSection);
    toggleSection();
})();
</script>
