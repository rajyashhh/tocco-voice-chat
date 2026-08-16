<?php
namespace App\Admin\Extensions\Form\Field;

use Encore\Admin\Form\Field;

class DynamicFields extends Field
{
    protected $view = 'admin.fields.dynamic_fields';

    public function render()
    {
        $this->script = <<<SCRIPT
            $("#add_field").off("click").on("click", function() {
                var newField = '<div class="dynamic-field-group" style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">' +
                    '<input type="number" name="dynamic_fields[]" class="form-control" placeholder="' + window.translations.add_placeholder + '" style="flex: 1;">' +
                    '<button type="button" class="btn btn-danger remove-field">' + window.translations.delete_text + '</button>' +
                '</div>';
                $("#dynamic_fields_container").append(newField);
            });
        SCRIPT;

        return parent::render();
    }
} 