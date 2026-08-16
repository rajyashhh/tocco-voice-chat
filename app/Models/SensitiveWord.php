<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensitiveWord extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'word' => 'array',
    ];

    /**
     * Accessor so multi_lang_tabs blade can read $model->title from the word column.
     */
    public function getTitleAttribute()
    {
        return $this->word;
    }
}
