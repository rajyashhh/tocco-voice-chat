<?php

namespace  Modules\Form\Entities;

use Illuminate\Database\Eloquent\Model;

class FormSubmissionValue extends Model
{
    protected $fillable = [
        'submission_id',
        'field_id',
        'field_value',
        'field_value_json',
        'file_path',
        'file_name',
        'file_size',
    ];

    protected $casts = [
        'field_value_json' => 'array',
    ];

    public function submission()
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }

    public function field()
    {
        return $this->belongsTo(FormField::class, 'field_id');
    }

    /**
     * Get the value (handles both regular and JSON values)
     */
    public function getValue()
    {
        // If field uses custom widget with multiple selections, use JSON value
        if ($this->field && $this->field->hasCustomWidget() && $this->field->widget->allows_multiple) {
            return $this->field_value_json ?? [];
        }

        return $this->field_value;
    }

    /**
     * Set the value (handles both regular and JSON values)
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            $this->field_value_json = $value;
            $this->field_value = json_encode($value); // Keep text version for backward compatibility
        } else {
            $this->field_value = $value;
            $this->field_value_json = null;
        }
    }
}
