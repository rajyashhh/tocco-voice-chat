
 @php
    $enabledLanguages = Cache::rememberForever('languages', function () {
        return \App\Models\Language::where('is_enabled', true)
            ->pluck('name', 'code')
            ->toArray();
    });

    $languages = $enabledLanguages;

    // default first tab
    $defaultLang = array_key_first($languages);

    // previously selected tab (restore)
    $selectedLang = request()->input('tab', $defaultLang);

    // $model is a GiftCategory object on edit, but an empty array [] on create
    // (controller passes [] to form()). Reading ->title on an array 500s, so
    // resolve the titles defensively for both shapes.
    $titles = is_object($model) ? ($model->title ?? []) : (is_array($model) ? ($model['title'] ?? []) : []);
    if (!is_array($titles)) {
        $decoded = json_decode($titles, true);
        $titles = is_array($decoded) ? $decoded : [];
    }
@endphp

<ul class="nav nav-tabs" role="tablist">
    @foreach ($languages as $code => $label)
        <li class="nav-item {{ $code === $selectedLang ? 'active' : '' }}">
            <a class="nav-link"
               href="#lang-{{ $code }}"
               data-toggle="tab"
               data-lang="{{ $code }}">
                {{ __($label) }}
            </a>
        </li>
    @endforeach
</ul>

<div class="tab-content p-3 border border-top-0 rounded-bottom">
    @foreach ($languages as $code => $label)
        <div class="tab-pane fade {{ $code === $selectedLang ? 'in active show' : '' }}"
             id="lang-{{ $code }}">

            <div class="form-group">
                <label>{{ __('Title') }} ({{ $label }})</label>
                <input type="text"
                       name="title[{{ $code }}]"
                       class="form-control"
                       value="{{ old("title.$code", $titles[$code] ?? '') }}">
            </div>
        </div>
    @endforeach
</div>

<script>
    $(document).ready(function () {

        // Force first tab to show on initial load
        $('.nav-tabs li.active a').tab('show');

        // Save selected tab when user switches
        $('.nav-tabs a[data-toggle="tab"]').on('click', function () {
            let selected = $(this).data('lang');
            $('<input>').attr({
                type: 'hidden',
                name: 'tab',
                value: selected
            }).appendTo('form');
        });
    });
</script>

