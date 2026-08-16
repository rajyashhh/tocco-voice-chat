<?php

namespace  Modules\Form\Entities;

use App\Models\Bd;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Form\Entities\FormTemplate;

class FormRequest extends Model
{
    use HasFactory;

    protected $table = 'form_requests';

    protected $fillable = [
        'form_template_id',
        'submitted_by',
        'bd_id',
        'name',
        'whatsapp_number',
        'form_template_type',
        'data',
        'country',
        'status',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // علاقات
    public function template()
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function bd()
    {
        return $this->belongsTo(Bd::class, 'bd_id');
    }
}
