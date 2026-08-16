@php
    $enabledLanguages = Cache::rememberForever('languages', function () {
        return \App\Models\Language::where('is_enabled', true)
            ->pluck('name', 'code')
            ->toArray();
    });

    $languages = $enabledLanguages;
    $defaultLang = array_key_first($languages);
    $selectedLang = request()->input('tab', $defaultLang);
    $words = $model->title ?? [];
@endphp

<ul class="nav nav-tabs" id="word-lang-tabs" role="tablist">
    @foreach ($languages as $code => $label)
        <li class="{{ $code === $selectedLang ? 'active' : '' }}">
            <a href="#word-lang-{{ $code }}"
               data-toggle="tab"
               data-lang="{{ $code }}">
                {{ __($label) }}
            </a>
        </li>
    @endforeach
</ul>

<div class="tab-content">
    @foreach ($languages as $code => $label)
        <div class="tab-pane {{ $code === $selectedLang ? 'active' : '' }}"
             id="word-lang-{{ $code }}">
            <div class="form-group" style="margin-top:10px; margin-bottom:0;">
                <label><b>{{ __('word') }} ({{ $label }})</b></label>
                <input type="text"
                       name="word[{{ $code }}]"
                       class="form-control"
                       value="{{ old("word.$code", $words[$code] ?? '') }}">
            </div>
        </div>
    @endforeach
</div>

<script>
    $(document).ready(function () {
        $('#word-lang-tabs li.active a').tab('show');
    });
</script>
