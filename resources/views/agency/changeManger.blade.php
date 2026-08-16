<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="box box-solid">
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
                  $users = DB::table('users')->where('is_manger',1)->get();

        @endphp

       <form  method="POST" action="{{ url('admin/change_agency_mangers') }}" enctype="multipart/form-data">
        @csrf
    
        <div class="form-group">
            <label for="user_id">new agency manger</label>
            <select name="new_agency_manger_id" id="new_agency_manger_id" class="form-control select2">
                <option value="">{{__('admin.selectUser')}}</option>

                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        


        <div class="form-group">
            <label for="achievement_id"> old agency Manger</label>
            <input class="form-control mb-2" type="text" name="old_agency_manger_id" id="old_agency_manger_id" required>
            
        </div>
 


       
        
        <button type="submit" class="btn btn-primary">{{__('admin.submit')}}</button>
    </form>

    <br>
    <table id="table" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th style="width: 10px">#</th>
                <th>Agency Namr</th>
                <th>Agency Manger Name</th>
                <th>Agency Manger uuid</th>
                
                
            </tr>
        </thead>
        <tbody class="list agency_table">
        {{-- @foreach($agencies ?? [] as $key => $agency)
            <tr>
                <td>{{$key + 1}}</td>
                <td>{{$agency->name}}</td>
                <td>{{$agency->agencyManger->name}}</td>
                <td>{{$agency->agencyManger->uuid}}</td>
            </tr>
        @endforeach --}}
        </tbody>
    </table>
    <br>
    @if(session()->has('message'))
    <div class="alert alert-success">
        {{ session()->get('message') }}
    </div>
@endif

  <script>

// document.getElementById('myForm').addEventListener('submit', function (event) {
//   // Prevent the default form submission
//   event.preventDefault();

//   // Get form data
//   const formData = new FormData(event.target);

//   // Create URL parameters
//   const urlParams = new URLSearchParams(formData);

//   // Get the original form action
//   const formAction = event.target.action;

//   // Create the new URL with parameters
//   const newUrl = formAction + '?' + urlParams.toString();

//   // Redirect to the new URL
//   window.location.href = newUrl;
// });
// $(document).ready(function(){
//  getAgencies()
//         function getAgencies(){
//             $.ajax({
//                 type:'POST',
//                 dataType :'json',
//                 url :"/admin/change_agency_mangers",
//                 success:function(response){
//                     $('.agency_table').html('');
//                         $.each(response.agencies ,function(key , item){
//                                 $('.agency_table').append('<tr>\
//                                         <td class=" text-center min-w-100 pt-4">\
//                                             <h6>'+item.id+'</h6> \
//                                         </td>\
//                                         <td class=" text-center min-w-100 pt-4">\
//                                             <h6>'+item.name+'</h6> \
//                                         </td>\
//                                         <td class=" text-center min-w-100 pt-4">\
//                                             <h6>'+item.agencyManger.name+'</h6> \
//                                         </td>\
//                                         <td class=" text-center min-w-100 pt-4">\
//                                             <h6>'+item.agencyManger.uuid+'</h6> \
//                                         </td>\
//                                     </tr>\
//                                 ')
                           
//                         });
                  
//                        
//                 }
//             })
//         }
//     });


$(document).ready(function(){
    // AJAX form submission
    $("form").submit(function(e) {
        e.preventDefault(); // Prevent default form submission

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: "/admin/change_agency_mangers",
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            success: function(response) {
                $('.agency_table').html('');
                $.each(response.agencies, function(key, item) {
                    $('.agency_table').append('<tr>\
                            <td class="text-center min-w-100 pt-4">\
                                <h6>' + item.id + '</h6> \
                            </td>\
                            <td class="text-center min-w-100 pt-4">\
                                <h6>' + item.name + '</h6> \
                            </td>\
                            <td class="text-center min-w-100 pt-4">\
                                <h6>' + item.agencyManger.name + '</h6> \
                            </td>\
                            <td class="text-center min-w-100 pt-4">\
                                <h6>' + item.agencyManger.uuid + '</h6> \
                            </td>\
                        </tr>');
                });
            },
            error: function(xhr, status, error) {
                // Handle errors here
                console.error(error);
            }
        });
    });
});

  </script>

    <!-- /.box-body -->
</div>