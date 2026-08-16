<input type="checkbox" class="{{ $class }}" {{ $checked }} data-key="{{ $key }}" />
<script>
$('.{{ $class }}').bootstrapSwitch({
    size: 'mini',
    onText: '{{ $states['on']['text'] }}',
    offText: '{{ $states['off']['text'] }}',
    onColor: '{{ $states['on']['color'] }}',
    offColor: '{{ $states['off']['color'] }}',
    onSwitchChange: function(event, state) {
        var key = $(this).data('key');
        var value = state ? {{ $states['on']['value'] }} : {{ $states['off']['value'] }};
        $(this).val(value);  // تعيين القيمة الصحيحة بناءً على الـ state

        var _status = true;
    
        $.ajax({
            url: "{{ $resource }}/" + key,
            type: "POST",
            data: {
                "{{ $name }}": value,
                _token: LA.token,
                _method: 'PUT',
                _edit_inline: true
            },
         
            success: function(data) {
                console.log(data);  // عرض الاستجابة في الـ console
                if (data.status) {
                    toastr.success(data.message);
                } else {
                    toastr.warning(data.message);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                console.log(xhr.responseJSON);  // عرض الاستجابة في الـ console عند حدوث خطأ
                _status = false;
                var data = xhr.responseJSON;
                if (data['errors'] || data['message']) {
                    var message = data['message'] || Object.values(data['errors']).join("\n");
                    toastr.error(message);
                } else {
                    toastr.error('Error: ' + errorThrown);
                }
            },
            complete: function(xhr, status) {
                if (status === 'success') {
                    _status = xhr.responseJSON.status;
                }
            }
        });

        return _status; // يتم إرجاع النتيجة بناءً على الاستجابة
    }
});
</script>
