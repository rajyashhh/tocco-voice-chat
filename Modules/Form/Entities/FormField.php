<?php

namespace  Modules\Form\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class FormField extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['field_label', 'placeholder', 'help_text'];

    protected $fillable = [
        'section_id',
        'field_label',
        'field_name',
        'field_type',
        'widget_id',
        'widget_config',
        'placeholder',
        'help_text',
        'options',
        'data_source',
        'is_required',
        'is_enabled',
        'field_order',
        'can_not_delete'
    ];

    protected $casts = [
        'options' => 'array',
        'widget_config' => 'array',
        'is_required' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    /**
     * Get the section that owns this field
     */
    public function section()
    {
        return $this->belongsTo(FormSection::class, 'section_id');
    }

    /**
     * Get the custom widget for this field
     */
    public function widget()
    {
        return $this->belongsTo(CustomFieldWidget::class, 'widget_id');
    }

    /**
     * Check if this field uses a custom widget
     */
    public function hasCustomWidget()
    {
        return !is_null($this->widget_id) && $this->widget !== null;
    }

    /**
     * Render the field (either standard input or custom widget)
     */
    public function renderInput($value = null, $attributes = [])
    {
        if ($this->hasCustomWidget()) {
            return $this->widget->render($this, $value, $attributes);
        }

        // Return standard input rendering
        return view('components.form.standard-input', [
            'field' => $this,
            'value' => $value,
            'attributes' => $attributes,
        ]);
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        
        if (in_array($key, $this->translatable) && is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        return $value;
    }
}
