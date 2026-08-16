@php
    $allowedRoles = array_filter(array_map('trim', explode(',', $settings['games_allowed_roles'] ?? '')), 'strlen');
    $allowedRoles = array_map('intval', $allowedRoles);
    $roleOptions = [
        1 => __('host'),
        2 => __('agency-agent'),
        3 => __('shipping-agent'),
    ];
@endphp

{{-- Colors come from the unified admin theme tokens (theme-tokens.blade.php),
     so the card follows BOTH light and dark modes. Fallbacks mirror the old
     hardcoded dark palette. The purple header/save gradient is a fixed brand
     fill with white text — correct in both modes by design. --}}
<style>
    .gas-wrap {
        max-width: 720px;
    }
    .gas-card {
        background: var(--surface, #1f2937);
        border: 1px solid var(--border, #374151);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--shadow-card, 0 4px 18px rgba(0, 0, 0, 0.25));
    }
    .gas-card-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px 22px;
        background: linear-gradient(135deg, #6d28d9, #a78bfa);
        color: #fff;
    }
    .gas-card-header .gas-logo {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.18);
        font-size: 22px;
    }
    .gas-card-header h5 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
    }
    .gas-card-header span {
        font-size: 13px;
        opacity: 0.9;
    }
    .gas-card-body {
        padding: 22px;
        color: var(--text-primary, #e5e7eb);
    }
    .gas-group {
        margin-bottom: 20px;
    }
    .gas-group > label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--text-primary, #f3f4f6);
    }
    .gas-group > label i {
        margin-inline-end: 6px;
        color: #8b5cf6;
    }
    .gas-input {
        width: 100%;
        padding: 11px 13px;
        background: var(--input-bg, #111827);
        border: 1px solid var(--input-border, #374151);
        border-radius: 8px;
        color: var(--input-text, #fff);
        font-size: 14px;
    }
    .gas-input:focus {
        outline: none;
        border-color: #a78bfa;
        box-shadow: 0 0 0 2px rgba(167, 139, 250, 0.25);
    }
    .gas-hint {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        color: var(--text-secondary, #9ca3af);
    }
    .gas-roles {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .gas-role {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        background: var(--surface-sunken, #111827);
        border: 1px solid var(--border, #374151);
        border-radius: 8px;
        cursor: pointer;
        user-select: none;
    }
    .gas-role input {
        width: 16px;
        height: 16px;
        margin: 0;
        cursor: pointer;
    }
    .gas-divider {
        height: 1px;
        background: var(--border, #374151);
        margin: 22px 0;
        border: 0;
    }
    .gas-footer {
        padding: 16px 22px;
        background: var(--surface-raised, #111827);
        text-align: end;
    }
    .gas-btn-save {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        background: linear-gradient(135deg, #6d28d9, #a78bfa);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
    }
    .gas-btn-save:hover {
        opacity: 0.92;
    }
</style>

<div class="gas-wrap">
    <form action="{{ route('admin.games-access-settings.store') }}" method="POST">
        @csrf

        <div class="gas-card">
            <div class="gas-card-header">
                <div class="gas-logo"><i class="fas fa-gamepad"></i></div>
                <div>
                    <h5>{{ __('Play Conditions') }}</h5>
                    <span>{{ __('These are conditions, any one of which grants access. A user can play if ANY condition below is met: became a host, an agency agent, or a shipping agent, reached the minimum level, or reached the minimum total recharge.') }}</span>
                </div>
            </div>

            <div class="gas-card-body">

                {{-- ROLES --}}
                <div class="gas-group">
                    <label><i class="fas fa-user-tag"></i> {{ __('Role Condition — any matching role is enough') }}</label>
                    <div class="gas-roles">
                        @foreach($roleOptions as $value => $label)
                            <label class="gas-role">
                                <input type="checkbox" class="gas-role-cb" value="{{ $value }}"
                                       {{ in_array($value, $allowedRoles, true) ? 'checked' : '' }}>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <input type="hidden" name="games_allowed_roles" id="games_allowed_roles"
                           value="{{ implode(',', $allowedRoles) }}">
                    <small class="gas-hint">{{ __('Any user with one of the selected roles can play, regardless of level or recharge. Leave all unchecked to disable the role condition.') }}</small>
                </div>

                <hr class="gas-divider">

                {{-- MIN LEVEL --}}
                <div class="gas-group">
                    <label><i class="fas fa-layer-group"></i> {{ __('Level Condition') }}</label>
                    <input type="number" min="0" name="games_min_level" id="games_min_level"
                           class="gas-input" value="{{ $settings['games_min_level'] ?? 0 }}">
                    <small class="gas-hint">{{ __('Any user at or above this level can play. Set 0 to disable the level condition.') }}</small>
                </div>

                {{-- MIN RECHARGE --}}
                <div class="gas-group">
                    <label><i class="fas fa-coins"></i> {{ __('Recharge Condition') }}</label>
                    <input type="number" min="0" name="games_min_recharge"
                           class="gas-input" value="{{ $settings['games_min_recharge'] ?? 0 }}">
                    <small class="gas-hint">{{ __('Any user whose total recharge reaches this amount can play. Set 0 to disable the recharge condition.') }}</small>
                </div>

                <hr class="gas-divider">

                {{-- MAP GAME WIN COINS (absorbed from the retired game-settings page) --}}
                <div class="gas-group">
                    <label><i class="fas fa-map"></i> {{ __('game map win coins') }}</label>
                    <input type="number" min="1" name="game_map_win_coins"
                           class="gas-input" value="{{ $settings['game_map_win_coins'] ?? 10000 }}">
                    <small class="gas-hint">{{ __('Coins awarded for a winning map game round.') }}</small>
                </div>

            </div>

            <div class="gas-footer">
                <button type="submit" class="gas-btn-save">
                    <i class="fas fa-save"></i> {{ __('save') }}
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    (function () {
        var checkboxes = document.querySelectorAll('.gas-role-cb');
        var hidden = document.getElementById('games_allowed_roles');

        function syncRoles() {
            var values = [];
            checkboxes.forEach(function (cb) {
                if (cb.checked) values.push(cb.value);
            });
            hidden.value = values.join(',');
        }

        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', syncRoles);
        });
    })();
</script>
