<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * RETIRED feature, model kept for APK backward-compat only: the published app
 * (v1.0.30) still calls GET /countries/categories (CountryController@countryCategory
 * -> CountryService::countryCategory()), which must keep answering with the same
 * response shape (empty data — the table has 0 rows everywhere). Delete this model
 * together with that endpoint when a new app release drops the call.
 */
class CountryCategory extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'title' => 'array',
    ];

    public $sortable = [
        'order_column_name' => 'sort',
        'sort_when_creating' => true,
    ];
}
