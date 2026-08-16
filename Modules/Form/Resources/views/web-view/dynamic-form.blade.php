@extends('Form::layouts.appUser')

@section('content')
<style>

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

        justify-content: center;
        align-items: center;
        padding: 20px;
        position: relative;
        overflow-x: hidden;
    }


    .container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 900px;
        width: 100%;
        position: relative;
        z-index: 1;
        animation: slideUp 0.5s
    ease-out;
    }
    .form-container {
        max-width: 950px;
        margin: 0 auto;
    }

    .card {
        border-radius: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border: none;
        overflow: hidden;
    }

    .card-header {
        font-size: 1.2rem;
        font-weight: 600;
        background: linear-gradient(90deg, #f8f9fa 0%, #eef2f7 100%);
        color: #333;
        border-bottom: 2px solid #dee2e6;
        padding: 1rem 1.25rem;
    }

    .card-body {
        background-color: #fff;
        padding: 1.5rem;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: #222;
        margin-bottom: 6px;
        font-size: 0.95rem;
    }

    .form-control, .form-select, textarea {
        border-radius: 10px;
        padding: 10px 14px;
        border: 1px solid #ced4da;
        transition: all 0.25s ease;
        width: 100%;
    }

    .form-control:focus, .form-select:focus, textarea:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13,110,253,.15);
    }

    .field-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1.25rem;
    }

    .field-col {
        flex: 1 1 calc(50% - 1.25rem);
        min-width: 260px;
    }

    @media (max-width: 767px) {
        .field-col {
            flex: 1 1 100%;
        }
    }

    button[type="submit"] {
        border-radius: 10px;
        padding: 12px 30px;
        font-weight: 600;
        font-size: 1.05rem;
        background-color: #0d6efd;
        border: none;
        transition: all 0.25s ease;
    }

    button[type="submit"]:hover {
        background-color: #0b5ed7;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(13,110,253,0.3);
    }

    h2.text-primary {
        font-weight: 700;
        color: #0d6efd !important;
        letter-spacing: 0.5px;
    }

    /* زر اللغة */
    .lang-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 6px 14px;
        background-color: #f8f9fa;
        cursor: pointer;
        font-weight: 600;
        transition: 0.2s;
    }

    .lang-toggle:hover {
        background-color: #e9ecef;
    }

    .lang-toggle i {
        color: #0d6efd;
    }
    .btn-style {
        border: 2px solid blue;
        border-radius: 10px;
        background: blue;
        width: 17%;
        color: white;
    }

    /* ======== Container & Layout ======== */
.form-container {
    /* max-width: 1200px; */
    margin: auto;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* ======== Cards ======== */
.card {
    border-radius: 10px;
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.card-header {
    font-weight: 600;
    font-size: 1.1rem;
}

/* ======== Form Fields ======== */
.form-control {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
}

/* Textareas */
textarea.form-control {
    resize: vertical;
}

/* ======== Selects ======== */
.form-select {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.5rem 1rem;
    font-size: 1rem;
}

/* ======== Buttons ======== */
.btn {
    border-radius: 8px;
    padding: 0.6rem 1.2rem;
    font-size: 1rem;
    transition: all 0.2s ease-in-out;
    margin-bottom: 12px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* ======== Labels ======== */
.form-label {
    font-weight: 500;
    margin-bottom: 0.4rem;
}

/* ======== Search Results Cards ======== */
#search-results .card {
    border-radius: 10px;
    transition: all 0.3s ease;
}

#search-results .card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

#search-results .card-title {
    font-weight: 600;
    font-size: 1.1rem;
}

/* ======== Badges ======== */
.badge {
    border-radius: 12px;
    padding: 0.3rem 0.7rem;
    font-size: 0.85rem;
}

/* ======== Images in Fields ======== */
input[type="file"] {
    padding: 0.5rem;
}

input[type="file"]::-webkit-file-upload-button {
    border: none;
    background-color: #0d6efd;
    color: #fff;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}

input[type="file"]::-webkit-file-upload-button:hover {
    background-color: #0b5ed7;
    transform: translateY(-1px);
}

/* ======== Responsive Adjustments ======== */
@media (max-width: 768px) {
    .form-container {
        padding: 0 1rem;
    }
    #search-results .col-md-6, #search-results .col-lg-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

.agency-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f3f6fb;
    padding: 0.6rem 1rem;
    border-radius: 10px;
    margin-bottom: 10px;
}
.agency-name {
    font-size: 1.1rem;
    font-weight: bold;
    color: #0d6efd;
    margin: 0;
}
.agency-id {
    background: #e9efff;
    color: #0d6efd;
    font-size: 0.9rem;
    padding: 3px 8px;
    border-radius: 8px;
}
.agency-info p {
    margin: 0.3rem 0;
    font-size: 0.9rem;
    color: #444;
}
.agency-bio {
    font-size: 0.85rem;
    color: #666;
    margin-top: 10px;
    text-align: center;
    min-height: 40px;
}
.agency-divider {
    border-bottom: 1px solid #e0e6ef;
    margin: 10px 0;
}
.agency-list {
    text-align: right;
    font-size: 0.9rem;
    background: darkkhaki;
    border: 1px solid;
    border-radius: 9%;
    padding: 12px;

}
.card-selected {
    border: 3px solid #0d6efd;
    box-shadow: 0 12px 30px rgba(13,110,253,0.2);
}

.custom-field-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.custom-item {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
    border: none !important;
    border-radius: 8px;
    padding: 8px 12px;
    background: #f9fafc;
    position: relative;
}
.custom-item input {
    min-width: 130px;
}
.remove-btn {
    margin-left: 6px;
    border-radius: 50%;
    padding: 4px 8px;
    font-size: 0.8rem;
    line-height: 1;
}
.remove-btn i {
    pointer-events: none;
}
.input, select, textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: white;
}
.file-upload {
    position: relative;
    display: inline-block;
    cursor: pointer;
    width: 100%;
}
.file-upload input[type="file"] {
    position: absolute;
    left: -9999px;
}
.file-upload-label {
    display: block;
    padding: 12px 15px;
    border: 2px dashed #667eea;
    border-radius: 10px;
    text-align: center;
    color: #667eea;
    transition: all 0.3s
ease;
    background: rgba(102, 126, 234, 0.05);
}


/* ===== BD Card Style ===== */
.bd-card {
    background: #fff;
    border-radius: 15px;
    border: 1px solid #dce3f0;
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    cursor: pointer;
    padding: 1.2rem;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
    direction: rtl;
    width: 48%;
}

.bd-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.bd-card.selected {
    border: 2px solid #667eea;
    box-shadow: 0 0 15px rgba(102,126,234,0.4);
}

/* === الشارة العلوية (موصى به) === */
.default-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: bold;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

/* === رقم الـ BD === */
.bd-id {
    position: absolute;
    top: 10px;
    right: 10px;
    color: #666;
    font-size: 13px;
}

/* === البروفايل === */
.bd-profile {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-top: 20px;
}

.bd-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #667eea;
}

.bd-info {
    flex: 1;
}

.bd-name {
    font-size: 18px;
    font-weight: bold;
    color: #333;
}

.bd-title {
    font-size: 14px;
    color: #777;
    margin-top: 3px;
}

.bd-duration {
    font-size: 13px;
    color: #888;
    margin-top: 5px;
}

.bd-phone {
    font-size: 13px;
    color: #555;
    margin-top: 5px;
}

/* === الرسالة === */
.bd-message {
    background: #f8f9fc;
    border-radius: 10px;
    padding: 10px 15px;
    margin-top: 15px;
    color: #444;
    font-size: 14px;
    line-height: 1.6;
}

/* === أفضل الوكالات === */
.top-agencies {
    margin-top: 20px;
    border-top: 1px solid #e4e9f2;
    padding-top: 15px;
}

.top-agencies-title {
    text-align: center;
    color: #667eea;
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 15px;
}

.agencies-grid {
    display: flex;
    justify-content: space-between;
    /* flex-wrap: wrap; */
    gap: 10px;
}


.bd-id {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}
.agency-logo {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    object-fit: cover;
    margin-bottom: 8px;
}

.agency-name {
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.agency-hosts {
    font-size: 12px;
    color: #666;
}

.agency-card {
    width: 32%;
    height: 148px;
    text-align: center;
    padding: 10px;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 237, 78, 0.1));
    border: 2px solid #ffd700;
    border-radius: 10px;
    transition: all 0.3s
ease;
}
.agency-logo {
    width: 91px;
    height: 56px;
    border-radius: 44px;
    margin-bottom: 8px;
    object-fit: cover;
    border: 2px solid #ffd700;
}
.agency-name {
    font-size: 12px;
    font-weight: bold;
    color: #333;
    margin-bottom: 3px;
}
.agency-hosts {
    font-size: 11px;
    color: #666;
}
.bd-duration {
    background: #4CAF50;
    color: white;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 12px;
    display: inline-block;
    margin-top: 5px;
}

.bd-phone {
    background: #2196F3;
    color: white;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 12px;
    display: inline-block;
    margin-top: 5px;
    margin-right: 5px;
}
/* === التجاوب مع الشاشات الصغيرة === */
@media (max-width: 768px) {
    .bd-card {
        width: 100%;
    }
    .agencies-grid {
        flex-direction: column;
    }

}

</style>

@php
    $currentLocale = $locale ?? app()->getLocale();
    $direction = $currentLocale === 'ar' ? 'rtl' : 'ltr';
@endphp
<div class="container py-5 form-container" data-current-locale="{{ $currentLocale }}">
    <div class="d-flex justify-content-between align-items-center mb-4" style=" text-align: center; font-size: 25px;">
        <h2 class="text-primary mb-0">
            {{ $template->getTranslation('title', $currentLocale) }}
        </h2>
    </div>

    <form id="dynamicForm" action="{{ route('form.submit', $template->form_type) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if (!empty($user))
        <input type="hidden" name="user_id" value="{{  $user->id}}">
        <input type="hidden" name="token" value="{{ $linkToken}}">
        <input type="hidden" name="lang" value="{{ $locale}}">
        @endif

        @if($template->getTranslation('description', $currentLocale))
        <div class="card mb-5 border-info shadow-sm">
            <div class="card-body bg-light">
                <h5 class="card-title text-primary mb-3">
                    <i class="fa fa-info-circle me-2"></i>{{ __('Form Description') }}
                </h5>
                <p class="card-text fs-5 text-muted" style="white-space: pre-line;">
                    {{ $template->getTranslation('description', $currentLocale) }}
                </p>
            </div>
        </div>
    @endif
        @foreach ($template->sections as $section)
            <div class="card mb-5">
                <div class="card-header">
                    {{ $section->getTranslation('title', $currentLocale) }}
                </div>

                <div class="card-body">
                    <div class="field-row">
                        @foreach ($section->fields as $field)
                            <div class="field-col mb-4">
                                <label class="form-label">
                                    {{ $field->getTranslation('field_label', $currentLocale) }}
                                    @if($field->is_required)
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>

                                {{-- Container for dynamic/custom fields --}}
                                <div id="field-container-{{ $section->id }}-{{ $field->id }}">
                                    {{-- Text, Email, Number, Tel, Date --}}
                                    @if (in_array($field->field_type, ['text', 'email', 'number', 'tel', 'date']))
                                        <input
                                            type="{{ $field->field_type }}"
                                            name="{{ $field->field_name }}"
                                            class="form-control"
                                            placeholder="{{ $field->getTranslation('placeholder', $currentLocale) }}"
                                            @required($field->is_required)>

                                    {{-- Textarea --}}
                                    @elseif ($field->field_type === 'textarea')
                                        <textarea
                                            name="{{ $field->field_name }}"
                                            class="form-control"
                                            rows="3"
                                            placeholder="{{ $field->getTranslation('placeholder', $currentLocale) }}"
                                            @required($field->is_required)></textarea>

                                    {{-- File --}}
                                    @elseif ($field->field_type === 'file')
                                    {{-- <div class="file-upload" style="position: relative; display: inline-block;">
                                        <input
                                            type="file"
                                            id="idBack"
                                            name="{{ $field->field_name ?? 'idBack' }}"
                                            accept="image/*"
                                            class="file-input"
                                            @required($field->is_required)
                                            onchange="handleFileUpload(this, 'idBackLabel')"
                                            style="opacity: 0; position: absolute; left: 0; top: 0; width: 100%; cursor: pointer;"
                                        >
                                        <label for="idBack"
                                            class="file-upload-label d-flex align-items-center justify-content-center flex-column"
                                            id="idBackLabel"
                                            style="border: 2px dashed #ccc; border-radius: 10px;   text-align: center; cursor: pointer; transition: 0.3s;">
                                        {{ __('upload_card_back') }}
                                        </label>
                                    </div> --}}

                                    @php
                                        // generate unique IDs for each input & label
                                        $inputId = 'file_' . $section->id . '_' . $field->id . '_input';
                                        $labelId = 'file_' . $section->id . '_' . $field->id . '_label';
                                    @endphp

                                    <div class="file-upload" style="position: relative; display: inline-block;">
                                        <input type="file"
                                            id="{{ $inputId }}"
                                            name="{{ $field->field_name }}"
                                            accept="image/*"
                                            capture="environment"
                                            class="file-input"
                                            @required($field->is_required)
                                            onchange="handleFileUpload(this, '{{ $labelId }}')"
                                            style="opacity: 0; position: absolute; left: 0; top: 0; width: 100%; cursor: pointer;"
                                        >

                                        <label for="{{ $inputId }}"
                                            class="file-upload-label d-flex align-items-center justify-content-center flex-column"
                                            id="{{ $labelId }}"
                                             style="border: 2px dashed #ccc; border-radius: 10px;   text-align: center; cursor: pointer; transition: 0.3s;">
                                        {{ __('upload_card_back') }}
                                        </label>
                                    </div>

                                    {{-- <script>
                                    function handleFileUpload(input, labelId) {
                                        const label = document.getElementById(labelId);
                                        if (!input.files || !input.files[0]) return;

                                        const file = input.files[0];
                                        const reader = new FileReader();

                                        reader.onload = function(e) {
                                            label.innerHTML = `
                                                <img src="${e.target.result}" alt="Uploaded Image"
                                                    style="max-width: 100%; max-height: 150px; border-radius: 8px; margin-bottom: 8px;">
                                                <div style="font-size: 12px; color: #555;">${file.name}</div>
                                            `;
                                        };

                                        reader.readAsDataURL(file);
                                    }
                                    </script> --}}

                                    <script>
                                        function handleFileUpload(input, labelId) {
                                            const label = document.getElementById(labelId);
                                            if (!input.files || !input.files[0]) return;

                                            const file = input.files[0];
                                            const reader = new FileReader();

                                            reader.onload = function(e) {
                                                label.innerHTML = `
                                                    <img src="${e.target.result}" alt="Uploaded Image"
                                                        style="max-width: 100%; max-height: 150px; border-radius: 8px; margin-bottom: 8px;">
                                                    <div style="font-size: 12px; color: #555;">${file.name}</div>
                                                `;
                                            };

                                            reader.readAsDataURL(file);
                                        }
                                    </script>


                                    {{-- Select --}}
                                    @elseif ($field->field_type === 'select')
                                        @php
                                            $options = is_array($field->options)
                                                ? $field->options
                                                : (is_string($field->options) ? json_decode($field->options, true) : []);

                                            $data = [];

                                            if (!empty($field->data_source)) {
                                                switch ($field->data_source) {
                                                    case 'countries':
                                                        $name = app()->getLocale() == 'ar' ? 'name' : 'e_name';
                                                        $data = \App\Models\Country::select('id', "$name as name")->get();
                                                        break;
                                                    case 'cities':
                                                        $data = \App\Models\City::select('id', 'name')->get();
                                                        break;
                                                    case 'languages':
                                                        $data = \App\Models\Language::select('id', 'name')->get();
                                                        break;
                                                    case 'currencies':
                                                        $data = \App\Models\Currency::select('id', 'name')->get();
                                                        break;
                                                }
                                            }
                                        @endphp

                                        <select
                                            name="{{ $field->field_name }}"
                                            class="form-select"
                                            @required($field->is_required)>
                                            <option value="">-- {{ __('Select') }} --</option>

                                            @if (!empty($options))
                                                @foreach ($options as $key => $value)
                                                    @php
                                                        $displayValue = is_array($value)
                                                            ? ($value['label'][$currentLocale] ?? $value['label']['en'] ?? $value['value'] ?? $key)
                                                            : $value;
                                                    @endphp
                                                    <option value="{{ $key }}">{{ $displayValue }}</option>
                                                @endforeach
                                            @endif

                                            @if (!empty($data))
                                                @foreach ($data as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>

                                    {{-- Radio --}}


                                    @elseif ($field->field_type === 'radio')
                                        @php
                                            $options = is_array($field->options)
                                                ? $field->options
                                                : (is_string($field->options) ? json_decode($field->options, true) : []);
                                        @endphp
                                        <div class="radio-group">
                                            @foreach ($options as $key => $value)
                                                @php
                                                    $displayValue = is_array($value)
                                                        ? ($value[$currentLocale] ?? $value['en'] ?? $key)
                                                        : $value;
                                                @endphp
                                                <div class="form-check">
                                                    <input
                                                        type="radio"
                                                        name="{{ $field->field_name }}"
                                                        id="{{ $field->field_name }}_{{ $key }}"
                                                        value="{{ $key }}"
                                                        class="form-check-input"
                                                        @if($field->is_required && $loop->first) required @endif>
                                                    <label class="form-check-label" for="{{ $field->field_name }}_{{ $key }}">
                                                        {{ $displayValue }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>

                                    {{-- Checkbox --}}
                                    @elseif ($field->field_type === 'checkbox')
                                        @php
                                            $options = is_array($field->options)
                                                ? $field->options
                                                : (is_string($field->options) ? json_decode($field->options, true) : []);
                                        @endphp
                                        <div class="checkbox-group">
                                            @foreach ($options as $key => $value)
                                                @php
                                                    $displayValue = is_array($value)
                                                        ? ($value[$currentLocale] ?? $value['en'] ?? $key)
                                                        : $value;
                                                @endphp
                                                <div class="form-check">
                                                    <input
                                                        type="checkbox"
                                                        name="{{ $field->field_name }}[]"
                                                        id="{{ $field->field_name }}_{{ $key }}"
                                                        value="{{ $key }}"
                                                        class="form-check-input">
                                                    <label class="form-check-label" for="{{ $field->field_name }}_{{ $key }}">
                                                        {{ $displayValue }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>

                                    {{-- Custom --}}
                                    @elseif ($field->field_type === 'custom')
                                        <div class="custom-field-list d-flex flex-wrap gap-2 align-items-start"
                                            id="custom-list-{{ $section->id }}-{{ $field->id }}"></div>

                                        {{-- إذا كان allow_add_more = true في config --}}
                                        @if(optional($field->config)['allow_add_more'] ?? true)
                                            <button type="button"
                                                     style=" background: #8b8be1;"
                                                    class="btn btn-sm btn-outline-primary mt-2"
                                                    onclick="addCustomField({{ $section->id }}, {{ $field->id }})">
                                                <i class="fa fa-plus"></i> {{ __('Add') }}
                                            </button>
                                        @endif
                                    @endif

                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach


        @if($template->form_type === 'host_agency') <div class="card mb-5 border-primary shadow-sm p-4"> <h4 class="text-primary mb-4"> <i class="fa fa-search me-2"></i>{{ __('Search for Certified Agency Manager') }} </h4>

                <div class="input-group mb-4">
                    <input type="text" id="search-query" class="form-control" placeholder="{{ __('Enter name or ID') }}">
                </div>

                <input type="hidden" name="bd_id" id="selected-bd-id">

                <div id="search-results" class="d-flex flex-wrap gap-4" style="display: flex;"></div>
            </div>
            @php
                $currentLocale = request()->query('lang') ?? app()->getLocale();
            @endphp

            <script>
                let selectedCard = null;
                let searchTimeout = null;
                let lang = "{{ $currentLocale }}";

                const searchInput = document.getElementById('search-query');
                const resultsContainer = document.getElementById('search-results');

                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    const query = this.value.trim();

                    if (!query) {
                        resultsContainer.innerHTML = '';
                        return;
                    }

                    searchTimeout = setTimeout(() => performSearch(query), 400);
                });

                function performSearch(query) {
                    fetch(`{{ route('host_agency.search') }}?query=${encodeURIComponent(query)}&lang=${lang}`)
                        .then(res => res.json())
                        .then(res => {
                            resultsContainer.innerHTML = '';

                            if (!res.data.length) {
                                resultsContainer.innerHTML = `<div class="text-center text-muted w-100">{{ __('No results found') }}</div>`;
                                return;
                            }

                            res.data.forEach(bd => {
                                let topAgencies = '';
                                bd.top_agencies.forEach(agency => {
                                    topAgencies += `
                                        <div class="agency-card" bis_skin_checked="1">
                                            <img src="${agency.image ?? ''}" alt="Agency" class="agency-logo">
                                            <div class="agency-name" bis_skin_checked="1"> ${agency.name ?? ''} </div>
                                            <div class="agency-hosts" bis_skin_checked="1">${agency.members_count ?? ''} {{ __('Members') }}</div>
                                        </div>
                                    `;
                                });

                                const card = document.createElement('div');
                                card.classList.add('bd-card');
                                card.setAttribute('onclick', `selectBD(this, '${bd.id}')`);
                                card.setAttribute('data-bd-id', bd.id ?? '');
                                card.setAttribute('data-bd-name', bd.name ?? '');

                                card.innerHTML = `
                                    <span class="default-badge">${bd.is_default ? "{{ __('Recommended') }}" : ''}</span>
                                    <span class="bd-id">ID: ${bd.id ?? ''}</span>
                                    <input type="radio" name="bd" value="${bd.id ?? ''}" ${bd.is_default ? 'checked' : ''} style="display: none;">

                                    <div class="bd-profile">
                                        <img src="${bd.image ?? 'https://via.placeholder.com/80/667eea/ffffff?text=BD'}"
                                            alt="${bd.name ?? 'BD'}"
                                            class="bd-avatar">
                                        <div class="bd-info">
                                            <div class="bd-name">${bd.name ?? ''}</div>
                                            <div class="bd-title">${bd.title ?? "{{ __('Agency Manager') }}"}</div>
                                            <div class="bd-duration">{{ __('Works since') }}: ${bd.since ?? '—'}</div>
                                            <div class="bd-phone">📞 ${bd.phone ?? ''}</div>
                                        </div>
                                    </div>

                                    <div class="bd-message">
                                        ${bd.bio ?? "{{ __('Welcome message') }}"}
                                    </div>

                                    <div class="top-agencies">
                                        <div class="top-agencies-title">⭐ {{ __('Top 3 Agencies') }}</div>
                                        <div class="agencies-grid">
                                            ${topAgencies}
                                        </div>
                                    </div>
                                `;




                                card.addEventListener('click', function() {
                                    if (selectedCard) selectedCard.classList.remove('card-selected');
                                    card.classList.add('card-selected');
                                    selectedCard = card;
                                    document.getElementById('selected-bd-id').value = bd.id;
                                });

                                resultsContainer.appendChild(card);
                            });
                        })
                        .catch(err => console.error(err));
                }
            </script>

            <style>
                #search-results {
                    justify-content: flex-start;
                }
                .selectable-card {
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                .selectable-card:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
                }
                .card-selected {
                    border: 3px solid #0d6efd;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                }
            </style>


    @endif



             <div class="text-center mb-5">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fa fa-paper-plane me-2"></i> {{ __('Submit') }}
                </button>
            </div>
    </form>



</div>

 <div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); z-index:9999; text-align:center;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%);">
        <i class="fa fa-spinner fa-spin fa-3x"></i>
        <p>{{ __('Loading...') }}</p>
    </div>
</div>




</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>



    const translations = {
        members: "{{ __('Members') }}",
        recommended: "{{ __('موصى به') }}",
        working_since: "{{ __('يعمل هنا منذ:') }}",
        manager: "{{ __('مدير وكالات') }}",
        welcome_message: "{{ __('مرحباً بك في عائلتنا! أنا هنا لأدعمك في كل خطوة نحو النجاح.') }}",
        top_agencies: "{{ __('⭐ أفضل 3 وكالات') }}"
    };
</script>
<script>

     jQuery(document).ready(function($) {
        $('#dynamicForm').on('submit', function(e) {
            // Show overlay immediately
            jQuery('#loadingOverlay').show();

            // Disable the submit button to prevent multiple clicks
            jQuery('#submitBtn').prop('disabled', true);

            // Optional: prevent actual submit for testing
            // e.preventDefault();
        });
    });
document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('.form-container');
    const formId = "{{ $template->id }}";
    let currentLang = container.dataset.currentLocale;

    function updateDirection(lang) {
        document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
        document.documentElement.lang = lang;
    }

    updateDirection(currentLang);

    // ---------------- Custom field dynamic addition ----------------
        window.addCustomField = function(sectionId, fieldId) {
        const list = document.getElementById(`custom-list-${sectionId}-${fieldId}`);
        const index = list.children.length;

        const fields = [
            { name: 'app_name', type: 'text', placeholder: "{{ __('Application Name') }}" },
            { name: 'work_duration', type: 'number', placeholder: "{{ __('Work Duration (months)') }}" }
        ];

        let html = `<div style="width: 100%;" class="custom-item d-flex align-items-start gap-2 flex-wrap bg-light p-2 rounded border position-relative" style="min-width:250px">`;

        fields.forEach(field => {
            html += `
                <div class="flex-grow-1" style="width: 46%;">
                    <input type="${field.type}"
                        name="sections[${sectionId}][fields][${fieldId}][items][${index}][${field.name}]"
                        class="form-control form-control-sm mb-1"
                        placeholder="${field.placeholder}">
                </div>
            `;
        });

        html += `
            <button type="button" class="btn btn-sm btn-danger remove-btn"
                onclick="this.closest('.custom-item').remove()">
                <i class="fa fa-times"></i>
            </button>
        </div>`;

        list.insertAdjacentHTML('beforeend', html);
    };

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.custom-field-list').forEach(list => {
            const [sectionId, fieldId] = list.id.replace('custom-list-', '').split('-');
            addCustomField(sectionId, fieldId);
        });
    });

    // ---------------- Language toggle example ----------------
    const toggleBtn = document.getElementById('toggle-lang');
    if(toggleBtn){
        toggleBtn.addEventListener('click', async () => {
            currentLang = currentLang === 'en' ? 'ar' : 'en';
            updateDirection(currentLang);
            await loadTranslations(currentLang);
            toggleBtn.querySelector('span').textContent = currentLang === 'en' ? 'عربي' : 'English';
        });
    }

    async function loadTranslations(lang) {
        try {
            const response = await fetch(`/form-translations?id=${formId}&locale=${lang}`);
            const data = await response.json();

            document.querySelector('h2').textContent = data.title;

            data.sections.forEach((section, sIndex) => {
                const cards = document.querySelectorAll('.card');
                if (cards[sIndex]) {
                    cards[sIndex].querySelector('.card-header').textContent = section.title;
                    const fields = cards[sIndex].querySelectorAll('.field-col');
                    section.fields.forEach((field, fIndex) => {
                        if(fields[fIndex]){
                            const label = fields[fIndex].querySelector('.form-label');
                            const requiredStar = label.querySelector('.text-danger');
                            label.textContent = field.label;
                            if(requiredStar) label.appendChild(requiredStar);
                        }
                    });
                }
            });

        } catch (error) {
            console.error('Translation error:', error);
        }
    }
});
</script>


<style>
.field-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.radio-group,
.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-check {
    padding: 0.5rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
}

.form-check:hover {
    background-color: #f8f9fa;
}

.form-check-input {
    margin-top: 0.3rem;
}

.form-check-label {
    margin-bottom: 0;
    cursor: pointer;
}

[dir="rtl"] .form-check-input {
    margin-left: 0.5rem;
    margin-right: 0;
}

</style>

@endsection
