<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>



<style>
    .achievement-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
         width: 800px; /* Fixed width instead of max-width */
        height: auto; /* Height will adjust to content */
        min-height: 500px; /* Minimum height to prevent container from being too small */
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    .form-label {
        font-weight: 600;
       /* // color: #495057; */
         color: #000; /* Pure black */
    font-size: 1.25rem; /* 20px equivalent */
        margin-bottom: 0.5rem;
    }

    .select2-container--default .select2-selection--single {
        height: 42px;
        border: 1px solid #ced4da;
        border-radius: 4px;
         color: #000;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }

    .image-option {
        display: inline-block;
        margin-right: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        border-radius: 5px;
    }

    .image-option:hover {
        transform: scale(1.05);
    }

    .image-option.selected {
        border-color: #0d6efd;
        box-shadow: 0 0 10px rgba(13, 110, 253, 0.5);
    }

    .image-scroll-container {
        display: flex;
        overflow-x: auto;
        padding: 10px 0;
        gap: 15px;
    }

    .image-scroll-container::-webkit-scrollbar {
        height: 8px;
    }

    .image-scroll-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .image-scroll-container::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }

    .image-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .submit-btn {
        width: 100%;
        padding: 10px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .form-section {
        margin-bottom: 1.5rem;
        padding: 1.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .form-section.active {
        /* background: #e7f1ff; */
        /* border-left: 4px solid #0d6efd; */
    }

    .file-upload-wrapper {
        position: relative;
        margin-top: 10px;
    }

    .file-upload-label {
        display: block;
        padding: 10px 15px;
        background: #e9ecef;
        border: 1px dashed #adb5bd;
        border-radius: 4px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }

    .file-upload-label:hover {
        background: #dee2e6;
    }

    .file-upload-input {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .achievement-container {
            padding: 1rem;
            margin: 1rem;
        }
    }
</style>

<div class="box box-solid">

    <div class="achievement-container">
      <h3 class="text-center mb-4">{{ __('assign achievement') }}</h3>

        @php

                    $gift=DB::table('gifts')->where('type',5)->where('enable',true)->get();
                   // $users=DB::table('users')->get();
        @endphp
        <form method="POST" action="{{ route('admin.postAddGiftAchievement') }}" class="formcustumPage" enctype="multipart/form-data"
        >
            @csrf


                <div class="form-section active">
                    <div class="form-group mb-3">
                        <label for="user_id" class="form-label">{{ __('admin.users') }}</label>
                        <select name="user_id" id="user_id" class="form-control select2" required>
                            <option value="">{{ __('admin.selectUser') }}</option>
                        </select>

                    </div>
                </div>
                <br>
    <br>
                <input type="hidden" name="achievement_id"  value="{{ $achievement_id }}" class="inputs_cus_form">
                 <div class="form-section active">
                    <div class="form-group mb-3">
                         <label for="user_id" class="form-label">{{ __('gifts') }}</label>
                         <select name="gift_id" id="cars" class="form-control" required>
                            <option value="">{{__('select Gift')}}</option>
                            @foreach ($gift as $label)
                                <option value="{{ $label->id }}">{{ $label->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <br>
                <br>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary submit-btn">
                        <i class="fas fa-paper-plane me-2"></i>
                        {{ __('admin.submit') }}
                    </button>
                </div>
        </form>
        <br>

    </div>
    <!-- /.box-body -->
    @if(session()->has('message'))
    <div class="alert alert-danger form-group">
        {{ session()->get('message') }}
    </div>
@endif
<style>
    #file-preview-container {
        max-width: 100%;
        margin-top: 15px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    #file-preview-container img {
        max-width: 300px;
        max-height: 300px;
        object-fit: contain;
    }

    #file-preview-container .preview-content {
        text-align: center;
    }

    .pdf-preview {
        width: 100%;
        height: 500px;
        border: none;
    }

    .doc-preview {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 4px;
    }
</style>

<script>

      $(document).ready(function() {

         $('#user_id').select2({
            ajax: {
                url: '{{ route("search.users") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        type: 'public',
                        page: params.page || 1
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.data.map(function(user) {
                            return {
                                id: user.id,
                                text: user.name || '',
                                image: user.avatar? "{{ getImagePath('__PATH__') }}".replace('__PATH__', user.avatar)
                                                : "{{ asset('images/default-user.png') }}",
                            };
                        }),
                        pagination: {
                            more: (params.page * 10) < data.total
                        }
                    };
                },
                cache: true
            },
            templateResult: formatUser,
            templateSelection: formatUserSelection,
            placeholder: '{{ __("admin.searchUsers") }}',
            minimumInputLength: 1
        });

        function formatUser(user) {
            if (!user.id) return user.text;

            var $container = $(
                '<div class="d-flex align-items-center">' +
                '<img src="' + user.image + '" class="rounded-circle me-2" width="30" height="30">' +
                '<span>' + user.text + '</span>' +
                '</div>'
            );
            return $container;
        }

        function formatUserSelection(user) {
            if (!user.id) return user.text;

            return $(
                '<div class="d-flex align-items-center">' +
                '<img src="' + user.image + '" class="rounded-circle me-2" width="20" height="20">' +
                '<span>' + user.text + '</span>' +
                '</div>'
            );
        }

  });

</script>

</div>


