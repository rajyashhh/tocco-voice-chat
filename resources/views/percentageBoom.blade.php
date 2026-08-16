

<div class="card mb-4 border-0 ">
 <form action="{{ route('admin.room-boom-settings') }}" method="POST" enctype="multipart/form-data">
    @csrf

    @foreach($percentages as $percentage)
    <div class="card mb-4 border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="card-header bg-gradient bg-primary bg-opacity-10 border-0 py-3 px-4">
            <h5 class="mb-0 fw-semibold text-primary">
                <i class="bi bi-pie-chart-fill me-2"></i>
                {{ __('percentage') }} 
                <span class="badge bg-primary ms-2 rounded-pill px-3 py-2">{{ $percentage->percentage }}%</span>
            </h5>
        </div>
        
        <div class="card-body p-4">
            <div class="row g-4">

                <!-- File Upload -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="file_{{ $percentage->id }}" class="form-label fw-medium text-secondary mb-2">
                            <i class="bi bi-cloud-upload me-1"></i>
                            {{ __('img') }}
                        </label>
                        <div class="upload-area border border-2 border-dashed rounded-4 p-3 text-center bg-light bg-opacity-25 transition-all" 
                             onmouseover="this.classList.add('border-primary', 'bg-primary', 'bg-opacity-10')"
                             onmouseout="this.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10')">
                            <i class="bi bi-images fs-1 text-muted mb-2 d-block"></i>
                            <input type="file"
                                   name="files[{{ $percentage->id }}]"
                                   id="file_{{ $percentage->id }}"
                                   class="form-control form-control-sm"
                                   accept=".svga,.mp4,.png,.jpg,.jpeg,.gif,.webp,.mov,.avi,.mkv,.webm"
                                   onchange="previewFile(this, 'preview_{{ $percentage->id }}')">
                            <small class="text-muted d-block mt-2">SVGA, MP4, PNG, JPG, GIF, WEBP, MOV, AVI, MKV, WEBM</small>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="col-md-4">
                    <label class="form-label fw-medium text-secondary mb-2">
                        <i class="bi bi-eye me-1"></i>
                        {{ __('Preview') }}
                    </label>

                        <div class="border rounded-4 d-flex align-items-center justify-content-center bg-light bg-gradient preview-wrapper" style="height:150px;">
                            {!! handleShowImageWithTypes(
                                    $percentage->id,
                                    $percentage->image ? getImagePath($percentage->image) : null,
                                    400, 140
                            ) !!}
                        </div>
                </div>

                <!-- Type -->
                <div class="col-md-4">
                    <label for="type_{{ $percentage->id }}" class="form-label fw-medium text-secondary mb-2">
                        <i class="bi bi-tag me-1"></i>
                        {{ __('image_type') }}
                    </label>
                    <select name="types[{{ $percentage->id }}]" id="type_{{ $percentage->id }}" class="form-select type-select">
                        <option value="" class="text-muted">{{ __('Select type') }}</option>
                        <option value="svga" {{ $percentage->image_type == 'svga' ? 'selected' : '' }}>{{ __('svga') }}</option>
                        <option value="alpha" {{ $percentage->image_type == 'alpha' ? 'selected' : '' }}>{{ __('alpha') }}</option>
                        <option value="mp4" {{ $percentage->image_type == 'mp4' ? 'selected' : '' }}>{{ __('mp4') }}</option>
                        <option value="vap" {{ $percentage->image_type == 'vap' ? 'selected' : '' }}>{{ __('vap') }}</option>
                        <option value="png" {{ $percentage->image_type == 'png' ? 'selected' : '' }} title="image:(jpg, jpeg, png, gif, bmp, tiff, svg, webp, mov, avi, wmv, flv, mkv, webm)">Image (png)</option>
                    </select>
                   
                </div>

            </div>
        </div>
    </div>
    @endforeach


        <div class="box-footer">


            <div class="col-md-2">
            </div>
            <div class="col-md-2">
            </div>
                <div class="col-md-8">

                    <div class="btn-group pull-right">
                        <button type="submit" class="btn btn-primary"> <i class="bi bi-check-circle me-2"></i>
                        {{ __('Save') }}
                        <i class="bi bi-arrow-right ms-2"></i></button>
                    </div>
                </div>
            </div>

   
</form>
</div>

<style>

.transition-all {
    transition: all 0.3s ease;
}

.hover-scale:hover {
    transform: scale(1.02);
}

.border-dashed {
    border-style: dashed !important;
}

.upload-area {
    cursor: pointer;
}

.form-select, .form-control {
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out, background-color 0.15s ease-in-out;
}

.form-select:focus, .form-control:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    background-color: #ffffff;
}

/* Animated gradient background for cards */
.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.1) !important;
}


.type-select {
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    padding-right: 2.5rem;
}

/* Dropdown menu styling */
.type-select option {
    white-space: normal;
    word-wrap: break-word;
    padding: 10px 15px;
    min-height: auto;
    line-height: 1.4;
}

/* Better dropdown positioning */
select.form-select option {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* For very long options, ensure they wrap */
select.form-select option:last-child {
    white-space: normal;
    word-wrap: break-word;
    max-width: 250px;
}

/* Alternative: Use a dropdown with fixed max-height and scrolling */
select.form-select[size] {
    max-height: 300px;
    overflow-y: auto;
}

/* Option groups styling */
select.form-select optgroup {
    font-weight: 600;
    background-color: #f8f9fa;
    padding: 8px 10px;
}

/* Hover effect for options */
select.form-select option:hover,
select.form-select option:focus {
    background-color: #e9ecef;
}

/* Custom dropdown arrow */
.form-select {
    background-size: 16px 12px;
}

/* Mobile-friendly adjustments */
@media (max-width: 768px) {
    select.form-select option {
        font-size: 16px; /* Prevents zoom on iOS */
        padding: 12px;
    }
    
    .type-select {
        font-size: 16px;
    }
}

/* Preview wrapper centering and size constraints */
.preview-wrapper img,
.preview-wrapper video,
.preview-wrapper svg,
.preview-wrapper svga {
    max-height: 140px;
    max-width: 100%;
    object-fit: contain;
    display: block;
    margin: 0 auto;
}
</style>

<!-- Add Bootstrap Icons if not already included -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">