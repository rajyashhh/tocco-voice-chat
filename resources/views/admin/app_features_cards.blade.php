{{-- Unified App Features page (owner 2026-08-14): cards are the index.
     Three groups on one page, each card carrying an instant AJAX switch:
       1. App switches   — settings-table keys the mobile app reads directly
                           (VersionController::getSettingsArray).
       2. In-app features — app_features flags gating mobile features via API
                           middleware/services (appFeatureEnable / isEnable).
       3. System modules  — app_features flags locking panel/backend modules
                           (validateStatusEnable).
     Colors come from the central theme tokens so the page follows light/dark
     automatically. Images are owner-uploaded; a neutral placeholder shows
     when absent. --}}
<style>
    .af-section {
        margin: 4px 4px 28px;
    }

    .af-section__header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0 0 4px;
    }

    .af-section__icon {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: var(--surface-sunken);
        color: var(--accent);
        font-size: 16px;
    }

    .af-section__title {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
        color: var(--text-primary);
    }

    .af-section__hint {
        margin: 0 0 14px;
        font-size: 12.5px;
        color: var(--text-muted);
    }

    .af-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 20px;
        padding: 8px 0 8px;
    }

    .af-card {
        display: flex;
        flex-direction: column;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg, 16px);
        box-shadow: var(--shadow-card);
        overflow: hidden;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    .af-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md, 0 4px 20px rgba(0,0,0,.10));
        border-color: var(--accent);
    }

    .af-card--off {
        opacity: .72;
    }

    .af-card__media {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 10;
        background: var(--surface-sunken);
        overflow: hidden;
    }

    .af-card__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .af-card__placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        font-size: 40px;
        background: linear-gradient(135deg, var(--surface-sunken), var(--surface-raised));
    }

    .af-card__status {
        position: absolute;
        inset-inline-end: 10px;
        inset-block-start: 10px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.6;
    }

    .af-card__status--on { background: var(--chip-success-bg); color: var(--chip-success-fg); }
    .af-card__status--off { background: var(--chip-neutral-bg); color: var(--chip-neutral-fg); }

    .af-card__body {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 16px;
        flex: 1;
    }

    .af-card__title {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
    }

    .af-card__desc {
        margin: 0;
        font-size: 13.5px;
        line-height: 1.65;
        color: var(--text-secondary);
        flex: 1;
    }

    .af-card__slug {
        margin-top: 4px;
        font-size: 11.5px;
        color: var(--text-muted);
        font-family: monospace;
        letter-spacing: .3px;
    }

    .af-card__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 16px;
        border-top: 1px solid var(--border);
        background: var(--surface-sunken);
    }

    .af-card__state-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary);
    }

    {{-- Compact card variant for the settings switches (no image). --}}
    .af-card--switch .af-card__body {
        flex-direction: row;
        align-items: center;
        gap: 12px;
    }

    .af-card__icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: var(--surface-sunken);
        color: var(--accent);
        font-size: 18px;
    }

    {{-- Toggle switch — theme-token colors, RTL-safe (transform is direction-agnostic). --}}
    .af-switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 26px;
        flex-shrink: 0;
    }

    .af-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .af-switch__slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background: var(--chip-neutral-bg);
        border: 1px solid var(--border);
        border-radius: 999px;
        transition: background .25s ease, border-color .25s ease;
    }

    .af-switch__slider::before {
        content: "";
        position: absolute;
        height: 20px;
        width: 20px;
        inset-inline-start: 2px;
        top: 2px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,.25);
        transition: transform .25s ease;
    }

    .af-switch input:checked + .af-switch__slider {
        background: var(--accent);
        border-color: var(--accent);
    }

    [dir="ltr"] .af-switch input:checked + .af-switch__slider::before { transform: translateX(22px); }
    [dir="rtl"] .af-switch input:checked + .af-switch__slider::before { transform: translateX(-22px); }

    .af-switch input:disabled + .af-switch__slider {
        opacity: .5;
        cursor: wait;
    }

    .af-toolbar {
        display: flex;
        justify-content: flex-end;
        padding: 4px 4px 0;
    }

    .af-empty {
        padding: 48px 16px;
        text-align: center;
        color: var(--text-muted);
    }
</style>

@php $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr'; @endphp

<div dir="{{ $dir }}">

    <div class="af-toolbar">
        <a href="{{ url('admin/app-features-list') }}" class="btn btn-sm btn-primary">
            <i class="fa fa-list"></i> {{ __('Manage List') }}
        </a>
    </div>

    {{-- ════════ 1. Settings switches the app reads directly ════════ --}}
    <div class="af-section">
        <div class="af-section__header">
            <span class="af-section__icon"><i class="fa fa-mobile"></i></span>
            <h3 class="af-section__title">{{ __('App Switches') }}</h3>
        </div>
        <p class="af-section__hint">{{ __('Read live by the mobile app — turning a switch off hides the feature from users immediately.') }}</p>

        <div class="af-cards-grid">
            @foreach ($switches as $key => $switch)
                <div class="af-card af-card--switch {{ $switch['enabled'] ? '' : 'af-card--off' }}" data-card>
                    <div class="af-card__body">
                        <span class="af-card__icon"><i class="fa {{ $switch['icon'] }}"></i></span>
                        <div style="flex:1;min-width:0;">
                            <h3 class="af-card__title">{{ __($switch['label']) }}</h3>
                            <span class="af-card__slug">{{ $key }}</span>
                        </div>
                    </div>
                    <div class="af-card__footer">
                        <span class="af-card__state-label" data-state-label>
                            {{ $switch['enabled'] ? __('Enabled') : __('Disabled') }}
                        </span>
                        <label class="af-switch">
                            <input type="checkbox" {{ $switch['enabled'] ? 'checked' : '' }}
                                   data-af-toggle="setting" data-key="{{ $key }}">
                            <span class="af-switch__slider"></span>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ════════ 2 + 3. app_features flags ════════ --}}
    @foreach ([
        [
            'features' => $inAppFeatures,
            'icon' => 'fa-rocket',
            'title' => __('In-App Features'),
            'hint' => __('Feature flags for what users can reach inside the app (games, events, moments, chat…). Off blocks the feature\'s API for everyone.'),
        ],
        [
            'features' => $systemFeatures,
            'icon' => 'fa-cogs',
            'title' => __('System & Panel Modules'),
            'hint' => __('Flags that lock admin panel and backend modules. They do not appear to app users directly.'),
        ],
    ] as $group)
        <div class="af-section">
            <div class="af-section__header">
                <span class="af-section__icon"><i class="fa {{ $group['icon'] }}"></i></span>
                <h3 class="af-section__title">{{ $group['title'] }}</h3>
            </div>
            <p class="af-section__hint">{{ $group['hint'] }}</p>

            <div class="af-cards-grid">
                @forelse ($group['features'] as $feature)
                    @php
                        $isAr = app()->getLocale() === 'ar';
                        $title = $isAr ? ($feature->name_ar ?: $feature->name) : ($feature->name ?: $feature->name_ar);
                        $desc = $isAr
                            ? ($feature->description_ar ?: $feature->description)
                            : ($feature->description ?: $feature->description_ar);
                        $imagePath = getImagePath($feature->image);
                        $hasImage = $feature->image && isImageExists($imagePath);
                    @endphp
                    <div class="af-card {{ $feature->status ? '' : 'af-card--off' }}" data-card>
                        <div class="af-card__media">
                            @if ($hasImage)
                                <img src="{{ $imagePath }}" alt="{{ $title }}">
                            @else
                                <div class="af-card__placeholder"><i class="fa fa-image"></i></div>
                            @endif
                            <span class="af-card__status {{ $feature->status ? 'af-card__status--on' : 'af-card__status--off' }}" data-status-chip>
                                {{ $feature->status ? __('open') : __('close') }}
                            </span>
                        </div>
                        <div class="af-card__body">
                            <h3 class="af-card__title">{{ $title }}</h3>
                            @if ($desc)
                                <p class="af-card__desc">{{ $desc }}</p>
                            @endif
                            <span class="af-card__slug">{{ $feature->slug }}</span>
                        </div>
                        <div class="af-card__footer">
                            <span class="af-card__state-label" data-state-label>
                                {{ $feature->status ? __('Enabled') : __('Disabled') }}
                            </span>
                            <label class="af-switch">
                                <input type="checkbox" {{ $feature->status ? 'checked' : '' }}
                                       data-af-toggle="feature" data-id="{{ $feature->id }}">
                                <span class="af-switch__slider"></span>
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="af-empty">{{ __('No data') }}</div>
                @endforelse
            </div>
        </div>
    @endforeach

</div>

<script>
    (function () {
        var LABEL_ON = @json(__('Enabled'));
        var LABEL_OFF = @json(__('Disabled'));
        var CHIP_ON = @json(__('open'));
        var CHIP_OFF = @json(__('close'));
        var URLS = {
            feature: "{{ url('admin/app-features/toggle-status') }}",
            setting: "{{ url('admin/app-features/toggle-setting') }}"
        };

        function paint(input, on) {
            var card = input.closest('[data-card]');
            if (!card) return;
            card.classList.toggle('af-card--off', !on);
            var label = card.querySelector('[data-state-label]');
            if (label) label.textContent = on ? LABEL_ON : LABEL_OFF;
            var chip = card.querySelector('[data-status-chip]');
            if (chip) {
                chip.textContent = on ? CHIP_ON : CHIP_OFF;
                chip.classList.toggle('af-card__status--on', on);
                chip.classList.toggle('af-card__status--off', !on);
            }
        }

        function bind() {
            document.querySelectorAll('[data-af-toggle]').forEach(function (input) {
                if (input.dataset.afBound) return;
                input.dataset.afBound = '1';

                input.addEventListener('change', function () {
                    var on = input.checked;
                    var type = input.dataset.afToggle;
                    var payload = { _token: LA.token };

                    if (type === 'feature') {
                        payload.id = input.dataset.id;
                        payload.status = on ? 1 : 0;
                    } else {
                        payload.key = input.dataset.key;
                        payload.value = on ? 1 : 0;
                    }

                    input.disabled = true;

                    $.post(URLS[type], payload)
                        .done(function (res) {
                            paint(input, on);
                            if (typeof toastr !== 'undefined') {
                                toastr.success(res.message || @json(__('Settings updated successfully!')));
                            }
                        })
                        .fail(function () {
                            input.checked = !on; // revert
                            if (typeof toastr !== 'undefined') {
                                toastr.error(@json(__('Something went wrong')));
                            }
                        })
                        .always(function () {
                            input.disabled = false;
                        });
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bind);
        } else {
            bind();
        }
        $(document).on('pjax:complete', bind);
    })();
</script>