<?php

namespace  Modules\Form\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class FormSection extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['title', 'description'];

    protected $fillable = [
        'form_template_id',
        'title',
        'description',
        'section_order',
        'is_visible',
        'can_not_delete'
    ];

    // Remove JSON casting since HasTranslations trait handles it
    protected $casts = [];

    public function fields()
    {
        return $this->hasMany(FormField::class, 'section_id');
    }
}
