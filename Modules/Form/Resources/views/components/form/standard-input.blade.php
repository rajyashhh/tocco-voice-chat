{{-- Standard Form Input Component --}}
@php
    $fieldId = $field->id ?? uniqid('field_');
    $fieldName = $name ?? ($field->field_name ?? 'field');
    $fieldType = $field->field_type ?? 'text';
    $required = $field->is_required ?? false;
    $placeholder = $field->getTranslation('placeholder', app()->getLocale()) ?? '';
    $helpText = $field->getTranslation('help_text', app()->getLocale()) ?? '';
    $value = $value ?? '';
    $extraAttributes = $attributes ?? [];
@endphp

<div class="form-field mb-4">
    <label for="field-{{ $fieldId }}" class="block text-sm font-semibold mb-2">
        {{ $field->getTranslation('field_label', app()->getLocale()) }}
        @if($required)
            <span class="text-red-600">*</span>
        @endif
    </label>

    @if($helpText)
        <p class="text-xs text-gray-600 mb-2">{{ $helpText }}</p>
    @endif

    @if($fieldType === 'textarea')
        <textarea 
            id="field-{{ $fieldId }}"
            name="{{ $fieldName }}"
            {{ $required ? 'required' : '' }}
            rows="4"
            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
            placeholder="{{ $placeholder }}"
        >{{ old($fieldName, $value) }}</textarea>

    @elseif($fieldType === 'select')
        <select 
            id="field-{{ $fieldId }}"
            name="{{ $fieldName }}"
            {{ $required ? 'required' : '' }}
            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
            <option value="">{{ __('Select an option') }}</option>
            @if($field->options)
                @foreach($field->options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" {{ old($fieldName, $value) == $optionValue ? 'selected' : '' }}>
                        {{ is_array($optionLabel) ? ($optionLabel[app()->getLocale()] ?? $optionValue) : $optionLabel }}
                    </option>
                @endforeach
            @endif
        </select>

    @elseif($fieldType === 'checkbox' || $fieldType === 'radio')
        <div class="space-y-2">
            @if($field->options)
                @foreach($field->options as $optionValue => $optionLabel)
                    <label class="flex items-center">
                        <input 
                            type="{{ $fieldType }}"
                            name="{{ $fieldName }}{{ $fieldType === 'checkbox' ? '[]' : '' }}"
                            value="{{ $optionValue }}"
                            {{ is_array(old($fieldName, $value)) ? (in_array($optionValue, old($fieldName, $value)) ? 'checked' : '') : (old($fieldName, $value) == $optionValue ? 'checked' : '') }}
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm">
                            {{ is_array($optionLabel) ? ($optionLabel[app()->getLocale()] ?? $optionValue) : $optionLabel }}
                        </span>
                    </label>
                @endforeach
            @endif
        </div>

    @elseif($fieldType === 'file')
        <input 
            type="file"
            id="field-{{ $fieldId }}"
            name="{{ $fieldName }}"
            {{ $required ? 'required' : '' }}
            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">

    @else
        <input 
            type="{{ $fieldType }}"
            id="field-{{ $fieldId }}"
            name="{{ $fieldName }}"
            value="{{ old($fieldName, $value) }}"
            {{ $required ? 'required' : '' }}
            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
            placeholder="{{ $placeholder }}">
    @endif
</div>
