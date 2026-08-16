@php
    $moment = $extras['moment'] ?? [];
    $reel   = $extras['reel'] ?? [];
@endphp

<div style="line-height:1.6">
    <b>{{ __('Moments') }}</b>
    <ul>
        <li><b>{{ __('Uploads') }}:</b> {{ $moment['upload'] ?? '-' }}</li>
        <li><b>{{ __('Likes') }}:</b> {{ $moment['likes'] ?? '-' }}</li>
        <li><b>{{ __('Comments') }}:</b> {{ $moment['comments'] ?? '-' }}</li>
    </ul>

    <b>{{ __('Reels') }}</b>
    <ul>
        <li><b>{{ __('Uploads') }}:</b> {{ $reel['upload'] ?? '-' }}</li>
        <li><b>{{ __('Likes') }}:</b> {{ $reel['likes'] ?? '-' }}</li>
        <li><b>{{ __('Comments') }}:</b> {{ $reel['comments'] ?? '-' }}</li>
    </ul>
</div>
