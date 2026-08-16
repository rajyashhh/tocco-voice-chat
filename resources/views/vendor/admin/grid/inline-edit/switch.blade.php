<input type="checkbox" class="{{ $class }}" {{ $checked }} data-key="{{ $key }}" />
<script>
    $('.{{ $class }}').bootstrapSwitch({
        size:'mini',
        onText: '{{ $states['on']['text'] }}',
        offText: '{{ $states['off']['text'] }}',
        onColor: '{{ $states['on']['color'] }}',
        offColor: '{{ $states['off']['color'] }}',
        onSwitchChange: function(event, state){

            $(this).val(state ? {{ $states['on']['value'] }} : {{ $states['off']['value'] }});

            var key = $(this).data('key');
            var value = $(this).val();
            var _status = true;
            var url = "{{ $resource }}/" + key+"";
            console.log('Request URL:', url);

            $.ajax({
                url: "{{ $resource }}/" + key,
                type: "POST",
                data: {
                    "{{ $name }}": value,
                    _token: LA.token,
                    _method: 'PUT',
                    _edit_inline: true
                },
                headers: {
                    'Accept': 'application/json'
                },
                success: function(data) {
                    console.log(data);
                    if (data.status) {
                        toastr.success(data.message);
                    } else {
                        toastr.warning(data.message);
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    console.log(xhr.responseJSON);
                    _status = false;
                    var data = xhr.responseJSON;
                    if (data && (data['errors'] || data['message'])) {
                        var message = data['message'] || Object.values(data['errors']).join("\n");
                        toastr.error(message);
                    } else {
                        toastr.error('Error: ' + errorThrown);
                    }
                },
                complete: function(xhr, status) {
                    try {
                        var response = xhr.responseJSON;
                        if (response && response.status !== undefined) {
                            _status = response.status;
                        }
                    } catch (e) {
                        console.warn('Invalid JSON response');
                    }
                }
            });
               
          

            return _status;
        }
    });
</script>
