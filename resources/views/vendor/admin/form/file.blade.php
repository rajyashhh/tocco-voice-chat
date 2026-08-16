<div class="{{$viewClass['form-group']}} {!! !$errors->has($errorKey) ? '' : 'has-error' !!}">

    <label for="{{$id}}" class="{{$viewClass['label']}} control-label">{{$label}}</label>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')

{{--        @if($value)--}}
{{--            @php--}}
{{--                $url = \Illuminate\Support\Facades\Storage::disk(config('admin.upload.disk'))->url($value);--}}
{{--                $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));--}}
{{--                $uniqueId = 'file_' . uniqid();--}}
{{--            @endphp--}}

{{--            @if(!in_array($ext, ['png','jpg','jpeg','gif','webp','svg']))--}}
{{--                <div style="margin-bottom:10px;">--}}
{{--                    {!! handleShowImageWithTypes($uniqueId, $url, 100, 100, 10) !!}--}}
{{--                </div>--}}
{{--            @endif--}}
{{--        @endif--}}

        <input type="file" class="{{$class}}" name="{{$name}}" {!! $attributes !!} />

        @include('admin::form.help-block')

    </div>
</div>

<script>
    $(document).ready(function () {
        $('#file-input-img2').on('change', function (event) {
            let file = event.target.files[0];
            if (!file) return;

            let ext = file.name.split('.').pop().toLowerCase();

            $('#preview-img2').empty();
            $('#preview-display-img2').empty();

            if (['png','jpg','jpeg','gif','webp','svg'].includes(ext)) {
                let reader = new FileReader();
                reader.onload = function (e) {
                    let html = `<img src="${e.target.result}" style="max-height:150px" class="img img-thumbnail" />`;
                    $('#preview-img2').html(html);
                    $('#preview-display-img2').html(html);
                };
                reader.readAsDataURL(file);
            } else if (['mp4','mov','webm'].includes(ext)) {
                let reader = new FileReader();
                reader.onload = function (e) {
                    let html = `<video src="${e.target.result}" controls style="max-height:150px"></video>`;
                    $('#preview-img2').html(html);
                    $('#preview-display-img2').html(html);
                };
                reader.readAsDataURL(file);
            } else if (ext === 'svga') {
                let uniqueId = 'svga_preview_' + Date.now();
                let html = `<div id="${uniqueId}" style="height:110px;"></div>`;
                $('#preview-img2').html(html);
                $('#preview-display-img2').html(html);

                let player = new SVGA.Player('#' + uniqueId);
                player.loops = 0;
                player.clearsAfterStop = false;
                let parser = new SVGA.Parser('#' + uniqueId);
                let blobUrl = URL.createObjectURL(file);

                parser.load(blobUrl, function(videoItem) {
                    player.setVideoItem(videoItem);
                    player.startAnimation();
                });
            } else {
                let html = `<p>Selected file: ${file.name}</p>`;
                $('#preview-img2').html(html);
                $('#preview-display-img2').html(html);
            }
        });
    });
</script>

<style>

</style>
