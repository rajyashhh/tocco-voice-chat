@csrf

{{-- Form Template Info --}}
<div class="mb-8 p-6 rounded-lg border-2  ">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">{{ __('Template Information') }}</h2>
        <button type="button" onclick="toggleSection(this)" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-chevron-up"></i>
        </button>
    </div>

    <div class="section-content">
        {{-- Title Fields for All Languages --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">{{ __('Form Title') }} *</h3>
            <div class="grid grid-cols-1 md:grid-cols-{{ count(config('app.available_locales')) }} gap-4">
                @foreach(config('app.available_locales') as $locale)
                <div class="relative">
                    <div class="absolute top-0 right-0 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">{{ strtoupper($locale) }}</div>
                    <input type="text" 
                           name="title[{{ $locale }}]" 
                           {{ $locale === 'ar' ? 'dir=rtl' : '' }}
                           required
                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                           placeholder="{{ __('Form Title') }}"
                           value="{{ old('title.'.$locale, $template->getTranslation('title', $locale)) }}">
                </div>
                @endforeach
            </div>
        </div>

        {{-- Form Type --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold mb-2">{{ __('Form Type') }} *</label>
            <input type="text" name="form_type" required
                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                placeholder="agency" value="{{ old('form_type', $template->form_type) }}">
        </div>

        {{-- Description Fields for All Languages --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">{{ __('Description') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-{{ count(config('app.available_locales')) }} gap-4">
                @foreach(config('app.available_locales') as $locale)
                <div class="relative">
                    <div class="absolute top-0 right-0 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">{{ strtoupper($locale) }}</div>
                    <textarea name="description[{{ $locale }}]" 
                            rows="3" 
                            {{ $locale === 'ar' ? 'dir=rtl' : '' }}
                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                            placeholder="{{ __('Form Description') }}">{{ old('description.'.$locale, $template->getTranslation('description', $locale)) }}</textarea>
                </div>
                @endforeach
            </div>
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
    <a href="{{ admin_url('form-templates') }}" 
       class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
        {{ __('Cancel') }}
    </a>
    <button type="submit" 
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-lg">
        <i class="fas fa-save {{ app()->getLocale() == 'ar' ? 'ml-2' : 'mr-2' }}"></i>
        {{ $buttonText ?? __('Save') }}
    </button>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
let sectionCount = {{ $template->sections->count() > 0 ? $template->sections->max('section_order') : 0 }};
let fieldCounts = {};
const availableLocales = @json(config('app.available_locales'));
const existingSections = @json($template->sections->keyBy('section_order'));

document.addEventListener('DOMContentLoaded', function() {
    // Enable sorting for sections
    new Sortable(document.getElementById('sections'), {
        animation: 150,
        handle: '.section-drag-handle',
        onEnd: updateSectionOrders
    });

    if (Object.keys(existingSections).length > 0) {
        for (const order in existingSections) {
            addSection(existingSections[order]);
        }
    } else {
        addSection();
    }
});

function updateSectionOrders() {
    document.querySelectorAll('.section-item').forEach((section, index) => {
        section.querySelector('input[name$="[order]"]').value = index + 1;
    });
}

function updateFieldOrders(sectionId) {
    document.querySelectorAll(`#section-${sectionId}-fields .field-item`).forEach((field, index) => {
        field.querySelector('input[name$="[order]"]').value = index + 1;
    });
}

function toggleSection(button) {
    const content = button.closest('.section-item, .field-item, .mb-8').querySelector('.section-content');
    const icon = button.querySelector('i');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        content.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

function addSection(data = null) {
    if (data) {
        sectionCount = data.section_order;
    } else {
        sectionCount++;
    }
    
    fieldCounts[sectionCount] = data ? data.fields.length : 0;

    const sectionHtml = `
        <div class="section-item mb-6 p-6   rounded-lg border-2 border-blue-300" data-section="${sectionCount}">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center">
                    <span class="section-drag-handle cursor-move px-2">
                        <i class="fas fa-grip-vertical text-gray-500"></i>
                    </span>
                    <h3 class="text-lg font-bold text-blue-800">{{ __('Section') }} #${sectionCount}</h3>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="toggleSection(this)" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <button type="button" onclick="removeSection(${sectionCount})" 
                            class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            <div class="section-content">
                <div class="mb-4">
                    <h4 class="text-md font-semibold mb-3">{{ __('Section Title') }} *</h4>
                    <div class="grid grid-cols-1 md:grid-cols-\${availableLocales.length} gap-4">
                        \${availableLocales.map(locale => `
                            <div class="relative">
                                <div class="absolute top-0 right-0 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">\${locale.toUpperCase()}</div>
                                <input type="text" 
                                       name="sections[\${sectionCount}][title][\${locale}]" 
                                       \${locale === 'ar' ? 'dir="rtl"' : ''} 
                                       required
                                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                                       placeholder="{{ __('Section Title') }}" 
                                       value="\${data ? (data.title[locale] || '') : ''}">
                            </div>
                        `).join('')}
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
        </div>
    `;

    document.getElementById('sections').insertAdjacentHTML('beforeend', sectionHtml);
    
    // Initialize sorting for fields
    new Sortable(document.getElementById(`section-${sectionCount}-fields`), {
        animation: 150,
        handle: '.field-drag-handle',
        onEnd: () => updateFieldOrders(sectionCount)
    });
    
    if (data && data.fields) {
        data.fields.forEach(fieldData => {
            addField(sectionCount, fieldData);
        });
    }
}

function removeSection(sectionId) {
    document.querySelector(`[data-section="${sectionId}"]`).remove();
    updateSectionOrders();
}

function addField(sectionId, data = null) {
    if (data) {
         fieldCounts[sectionId] = data.field_order;
    } else {
        fieldCounts[sectionId]++;
    }
    const fieldId = fieldCounts[sectionId];

    const fieldHtml = `
        <div class="field-item  p-4 rounded-lg border" data-field="${sectionId}-${fieldId}">
            <div class="flex justify-between items-center mb-3">
                <div class="flex items-center">
                    <span class="field-drag-handle cursor-move px-2">
                        <i class="fas fa-grip-vertical text-gray-400"></i>
                    </span>
                    <h4 class="font-semibold text-gray-700">{{ __('Field') }} #${fieldId}</h4>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="toggleSection(this)" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <button type="button" onclick="removeField(${sectionId}, ${fieldId})" 
                            class="text-red-600 hover:text-red-800 text-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="section-content">
                {{-- Label Fields for All Languages --}}
                <div class="mb-4">
                    <h5 class="text-sm font-semibold mb-2">{{ __('Field Label') }} *</h5>
                    <div class="grid grid-cols-1 md:grid-cols-\${availableLocales.length} gap-3">
                        \${availableLocales.map(locale => `
                            <div class="relative">
                                <div class="absolute top-0 right-0 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">\${locale.toUpperCase()}</div>
                                <input type="text" 
                                       name="sections[\${sectionId}][fields][\${fieldId}][label][\${locale}]" 
                                       \${locale === 'ar' ? 'dir="rtl"' : ''} 
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm"
                                       placeholder="{{ __('Field Label') }}" 
                                       value="\${data ? (data.field_label[locale] || '') : ''}">
                            </div>
                        `).join('')}
                    </div>
                </div>

                {{-- Field Properties --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('Field Name') }} *</label>
                        <input type="text" name="sections[${sectionId}][fields][${fieldId}][name]" required
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm"
                            placeholder="full_name" value="${data ? data.field_name : ''}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('Field Type') }} *</label>
                        <select name="sections[${sectionId}][fields][${fieldId}][type]" required
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                            <option value="text" ${data && data.field_type === 'text' ? 'selected' : ''}>{{ __('Text') }}</option>
                            <option value="email" ${data && data.field_type === 'email' ? 'selected' : ''}>{{ __('Email') }}</option>
                            <option value="number" ${data && data.field_type === 'number' ? 'selected' : ''}>{{ __('Number') }}</option>
                            <option value="tel" ${data && data.field_type === 'tel' ? 'selected' : ''}>{{ __('Phone') }}</option>
                            <option value="date" ${data && data.field_type === 'date' ? 'selected' : ''}>{{ __('Date') }}</option>
                            <option value="textarea" ${data && data.field_type === 'textarea' ? 'selected' : ''}>{{ __('Textarea') }}</option>
                            <option value="file" ${data && data.field_type === 'file' ? 'selected' : ''}>{{ __('File') }}</option>
                            <option value="select" ${data && data.field_type === 'select' ? 'selected' : ''}>{{ __('Select') }}</option>
                            <option value="checkbox" ${data && data.field_type === 'checkbox' ? 'selected' : ''}>{{ __('Checkbox') }}</option>
                            <option value="radio" ${data && data.field_type === 'radio' ? 'selected' : ''}>{{ __('Radio') }}</option>
                        </select>
                    </div>
                </div>

                {{-- Placeholder Fields for All Languages --}}
                <div class="mb-4">
                    <h5 class="text-sm font-semibold mb-2">{{ __('Placeholder Text') }}</h5>
                    <div class="grid grid-cols-1 md:grid-cols-\${availableLocales.length} gap-3">
                        \${availableLocales.map(locale => `
                            <div class="relative">
                                <div class="absolute top-0 right-0 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">\${locale.toUpperCase()}</div>
                                <input type="text" 
                                       name="sections[\${sectionId}][fields][\${fieldId}][placeholder][\${locale}]" 
                                       \${locale === 'ar' ? 'dir="rtl"' : ''} 
                                       class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm"
                                       placeholder="{{ __('Field Placeholder') }}" 
                                       value="\${data ? (data.placeholder ? data.placeholder[locale] : '') : ''}">
                            </div>
                        `).join('')}
                    </div>
                </div>

                {{-- Field Options --}}
                <div class="flex items-center space-x-4">
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="sections[${sectionId}][fields][${fieldId}][required]" value="1" ${data && data.is_required ? 'checked' : ''} class="mr-2">
                        {{ __('Required') }}
                    </label>
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="sections[${sectionId}][fields][${fieldId}][enabled]" value="1" ${data && data.is_enabled ? 'checked' : ''} class="mr-2">
                        {{ __('Enabled') }}
                    </label>
                </div>

                <input type="hidden" name="sections[${sectionId}][fields][${fieldId}][order]" value="${fieldId}">
            </div>
        </div>
    `;

    document.getElementById(`section-${sectionId}-fields`).insertAdjacentHTML('beforeend', fieldHtml);
}

function removeField(sectionId, fieldId) {
    document.querySelector(`[data-field="${sectionId}-${fieldId}"]`).remove();
    updateFieldOrders(sectionId);
}
</script>
@endpush