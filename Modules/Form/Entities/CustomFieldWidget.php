<?php

namespace Modules\Form\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class CustomFieldWidget extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['widget_name', 'description'];

    protected $fillable = [
        'widget_type',
        'widget_name',
        'description',
        'component_path',
        'default_config',
        'allows_multiple',
        'is_active',
    ];

    protected $casts = [
        'default_config' => 'array',
        'allows_multiple' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get all form fields using this widget
     */
    public function fields()
    {
        return $this->hasMany(FormField::class, 'widget_id');
    }

    /**
     * Render the widget component
     */
    public function render($field, $value = null, $attributes = [])
    {
        $config = array_merge(
            $this->default_config ?? [],
            $field->widget_config ?? []
        );

        return view($this->component_path, [
            'field' => $field,
            'widget' => $this,
            'value' => $value,
            'config' => $config,
            'attributes' => $attributes,
        ]);
    }

    /**
     * Get widget data from configured source
     */
    public function getData($config = [])
    {
        $mergedConfig = array_merge($this->default_config ?? [], $config);

        // Handle different data source types
        if (isset($mergedConfig['data_source'])) {
            switch ($mergedConfig['data_source']) {
                case 'users':
                    return $this->getUsersData($mergedConfig);
                case 'api':
                    return $this->getApiData($mergedConfig);
                case 'static':
                    return $mergedConfig['static_data'] ?? [];
                default:
                    return [];
            }
        }

        return [];
    }

    /**
     * Get users data (for BD selector, etc.)
     */
    protected function getUsersData($config)
    {
        $query = User::query()->where('is_active', true);

        // Filter by role if specified
        if (isset($config['role'])) {
            $query->where('role', $config['role']);
        }

        // Filter by roles if specified (multiple)
        if (isset($config['roles'])) {
            $query->whereIn('role', $config['roles']);
        }

        return $query->select('id', 'name', 'email', 'role')->get();
    }

    /**
     * Get data from API endpoint
     */
    protected function getApiData($config)
    {
        // Implement API call logic here
        // This would use Laravel HTTP client or Guzzle
        return [];
    }
}
