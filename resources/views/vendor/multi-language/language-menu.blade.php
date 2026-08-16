<li class="dropdown messages-menu">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
        <i class="fa fa-language"></i>
    </a>
    <ul class="dropdown-menu">
        <li>
            <!-- inner menu: contains the actual data -->
            <ul class="menu">
                @foreach($languages as $key => $language)
                    <li><!-- start message -->
                        <a class="language" href="#" data-id="{{ $key }}">
                            {{ $language }}
                            @if($key == $current)
                                <i class="fa fa-check pull-right"></i>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </li>
    </ul>
</li>

<script>
    $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}});

    $(".language").click(function () {
        let id = $(this).data('id');

        @if(auth()->check() && auth()->user()->type === 'bd')
        var url = "{{ url('bd/locale') }}";
        @elseif(auth()->check() && auth()->user()->type === 'superadmin')
        var url = "{{ url('superadmin/locale') }}";
        @else
        var url = "{{ admin_url('/locale') }}";
        @endif

        $.post(url, {
            _token: $('meta[name="csrf-token"]').attr('content'),
            locale: id
        }, function () {
            // Update all links on the page to include the new locale
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('locale', id);
            window.location.href = currentUrl.toString();
        });
    });
</script>

