@php
$currentLocale = $locale ?? app()->getLocale();
$direction = $currentLocale === 'ar' ? 'rtl' : 'ltr';
@endphp

<div class="form-preview-container" data-current-locale="{{ $currentLocale }}" dir="{{ $direction }}">
    
    {{-- Form Header --}}
@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'he', 'ur']);
@endphp
<style>

.body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* min-height: 100vh; */
    /* display: flex; */
    justify-content: center;
    align-items: center;
    padding: 20px;
    position: relative;
    overflow-x: hidden;
    border-radius: 8px;
}
.contenten {
    width: 89%;
    min-height: 250px;
    padding: 15px;
    margin: auto;
    background: rgba(255, 255, 255, 0.95);
    padding-left: 15px;
    padding-right: 15px;
    border-radius: 32px;
}

.custom-widget-container{
   width: 200%;

}

.custom-item {
   width: 200%;

}

.custom-item input {
    width: 95%;
    margin: 10px 0px;
}

@media (max-width: 768px) {
    .custom-widget-container{
    width: 100% ;

    }
    .custom-item {
        min-width: 0px !important;;

    }
  
}

</style>
 <div class=" body">
 <div class=" contenten">
 <div class=" ">
<div class=" form-header mb-5" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">

        {{-- عنوان النموذج --}}
        <h2 class="form-title mb-0 d-flex align-items-center text-primary fw-bold">
            <i class="fa fa-file-text {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
            {{ $template->getTranslation('title', app()->getLocale()) }}
        </h2>

        {{-- زر تعديل --}}
        <a href="{{ admin_url('form-templates/' . $template->id . '/edit') }}"
           class="btn btn-primary d-flex align-items-center mt-2 mt-md-0">
            <i class="fa fa-edit {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
            {{ __('Edit Form') }}
        </a>
    </div>

    <hr class="border-2 border-primary opacity-75">
</div>

        @if($template->getTranslation('description', $currentLocale))
            <div class="alert  border-0 shadow-sm">
                <div class="d-flex align-items-start">
                    <i class="fa fa-info-circle fa-lg me-3 mt-1 text-info"></i>
                    <div>
                        <h5 class="alert-heading mb-2">{{ __('Form Description') }}</h5>
                        <p class="mb-0" style="white-space: pre-line;">
                            {{ $template->getTranslation('description', $currentLocale) }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Form Sections --}}
    @foreach ($template->sections as $section)
        <div class="section-card mb-4">
            <div class="section-header">
                <i class="fa fa-folder-open me-2"></i>
                {{ $section->getTranslation('title', $currentLocale) }}
            </div>
            
            <div class="section-body">
                @if($section->description)
                    <p class="section-description">
                        {{ $section->getTranslation('description', $currentLocale) }}
                    </p>
                @endif

                <div class="row g-4">
                    @foreach ($section->fields as $field)
                        @if($field->is_enabled)
                            <div class="col-md-6">
                                <div class="field-group">
                                    <label class="field-label">
                                        {{ $field->getTranslation('field_label', $currentLocale) }}
                                        @if($field->is_required)
                                            <span class="text-danger ms-1">*</span>
                                        @endif
                                    </label>

                                    @php
                                        $placeholder = $field->getTranslation('placeholder', $currentLocale);
                                        $helpText = $field->help_text ? $field->getTranslation('help_text', $currentLocale) : null;
                                    @endphp

                                    {{-- Render Field Based on Type --}}
                                    @switch($field->field_type)
                                        
                                        {{-- Text Input --}}
                                        @case('text')
                                        @case('email')
                                        @case('number')
                                        @case('tel')
                                            <input 
                                                type="{{ $field->field_type }}"
                                                class="form-control preview-field"
                                                placeholder="{{ $placeholder }}"
                                                disabled>
                                            @break

                                        {{-- Date Input --}}
                                        @case('date')
                                            <input 
                                                type="date"
                                                class="form-control preview-field"
                                                disabled>
                                            @break

                                        {{-- Textarea --}}
                                        @case('textarea')
                                            <textarea 
                                                class="form-control preview-field" 
                                                rows="4" 
                                                placeholder="{{ $placeholder }}"
                                                disabled></textarea>
                                            @break

                                        {{-- File Upload --}}
                                        @case('file')
                                            <div class="file-upload-preview">
                                                <input type="file" class="form-control" disabled>
                                                <small class="text-muted mt-1 d-block">
                                                    <i class="fa fa-upload me-1"></i>
                                                    {{ __('Click to upload file') }}
                                                </small>
                                            </div>
                                            @break

                                        {{-- Select Dropdown --}}
                                        @case('select')
                                            @if(!empty($field->data_source))
                                                {{-- Predefined Data Source --}}
                                                <select class="form-select preview-field" disabled>
                                                    <option value="">-- {{ __('Select') }} --</option>
                                                    <option disabled>{{ __('Loading from') }}: {{ ucfirst($field->data_source) }}</option>
                                                </select>
                                                <small class="text-muted mt-1 d-block">
                                                    <i class="fa fa-database me-1"></i>
                                                    {{ __('Data Source') }}: <strong>{{ ucfirst($field->data_source) }}</strong>
                                                </small>
                                            @elseif(!empty($field->options))
                                                {{-- Custom Options --}}
                                                <select class="form-select preview-field" disabled>
                                                    <option value="">-- {{ __('Select') }} --</option>
                                                    @foreach ($field->options as $option)
                                                        @php
                                                            $label = $option['label'] ?? [];
                                                            $value = $option['value'] ?? '';
                                                            $displayLabel = is_array($label) 
                                                                ? ($label[$currentLocale] ?? $label['en'] ?? $value)
                                                                : $label;
                                                        @endphp
                                                        <option value="{{ $value }}">{{ $displayLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <select class="form-select preview-field" disabled>
                                                    <option value="">-- {{ __('No options available') }} --</option>
                                                </select>
                                            @endif
                                            @break

                                        {{-- Radio Buttons --}}
                                        @case('radio')
                                            <div class="radio-group">
                                                @if(!empty($field->data_source))
                                                    {{-- Predefined Data Source --}}
                                                    <div class="alert alert-secondary py-2 px-3 mb-0">
                                                        <i class="fa fa-database me-2"></i>
                                                        {{ __('Options loaded from') }}: <strong>{{ ucfirst($field->data_source) }}</strong>
                                                    </div>
                                                @elseif(!empty($field->options))
                                                    {{-- Custom Options --}}
                                                    @foreach ($field->options as $option)
                                                        @php
                                                            $label = $option['label'] ?? [];
                                                            $value = $option['value'] ?? '';
                                                            $displayLabel = is_array($label) 
                                                                ? ($label[$currentLocale] ?? $label['en'] ?? $value)
                                                                : $label;
                                                        @endphp
                                                        <div class="form-check">
                                                            <input 
                                                                type="radio" 
                                                                class="form-check-input" 
                                                                name="radio_{{ $field->id }}"
                                                                id="radio_{{ $field->id }}_{{ $loop->index }}"
                                                                disabled>
                                                            <label class="form-check-label" for="radio_{{ $field->id }}_{{ $loop->index }}">
                                                                {{ $displayLabel }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <p class="text-muted mb-0">{{ __('No options available') }}</p>
                                                @endif
                                            </div>
                                            @break

                                        {{-- Checkboxes --}}
                                        @case('checkbox')
                                            <div class="checkbox-group">
                                                @if(!empty($field->data_source))
                                                    {{-- Predefined Data Source --}}
                                                    <div class="alert alert-secondary py-2 px-3 mb-0">
                                                        <i class="fa fa-database me-2"></i>
                                                        {{ __('Options loaded from') }}: <strong>{{ ucfirst($field->data_source) }}</strong>
                                                    </div>
                                                @elseif(!empty($field->options))
                                                    {{-- Custom Options --}}
                                                    @foreach ($field->options as $option)
                                                        @php
                                                            $label = $option['label'] ?? [];
                                                            $value = $option['value'] ?? '';
                                                            $displayLabel = is_array($label) 
                                                                ? ($label[$currentLocale] ?? $label['en'] ?? $value)
                                                                : $label;
                                                        @endphp
                                                        <div class="form-check">
                                                            <input 
                                                                type="checkbox" 
                                                                class="form-check-input"
                                                                id="checkbox_{{ $field->id }}_{{ $loop->index }}"
                                                                disabled>
                                                            <label class="form-check-label" for="checkbox_{{ $field->id }}_{{ $loop->index }}">
                                                                {{ $displayLabel }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <p class="text-muted mb-0">{{ __('No options available') }}</p>
                                                @endif
                                            </div>
                                            @break

                                        {{-- Custom Widget --}}
                                        @case('custom')
    @php
        $widget = \Modules\Form\Entities\CustomFieldWidget::find($field->widget_id);
        $widgetName = $widget ? ($widget->widget_name[$currentLocale] ?? $widget->widget_name['en'] ?? $widget->widget_type) : 'Custom Widget';
        
        // Get widget config with fallback to widget default_config
        $widgetConfig = $field->widget_config ?? ($widget ? $widget->default_config : []);
        $fields = $widgetConfig['fields'] ?? [];
        $allowAddMore = $widgetConfig['allow_add_more'] ?? true;
        $minItems = $widgetConfig['min_items'] ?? 0;
        $maxItems = $widgetConfig['max_items'] ?? 10;
        
        // Fallback fields if empty (for preview purposes)
        if (empty($fields)) {
            $fields = [
                [
                    'name' => 'field_1',
                    'type' => 'text',
                    'placeholder' => [
                        'en' => 'Field 1',
                        'ar' => 'حقل 1'
                    ]
                ],
                [
                    'name' => 'field_2',
                    'type' => 'text',
                    'placeholder' => [
                        'en' => 'Field 2',
                        'ar' => 'حقل 2'
                    ]
                ]
            ];
        }
    @endphp
    
    <div class="custom-widget-container">
        {{-- Widget Info Header --}}
        <div class="alert border-0 mb-3 py-2 px-3">
            <div class="d-flex align-items-center">
                <i class="fa fa-puzzle-piece me-2 text-info"></i>
                <div>
                    <strong>{{ $widgetName }}</strong>
                    @if($widget && isset($widget->description))
                        <small class="d-block text-muted">
                            {{ is_array($widget->description) ? ($widget->description[$currentLocale] ?? $widget->description['en'] ?? '') : $widget->description }}
                        </small>
                    @endif
                </div>
            </div>
        </div>

        {{-- Custom Fields List --}}
        <div class="custom-field-list d-flex flex-wrap gap-2 align-items-start" 
             id="custom-list-{{ $section->id }}-{{ $field->id }}"
             data-widget-fields="{{ htmlspecialchars(json_encode($fields), ENT_QUOTES, 'UTF-8') }}"
             data-section-id="{{ $section->id }}"
             data-field-id="{{ $field->id }}">
            
            {{-- Example Items (2 examples for preview) --}}
            @for($i = 0; $i < 2; $i++)
    <div class="custom-item d-flex align-items-start gap-2 flex-wrap bg-light p-2 rounded border position-relative" 
         style="width: 100%; min-width: 250px;     display: flex !important;">
        
        @foreach($fields as $fieldConfig)
            @php
                $placeholder = '';
                if (isset($fieldConfig['placeholder'])) {
                    if (is_array($fieldConfig['placeholder'])) {
                        $placeholder = $fieldConfig['label'][$currentLocale] 
                            ?? $fieldConfig['label']['en'] 
                            ?? $fieldConfig['name'];
                    } else {
                        $placeholder = $fieldConfig['label'][$currentLocale];
                    }
                } else {
                    $placeholder = $fieldConfig['label'][$currentLocale]  ?? 'Field';
                }
            @endphp

            <div class="flex-shrink-0" style="flex: 0 0 48%;">
                <input 
                    type="{{ $fieldConfig['type'] ?? 'text' }}" 
                    class="form-control form-control-sm mb-1"
                    placeholder="{{ $placeholder }}"
                    disabled>
            </div>
        @endforeach

    </div>
@endfor

        </div>

        {{-- Add Button (if allowed) --}}
        @if($allowAddMore)
            <button type="button"
                    class="btn btn-sm btn-primary mt-3"
                    onclick="addCustomFieldPreview({{ $section->id }}, {{ $field->id }})"
                    style="background: #8b8be1; border-color: #8b8be1;">
                <i class="fa fa-plus me-1"></i>
                {{ __('Add') }}
            </button>
        @endif

        {{-- Widget Config Info --}}
        <div class="mt-2">
            <small class="text-muted d-block">
                <i class="fa fa-info-circle me-1"></i>
                @if($allowAddMore)
                    @if($maxItems > 0)
                        ({{ __('Max') }}: {{ $maxItems }})
                    @endif
                @else
                    {{ __('Single item only') }}
                @endif
            </small>
        </div>
    </div>
                                            @break

                                        {{-- Default/Unknown Type --}}
                                        @default
                                            <input 
                                                type="text"
                                                class="form-control preview-field"
                                                placeholder="{{ $placeholder }}"
                                                disabled>
                                    @endswitch

                                    {{-- Help Text --}}
                                    @if($helpText)
                                        <div class="field-help-text">
                                            <i class="fa fa-question-circle me-1"></i>
                                            {{ $helpText }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

 

</div>
</div>
</div>
</div>
<script>
// Custom Field Dynamic Addition for Preview
window.addCustomFieldPreview = function(sectionId, fieldId) {
    const list = document.getElementById(`custom-list-${sectionId}-${fieldId}`);
    
    if (!list) {
        console.error('Custom field list not found');
        return;
    }
    
    // Get widget fields configuration from data attribute
    const fieldsDataRaw = list.getAttribute('data-widget-fields');
    
    if (!fieldsDataRaw) {
        console.error('No fields configuration found in data attribute');
        return;
    }
    
    let fields = [];
    try {
        fields = JSON.parse(fieldsDataRaw);
    } catch (e) {
        console.error('Error parsing fields data:', e);
        return;
    }
    
    if (!fields || fields.length === 0) {
        console.error('Fields array is empty');
        return;
    }

    const index = list.children.length;
    const currentLocale = document.querySelector('.form-preview-container')?.getAttribute('data-current-locale') || 'en';

    let html = `<div class="custom-item d-flex align-items-start gap-2 flex-wrap bg-light p-2 rounded border position-relative" style="width: 100%; min-width: 250px;">`;

    fields.forEach(field => {
        let placeholder = '';
        
        if (field.placeholder) {
            if (typeof field.placeholder === 'object') {
                placeholder = field.placeholder[currentLocale] || field.placeholder['en'] || field.name || 'Field';
            } else {
                placeholder = field.placeholder;
            }
        } else {
            placeholder = field.name || 'Field';
        }
        
        const fieldType = field.type || 'text';
        
        html += `
            <div class="flex-grow-1" style="min-width: 41%;">
                <input type="${fieldType}" 
                    class="form-control form-control-sm mb-1"
                    placeholder="${escapeHtml(placeholder)}"
                    disabled>
            </div>
        `;
    });

    html += `
        <button type="button" 
                class="btn btn-sm btn-danger" 
                onclick="this.closest('.custom-item').remove()">
            <i class="fa fa-times"></i>
        </button>
    </div>`;

    list.insertAdjacentHTML('beforeend', html);

    // Add animation
    const newItem = list.lastElementChild;
    if (newItem) {
        newItem.style.opacity = '0';
        newItem.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            newItem.style.transition = 'all 0.3s ease';
            newItem.style.opacity = '1';
            newItem.style.transform = 'translateY(0)';
        }, 10);
    }
};

// Helper function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Debug function (remove after testing)
window.debugCustomWidget = function(sectionId, fieldId) {
    const list = document.getElementById(`custom-list-${sectionId}-${fieldId}`);
    console.log('List element:', list);
    console.log('Fields data attribute:', list?.getAttribute('data-widget-fields'));
    
    try {
        const fields = JSON.parse(list?.getAttribute('data-widget-fields'));
        console.log('Parsed fields:', fields);
    } catch (e) {
        console.error('Parse error:', e);
    }
};
</script>
<style>

.form-header {
    /* background: #f8fafc; */
    padding: 1.5rem;
    border-radius: 10px;
    border: 2px solid #cce0ff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.form-title {
    font-size: 1.6rem;
    color: #004085;
}

.btn-primary {
    background-color: #007bff !important;
    border-color: #007bff !important;
    font-weight: 500;
    padding: 0.6rem 1.2rem;
}

.btn-primary i {
    font-size: 1.1rem;
}

.btn-primary:hover {
    background-color: #0056b3 !important;
}

============================================ */
.back-btn{
    position: relative;
    right: 90%;
    margin: 10px;
}
/* ============================================
   Form Preview Container
============================================ */
.form-preview-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* ============================================
   Form Header
============================================ */
.form-header .form-title {
    color: #2c3e50;
    font-weight: 700;
    font-size: 2rem;
}

.form-header .alert-info {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 12px;
}

.form-header .alert-heading {
    color: #1976d2;
    font-weight: 600;
}

/* ============================================
   Section Cards
============================================ */
.section-card {
    margin: 20px 0px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.section-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
}

.section-header {
    /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
    /* color: #ffffff; */
    padding: 1.25rem 1.5rem;
    font-size: 1.25rem;
    font-weight: 600;
    border-bottom: 3px solid rgba(255, 255, 255, 0.2);
}

.section-body {
    padding: 2rem 1.5rem;
}

.section-description {
    color: #6c757d;
    margin-bottom: 1.5rem;
    padding: 0.75rem 1rem;
    background: #f8f9fa;
    border-left: 4px solid #667eea;
    border-radius: 6px;
}

/* ============================================
   Field Groups
============================================ */
.field-group {
    margin-bottom: 0;
}

.field-label {
    display: block;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.field-help-text {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: #6c757d;
    display: flex;
    align-items: start;
}

.field-help-text i {
    margin-top: 2px;
}

/* ============================================
   Form Controls (Preview Mode)
============================================ */
.preview-field,
.form-control:disabled,
.form-select:disabled {
    /* background-color: #f8f9fa; */
    border: 2px solid #e9ecef;
    color: #495057;
    cursor: not-allowed;
    border-radius: 8px;
    padding: 0.65rem 1rem;
    font-size: 0.95rem;
}

.preview-field:focus,
.form-control:disabled:focus {
    border-color: #dee2e6;
    box-shadow: none;
}

textarea.preview-field {
    resize: vertical;
    min-height: 100px;
}

/* ============================================
   Radio & Checkbox Groups
============================================ */
.radio-group,
.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.form-check {
    padding: 0.75rem 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.2s ease;
}

.form-check:hover {
    background: #e9ecef;
    border-color: #dee2e6;
}

.form-check-input {
    width: 1.25rem;
    height: 1.25rem;
    margin-top: 0.125rem;
    cursor: not-allowed;
}

.form-check-input:disabled {
    opacity: 0.6;
}

.form-check-label {
    font-size: 0.95rem;
    color: #495057;
    cursor: not-allowed;
    margin-left: 0.5rem;
}

/* ============================================
   Custom Widget Preview
============================================ */
.custom-widget-preview .alert {
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
}

.custom-widget-preview .list-group-item {
    border: 1px solid #e9ecef;
    border-radius: 6px;
    margin-bottom: 0.5rem;
}

.custom-widget-preview .widget-example-items {
    margin-top: 1rem;
}

/* ============================================
   File Upload Preview
============================================ */
.file-upload-preview input[type="file"] {
    cursor: not-allowed;
}

.file-upload-preview input[type="file"]::-webkit-file-upload-button {
    background: #6c757d;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: not-allowed;
    opacity: 0.6;
}

/* ============================================
   Badges & Alerts
============================================ */
.badge {
    padding: 0.4rem 0.8rem;
    font-weight: 600;
    border-radius: 6px;
}

.alert {
    border-radius: 10px;
}

.alert-secondary {
    /* background-color: #f8f9fa; */
    border: 2px dashed #dee2e6;
    color: #6c757d;
}

/* ============================================
   Responsive Design
============================================ */
@media (max-width: 768px) {
    .form-preview-container {
        padding: 1rem 0.5rem;
    }

    .form-header .form-title {
        font-size: 1.5rem;
    }

    .section-header {
        font-size: 1.1rem;
        padding: 1rem;
    }

    .section-body {
        padding: 1.5rem 1rem;
    }

    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

/* ============================================
   RTL Support
============================================ */
[dir="rtl"] .form-preview-container {
    text-align: right;
}

[dir="rtl"] .field-help-text i {
    margin-left: 0.5rem;
    margin-right: 0;
}

[dir="rtl"] .form-check-label {
    margin-right: 0.5rem;
    margin-left: 0;
}

/* ============================================
   Print Styles
============================================ */
@media print {
    .form-preview-container {
        max-width: 100%;
    }

    .section-card {
        box-shadow: none;
        border: 1px solid #dee2e6;
        page-break-inside: avoid;
    }

    .badge,
    .alert:last-child {
        display: none;
    }
}

/* ============================================
   Animation
============================================ */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.section-card {
    animation: fadeIn 0.5s ease-out;
}

.section-card:nth-child(1) { animation-delay: 0.1s; }
.section-card:nth-child(2) { animation-delay: 0.2s; }
.section-card:nth-child(3) { animation-delay: 0.3s; }
.section-card:nth-child(4) { animation-delay: 0.4s; }
</style>