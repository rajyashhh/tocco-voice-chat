@extends('Form::layouts.app')

@section('title', __('Create Form Template'))

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- <div class="mb-6">
        <a href="{{ route('form-templates.index') }}" class="text-blue-600 hover:underline">
            <i class="fas fa-arrow-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }} {{ app()->getLocale() == 'ar' ? 'ml-1' : 'mr-1' }}"></i>
            {{ __('Back') }}
        </a>
    </div> -->

    <div class=" rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">{{ __('Create New Form Template') }}</h1>

        <form id="formBuilder" action="{{ route('form-templates.store') }}" method="POST">
            @csrf

            {{-- Form Template Info --}}
            <div class="mb-8 p-6  rounded-lg border-2  ">
                <h2 class="text-xl font-bold mb-4">{{ __('Template Information') }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">{{ __('Title (Arabic)') }} *</label>
                        <input type="text" name="title[ar]" required
                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                               placeholder="نموذج تسجيل الوكالة">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">{{ __('Title (English)') }} *</label>
                        <input type="text" name="title[en]" required
                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                               placeholder="Agency Registration Form">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">{{ __('Form Type') }} *</label>
                    <input type="text" name="form_type" required
                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                           placeholder="agency">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">{{ __('Description (Arabic)') }}</label>
                        <textarea name="description[ar]" rows="3"
                                  class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                                  placeholder="وصف النموذج"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">{{ __('Description (English)') }}</label>
                        <textarea name="description[en]" rows="3"
                                  class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                                  placeholder="Form description"></textarea>
                    </div>
                </div>
            </div>

            {{-- Sections --}}
            <div id="sectionsContainer" class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">{{ __('Section') }}</h2>
                    <button type="button" onclick="addSection()" 
                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        <i class="fas fa-plus {{ app()->getLocale() == 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                        {{ __('Add Section') }}
                    </button>
                </div>
                <div id="sections"></div>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('form-templates.index') }}" 
                   class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-lg">
                    <i class="fas fa-save {{ app()->getLocale() == 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('Create Template') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let sectionCount = 0;
let fieldCounts = {};

function addSection() {
    sectionCount++;
    fieldCounts[sectionCount] = 0;

    const sectionHtml = `
        <div class="section-item mb-6 p-6   rounded-lg border-2 border-blue-300" data-section="${sectionCount}">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-bold text-blue-800">{{ __('Section') }} #${sectionCount}</h3>
                <button type="button" onclick="removeSection(${sectionCount})" 
                        class="text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i> {{ __('Remove') }}
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">{{ __('Section Title (Arabic)') }} *</label>
                    <input type="text" name="sections[${sectionCount}][title][ar]" required
                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                           placeholder="البيانات الشخصية">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">{{ __('Section Title (English)') }} *</label>
                    <input type="text" name="sections[${sectionCount}][title][en]" required
                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                           placeholder="Personal Information">
                </div>
            </div>

            <input type="hidden" name="sections[${sectionCount}][order]" value="${sectionCount}">

            <div class="mb-4">
                <button type="button" onclick="addField(${sectionCount})"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus"></i> {{ __('Add Field') }}
                </button>
            </div>

            <div id="section-${sectionCount}-fields" class="space-y-4"></div>
        </div>
    `;

    document.getElementById('sections').insertAdjacentHTML('beforeend', sectionHtml);
}

function removeSection(sectionId) {
    document.querySelector(`[data-section="${sectionId}"]`).remove();
}

function addField(sectionId) {
    fieldCounts[sectionId]++;
    const fieldId = fieldCounts[sectionId];

    const fieldHtml = `
        <div class="field-item  p-4 rounded-lg border" data-field="${sectionId}-${fieldId}">
            <div class="flex justify-between items-start mb-3">
                <h4 class="font-semibold text-gray-700">{{ __('Field') }} #${fieldId}</h4>
                <button type="button" onclick="removeField(${sectionId}, ${fieldId})" 
                        class="text-red-600 hover:text-red-800 text-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('Label (Arabic)') }} *</label>
                    <input type="text" name="sections[${sectionId}][fields][${fieldId}][label][ar]" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm"
                           placeholder="الاسم بالكامل">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('Label (English)') }} *</label>
                    <input type="text" name="sections[${sectionId}][fields][${fieldId}][label][en]" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm"
                           placeholder="Full Name">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('Field Name') }} *</label>
                    <input type="text" name="sections[${sectionId}][fields][${fieldId}][name]" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm"
                           placeholder="full_name">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('Field Type') }} *</label>
                    <select name="sections[${sectionId}][fields][${fieldId}][type]" required
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        <option value="text">{{ __('Text') }}</option>
                        <option value="email">{{ __('Email') }}</option>
                        <option value="number">{{ __('Number') }}</option>
                        <option value="tel">{{ __('Phone') }}</option>
                        <option value="date">{{ __('Date') }}</option>
                        <option value="textarea">{{ __('Textarea') }}</option>
                        <option value="file">{{ __('File') }}</option>
                        <option value="select">{{ __('Select') }}</option>
                        <option value="checkbox">{{ __('Checkbox') }}</option>
                        <option value="radio">{{ __('Radio') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('Placeholder (Arabic)') }}</label>
                    <input type="text" name="sections[${sectionId}][fields][${fieldId}][placeholder][ar]"
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm"
                           placeholder="أدخل الاسم">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('Placeholder (English)') }}</label>
                    <input type="text" name="sections[${sectionId}][fields][${fieldId}][placeholder][en]"
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm"
                           placeholder="Enter name">
                </div>
            </div>

            <div class="flex items-center space-x-4 mt-3">
                <label class="flex items-center text-sm">
                    <input type="checkbox" name="sections[${sectionId}][fields][${fieldId}][required]" value="1" class="mr-2">
                    {{ __('Required') }}
                </label>
                <label class="flex items-center text-sm">
                    <input type="checkbox" name="sections[${sectionId}][fields][${fieldId}][enabled]" value="1" checked class="mr-2">
                    {{ __('Enabled') }}
                </label>
            </div>

            <input type="hidden" name="sections[${sectionId}][fields][${fieldId}][order]" value="${fieldId}">
        </div>
    `;

    document.getElementById(`section-${sectionId}-fields`).insertAdjacentHTML('beforeend', fieldHtml);
}

function removeField(sectionId, fieldId) {
    document.querySelector(`[data-field="${sectionId}-${fieldId}"]`).remove();
}

// Add first section on load
document.addEventListener('DOMContentLoaded', function() {
    addSection();
});
</script>
@endpush
