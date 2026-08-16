<?php

namespace  Modules\Form\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_template_id',
        'entity_id',
        'entity_type',
        'submitted_by',
        'submission_status',
        'ip_address',
        'user_agent',
        'form_template_type',
        'status'
    ];

    protected $casts = [
        'data' => 'array',
    ];
    public function template()
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function values()
    {
        return $this->hasMany(FormSubmissionValue::class, 'submission_id');
    }
}
