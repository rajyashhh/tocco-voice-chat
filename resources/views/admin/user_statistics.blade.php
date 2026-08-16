@php
    $isRtl = app()->getLocale() === 'ar';
    $defaultImage = asset('images/businessman-icon.jpg');
    $fmt = fn($v) => is_numeric($v) ? number_format((float) $v) : $v;
@endphp

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    .us-wrap { padding: 4px 2px 24px; }

    .us-search-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 20px 22px;
        box-shadow: 0 8px 22px rgba(0,0,0,0.05);
        margin-bottom: 24px;
    }
    .us-search-card h3 {
        margin: 0 0 14px;
        font-size: 16px;
        font-weight: 700;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .us-search-form { display: flex; gap: 10px; flex-wrap: wrap; }
    .us-search-form input[type=text] {
        flex: 1 1 280px;
        min-width: 220px;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        font-size: 14px;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }
    .us-search-form input[type=text]:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .us-search-form button {
        padding: 12px 26px;
        border: none;
        border-radius: 10px;
        background: linear-gradient(135deg,#1d4ed8,#2563eb);
        color: #fff;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: transform .15s, box-shadow .2s;
    }
    .us-search-form button:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(37,99,235,.28); }
    .us-hint { margin-top: 10px; color: #6b7280; font-size: 12.5px; }

    .us-matches { list-style: none; margin: 16px 0 0; padding: 0; display: grid; gap: 8px; }
    .us-match {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px;
        text-decoration: none; color: #111827; transition: background .15s, border-color .15s;
    }
    .us-match:hover { background: #f9fafb; border-color: #c7d2fe; }
    .us-match img { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; }
    .us-match .m-name { font-weight: 600; }
    .us-match .m-uuid { font-size: 12px; color: #6b7280; }

    .us-empty {
        background: #fff; border: 1px dashed #d1d5db; border-radius: 14px;
        padding: 48px 20px; text-align: center; color: #6b7280;
    }
    .us-empty i { font-size: 40px; color: #cbd5e1; margin-bottom: 12px; display: block; }

    .us-profile {
        display: flex; align-items: center; gap: 16px;
        background: linear-gradient(135deg,#111827,#1f2937);
        color: #fff; border-radius: 16px; padding: 20px 24px; margin-bottom: 22px;
    }
    .us-profile img { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,.15); }
    .us-profile .p-name { font-size: 20px; font-weight: 700; }
    .us-profile .p-meta { font-size: 13px; opacity: .8; margin-top: 4px; }
    .us-profile .p-meta span { margin-inline-end: 14px; }

    .us-cards { display: grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap: 16px; margin-bottom: 26px; }
    .us-card {
        background: #fff; border: 1px solid #eef0f3; border-radius: 14px;
        padding: 18px; box-shadow: 0 8px 22px rgba(0,0,0,.05);
        transition: transform .25s, box-shadow .25s;
    }
    .us-card:hover { transform: translateY(-4px); box-shadow: 0 16px 34px rgba(0,0,0,.1); }
    .us-card .c-icon {
        width: 46px; height: 46px; border-radius: 12px; display: flex;
        align-items: center; justify-content: center; color: #fff; font-size: 19px; margin-bottom: 12px;
    }
    .us-card .c-label { font-size: 13.5px; color: #6b7280; font-weight: 600; }
    .us-card .c-value { font-size: 24px; font-weight: 800; color: #111827; margin-top: 4px; }
    .us-card .c-sub { font-size: 12px; color: #9ca3af; margin-top: 2px; }

    .us-tables { display: grid; grid-template-columns: repeat(auto-fit,minmax(320px,1fr)); gap: 18px; }
    .us-table-card { background: #fff; border: 1px solid #eef0f3; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 22px rgba(0,0,0,.05); }
    .us-table-card .t-head { padding: 15px 20px; font-weight: 700; font-size: 15px; color: #111827; border-bottom: 1px solid #f1f2f4; display: flex; align-items: center; gap: 8px; }
    .us-table-card table { width: 100%; border-collapse: collapse; }
    .us-table-card td { padding: 12px 20px; font-size: 14px; border-bottom: 1px solid #f5f6f7; }
    .us-table-card tr:last-child td { border-bottom: none; }
    .us-table-card td:last-child { text-align: end; font-weight: 700; color: #111827; }
    .us-table-card .t-total td { background: #f9fafb; font-weight: 800; }
</style>

<div class="us-wrap" @if($isRtl) dir="rtl" @endif>
    <div class="us-search-card">
        <h3><i class="fa-solid fa-magnifying-glass" style="color:#2563eb"></i> {{ __('Search for a user') }}</h3>
        <form class="us-search-form" method="GET">
            <input type="text" name="q" value="{{ $query }}" placeholder="{{ __('Enter UUID or name') }}" autofocus>
            <button type="submit">{{ __('Search') }}</button>
        </form>
        <div class="us-hint">{{ __('Search by exact UUID or part of the name.') }}</div>

        @if($matches->isNotEmpty())
            <ul class="us-matches">
                @foreach($matches as $m)
                    @php
                        $avatar = $m->profile?->avatar;
                        $url = $avatar ? getImagePath($avatar) : $defaultImage;
                        if (!isImageExists($url)) { $url = $defaultImage; }
                    @endphp
                    <a class="us-match" href="?user={{ urlencode($m->uuid) }}">
                        <img src="{{ $url }}" alt="">
                        <div>
                            <div class="m-name">{{ $m->name }}</div>
                            <div class="m-uuid">UUID: {{ $m->uuid }}</div>
                        </div>
                    </a>
                @endforeach
            </ul>
        @elseif($query !== '' && !$user)
            <div class="us-hint" style="color:#dc2626">{{ __('No users found.') }}</div>
        @endif
    </div>

    @if(!$user)
        <div class="us-empty">
            <i class="fa-solid fa-chart-simple"></i>
            {{ __('Search for a user to view their statistics.') }}
        </div>
    @else
        @php
            $avatar = $user->profile?->avatar;
            $url = $avatar ? getImagePath($avatar) : $defaultImage;
            if (!isImageExists($url)) { $url = $defaultImage; }
            $countryName = $user->country ? ($isRtl ? ($user->country->name ?: $user->country->e_name) : ($user->country->e_name ?: $user->country->name)) : null;
        @endphp

        <div class="us-profile">
            <img src="{{ $url }}" alt="">
            <div>
                <div class="p-name">{{ $user->name }}</div>
                <div class="p-meta">
                    <span><i class="fa-solid fa-hashtag"></i> {{ $user->uuid }}</span>
                    @if($user->phone)<span><i class="fa-solid fa-phone"></i> {{ $user->phone }}</span>@endif
                    @if($countryName)<span><i class="fa-solid fa-location-dot"></i> {{ $countryName }}</span>@endif
                </div>
            </div>
        </div>

        <div class="us-cards">
            @foreach($stats['cards'] as $card)
                <div class="us-card">
                    <div class="c-icon" style="background: {{ $card['color'] }}"><i class="fa-solid {{ $card['icon'] }}"></i></div>
                    <div class="c-label">{{ $card['label'] }}</div>
                    <div class="c-value">{{ $fmt($card['value']) }}</div>
                    @isset($card['sub'])<div class="c-sub">{{ $card['sub'] }}</div>@endisset
                </div>
            @endforeach
        </div>

        <div class="us-tables">
            <div class="us-table-card">
                <div class="t-head"><i class="fa-solid fa-arrow-trend-up" style="color:#16a34a"></i> {{ __('Earnings Breakdown') }}</div>
                <table>
                    @foreach($stats['breakdown'] as $label => $value)
                        <tr><td>{{ $label }}</td><td>{{ $fmt($value) }}</td></tr>
                    @endforeach
                    <tr class="t-total"><td>{{ __('total earned') }}</td><td>{{ $fmt($stats['totals']['earned']) }}</td></tr>
                </table>
            </div>

            <div class="us-table-card">
                <div class="t-head"><i class="fa-solid fa-arrow-trend-down" style="color:#dc2626"></i> {{ __('Spending Breakdown') }}</div>
                <table>
                    @foreach($stats['spending'] as $label => $value)
                        <tr><td>{{ $label }}</td><td>{{ $fmt($value) }}</td></tr>
                    @endforeach
                    <tr class="t-total"><td>{{ __('total losed') }}</td><td>{{ $fmt($stats['totals']['losed']) }}</td></tr>
                </table>
            </div>
        </div>
    @endif
</div>
