@foreach($css as $c)
    <link rel="stylesheet" href="{{ admin_asset("$c") }}">

@endforeach
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.10.5/viewer.min.css" />

@include('css.dynamic-style')
@include('css.responsive-style')
@include('css.theme-tokens')

