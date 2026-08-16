{{-- Modern Standard Form Input Component - Mobile Optimized --}}
@php
    $fieldId = $field->id ?? uniqid('field_');
    $fieldName = $name ?? ($field->field_name ?? 'field');
    $fieldType = $field->field_type ?? 'text';
    $required = $field->is_required ?? false;
    $placeholder = $field->getTranslation('placeholder', app()->getLocale()) ?? '';
    $helpText = $field->getTranslation('help_text', app()->getLocale()) ?? '';
    $value = $value ?? '';
@endphp

<div class="form-field-modern transition-all duration-200">
    {{-- Label --}}
    <label for="field-{{ $fieldId }}" class="block text-sm font-semibold text-gray-700 mb-2">
        <span class="flex items-center">
            {{ $field->getTranslation('field_label', app()->getLocale()) }}
            @if($required)
                <span class="ml-1 text-red-500 text-base">*</span>
                <span class="ml-2 text-xs font-normal text-gray-500 bg-red-50 px-2 py-0.5 rounded-full">{{ __('Required') }}</span>
            @endif
        </span>
    </label>

    @if($helpText)
        <p class="text-xs text-gray-500 mb-3 flex items-start">
            <i class="fas fa-info-circle mr-1.5 mt-0.5 text-blue-500"></i>
            <span>{{ $helpText }}</span>
        </p>
    @endif

    @if($fieldType === 'textarea')
        {{-- Textarea with character counter --}}
        <div class="relative">
            <textarea 
                id="field-{{ $fieldId }}"
                name="{{ $fieldName }}"
                {{ $required ? 'required' : '' }}
                rows="5"
                maxlength="1000"
                class="w-full px-4 py-3 border-2   rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-200 resize-none"
                placeholder="{{ $placeholder }}"
                oninput="updateCharCount(this, 1000)"
            >{{ old($fieldName, $value) }}</textarea>
            <div class="absolute bottom-2 right-3 text-xs text-gray-400">
                <span class="char-count">0</span>/1000
            </div>
        </div>

    @elseif($fieldType === 'select')
        {{-- Modern Select with icon --}}
        <div class="relative">
            <select 
                id="field-{{ $fieldId }}"
                name="{{ $fieldName }}"
                {{ $required ? 'required' : '' }}
                class="w-full px-4 py-3 pr-10 border-2   rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-200 appearance-none bg-white"
                @if(isset($field->data_source)) 
                    data-source="{{ $field->data_source }}" 
                    data-load-on-init="true"
                @endif>
                <option value="">{{ __('Select an option') }}</option>
                @if($field->options && !isset($field->data_source))
                    @foreach($field->options as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" {{ old($fieldName, $value) == $optionValue ? 'selected' : '' }}>
                            {{ is_array($optionLabel) ? ($optionLabel[app()->getLocale()] ?? $optionValue) : $optionLabel }}
                        </option>
                    @endforeach
                @endif
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <i class="fas fa-chevron-down text-gray-400 select-icon"></i>
                <i class="fas fa-spinner fa-spin text-gray-400 select-loader" style="display: none;"></i>
            </div>
        </div>

    @elseif($fieldType === 'checkbox' || $fieldType === 'radio')
        {{-- Modern Checkbox/Radio with better styling --}}
        <div class="space-y-3">
            @if($field->options)
                @foreach($field->options as $optionValue => $optionLabel)
                    <label class="flex items-center p-3 border-2   rounded-xl hover:border-blue-300 hover:  cursor-pointer transition-all duration-200 group">
                        <input 
                            type="{{ $fieldType }}"
                            name="{{ $fieldName }}{{ $fieldType === 'checkbox' ? '[]' : '' }}"
                            value="{{ $optionValue }}"
                            {{ is_array(old($fieldName, $value)) ? (in_array($optionValue, old($fieldName, $value)) ? 'checked' : '') : (old($fieldName, $value) == $optionValue ? 'checked' : '') }}
                            class="w-5 h-5 text-blue-600 border-2 border-gray-300 {{ $fieldType === 'radio' ? 'rounded-full' : 'rounded' }} focus:ring-4 focus:ring-blue-100 transition-all">
                        <span class="ml-3 text-sm text-gray-700 font-medium group-hover:text-blue-700">
                            {{ is_array($optionLabel) ? ($optionLabel[app()->getLocale()] ?? $optionValue) : $optionLabel }}
                        </span>
                    </label>
                @endforeach
            @endif
        </div>

    @elseif($fieldType === 'file')
        {{-- Modern File Upload with drag & drop --}}
        <div class="file-upload-wrapper">
            <input 
                type="file"
                id="field-{{ $fieldId }}"
                name="{{ $fieldName }}"
                {{ $required ? 'required' : '' }}
                class="hidden"
                onchange="handleFileSelect(this)"
                accept="image/*,.pdf,.doc,.docx">
            
            <label for="field-{{ $fieldId }}" class="flex flex-col items-center justify-center w-full border-2 border-dashed border-gray-300 rounded-xl p-6 sm:p-8 cursor-pointer hover:border-blue-500 hover:  transition-all duration-200 group">
                <div class="file-preview mb-3" id="preview-{{ $fieldId }}" style="display: none;">
                    <img class="preview-image max-h-32 rounded-lg shadow-md" alt="Preview">
                </div>
                
                <div class="upload-icon mb-3 text-blue-500 group-hover:text-blue-600 transition-colors">
                    <i class="fas fa-cloud-upload-alt text-4xl sm:text-5xl"></i>
                </div>
                
                <div class="upload-text text-center">
                    <p class="text-sm sm:text-base font-semibold text-gray-700 mb-1">
                        <span class="text-blue-600">{{ __('Tap to upload') }}</span>
                        <span class="hidden sm:inline">{{ __(' or drag and drop') }}</span>
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ __('PDF, DOC, DOCX, JPG, PNG (Max 10MB)') }}
                    </p>
                </div>
                
                <div class="file-info mt-3 hidden">
                    <div class="flex items-center bg-white rounded-lg px-4 py-2 shadow-sm border  ">
                        <i class="fas fa-file-alt text-blue-500 mr-2"></i>
                        <span class="file-name text-sm text-gray-700 font-medium"></span>
                        <span class="file-size text-xs text-gray-500 ml-2"></span>
                    </div>
                </div>
            </label>
        </div>

    @elseif($fieldType === 'email')
        {{-- Email Input with icon --}}
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fas fa-envelope text-gray-400"></i>
            </div>
            <input 
                type="email"
                id="field-{{ $fieldId }}"
                name="{{ $fieldName }}"
                value="{{ old($fieldName, $value) }}"
                {{ $required ? 'required' : '' }}
                class="w-full pl-11 pr-4 py-3 border-2   rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-200"
                placeholder="{{ $placeholder }}">
        </div>

    @elseif($fieldType === 'tel' || $fieldType === 'phone')
        {{-- Phone Input with icon --}}
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fas fa-phone text-gray-400"></i>
            </div>
            <input 
                type="tel"
                id="field-{{ $fieldId }}"
                name="{{ $fieldName }}"
                value="{{ old($fieldName, $value) }}"
                {{ $required ? 'required' : '' }}
                class="w-full pl-11 pr-4 py-3 border-2   rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-200"
                placeholder="{{ $placeholder }}">
        </div>

    @elseif($fieldType === 'date')
        {{-- Date Input with icon --}}
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fas fa-calendar text-gray-400"></i>
            </div>
            <input 
                type="date"
                id="field-{{ $fieldId }}"
                name="{{ $fieldName }}"
                value="{{ old($fieldName, $value) }}"
                {{ $required ? 'required' : '' }}
                class="w-full pl-11 pr-4 py-3 border-2   rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-200">
        </div>

    @elseif($fieldType === 'number')
        {{-- Number Input --}}
        <input 
            type="number"
            id="field-{{ $fieldId }}"
            name="{{ $fieldName }}"
            value="{{ old($fieldName, $value) }}"
            {{ $required ? 'required' : '' }}
            class="w-full px-4 py-3 border-2   rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-200"
            placeholder="{{ $placeholder }}">

    @else
        {{-- Default Text Input --}}
        <input 
            type="{{ $fieldType }}"
            id="field-{{ $fieldId }}"
            name="{{ $fieldName }}"
            value="{{ old($fieldName, $value) }}"
            {{ $required ? 'required' : '' }}
            class="w-full px-4 py-3 border-2   rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-200"
            placeholder="{{ $placeholder }}">
    @endif
</div>

@once
@push('scripts')
<script>
// Character counter for textarea
function updateCharCount(textarea, maxLength) {
    const count = textarea.value.length;
    const counter = textarea.parentElement.querySelector('.char-count');
    if (counter) {
        counter.textContent = count;
        if (count > maxLength * 0.9) {
            counter.classList.add('text-red-500', 'font-bold');
        } else {
            counter.classList.remove('text-red-500', 'font-bold');
        }
    }
}

// File upload handler
function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    const wrapper = input.closest('.file-upload-wrapper');
    const preview = wrapper.querySelector('.file-preview');
    const previewImg = wrapper.querySelector('.preview-image');
    const uploadIcon = wrapper.querySelector('.upload-icon');
    const uploadText = wrapper.querySelector('.upload-text');
    const fileInfo = wrapper.querySelector('.file-info');
    const fileName = wrapper.querySelector('.file-name');
    const fileSize = wrapper.querySelector('.file-size');
    
    // Show file info
    if (fileName) fileName.textContent = file.name;
    if (fileSize) fileSize.textContent = formatFileSize(file.size);
    if (fileInfo) fileInfo.classList.remove('hidden');
    
    // Show preview for images
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (previewImg) previewImg.src = e.target.result;
            if (preview) preview.style.display = 'block';
            if (uploadIcon) uploadIcon.style.display = 'none';
            if (uploadText) uploadText.style.display = 'none';
        };
        reader.readAsDataURL(file);
    } else {
        if (uploadIcon) uploadIcon.style.display = 'none';
        if (uploadText) uploadText.style.display = 'none';
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Load predefined data for select fields
async function loadPredefinedData(selectElement) {
    const source = selectElement.dataset.source;
    if (!source) return;

    const wrapper = selectElement.parentElement;
    const loader = wrapper.querySelector('.select-loader');
    const icon = wrapper.querySelector('.select-icon');
    
    // Show loader
    if (loader) loader.style.display = 'inline-block';
    if (icon) icon.style.display = 'none';
    
    try {
        const locale = document.documentElement.lang || 'en';
        const response = await fetch(`/api/data-sources/${source}?locale=${locale}`);
        
        if (!response.ok) {
            throw new Error('Failed to load data');
        }
        
        const options = await response.json();
        
        // Clear existing options except the first one
        while (selectElement.options.length > 1) {
            selectElement.remove(1);
        }
        
        // Add new options
        options.forEach(option => {
            const optElement = document.createElement('option');
            optElement.value = option.value;
            optElement.textContent = option.label;
            selectElement.appendChild(optElement);
        });
        
    } catch (error) {
        console.error('Error loading predefined data:', error);
        // Show error message
        const errorOption = document.createElement('option');
        errorOption.textContent = '{{ __("Error loading options") }}';
        errorOption.disabled = true;
        selectElement.appendChild(errorOption);
    } finally {
        // Hide loader
        if (loader) loader.style.display = 'none';
        if (icon) icon.style.display = 'inline-block';
    }
}

// Initialize character counters and predefined data on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize character counters
    document.querySelectorAll('textarea[maxlength]').forEach(function(textarea) {
        const maxLength = parseInt(textarea.getAttribute('maxlength'));
        updateCharCount(textarea, maxLength);
    });
    
    // Load predefined data for select fields
    document.querySelectorAll('select[data-load-on-init]').forEach(function(select) {
        loadPredefinedData(select);
    });
});
</script>
@endpush
@endonce
