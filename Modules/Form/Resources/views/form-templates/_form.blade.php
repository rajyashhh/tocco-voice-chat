@csrf

@php
    use KevinSoft\MultiLanguage\MultiLanguage;
    use Modules\Form\Entities\CustomFieldWidget;

    $locales = MultiLanguage::config('languages'); 

    $allLocaleNames = [
        'ar' => 'العربية',
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español',
        'de' => 'Deutsch',
        'hi' => 'हिन्दी',
    ];

    $localeCodes = array_keys($locales);

    $localeNames = array_intersect_key($allLocaleNames, array_flip($localeCodes));

    $availableWidgets = CustomFieldWidget::where('is_active', true)->get();
@endphp

<style>
    /* ========== Form Builder Design System ========== */
    :root {
        --fb-primary: #4f46e5;
        --fb-primary-light: #6366f1;
        --fb-primary-bg: #eef2ff;
        --fb-primary-border: #c7d2fe;
        --fb-success: #059669;
        --fb-success-light: #10b981;
        --fb-success-bg: #ecfdf5;
        --fb-success-border: #a7f3d0;
        --fb-danger: #dc2626;
        --fb-danger-light: #ef4444;
        --fb-danger-bg: #fef2f2;
        --fb-warning: #d97706;
        --fb-warning-bg: #fffbeb;
        --fb-purple: #7c3aed;
        --fb-purple-bg: #f5f3ff;
        --fb-purple-border: #ddd6fe;
        --fb-surface: #ffffff;
        --fb-bg: #f8fafc;
        --fb-border: #e2e8f0;
        --fb-border-light: #f1f5f9;
        --fb-text: #0f172a;
        --fb-text-secondary: #475569;
        --fb-text-muted: #94a3b8;
        --fb-radius: 16px;
        --fb-radius-sm: 12px;
        --fb-radius-xs: 8px;
        --fb-shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
        --fb-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px -1px rgba(0,0,0,0.1);
        --fb-shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
    }

    /* ===== Form Panel ===== */
    .form-panel {
        background: var(--fb-surface);
        border: 1px solid var(--fb-border);
        border-radius: var(--fb-radius);
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--fb-shadow-sm);
        transition: box-shadow 0.3s ease;
    }

    .form-panel:hover {
        box-shadow: var(--fb-shadow);
    }

    .form-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--fb-border-light);
    }

    .form-panel-header h2 {
        font-size: 1.25rem !important;
        font-weight: 800;
        color: var(--fb-text);
        display: flex;
        align-items: center;
        gap: 0.625rem;
        margin: 0;
    }

    .form-panel-header h2 .panel-icon {
        width: 36px;
        height: 36px;
        background: var(--fb-primary-bg);
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--fb-primary);
        font-size: 0.9375rem;
    }

    .toggle-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        border: 1px solid var(--fb-border);
        background: var(--fb-bg);
        color: var(--fb-text-secondary);
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.8125rem;
    }

    .toggle-btn:hover {
        background: var(--fb-primary-bg);
        color: var(--fb-primary);
        border-color: var(--fb-primary-border);
    }

    /* ===== Form Labels ===== */
    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 700;
        color: var(--fb-text);
        margin-bottom: 0.5rem;
    }

    .form-label.required::after {
        content: ' *';
        color: var(--fb-danger);
    }

    .form-sublabel {
        font-size: 0.8125rem !important;
        font-weight: 700;
        color: var(--fb-text);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* ===== Form Inputs ===== */
    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1.5px solid var(--fb-border);
        border-radius: var(--fb-radius-xs);
        font-size: 0.9375rem;
        color: var(--fb-text);
        background: var(--fb-surface);
        transition: all 0.2s;
        outline: none;
    }

    .form-input:focus {
        border-color: var(--fb-primary);
        box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
    }

    .form-input:read-only {
        background: var(--fb-bg);
        color: var(--fb-text-secondary);
        cursor: not-allowed;
    }

    .form-input::placeholder {
        color: var(--fb-text-muted);
    }

    .form-select {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1.5px solid var(--fb-border);
        border-radius: var(--fb-radius-xs);
        font-size: 0.9375rem;
        color: var(--fb-text);
        background: var(--fb-surface);
        transition: all 0.2s;
        outline: none;
        cursor: pointer;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.75rem center;
        background-repeat: no-repeat;
        background-size: 1.25em 1.25em;
        padding-right: 2.5rem;
    }

    [dir="rtl"] .form-select {
        background-position: left 0.75rem center;
        padding-right: 1rem;
        padding-left: 2.5rem;
    }

    .form-select:focus {
        border-color: var(--fb-primary);
        box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
    }

    .form-textarea {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1.5px solid var(--fb-border);
        border-radius: var(--fb-radius-xs);
        font-size: 0.9375rem;
        color: var(--fb-text);
        background: var(--fb-surface);
        transition: all 0.2s;
        outline: none;
        resize: vertical;
        min-height: 80px;
    }

    .form-textarea:focus {
        border-color: var(--fb-primary);
        box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
    }

    /* ===== Locale Badge ===== */
    .locale-badge {
        position: absolute;
        top: 6px;
        right: 6px;
        background: var(--fb-primary-bg);
        color: var(--fb-primary);
        font-size: 0.6875rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        z-index: 2;
        border: 1px solid var(--fb-primary-border);
    }

    [dir="rtl"] .locale-badge {
        right: auto;
        left: 6px;
    }

    .locale-input-wrap {
        position: relative;
    }

    .locale-input-wrap .form-input,
    .locale-input-wrap .form-textarea {
        padding-right: 4rem;
    }

    [dir="rtl"] .locale-input-wrap .form-input,
    [dir="rtl"] .locale-input-wrap .form-textarea {
        padding-right: 1rem;
        padding-left: 4rem;
    }

    /* ===== Section Item ===== */
    .section-item {
        background: var(--fb-surface);
        border: 1.5px solid #93c5fd;
        border-radius: var(--fb-radius);
        padding: 0;
        margin-bottom: 1.25rem;
        box-shadow: var(--fb-shadow-sm);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .section-item:hover {
        box-shadow: var(--fb-shadow);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border-bottom: 1px solid #bfdbfe;
    }

    .section-header-left {
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .section-drag-handle {
        cursor: move;
        color: var(--fb-text-muted);
        padding: 0.25rem;
        transition: color 0.2s;
    }

    .section-drag-handle:hover {
        color: var(--fb-primary);
    }

    .section-number {
        font-size: 0.9375rem;
        font-weight: 800;
        color: #1e40af;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .section-number i {
        font-size: 0.8125rem;
    }

    .section-header-right {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-content {
        padding: 1.5rem;
    }

    /* ===== Field Item ===== */
    .field-item {
        background: var(--fb-surface);
        border: 1.5px solid var(--fb-border) !important;
        border-radius: var(--fb-radius-sm);
        padding: 0 !important;
        margin-bottom: 0.75rem;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .field-item:hover {
        border-color: #cbd5e1 !important;
        box-shadow: var(--fb-shadow-sm);
    }

    .field-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.875rem 1.25rem;
        background: var(--fb-bg);
        border-bottom: 1px solid var(--fb-border-light);
    }

    .field-header-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .field-drag-handle {
        cursor: move;
        color: var(--fb-text-muted);
        padding: 0.25rem;
        transition: color 0.2s;
    }

    .field-drag-handle:hover {
        color: var(--fb-primary);
    }

    .field-number {
        font-size: 0.875rem;
        font-weight: 700;
        color: var(--fb-text-secondary);
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .field-number i {
        font-size: 0.75rem;
        color: var(--fb-text-muted);
    }

    .field-header-right {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .field-content {
        padding: 1.25rem;
    }

    /* ===== Action Buttons ===== */
    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        border-radius: var(--fb-radius-xs);
        font-size: 0.875rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        text-decoration: none;
    }

    .btn-action:hover {
        transform: translateY(-1px);
        text-decoration: none;
    }

    .btn-action:active {
        transform: translateY(0);
    }

    .btn-action i {
        font-size: 0.8125rem;
    }

    /* Add Section */
    .btn-add-section {
        background: linear-gradient(135deg, var(--fb-success), var(--fb-success-light));
        color: #fff;
        box-shadow: 0 2px 8px rgba(5,150,105,0.3);
        padding: 0.75rem 1.5rem;
        font-size: 0.9375rem;
    }

    .btn-add-section:hover {
        box-shadow: 0 4px 16px rgba(5,150,105,0.4);
        color: #fff;
    }

    /* Add Field */
    .btn-add-field {
        background: linear-gradient(135deg, var(--fb-primary), var(--fb-primary-light));
        color: #fff;
        box-shadow: 0 2px 8px rgba(79,70,229,0.3);
    }

    .btn-add-field:hover {
        box-shadow: 0 4px 16px rgba(79,70,229,0.4);
        color: #fff;
    }

    /* Add Option */
    .btn-add-option {
        background: var(--fb-primary-bg);
        color: var(--fb-primary);
        border: 1px solid var(--fb-primary-border);
    }

    .btn-add-option:hover {
        background: var(--fb-primary);
        color: #fff;
    }

    /* Delete button */
    .btn-delete {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid transparent;
        background: transparent;
        color: var(--fb-text-muted);
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.875rem;
    }

    .btn-delete:hover {
        background: var(--fb-danger-bg);
        color: var(--fb-danger);
        border-color: rgba(220,38,38,0.15);
    }

    .btn-lock {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: var(--fb-text-muted);
        cursor: not-allowed;
        font-size: 0.8125rem;
    }

    /* ===== Save/Cancel Footer ===== */
    .form-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        padding-top: 1rem;
    }

    .btn-cancel {
        padding: 0.75rem 1.75rem;
        background: var(--fb-bg);
        color: var(--fb-text-secondary);
        border: 1.5px solid var(--fb-border);
        border-radius: var(--fb-radius-xs);
        font-size: 0.9375rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-cancel:hover {
        background: #e2e8f0;
        color: var(--fb-text);
        text-decoration: none;
    }

    .btn-save {
        padding: 0.75rem 2rem;
        background: linear-gradient(135deg, var(--fb-primary), var(--fb-primary-light));
        color: #fff;
        border: none;
        border-radius: var(--fb-radius-xs);
        font-size: 0.9375rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.25s;
        box-shadow: 0 4px 14px rgba(79,70,229,0.35);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-save:hover {
        box-shadow: 0 6px 20px rgba(79,70,229,0.45);
        transform: translateY(-1px);
    }

    /* ===== Widget Selector ===== */
    .widget-selector-container .form-select {
        border-color: var(--fb-purple-border);
        background-color: var(--fb-purple-bg);
    }

    .widget-selector-container .form-select:focus {
        border-color: var(--fb-purple);
        box-shadow: 0 0 0 3px rgba(124,58,237,0.1);
    }

    /* ===== Options Config ===== */
    .options-panel {
        background: var(--fb-bg);
        border: 1px solid var(--fb-border);
        border-radius: var(--fb-radius-sm);
        padding: 1.25rem;
    }

    .options-type-group {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .option-type-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border: 1.5px solid var(--fb-border);
        border-radius: var(--fb-radius-xs);
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--fb-text-secondary);
        background: var(--fb-surface);
    }

    .option-type-label:hover {
        border-color: var(--fb-primary-border);
        background: var(--fb-primary-bg);
    }

    .option-type-label input[type="radio"] {
        accent-color: var(--fb-primary);
    }

    .option-item {
        background: var(--fb-surface);
        border: 1.5px solid var(--fb-border);
        border-radius: var(--fb-radius-xs);
        padding: 1rem;
        margin-bottom: 0.625rem;
        transition: border-color 0.2s;
    }

    .option-item:hover {
        border-color: #cbd5e1;
    }

    .option-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .option-number {
        font-size: 0.8125rem;
        font-weight: 700;
        color: var(--fb-text-secondary);
    }

    /* ===== Predefined Data Select ===== */
    .predefined-select {
        border-color: var(--fb-success-border) !important;
        background-color: var(--fb-success-bg) !important;
    }

    .predefined-select:focus {
        border-color: var(--fb-success) !important;
        box-shadow: 0 0 0 3px rgba(5,150,105,0.1) !important;
    }

    /* ===== Checkbox Styling ===== */
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--fb-text-secondary);
        cursor: pointer;
        padding: 0.5rem 1rem;
        border: 1.5px solid var(--fb-border);
        border-radius: var(--fb-radius-xs);
        transition: all 0.2s;
        background: var(--fb-surface);
    }

    .checkbox-label:hover {
        border-color: var(--fb-primary-border);
        background: var(--fb-primary-bg);
    }

    .checkbox-label input[type="checkbox"] {
        accent-color: var(--fb-primary);
        width: 16px;
        height: 16px;
    }

    /* ===== Info Text ===== */
    .info-text {
        display: flex;
        align-items: flex-start;
        gap: 0.375rem;
        font-size: 0.8125rem;
        color: var(--fb-text-muted);
        margin-top: 0.375rem;
    }

    .info-text i {
        margin-top: 2px;
        flex-shrink: 0;
    }

    /* ===== Section Title Row ===== */
    .section-title-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
    }

    .section-main-title {
        font-size: 1.25rem !important;
        font-weight: 800;
        color: var(--fb-text);
        display: flex;
        align-items: center;
        gap: 0.625rem;
        margin: 0;
    }

    .section-main-title i {
        color: var(--fb-primary);
    }

    /* ===== Grid Helpers ===== */
    .form-grid {
        display: grid;
        gap: 0.75rem;
    }

    .form-grid-2 { grid-template-columns: repeat(2, 1fr); }
    .form-grid-3 { grid-template-columns: repeat(3, 1fr); }
    .form-grid-4 { grid-template-columns: repeat(4, 1fr); }

    @media (max-width: 768px) {
        .form-grid-2,
        .form-grid-3,
        .form-grid-4 {
            grid-template-columns: 1fr;
        }

        .form-panel { padding: 1.25rem; }
        .section-content { padding: 1rem; }
        .field-content { padding: 1rem; }
        .options-type-group { flex-direction: column; }
        .form-footer { flex-direction: column; }
        .form-footer .btn-cancel,
        .form-footer .btn-save { width: 100%; text-align: center; justify-content: center; }
    }

    /* ===== Spacing ===== */
    .mb-section { margin-bottom: 1.25rem; }
    .mb-field { margin-bottom: 1rem; }
    .mb-sm { margin-bottom: 0.5rem; }

    /* Global overrides */
    h2, h3, h4, h5 { font-size: inherit !important; font-weight: inherit; margin: 0; }

    .checkbox {
        margin: 0 !important;
    }
</style>

{{-- Template Information Panel --}}
<div class="form-panel">
    <div class="form-panel-header">
        <h2>
            <span class="panel-icon"><i class="fas fa-info-circle"></i></span>
            {{ __('Template Information') }}
        </h2>
        <button type="button" onclick="toggleSection(this)" class="toggle-btn">
            <i class="fas fa-chevron-up"></i>
        </button>
    </div>

    <div class="section-content">
        {{-- Title Fields --}}
        <div class="mb-section">
            <label class="form-label required">{{ __('Form Title') }}</label>
            <div class="form-grid form-grid-{{ min(4, count($locales)) }}">
                @foreach($locales as $key => $locale)
                    <div class="locale-input-wrap">
                        <span class="locale-badge">{{ strtoupper($locale) }}</span>
                        <input type="text" name="title[{{ $key }}]" {{ $loop->first ? 'required' : '' }}
                            @if($locale === 'ar') dir="rtl" @endif
                            class="form-input"
                            placeholder="{{ __('Form Title') }}"
                            value="{{ old('title.'.$key, is_array($template->getTranslation('title', $key)) ? json_encode($template->getTranslation('title', $key)) : $template->getTranslation('title', $key)) }}">
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Form Type --}}
        <div class="mb-section">
            <label class="form-label required">{{ __('Form Type') }}</label>
            <input type="text" name="form_type" required readonly
                   class="form-input"
                   placeholder="agency" value="{{ old('form_type', $template->form_type) }}">
        </div>

        {{-- Description Fields --}}
        <div>
            <label class="form-label">{{ __('Description') }}</label>
            <div class="form-grid form-grid-{{ min(4, count($locales)) }}">
                @foreach($locales as $key => $locale)
                    <div class="locale-input-wrap">
                        <span class="locale-badge">{{ strtoupper($locale) }}</span>
                        <textarea name="description[{{ $key }}]" rows="3" @if($locale === 'ar') dir="rtl" @endif
                                  class="form-textarea"
                                  placeholder="{{ __('Form description') }}">{{ old('description.'.$key, is_array($template->getTranslation('description', $key)) ? json_encode($template->getTranslation('description', $key)) : $template->getTranslation('description', $key)) }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Sections --}}
<div id="sectionsContainer" class="mb-section">
    <div class="section-title-row">
        <h2 class="section-main-title">
            <i class="fas fa-layer-group"></i>
            {{ __('Section') }}
        </h2>
        <button type="button" onclick="addSection()" class="btn-action btn-add-section">
            <i class="fas fa-plus"></i>
            {{ __('Add Section') }}
        </button>
    </div>
    <div id="sections"></div>
</div>

{{-- Form Footer --}}
<div class="form-footer">
    <a href="{{ admin_url('form-templates') }}" class="btn-cancel">
        {{ __('Cancel') }}
    </a>
    <button type="submit" class="btn-save">
        <i class="fas fa-save"></i>
        {{ __('Save') }}
    </button>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
const locales = @json(array_keys($locales));
const localeNames = @json($localeNames);
const availableWidgets = @json($availableWidgets);

let sectionCount = {{ $template->sections->count() > 0 ? $template->sections->max('section_order') : 0 }};
let fieldCounts = {};
let optionCounts = {};
const existingSections = @json($template->sections->keyBy('section_order'));

document.addEventListener('DOMContentLoaded', function() {
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
        const input = section.querySelector('input[name$="[order]"]');
        if (input) input.value = index + 1;
    });
}

function updateFieldOrders(sectionId) {
    document.querySelectorAll(`#section-${sectionId}-fields .field-item`).forEach((field, index) => {
        const input = field.querySelector('input[name$="[order]"]');
        if (input) input.value = index + 1;
    });
}

function toggleSection(button) {
    const container = button.closest('.section-item, .field-item, .form-panel');
    if (!container) return;
    const content = container.querySelector('.section-content, .field-content');
    const icon = button.querySelector('i');
    if (!content || !icon) return;

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

    fieldCounts[sectionCount] = data && data.fields ? data.fields.length : 0;

    const titlesHtml = locales.map(locale => {
        const val = data && data.title ? (data.title[locale] || '') : '';
        const dir = locale === 'ar' ? ' dir="rtl"' : '';
        return `
            <div class="locale-input-wrap">
                <span class="locale-badge">${locale.toUpperCase()}</span>
                <input type="text" name="sections[${sectionCount}][title][${locale}]" ${locale === locales[0] ? 'required' : ''} ${dir}
                    class="form-input"
                    placeholder="${@json(__('Section Title'))}" value="${escapeHtml(val)}">
            </div>`;
    }).join('');

    const canDeleteSection = !data || data.can_not_delete != 1;

    const deleteSectionButton = canDeleteSection
        ? `<button type="button" onclick="removeSection(${sectionCount})" class="btn-delete" title="${@json(__('Delete'))}">
                <i class="fas fa-trash"></i>
           </button>`
        : `<span class="btn-lock" title="${@json(__('Locked'))}">
                <i class="fas fa-lock"></i>
           </span>`;

    const sectionHtml = `
        <div class="section-item" data-section="${sectionCount}">
            <div class="section-header">
                <div class="section-header-left">
                    <span class="section-drag-handle">
                        <i class="fas fa-grip-vertical"></i>
                    </span>
                    <span class="section-number">
                        <i class="fas fa-folder-open"></i>
                        ${@json(__('Section'))} #${sectionCount}
                    </span>
                </div>
                <div class="section-header-right">
                    <button type="button" onclick="toggleSection(this)" class="toggle-btn">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    ${deleteSectionButton}
                </div>
            </div>

            <div class="section-content">
                <div class="mb-section">
                    <label class="form-sublabel required">${@json(__('Section Title'))}</label>
                    <div class="form-grid form-grid-${Math.min(4, locales.length)}">
                        ${titlesHtml}
                    </div>
                </div>

                <input type="hidden" name="sections[${sectionCount}][can_not_delete]" value="${data && data.can_not_delete ? data.can_not_delete : 0}">
                <input type="hidden" name="sections[${sectionCount}][order]" value="${sectionCount}">

                <div class="mb-section">
                    <button type="button" onclick="addField(${sectionCount})" class="btn-action btn-add-field">
                        <i class="fas fa-plus"></i>
                        ${@json(__('Add Field'))}
                    </button>
                </div>

                <div id="section-${sectionCount}-fields" class="space-y-3"></div>
            </div>
        </div>
    `;

    document.getElementById('sections').insertAdjacentHTML('beforeend', sectionHtml);
    document.querySelector(`[data-section="${sectionCount}"]`).scrollIntoView({ behavior: 'smooth', block: 'center' });

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
    const el = document.querySelector(`[data-section="${sectionId}"]`);
    if (el) el.remove();
    updateSectionOrders();
}

function addField(sectionId, data = null) {
    if (data) {
        fieldCounts[sectionId] = data.field_order;
    } else {
        fieldCounts[sectionId] = (fieldCounts[sectionId] || 0) + 1;
    }
    const fieldId = fieldCounts[sectionId];
    const canDeleteSection = data && data.can_not_delete;

    const labelHtml = locales.map(locale => {
        const val = data && data.field_label ? (data.field_label[locale] || '') : '';
        const dir = locale === 'ar' ? ' dir="rtl"' : '';
        return `
            <div class="locale-input-wrap">
                <span class="locale-badge">${locale.toUpperCase()}</span>
                <input type="text" name="sections[${sectionId}][fields][${fieldId}][label][${locale}]" ${locale === locales[0] ? 'required' : ''} ${dir}
                    class="form-input"
                    placeholder="${@json(__('Field Label'))}" value="${escapeHtml(val)}">
            </div>`;
    }).join('');

    const placeholderHtml = locales.map(locale => {
        const val = data && data.placeholder ? (data.placeholder[locale] || '') : '';
        const dir = locale === 'ar' ? ' dir="rtl"' : '';
        return `
            <div class="locale-input-wrap">
                <span class="locale-badge">${locale.toUpperCase()}</span>
                <input type="text" name="sections[${sectionId}][fields][${fieldId}][placeholder][${locale}]" ${dir}
                    class="form-input"
                    placeholder="${@json(__('Placeholder'))}" value="${escapeHtml(val)}">
            </div>`;
    }).join('');

    const widgetOptionsHtml = availableWidgets.map(widget => {
        const widgetName = widget.widget_name.en || widget.widget_type;
        const isSelected = data && data.widget_id === widget.id ? 'selected' : '';
        return `<option value="${widget.id}" ${isSelected}>${widgetName}</option>`;
    }).join('');

    const canDelete = !data || data.can_not_delete != 1;

    const deleteButton = canDelete
        ? `<button type="button" onclick="removeField(${sectionId}, ${fieldId})" class="btn-delete" title="${@json(__('Delete'))}">
                <i class="fas fa-times"></i>
           </button>`
        : `<span class="btn-lock" title="${@json(__('Locked'))}">
                <i class="fas fa-lock"></i>
           </span>`;

    const fieldHtml = `
        <div class="field-item" data-field="${sectionId}-${fieldId}" id="field-${sectionId}-${fieldId}">
            <div class="field-header">
                <div class="field-header-left">
                    <span class="field-drag-handle">
                        <i class="fas fa-grip-vertical"></i>
                    </span>
                    <span class="field-number">
                        <i class="fas fa-cube"></i>
                        ${@json(__('Field'))} #${fieldId}
                    </span>
                </div>
                <div class="field-header-right">
                    <button type="button" onclick="toggleSection(this)" class="toggle-btn">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    ${deleteButton}
                </div>
            </div>

            <div class="field-content section-content">
                <!-- Field Label -->
                <div class="mb-field">
                    <label class="form-sublabel required">${@json(__('Field Label'))}</label>
                    <div class="form-grid form-grid-${Math.min(4, locales.length)}">
                        ${labelHtml}
                    </div>
                </div>

                <!-- Field Name & Type -->
                <div class="form-grid form-grid-2 mb-field">
                    <div class="field-name-container">
                        <label class="form-sublabel required">${@json(__('Field Name'))}</label>
                        <input type="text" ${canDeleteSection ? 'readonly' : ''} name="sections[${sectionId}][fields][${fieldId}][name]" required
                            class="form-input field-name-input"
                            placeholder="full_name" value="${data ? escapeHtml(data.field_name || '') : ''}">
                    </div>
                    <div>
                        <label class="form-sublabel required">${@json(__('Field Type'))}</label>
                        <select ${canDeleteSection ? 'disabled' : ''} name="sections[${sectionId}][fields][${fieldId}][type]" required
                                class="form-select field-type-select"
                                onchange="handleFieldTypeChange(${sectionId}, ${fieldId}, this)">
                            <option value="text" ${data && data.field_type === 'text' ? 'selected' : ''}>${@json(__('Text'))}</option>
                            <option value="email" ${data && data.field_type === 'email' ? 'selected' : ''}>${@json(__('Email'))}</option>
                            <option value="number" ${data && data.field_type === 'number' ? 'selected' : ''}>${@json(__('integer'))}</option>
                            <option value="tel" ${data && data.field_type === 'tel' ? 'selected' : ''}>${@json(__('Phone'))}</option>
                            <option value="date" ${data && data.field_type === 'date' ? 'selected' : ''}>${@json(__('Date'))}</option>
                            <option value="textarea" ${data && data.field_type === 'textarea' ? 'selected' : ''}>${@json(__('Textarea'))}</option>
                            <option value="file" ${data && data.field_type === 'file' ? 'selected' : ''}>${@json(__('file'))}</option>
                            <option value="select" ${data && data.field_type === 'select' ? 'selected' : ''}>${@json(__('select'))}</option>
                            <option value="checkbox" ${data && data.field_type === 'checkbox' ? 'selected' : ''}>${@json(__('Checkbox'))}</option>
                            <option value="radio" ${data && data.field_type === 'radio' ? 'selected' : ''}>${@json(__('Radio'))}</option>
                            <option value="custom" ${data && data.field_type === 'custom' ? 'selected' : ''}>${@json(__('Custom Widget'))}</option>
                        </select>
                    </div>
                </div>

                <!-- Custom Widget Selector -->
                <div class="widget-selector-container mb-field" style="display: ${data && data.field_type === 'custom' ? 'block' : 'none'};">
                    <label class="form-sublabel required">
                        <i class="fas fa-puzzle-piece" style="color: var(--fb-purple);"></i>
                        ${@json(__('Select Custom Widget'))}
                    </label>
                    <select name="sections[${sectionId}][fields][${fieldId}][widget_id]"
                            class="form-select widget-select" style="border-color: var(--fb-purple-border); background-color: var(--fb-purple-bg);">
                        <option value="">-- ${@json(__('Choose a widget'))} --</option>
                        ${widgetOptionsHtml}
                    </select>
                    <div class="info-text">
                        <i class="fas fa-info-circle"></i>
                        <span>${@json(__('Custom widgets provide specialized UI (BD Selector, User Picker, etc.)'))}</span>
                    </div>
                </div>

                <!-- Placeholder -->
                <div class="mb-field placeholder-container" style="display: ${data && data.field_type === 'custom' ? 'none' : 'block'};">
                    <label class="form-sublabel">${@json(__('Placeholder Text'))}</label>
                    <div class="form-grid form-grid-${Math.min(4, locales.length)}">
                        ${placeholderHtml}
                    </div>
                </div>

                <!-- Select Options -->
                <div class="mb-field options-container" style="display: ${data && ['select', 'checkbox', 'radio'].includes(data.field_type) ? 'block' : 'none'};">
                    <div class="options-panel">
                        <label class="form-sublabel" style="margin-bottom: 0.75rem;">
                            <i class="fas fa-list-ul" style="color: var(--fb-primary);"></i>
                            ${@json(__('Select Options Configuration'))}
                        </label>

                        <!-- Options Type -->
                        <div class="options-type-group">
                            <label class="option-type-label">
                                <input type="radio" name="sections[${sectionId}][fields][${fieldId}][options_type]"
                                       value="custom" ${!data || !data.data_source ? 'checked' : ''}
                                       class="options-type-radio"
                                       onchange="toggleOptionsType(${sectionId}, ${fieldId}, 'custom')">
                                <i class="fas fa-edit" style="color: var(--fb-primary);"></i>
                                ${@json(__('Custom Options'))}
                            </label>
                            <label class="option-type-label">
                                <input type="radio" name="sections[${sectionId}][fields][${fieldId}][options_type]"
                                       value="predefined" ${data && data.data_source ? 'checked' : ''}
                                       class="options-type-radio"
                                       onchange="toggleOptionsType(${sectionId}, ${fieldId}, 'predefined')">
                                <i class="fas fa-database" style="color: var(--fb-success);"></i>
                                ${@json(__('Pre-defined Data'))}
                            </label>
                        </div>

                        <!-- Custom Options Editor -->
                        <div class="custom-options-editor" style="display: ${!data || !data.data_source ? 'block' : 'none'};">
                            <div class="mb-sm">
                                <button type="button" onclick="addCustomOption(${sectionId}, ${fieldId})" class="btn-action btn-add-option">
                                    <i class="fas fa-plus"></i>
                                    ${@json(__('Add Option'))}
                                </button>
                            </div>
                            <div id="custom-options-${sectionId}-${fieldId}" class="space-y-2"></div>
                        </div>

                        <!-- Predefined Data Selector -->
                        <div class="predefined-options-selector" style="display: ${data && data.data_source ? 'block' : 'none'};">
                            <select name="sections[${sectionId}][fields][${fieldId}][data_source]"
                                    class="form-select predefined-select">
                                <option value="">-- ${@json(__('Select Data Source'))} --</option>
                                <option value="countries" ${data && data.data_source === 'countries' ? 'selected' : ''}>${@json(__('Countries'))}</option>
                                <option value="cities" ${data && data.data_source === 'cities' ? 'selected' : ''}>${@json(__('Cities'))}</option>
                                <option value="languages" ${data && data.data_source === 'languages' ? 'selected' : ''}>${@json(__('Languages'))}</option>
                                <option value="currencies" ${data && data.data_source === 'currencies' ? 'selected' : ''}>${@json(__('Currencies'))}</option>
                            </select>
                            <div class="info-text">
                                <i class="fas fa-info-circle"></i>
                                <span>${@json(__('Options will be loaded dynamically from server'))}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="sections[${sectionId}][fields][${fieldId}][can_not_delete]" value="${data && data.can_not_delete ? data.can_not_delete : 0}">

                <!-- Checkboxes -->
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="sections[${sectionId}][fields][${fieldId}][required]" value="1" ${data && data.is_required ? 'checked' : ''}>
                        ${@json(__('Required'))}
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="sections[${sectionId}][fields][${fieldId}][enabled]" value="1" ${data && data.is_enabled ? 'checked' : ''}>
                        ${@json(__('Enabled'))}
                    </label>
                </div>

                <input type="hidden" name="sections[${sectionId}][fields][${fieldId}][order]" value="${fieldId}">
            </div>
        </div>
    `;

    document.getElementById(`section-${sectionId}-fields`).insertAdjacentHTML('beforeend', fieldHtml);

    optionCounts[`${sectionId}-${fieldId}`] = 0;

    if (data && data.options && Array.isArray(data.options)) {
        data.options.forEach(optionData => {
            addCustomOption(sectionId, fieldId, optionData);
        });
    }

    setTimeout(() => {
        const fieldTypeSelect = document.querySelector(`#field-${sectionId}-${fieldId} .field-type-select`);
        if (fieldTypeSelect && data) {
            handleFieldTypeChange(sectionId, fieldId, fieldTypeSelect);
        }
    }, 50);

    setTimeout(() => {
        const newField = document.getElementById(`field-${sectionId}-${fieldId}`);
        if (newField) {
            newField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            newField.style.boxShadow = '0 0 0 3px rgba(16,185,129,0.3)';
            setTimeout(() => { newField.style.boxShadow = ''; }, 2000);
        }
    }, 100);
}

function addCustomOption(sectionId, fieldId, data = null) {
    const key = `${sectionId}-${fieldId}`;
    optionCounts[key] = (optionCounts[key] || 0) + 1;
    const optionId = optionCounts[key];

    const optionLabelsHtml = locales.map(locale => {
        const val = data && data.label ? (data.label[locale] || '') : '';
        const dir = locale === 'ar' ? ' dir="rtl"' : '';
        return `
            <div class="locale-input-wrap">
                <span class="locale-badge" style="background:#f1f5f9;color:#475569;border-color:#e2e8f0;">${locale.toUpperCase()}</span>
                <input type="text"
                       name="sections[${sectionId}][fields][${fieldId}][options][${optionId}][label][${locale}]"
                       ${locale === locales[0] ? 'required' : ''} ${dir}
                       class="form-input"
                       placeholder="${@json(__('Option Label'))}"
                       value="${escapeHtml(val)}">
            </div>`;
    }).join('');

    const optionHtml = `
        <div class="option-item" data-option="${optionId}">
            <div class="option-header">
                <span class="option-number">${@json(__('Option'))} #${optionId}</span>
                <button type="button" onclick="removeCustomOption(${sectionId}, ${fieldId}, ${optionId})" class="btn-delete">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="form-grid form-grid-${Math.min(4, locales.length)} mb-sm">
                ${optionLabelsHtml}
            </div>
            <div>
                <label class="form-sublabel required" style="font-size:0.8125rem !important;">${@json(__('Value'))}</label>
                <input type="text"
                       name="sections[${sectionId}][fields][${fieldId}][options][${optionId}][value]"
                       required
                       class="form-input"
                       placeholder="value_key"
                       value="${data && data.value ? escapeHtml(data.value) : ''}">
            </div>
        </div>
    `;

    document.getElementById(`custom-options-${sectionId}-${fieldId}`).insertAdjacentHTML('beforeend', optionHtml);
}

function removeCustomOption(sectionId, fieldId, optionId) {
    const optionElement = document.querySelector(`#custom-options-${sectionId}-${fieldId} [data-option="${optionId}"]`);
    if (optionElement) optionElement.remove();
}

function handleFieldTypeChange(sectionId, fieldId, selectElement) {
    const fieldContainer = document.getElementById(`field-${sectionId}-${fieldId}`);
    const widgetContainer = fieldContainer.querySelector('.widget-selector-container');
    const placeholderContainer = fieldContainer.querySelector('.placeholder-container');
    const optionsContainer = fieldContainer.querySelector('.options-container');
    const fieldNameContainer = fieldContainer.querySelector('.field-name-container');
    const fieldNameInput = fieldContainer.querySelector('.field-name-input');
    const widgetSelect = fieldContainer.querySelector('.widget-select');

    if (widgetContainer) widgetContainer.style.display = 'none';
    if (optionsContainer) optionsContainer.style.display = 'none';
    if (placeholderContainer) placeholderContainer.style.display = 'block';
    if (fieldNameContainer) fieldNameContainer.style.display = 'block';

    if (selectElement.value === 'custom') {
        widgetContainer.style.display = 'block';
        widgetSelect.required = true;
        if (placeholderContainer) placeholderContainer.style.display = 'none';
        if (fieldNameContainer) fieldNameContainer.style.display = 'none';
        fieldNameInput.required = false;

        const newWidgetSelect = widgetSelect.cloneNode(true);
        widgetSelect.parentNode.replaceChild(newWidgetSelect, widgetSelect);

        newWidgetSelect.addEventListener('change', function() {
            if (this.value) {
                const selectedWidget = availableWidgets.find(w => w.id == this.value);
                if (selectedWidget) {
                    fieldNameInput.value = selectedWidget.widget_type + '_' + sectionId + '_' + fieldId;
                }
            }
        });
    } else if (selectElement.value === 'select' || selectElement.value === 'checkbox' || selectElement.value === 'radio') {
        if (optionsContainer) optionsContainer.style.display = 'block';
        fieldNameInput.required = true;
        widgetSelect.required = false;
    } else {
        widgetSelect.required = false;
        widgetSelect.value = '';
        fieldNameInput.required = true;
    }
}

function toggleOptionsType(sectionId, fieldId, type) {
    const fieldContainer = document.getElementById(`field-${sectionId}-${fieldId}`);
    const customEditor = fieldContainer.querySelector('.custom-options-editor');
    const predefinedSelector = fieldContainer.querySelector('.predefined-options-selector');

    if (customEditor && predefinedSelector) {
        if (type === 'custom') {
            customEditor.style.display = 'block';
            predefinedSelector.style.display = 'none';
        } else {
            customEditor.style.display = 'none';
            predefinedSelector.style.display = 'block';
        }
    }
}

function removeField(sectionId, fieldId) {
    const el = document.querySelector(`[data-field="${sectionId}-${fieldId}"]`);
    if (el) el.remove();
    updateFieldOrders(sectionId);
}

function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('input', function (e) {
    if (e.target.classList.contains('field-name-input')) {
        const input = e.target;
        const container = input.closest('.field-name-container');

        let name = input.value.replace(/\s+/g, '');
        input.value = name;

        if (name.length < 2) {
            showNameStatus(container, '', '');
            return;
        }

        fetch(`/check-field-name?name=${encodeURIComponent(name)}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    showNameStatus(container, '⚠️ الاسم موجود بالفعل', 'red');
                } else {
                    showNameStatus(container, '✅ الاسم متاح', 'green');
                }
            })
            .catch(() => {
                showNameStatus(container, 'حدث خطأ أثناء التحقق', 'orange');
            });
    }
});

function showNameStatus(container, message, color) {
    let status = container.querySelector('.name-status');
    if (!status) {
        status = document.createElement('div');
        status.classList.add('name-status');
        status.style.marginTop = '6px';
        status.style.fontSize = '13px';
        status.style.fontWeight = '600';
        container.appendChild(status);
    }

    if (message === '') {
        status.textContent = '';
    } else {
        status.textContent = message;
        status.style.color = color;
    }
}
</script>
@endpush
