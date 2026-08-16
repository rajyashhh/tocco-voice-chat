<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="box box-solid">
    <div style="height: 417px;     height: 517px;
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
                   
                    $achievements=DB::table('achievements')->get();
                  //  $users=DB::table('users')->get();
                  $gifts = \Modules\Achievement\Entities\GiftAchievement::with('gift')->get();

        @endphp
       <form method="POST" action="{{ route('admin.store-user-achievement') }}" enctype="multipart/form-data">
        @csrf
    
        <div class="form-group">
            <label for="user_id">{{__('admin.users')}}</label>
            <select name="user_id" id="user_id" class="form-control select2" required>
                <option value="">{{__('admin.selectUser')}}</option>

                {{-- @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach --}}
            </select>
        </div>

        
{{--     
        <div class="form-group">
            <label for="achievement_level_id">Achievement</label>
            <select name="achievement_level_id" id="achievement_level_id" class="form-control">
                <option value="">Select Achievement</option>
                @foreach ($achievements as $achievement)
                    <option value="{{ $achievement->id }}">{{ $achievement->type }}</option>
                @endforeach
            </select>
        </div> --}}

        <div class="form-group">
            <label for="achievement_id">{{__('admin.achievement')}}</label>
            <select name="achievement_id" id="achievement_id" class="form-control" required>
               
                <option value="">{{__('admin.selectUser')}}</option>
                <option value="">{{__('admin.withoutAchievement')}}</option>

                @foreach ($achievements as $achievement)
                            <option value="{{ $achievement->id }}" data-type="{{ $achievement->type }}">{{ $achievement->type }}</option>
                        @endforeach
                {{-- <option value="gift">gift_target</option> --}}
            </select>
        </div>
 


        {{-- <div class="form-group">
            <label for="achievement">{{__('admin.typeAchievement')}}</label>
            <select  id="achievement" class="form-control" required>
             
                <option value="achievement_level">{{__('admin.achievementLevel')}}</option>
                <option value="achievement">{{__('admin.achievement')}}</option>
            </select>
        </div> --}}
        
        <div class="form-group" id="achievementLevelDiv" style="display: none;">
            <label for="achievement_level_id">{{__('admin.achievementLevel')}}</label>
            <select name="achievement_level_id" id="achievement_level_id" class="form-control" >
                <option value="">{{__('admin.selectAchievementLevel')}}</option>
            </select>
        </div>
        
        <div class="form-group" id="file_image" style="display: none;">
            <label for="file-image">{{__('admin.type_file')}}</label>
            <select name="file_image_select" id="file_image_select" class="form-control" required>
               
                <option value="">{{__('admin.type_file')}}</option>
                <option value="file">{{__('admin.file')}}</option>
                <option value="image">{{__('admin.Image')}}</option>
                
            </select>
        </div>

        <div class="form-group" id="file_input" style="display: none;">
            <label for="file-image">{{__('admin.select_file')}}</label>
            <input type="file" id="custom_file" name="custom_file">

        </div>


        <div class="form-group" id="imageDiv" style="display: none;">
            <label for="custom_image">{{ __('admin.selectImage') }}</label>
            
            <div class="d-flex flex-wrap">
                @foreach ($achievementValidImage as $data)
              
                    <label class="image-option">
                        <input type="radio" name="custom_image" value="{{ $data->image }}" class="d-none">
                        <img src="{{ getImagePath($data->image) }}" class="img-thumbnail" width="100" height="100">
                    </label>
                @endforeach
            </div>

            <hr>
            <input type="file" id="custom_image" name="custom_image">
        </div>

        
        <div class="form-group" id="gift_achievement_div">
            <label for="gift_achievement_id">{{__('admin.giftAchievement')}}</label>
            <select name="gift_achievement_id" id="gift_achievement_id" class="form-control" >
                <option value="">{{__('admin.selectAchievement')}}</option>
                @foreach ($gifts as $gift)
                    <option value="{{ $gift->id }}">{{ $gift->gift->name ?? '' }}</option>
                @endforeach
            </select>
        </div>

    
        <button type="submit" class="btn btn-primary">{{__('admin.submit')}}</button>
    </form>
    </div>
    <!-- /.box-body -->

<script>
    $(document).ready(function() {

        $('#user_id').select2({
            ajax: {
                url: '{{ route('search.users') }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    var query = {
                        search: params.term,
                        type: 'public',
                        page: params.page || 1 // Pass the current page number to the server
                    };

                    return query;
                },
                processResults: function (data, params) {
                
                    params.page = params.page || 1; // Set the current page number
                    
                        return {
                        results: data.data.map(function(user) {
                            return { id: user.id, text: user.name || '' };

                        }), // Set the results array to the received data
                        pagination: {
                            more: (params.page * 10) < data.total // Check if there are more pages
                        }
                    };
                },
                cache: true
            },
            placeholder: '{{__('admin.searchUsers')}}',
        });   

        
        // When the achievement select changes
        $('#achievement_id').change(function() {
            var achievementId = $(this).val();
            $('#achievementLevelDiv, #imageDiv,#gift_achievement_div,#file_image').hide();
            let selected = $(this).find(':selected').data('type');
           

          if(selected == '{{\Modules\Achievement\Enums\AchievementType::GIFT_TARGET}}'){
                   $('#gift_achievement_div').fadeIn()
            }else if (achievementId === ''){
            $('#file_image').show();
            // $('#imageDiv').show();

                    $('#gift_achievement_div').hide()
                    $('#achievementLevelDiv').hide();
            }else if (selected !== '{{\Modules\Achievement\Enums\AchievementType::GIFT_TARGET}}' || selected !== '')
            {
                $('#gift_achievement_div').hide()
                $('#achievementLevelDiv').show();
            }

            
            // Send an AJAX request to retrieve levels based on the selected achievement
            $.ajax({
                url: '/admin/get-achievement-levels/' + achievementId,
                type: 'GET',
                success: function(data) {
                    // Clear existing options and add new options
                    $('#achievement_level_id').empty();
                    $('#achievement_level_id').append('<option value="">{{__('admin.selectAchievementLevel')}}</option>');
                    
                    $.each(data, function(key, value) {
                        $('#achievement_level_id').append('<option value="' + key + '">' + value + '</option>');
                    });
                }
            });
        });

        $('#file_image_select').change(function() {
            var achievementId = $(this).val();
    
            console.log(achievementId);
          if(achievementId == 'file'){
           
              $('#file_input').fadeIn()

            }else if (achievementId === 'image'){
           
            $('#imageDiv').show();
            $('#file_input').hide()
             
            }
        });
        // $('#achievement_id').change(function() {
        //     // Hide all divs initially
        //     $('#achievementLevelDiv, #imageDiv,#gift_achievement_div').hide();

        //     // Check the selected value
        //     var selectedValue = $(this).val();

        //      if (selectedValue === '') {
        //         // If "Achievement" is selected, show the corresponding div
        //         $('#imageDiv').show();
        //     } else if(selectedValue == '{{\Modules\Achievement\Enums\AchievementType::GIFT_TARGET}}'){
        //         $('#gift_achievement_div').show();
                
        //         }else{
        //         $('#achievementLevelDiv').show();
        //     }
        // });

        // Trigger the change event to handle the initial state
        // $('#achievement_id').trigger('change');

        //search user
        
    });
</script>

</div>
