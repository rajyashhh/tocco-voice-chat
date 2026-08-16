<div class="box box-solid" style="    height: 695px;
">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <div style="height: 417px;     height: 417px;
    margin: auto;
    width: 46%;
    top:50px;
    position: relative;
    ">
        @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
        @endif

        @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif
        @php
                 
                    $id = $id_achi; 

                    $users=DB::table('users')->get();
                    $achievement_levels = DB::table('achievement_levels')->where('id',$id)->first();

        @endphp
        <form method="POST" action="{{ route('admin.posteditGiftAchievementLevel') }}" class="formcustomPage" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="achievement_id" placeholder="" value="{{ @$achievement_levels->achievement_id }}" class="inputs_cus_form">
                <input type="hidden" name="id" placeholder="" value="{{ @$id }}" class="inputs_cus_form">

                <label for="target" class="control-label">{{ __('Target') }}</label>
                <input type="number" name="target" id="target" placeholder="" value="{{ @$achievement_levels->target }}" class="inputs_cus_form" required>

                <label for="target_type" class="control-label">{{ __('Target type') }}</label>
                <select name="target_type" id="target_type" class="inputs_cus_form" >
                    <option value="">{{ @$achievement_levels->target_type }}</option>
                    @foreach ($targetTypes as  $label)
                        <option value="{{ $label->value }}">{{ __($label->value) }}</option>
                    @endforeach
                </select>

                <label for="valid_image" class="control-label">{{ __('Valid image') }}</label>
                <input type="file" name="valid_image" id="valid_image" value="{{ @$achievement_levels->valid_image }}" class="inputs_cus_form" >

                <label for="invalid_image" class="control-label">{{ __('Invalid image') }}</label>
                <input type="file" name="invalid_image" id="invalid_image" value="{{ @$achievement_levels->invalid_image }}" class="inputs_cus_form" >

                <label for="target" class="control-label">{{ __('ar_description') }}</label>
                <input type="text" name="ar_description" id="target" placeholder="" value="{{ @$achievement_levels->ar_description }}" class="inputs_cus_form" required>


                <label for="target" class="control-label">{{ __('en_description') }}</label>
                <input type="text" name="en_description" id="target" placeholder="" value="{{ @$achievement_levels->en_description }}" class="inputs_cus_form" required>

                

                <button type="submit" class="button_form_cus">{{ __('Submit') }}</button>
            </form>

    </div>
    <!-- /.box-body -->

<script>
    $(document).ready(function() {
        // When the achievement select changes
        $('#achievement_id').change(function() {
            var achievementId = $(this).val();
            
            // Send an AJAX request to retrieve levels based on the selected achievement
            $.ajax({
                url: '/get-achievement-levels/' + achievementId,
                type: 'GET',
                success: function(data) {
                    // Clear existing options and add new options
                    $('#achievement_level_id').empty();
                    $('#achievement_level_id').append('<option value="">Select Achievement Level</option>');
                    
                    $.each(data, function(key, value) {
                        $('#achievement_level_id').append('<option value="' + key + '">' + value + '</option>');
                    });
                }
            });
        });
    });
</script>

</div>
