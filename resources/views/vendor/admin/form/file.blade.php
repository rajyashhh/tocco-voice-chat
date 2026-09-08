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
        // Bind to EVERY file input on the page, not a hardcoded ID.
        // Each input's closest .form-group provides the preview containers.
        $(document).on('change', 'input[type="file"]', function (event) {
            let file = event.target.files[0];
            if (!file) return;

            let ext = file.name.split('.').pop().toLowerCase();
            let $group = $(this).closest('.form-group');
            let $preview = $group.find('.svga-live-preview, .file-preview, .help-block').first();
            // Fall back to a temporary container if no preview area exists.
            if (!$preview.length) {
                $preview = $('<div class="svga-live-preview" style="margin-top:8px;"></div>');
                $group.find('input[type="file"]').after($preview);
            }
            $preview.empty();

            if (['png','jpg','jpeg','gif','webp','svg'].includes(ext)) {
                let reader = new FileReader();
                reader.onload = function (e) {
                    $preview.html(`<img src="${e.target.result}" style="max-height:150px" class="img img-thumbnail" />`);
                };
                reader.readAsDataURL(file);
            } else if (['mp4','mov','webm'].includes(ext)) {
                let reader = new FileReader();
                reader.onload = function (e) {
                    $preview.html(`<video src="${e.target.result}" controls style="max-height:150px"></video>`);
                };
                reader.readAsDataURL(file);
            } else if (ext === 'svga') {
                let uniqueId = 'svga_preview_' + Date.now();
                $preview.html(`<div id="${uniqueId}" style="height:110px;"></div>`);

                if (typeof SVGA !== 'undefined') {
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
                    $preview.html('<p style="color:#888;">SVGA file selected (player loading…)</p>');
                }
            } else {
                $preview.html(`<p>Selected file: ${file.name}</p>`);
            }
        });
    });
</script>

<style>

</style>
