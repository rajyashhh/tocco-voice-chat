<?php

namespace Modules\Form\Services;

use Modules\Form\Entities\FormField;

class FormRenderService
{
    public function renderFormData(array $data): string
    {
        $fields = $data;
        $mainFields = $data;
        $sections = $data['sections'] ?? [];

        if (!empty($sections)) {
            foreach ($sections as $section) {
                if (!empty($section['fields'])) {
                    foreach ($section['fields'] as $subField) {
                        if (!empty($subField['items'])) {
                            foreach ($subField['items'] as $item) {
                                foreach ($item as $key => $val) {
                                    $fields[$key] = $val;
                                }
                            }
                        }
                    }
                }
            }
        }

        unset($fields['sections']);

        // Add dark mode styles
        $html = $this->getStyles();

        $html .= '<div class="form-data-container" style="max-width:100%;">';
        foreach ($fields as $name => $value) {
            $html .= $this->renderField($name, $value);
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Get CSS styles including dark mode support
     */
    protected function getStyles(): string
    {
        return '
        <style>
            /* Light Mode (Default) */
            .form-data-container .form-field-item {
                margin-bottom: 15px;
                padding: 12px;
                background: #f9f9f9;
                border-radius: 6px;
                display: flex;
                flex-direction: column;
            }

            .form-data-container .form-field-label {
                color: #2c3e50;
                margin-bottom: 5px;
                font-weight: bold;
            }

            .form-data-container .form-field-value {
                color: #333;
            }

            .form-data-container .form-field-email {
                color: #3498db;
                text-decoration: none;
            }

            .form-data-container .form-field-tel {
                color: #27ae60;
                text-decoration: none;
            }

            .form-data-container .form-field-number {
                font-weight: 500;
                color: #2c3e50;
            }

            .form-data-container .form-field-file-link {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 5px 10px;
                background: #eef;
                border-radius: 6px;
                text-decoration: none;
                color: #333;
            }

            /* Dark Mode */
            .dark-mode .form-data-container .form-field-item {
                background: var(--dark-secondry-color);
                border: 1px solid #4a5568;
            }

            .dark-mode .form-data-container .form-field-label {
                color: #e2e8f0;
            }

            .dark-mode .form-data-container .form-field-value {
                color: #ffffff;
            }

            .dark-mode .form-data-container .form-field-email {
                color: #63b3ed !important;
            }

            .dark-mode .form-data-container .form-field-tel {
                color: #68d391;
            }

            .dark-mode .form-data-container .form-field-number {
                color: #e2e8f0;
            }

            .dark-mode .form-data-container .form-field-textarea {
                color: #ffffff;
            }

            .dark-mode .form-data-container .form-field-file-link {
                background: #4a5568;
                color: #e2e8f0;
            }

            .dark-mode .form-data-container .form-field-file-link:hover {
                background: #5a6578;
            }

            .dark-mode .form-data-container img {
                border-color: #4a5568 !important;
            }
        </style>
        ';
    }

    public function renderField(string $name, $value): string
    {
        $field = FormField::where('field_name', $name)->first();

        $label = $field?->field_label ?? ucwords(str_replace('_', ' ', $name));
        $type = $field?->field_type ?? $this->detectFieldType($name, $value);

        $html = '<div class="form-field-item">';
        $html .= '<strong class="form-field-label">' . htmlspecialchars($label) . ':</strong> ';

        switch ($type) {
            case 'file':
                $html .= $this->renderFile($value);
                break;

            case 'email':
                $html .= '<a href="mailto:' . e($value) . '" class="form-field-email">' . e($value) . '</a>';
                break;

            case 'tel':
                $html .= '<a href="tel:' . e($value) . '" class="form-field-tel">' . e($value) . '</a>';
                break;

            case 'textarea':
                $html .= '<div class="form-field-value form-field-textarea" style="white-space:pre-wrap; line-height:1.4;">' . e($value) . '</div>';
                break;

            case 'number':
                $html .= '<span class="form-field-value form-field-number">' . e($value) . '</span>';
                break;

            default:
                $html .= '<span class="form-field-value">' . nl2br(htmlspecialchars($value)) . '</span>';
        }

        $html .= '</div>';
        return $html;
    }

    public function renderFile($value): string
    {
        $files = is_array($value) ? $value : [$value];
        $html = '<div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:5px;">';

        foreach ($files as $file) {
            if (!$file) continue;

            $path = getImagePath($file);
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) {
                $html .= '<a href="' . $path . '" target="_blank">
                            <img src="' . $path . '" style="max-width:150px; max-height:150px; border-radius:6px; border:1px solid #ddd;">
                          </a>';
            } else {
                $html .= '<a href="' . $path . '" target="_blank" class="form-field-file-link">
                            <i class="fa fa-file"></i> ' . basename($file) . '
                          </a>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    protected function detectFieldType(string $name, $value): string
    {
        if (is_numeric($value)) return 'number';
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) return 'email';
        if (preg_match('/^\+?\d{6,15}$/', $value)) return 'tel';
        if (is_array($value)) return 'file';
        return 'text';
    }
}
