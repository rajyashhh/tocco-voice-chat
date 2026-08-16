@php
    $value = old('dynamic_fields', $value ?? '');
    $fields = is_array($value) ? $value : explode(',', $value);
@endphp

<div class="form-group">
    <label  class="col-sm-2 control-label" for="box_type">{{ __('users') }}</label>
             <div class="col-sm-8">
                <div class="input-group">
                    <div id="dynamic_fields_container">
                @foreach($fields as $field)
                    <div class="dynamic-field-group" style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                        <input type="number" name="dynamic_fields[]" class="form-control"
                            placeholder="{{ __('Enter users count') }}"
                            value="{{ trim($field) }}"
                            style="flex: 1;">
                        <button type="button" class="btn btn-danger remove-field">{{ __('Delete') }}</button>
                    </div>
                @endforeach
            </div>
         </div>

<div class="form-group">
    <button type="button" id="add_field" class="btn btn-primary" style="margin-top: 10px;">
        {{ __('Add') }}
    </button>
</div>
    </div>

</div>



<script>
    function initDynamicFieldsScript() {
        $('#add_field').off('click').on('click', function () {
            let placeholder = '{{ __("Enter users count") }}';
            let deleteText = '{{ __("Delete") }}';

            var newField = '<div class="dynamic-field-group" style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">' +
                '<input type="number" name="dynamic_fields[]" class="form-control" placeholder="' + placeholder + '" style="flex: 1;">' +
                '<button type="button" class="btn btn-danger remove-field">' + deleteText + '</button>' +
            '</div>';

            $('#dynamic_fields_container').append(newField);
        });

        $(document).on('click', '.remove-field', function () {
            $(this).closest('.dynamic-field-group').remove();
        });

        $(document).on("change", "#box_type", toggleFields);
        toggleFields();
    }

    function toggleFields() {
        var type = $('#box_type').val();
        console.log(type)
        if (type == '1') {
            $('#users_field').closest('.form-group').show();
            $('#duration_field').closest('.form-group').show();
        } else {
            $('#users_field').closest('.form-group').hide();
            $('#duration_field').closest('.form-group').hide();

        }
    }

    $(document).ready(function () {
        initDynamicFieldsScript();
    });
</script>
