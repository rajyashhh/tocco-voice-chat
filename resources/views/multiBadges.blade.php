

@php
use Illuminate\Support\Facades\Cache;

$enabledLanguages = Cache::rememberForever('languages', function () {
    return \App\Models\Language::where('is_enabled', true)
        ->pluck('name', 'code')
        ->toArray();
});

$languages = array_merge(
    ['default' => __('Default')],
    $enabledLanguages
);

$defaultLang  = array_key_first($languages);
$selectedLang = request()->input('tab', $defaultLang);
@endphp

<style>
:root {
    --primary: #2563eb;
    --primary-light: #3b82f6;
    --primary-lighter: #dbeafe;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --white: #ffffff;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --radius: 0.5rem;
    --radius-lg: 0.75rem;
}

.language-manager {
    font-family: system-ui, -apple-system, 'Inter', 'Segoe UI', Roboto, sans-serif;
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

/* Modern Tabs */
.language-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.125rem;
    background: var(--gray-100);
    padding: 0.5rem;
    border-bottom: none;
    margin: 0;
    list-style: none;
}

.language-tabs .nav-item {
    margin: 0;
}

.language-tabs .nav-link {
    display: flex;
    align-items: center;
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-600);
    background: transparent;
    border: none;
    border-radius: var(--radius);
    transition: all 0.2s ease;
    text-decoration: none;
    position: relative;
}

.language-tabs .nav-link:hover {
    color: var(--gray-800);
    background: rgba(37, 99, 235, 0.05);
}

.language-tabs .nav-link.active {
    color: var(--primary);
    background: var(--white);
    box-shadow: var(--shadow-sm);
}

.language-tabs .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -0.5rem;
    left: 50%;
    transform: translateX(-50%);
    width: 1.5rem;
    height: 0.125rem;
    background: var(--primary);
    border-radius: 1px;
}

/* Tab Content */
.tab-content-modern {
    background: var(--white);
    padding: 1.5rem;
    border-top: 1px solid var(--gray-200);
}

.tab-pane {
    display: none;
    animation: fadeIn 0.3s ease;
}

.tab-pane.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0.7; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Form Groups */
.form-group-modern {
    margin-bottom: 1.75rem;
}

.form-group-modern label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-700);
    letter-spacing: 0.025em;
    text-transform: uppercase;
}

.form-group-modern label span {
    font-weight: 400;
    color: var(--gray-600);
    text-transform: none;
    margin-left: 0.25rem;
}

/* Image Preview */
.image-preview-modern {
    background: var(--gray-50);
    border: 2px dashed var(--gray-300);
    border-radius: var(--radius);
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.image-preview-modern:hover {
    border-color: var(--primary-light);
    background: var(--primary-lighter);
}

.image-preview-modern img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: calc(var(--radius) - 2px);
    box-shadow: var(--shadow-sm);
    border: 2px solid var(--white);
}

/* File Input */
.file-input-modern {
    display: block;
    width: 100%;
    padding: 0.625rem 0.75rem;
    font-size: 0.875rem;
    color: var(--gray-700);
    background: var(--white);
    border: 1px solid var(--gray-300);
    border-radius: var(--radius);
    transition: all 0.2s ease;
}

.file-input-modern:hover {
    border-color: var(--primary);
}

.file-input-modern:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.file-input-modern::file-selector-button {
    padding: 0.5rem 1rem;
    margin-right: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--white);
    background: var(--primary);
    border: none;
    border-radius: calc(var(--radius) - 2px);
    cursor: pointer;
    transition: background 0.2s ease;
}

.file-input-modern::file-selector-button:hover {
    background: var(--primary-light);
}

/* Select Input */
.select-modern {
    display: block;
    width: 100%;
    padding: 0.625rem 0.75rem;
    font-size: 0.875rem;
    color: var(--gray-700);
    background: var(--white);
    border: 1px solid var(--gray-300);
    border-radius: var(--radius);
    transition: all 0.2s ease;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20' stroke='%234b5563'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.25rem;
}

.select-modern:hover {
    border-color: var(--primary);
}

.select-modern:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Inheritance Badge */
.inherit-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    margin-bottom: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--primary);
    background: var(--primary-lighter);
    border-radius: 2rem;
    letter-spacing: 0.025em;
    text-transform: uppercase;
}

.inherit-badge svg {
    width: 1rem;
    height: 1rem;
    margin-right: 0.375rem;
}

/* Language Label Badge */
.language-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--gray-600);
    background: var(--gray-100);
    border-radius: 0.375rem;
    margin-left: 0.5rem;
}

/* Grid Layout */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* Responsive */
@media (max-width: 640px) {
    .language-tabs .nav-link {
        padding: 0.5rem 0.875rem;
        font-size: 0.8125rem;
    }
    
    .tab-content-modern {
        padding: 1rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>


<div class="language-manager">
    {{-- Modern Tabs --}}
    <ul class="language-tabs" role="tablist">
        @foreach ($languages as $code => $label)
            <li class="nav-item">
                <a class="nav-link {{ $code === $selectedLang ? 'active' : '' }}"
                   href="#lang-{{ $code }}"
                   data-toggle="tab"
                   data-lang="{{ $code }}">
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content-modern">
        @foreach ($languages as $code => $label)
            @php
                $imageData = $badgeImages[$code] ?? null;
                $defaultImageData = $badgeImages['default'] ?? null;

                // Show image / presentation
                $showImagePath = $imageData?->show_image ?? $defaultImageData?->show_image ?? '';
                $presentationImagePath = $imageData?->image ?? $defaultImageData?->image ?? '';
                $imageType = $imageData?->image_type ?? $defaultImageData?->image_type ?? '';
            @endphp

            <div class="tab-pane {{ $code === $selectedLang ? 'active' : '' }}"
                 id="lang-{{ $code }}"
                 data-lang="{{ $code }}"
                 data-overridden="false"
                 style="{{ $code !== 'default' ? 'position: relative;' : '' }}">

                {{-- Inheritance indicator --}}
                @if($code !== 'default')
                    <div class="inherit-container">
                        @if(empty($imageData?->image) && !empty($defaultImageData?->image))
                            <div class="inherit-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-5m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ __('Inheriting from default') }}
                            </div>
                        @endif
                    </div>
                @endif

                <div class="form-grid">
                    {{-- Default Image --}}
                    <div class="form-group-modern">
                        <label>
                            {{ __('Default Image') }}
                            <span class="language-badge">{{ $label }}</span>
                        </label>
                        <div class="image-preview-modern">
                            {!! handleShowImageWithTypes(
                                $code.'_default',
                                getImagePath($showImagePath),
                                100,
                                100,
                                4,
                                'contain'
                            ) !!}
                        </div>
                        <input type="file"
                               name="images[{{ $code }}][default_image]"
                               class="file-input-modern file-input"
                               data-lang="{{ $code }}">
                    </div>

                    {{-- Presentation File --}}
                    <div class="form-group-modern">
                        <label>
                            {{ __('Presentation file') }}
                            <span class="language-badge">{{ $label }}</span>
                        </label>
                        <div class="image-preview-modern">
                            {!! handleShowImageWithTypes(
                                $code.'_presentation',
                                getImagePath($presentationImagePath),
                                100,
                                100,
                                4,
                                'contain'
                            ) !!}
                        </div>
                        <input type="file"
                               name="images[{{ $code }}][image]"
                               class="file-input-modern file-input"
                               data-lang="{{ $code }}">
                    </div>
                </div>

                {{-- Image Type --}}
                <div class="form-group-modern" style="margin-top: 0.5rem;">
                    <label>
                        {{ __('Image Type') }}
                        <span class="language-badge">{{ $label }}</span>
                    </label>
                    <select name="images[{{ $code }}][image_type]"
                            class="select-modern image-type"
                            data-lang="{{ $code }}"
                            required>
                        @foreach(\App\Enums\ImageType::options() as $key => $val)
                            <option value="{{ $key }}"
                                {{ $imageType == $key ? 'selected' : '' }}>
                                {{ $val }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Enhanced JS for inheritance --}}
<script>
$(function() {
    'use strict';

    const DEFAULT = 'default';
    const INHERIT_BADGE_TEMPLATE = `
        <div class="inherit-badge">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-5m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ __('Inheriting from default') }}
        </div>
    `;

    function applyInheritance() {
        const defaultType = $('select[data-lang="default"]').val();
        const defaultShowImage = $('div#lang-default .form-group-modern').eq(0).find('img').attr('src');
        const defaultPresentationImage = $('div#lang-default .form-group-modern').eq(1).find('img').attr('src');

        $('.tab-pane').each(function () {
            const pane = $(this);
            const lang = pane.data('lang');
            if(lang === DEFAULT) return;

            const overridden = pane.data('overridden');
            if(!overridden) {
                const defaultImgExists = defaultShowImage || defaultPresentationImage;
                if(defaultImgExists){
                    pane.find('.inherit-container').html(`
                        <input type="hidden" name="images[${lang}][inherit]" value="1">
                        ${INHERIT_BADGE_TEMPLATE}
                    `);
                    pane.find('.image-type').val(defaultType).trigger('change.select2');
                    if(!pane.find('.form-group-modern').eq(0).find('img').attr('src')){
                        pane.find('.form-group-modern').eq(0).find('img').attr('src', defaultShowImage);
                    }
                    if(!pane.find('.form-group-modern').eq(1).find('img').attr('src')){
                        pane.find('.form-group-modern').eq(1).find('img').attr('src', defaultPresentationImage);
                    }
                }
            }
        });
    }

    function createImagePreview(input, imgElement) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imgElement.attr('src', e.target.result);
                applyInheritance();
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    $('select[data-lang="default"]').on('change', applyInheritance);
    $('div#lang-default input[type="file"]').on('change', function(e) {
        const img = $(this).closest('.form-group-modern').find('img');
        createImagePreview(this, img);
    });

    $('.file-input').on('change', function() {
        const pane = $(this).closest('.tab-pane');
        const lang = pane.data('lang');
        if(lang !== DEFAULT) {
            pane.data('overridden', true);
            pane.find('.inherit-container').empty();
            const img = $(this).closest('.form-group-modern').find('img');
            createImagePreview(this, img);
        }
    });

    applyInheritance();

    $('.language-tabs .nav-link').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        $('.language-tabs .nav-link').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane').removeClass('active');
        $(target).addClass('active');
    });
});
</script>
