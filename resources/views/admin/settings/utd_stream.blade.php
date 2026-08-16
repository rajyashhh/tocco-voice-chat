@php
    $baseUrl = url('/api/stream/webhooks');

    // تعريف الويب هوك المنظمة حسب الفئات
    $webhookCategories = [
        [
            'name' => __('admin.webhook_category_rooms_streaming'),
            'icon' => 'fa-video',
            'gradient' => 'linear-gradient(135deg, #6366f1, #818cf8)',
            'webhooks' => [
                [
                    'event' => 'room_started',
                    'name' => __('admin.webhook_room_started_name'),
                    'description' => __('admin.webhook_room_started_desc')
                ],
                [
                    'event' => 'room_finished',
                    'name' => __('admin.webhook_room_finished_name'),
                    'description' => __('admin.webhook_room_finished_desc')
                ],
                [
                    'event' => 'participant_joined',
                    'name' => __('admin.webhook_participant_joined_name'),
                    'description' => __('admin.webhook_participant_joined_desc')
                ],
                [
                    'event' => 'participant_left',
                    'name' => __('admin.webhook_participant_left_name'),
                    'description' => __('admin.webhook_participant_left_desc')
                ],
                [
                    'event' => 'track_published',
                    'name' => __('admin.webhook_track_published_name'),
                    'description' => __('admin.webhook_track_published_desc')
                ],
                [
                    'event' => 'track_unpublished',
                    'name' => __('admin.webhook_track_unpublished_name'),
                    'description' => __('admin.webhook_track_unpublished_desc')
                ],
            ]
        ],
        [
            'name' => __('admin.webhook_category_calls'),
            'icon' => 'fa-phone',
            'gradient' => 'linear-gradient(135deg, #10b981, #34d399)',
            'webhooks' => [
                [
                    'event' => 'call_initiated',
                    'name' => __('admin.webhook_call_initiated_name'),
                    'description' => __('admin.webhook_call_initiated_desc')
                ],
                [
                    'event' => 'call_ringing',
                    'name' => __('admin.webhook_call_ringing_name'),
                    'description' => __('admin.webhook_call_ringing_desc')
                ],
                [
                    'event' => 'call_accepted',
                    'name' => __('admin.webhook_call_accepted_name'),
                    'description' => __('admin.webhook_call_accepted_desc')
                ],
                [
                    'event' => 'call_rejected',
                    'name' => __('admin.webhook_call_rejected_name'),
                    'description' => __('admin.webhook_call_rejected_desc')
                ],
                [
                    'event' => 'call_busy',
                    'name' => __('admin.webhook_call_busy_name'),
                    'description' => __('admin.webhook_call_busy_desc')
                ],
                [
                    'event' => 'call_ended',
                    'name' => __('admin.webhook_call_ended_name'),
                    'description' => __('admin.webhook_call_ended_desc')
                ],
                [
                    'event' => 'call_missed',
                    'name' => __('admin.webhook_call_missed_name'),
                    'description' => __('admin.webhook_call_missed_desc')
                ],
            ]
        ],
        [
            'name' => __('admin.webhook_category_presence'),
            'icon' => 'fa-user-circle',
            'gradient' => 'linear-gradient(135deg, #3b82f6, #60a5fa)',
            'webhooks' => [
                [
                    'event' => 'user_online',
                    'name' => __('admin.webhook_user_online_name'),
                    'description' => __('admin.webhook_user_online_desc')
                ],
                [
                    'event' => 'user_offline',
                    'name' => __('admin.webhook_user_offline_name'),
                    'description' => __('admin.webhook_user_offline_desc')
                ],
            ]
        ],
        [
            'name' => __('admin.webhook_category_messaging'),
            'icon' => 'fa-comments',
            'gradient' => 'linear-gradient(135deg, #d946ef, #e879f9)',
            'webhooks' => [
                [
                    'event' => 'message_sent',
                    'name' => __('admin.webhook_message_sent_name'),
                    'description' => __('admin.webhook_message_sent_desc')
                ],
            ]
        ]
    ];

    $totalEvents = array_sum(array_map(function($cat) { return count($cat['webhooks']); }, $webhookCategories));
@endphp

<div id="utdStream" class="settings-section">

    {{-- ══════════════════════════════════════════════════════════
         SECTION HEADER
    ══════════════════════════════════════════════════════════ --}}
    <div class="section-header-bar">
        <div class="section-header-icon"><i class="fas fa-broadcast-tower"></i></div>
        <div class="section-header-text">
            <h3>{{ __('UTD Stream') }}</h3>
            <p>{{ __('Real-time audio/video engine — credentials, settings & webhooks') }}</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         (a) CREDENTIALS
    ══════════════════════════════════════════════════════════ --}}
    <div class="rt-providers-grid">

        {{-- ── UTD-STREAM Card ───────────────────────────────────── --}}
        <form class="no-background-form" action="{{ route('admin.update-agora-zego') }}" method="POST">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
            <div class="rt-provider-card">
                <div class="rt-provider-header" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6);">
                    <div class="rt-provider-logo">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="rt-provider-title">
                        <h5>{{ __('UTD-STREAM') }}</h5>
                        <span>{{ __('Streaming & media service') }}</span>
                    </div>
                </div>
                <div class="rt-provider-body">
                    <div class="rt-input-row">
                        <div class="rt-input-group">
                            <label><i class="fas fa-fingerprint"></i> {{ __('admin.app_id') }}</label>
                            <input type="text" name="utd_stream_app_id" placeholder="App ID"
                                   value="{{ $utd_stream_app_id }}" class="form-control rt-input" required>
                        </div>
                        <div class="rt-input-group">
                            <label><i class="fas fa-key"></i> {{ __('admin.server_secret') }}</label>
                            <input type="text" name="utd_stream_server_secret" placeholder="Server Secret"
                                   value="{{ $utd_stream_server_secret }}" class="form-control rt-input" required>
                        </div>
                    </div>
                    <div class="rt-input-group">
                        <label><i class="fas fa-key"></i> {{ __('App Key') }}</label>
                        <input type="text" name="utd_stream_app_key" placeholder="{{ __('App Key') }}"
                               value="{{ $utd_stream_app_key ?? '' }}" class="form-control rt-input">
                    </div>
                    <div class="rt-input-group">
                        <label><i class="fas fa-lock"></i> Callback Secret</label>
                        <input type="text" name="utd_stream_callback_secret" placeholder="Callback Secret"
                               value="{{ $utd_stream_callback_secret ?? '' }}" class="form-control rt-input" required>
                        <small class="rt-hint">{{ __('Used to verify webhook signatures') }}</small>
                    </div>
                    <div class="rt-input-group">
                        <label><i class="fas fa-link"></i> Webhook URL</label>
                        <div class="rt-webhook-wrap">
                            <input type="text" id="utd_stream_webhook_url"
                                   value="{{ url('/api/utd-stream-webhook') }}"
                                   class="form-control rt-input rt-input-readonly" readonly>
                            <button class="rt-copy-btn" type="button" onclick="copyWebhookUrl(event)" title="Copy">
                                <i class="fa fa-copy"></i>
                            </button>
                        </div>
                        <small class="rt-hint">{{ __('This URL is used by UTD-STREAM to send webhook events') }}</small>
                    </div>
                </div>
                <div class="rt-provider-footer">
                    <button type="submit" class="btn rt-btn-save">
                        <i class="fas fa-save"></i> {{ __('save') }}
                    </button>
                </div>
            </div>
        </form>

    </div>

    {{-- ══════════════════════════════════════════════════════════
         (b) AUDIO & VIDEO SETTINGS
    ══════════════════════════════════════════════════════════ --}}

    {{-- ── Sound System ──────────────────────────────────────── --}}
    <form action="{{ route('admin.update-agora-zego') }}" method="POST">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
        <div class="rt-selector-section">
            <div class="rt-selector-header">
                <div class="rt-selector-icon" style="background: linear-gradient(135deg, var(--accent), var(--accent-strong));">
                    <i class="fas fa-volume-up"></i>
                </div>
                <div>
                    <h5>{{ __('Sound System Setting') }}</h5>
                    <span>{{ __('Choose the active sound provider') }}</span>
                </div>
            </div>
            <div class="rt-radio-grid">
                @foreach ([
                    ['id' => 'utdStreamSoundRadio', 'value' => '4', 'label' => __('UTD-STREAM'), 'icon' => 'fas fa-video', 'color' => '#7c3aed', 'var' => $soundLibrary],
                ] as $opt)
                    <label class="rt-radio-card {{ $opt['var'] == $opt['value'] ? 'active' : '' }}" for="{{ $opt['id'] }}">
                        <input type="radio" id="{{ $opt['id'] }}" class="custom-radio libraryRealTime"
                               name="sound_library" value="{{ $opt['value'] }}" {{ $opt['var'] == $opt['value'] ? 'checked' : '' }}>
                        <div class="rt-radio-icon" style="background: {{ $opt['color'] }};">
                            <i class="{{ $opt['icon'] }}"></i>
                        </div>
                        <span class="rt-radio-label">{{ $opt['label'] }}</span>
                        <div class="rt-radio-check"><i class="fas fa-check"></i></div>
                    </label>
                @endforeach
            </div>
        </div>
    </form>

    {{-- ── Video System ──────────────────────────────────────── --}}
    <form action="{{ route('admin.update-agora-zego') }}" method="POST">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
        <div class="rt-selector-section">
            <div class="rt-selector-header">
                <div class="rt-selector-icon" style="background: linear-gradient(135deg, #ec4899, #f472b6);">
                    <i class="fas fa-video"></i>
                </div>
                <div>
                    <h5>{{ __('Video System Setting') }}</h5>
                    <span>{{ __('Choose the active video provider') }}</span>
                </div>
            </div>
            <div class="rt-radio-grid">
                @foreach ([
                    ['id' => 'utdStreamVideoRadio', 'value' => '4', 'label' => __('UTD-STREAM'), 'icon' => 'fas fa-video', 'color' => '#7c3aed', 'var' => $videoLibrary],
                ] as $opt)
                    <label class="rt-radio-card {{ $opt['var'] == $opt['value'] ? 'active' : '' }}" for="{{ $opt['id'] }}">
                        <input type="radio" id="{{ $opt['id'] }}" class="custom-radio libraryRealTime"
                               name="video_library" value="{{ $opt['value'] }}" {{ $opt['var'] == $opt['value'] ? 'checked' : '' }}>
                        <div class="rt-radio-icon" style="background: {{ $opt['color'] }};">
                            <i class="{{ $opt['icon'] }}"></i>
                        </div>
                        <span class="rt-radio-label">{{ $opt['label'] }}</span>
                        <div class="rt-radio-check"><i class="fas fa-check"></i></div>
                    </label>
                @endforeach
            </div>
        </div>
    </form>

    {{-- ── Live System ───────────────────────────────────────── --}}
    <form action="{{ route('admin.update-agora-zego') }}" method="POST">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
        <div class="rt-selector-section">
            <div class="rt-selector-header">
                <div class="rt-selector-icon" style="background: linear-gradient(135deg, var(--accent), var(--accent-strong));">
                    <i class="fas fa-broadcast-tower"></i>
                </div>
                <div>
                    <h5>{{ __('Live System Setting') }}</h5>
                    <span>{{ __('Choose the active live streaming mode') }}</span>
                </div>
            </div>
            <div class="rt-radio-grid rt-radio-grid-3">
                @foreach ([
                    ['id' => 'rtcLiveRadio', 'value' => '0', 'label' => __('admin.RTC'), 'icon' => 'fas fa-phone-alt', 'color' => '#6366f1', 'var' => $liveLibrary],
                    ['id' => 'cdnLiveRadio', 'value' => '1', 'label' => __('admin.CDN'), 'icon' => 'fas fa-server', 'color' => '#10b981', 'var' => $liveLibrary],
                    ['id' => 'l3LiveRadio', 'value' => '2', 'label' => __('admin.L3'), 'icon' => 'fas fa-layer-group', 'color' => '#06b6d4', 'var' => $liveLibrary],
                ] as $opt)
                    <label class="rt-radio-card {{ $opt['var'] == $opt['value'] ? 'active' : '' }}" for="{{ $opt['id'] }}">
                        <input type="radio" id="{{ $opt['id'] }}" class="custom-radio libraryRealTime"
                               name="live_library" value="{{ $opt['value'] }}" {{ $opt['var'] == $opt['value'] ? 'checked' : '' }}>
                        <div class="rt-radio-icon" style="background: {{ $opt['color'] }};">
                            <i class="{{ $opt['icon'] }}"></i>
                        </div>
                        <span class="rt-radio-label">{{ $opt['label'] }}</span>
                        <div class="rt-radio-check"><i class="fas fa-check"></i></div>
                    </label>
                @endforeach
            </div>
        </div>
    </form>

    {{-- ── Auto Preview ──────────────────────────────────────── --}}
    <form id="autoPreviewForm" action="{{ route('admin.update-agora-zego') }}" method="POST">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
        <div class="rt-selector-section">
            <div class="rt-selector-header" style="margin-bottom: 0; padding-bottom: 0; border-bottom: none;">
                <div class="rt-selector-icon" style="background: linear-gradient(135deg, #14b8a6, #2dd4bf);">
                    <i class="fas fa-eye"></i>
                </div>
                <div style="flex: 1;">
                    <h5>{{ __('admin.is_preview') }}</h5>
                    <span>{{ __('Enable auto preview for streams') }}</span>
                </div>
                <div class="rt-preview-switch">
                    <input type="hidden" name="is_auto_preview" value="0">
                    <input type="checkbox" name="is_auto_preview" value="1" data-bootstrap-switch
                        {{ $is_auto_preview ? 'checked' : '' }}>
                </div>
            </div>
        </div>
    </form>

    {{-- ══════════════════════════════════════════════════════════
         (c) WEBHOOKS
    ══════════════════════════════════════════════════════════ --}}
    <div class="form">
        <div class="as-section">
            <div class="as-section-header">
                <div class="as-section-icon" style="background: linear-gradient(135deg, var(--accent), var(--accent-strong));">
                    <i class="fa fa-link"></i>
                </div>
                <div>
                    <h5>{{ __('admin.Base Webhook URL') }}</h5>
                    <span>{{ __('Configure this URL in your UTD-STREAM dashboard') }}</span>
                </div>
            </div>

            <div class="as-field-card">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text"
                           value="{{ $baseUrl }}"
                           class="form-control"
                           readonly
                           style="background-color: var(--table-background-color, #fff); border: 1px solid #e2e8f0; color: #475569; font-family: 'Courier New', monospace; font-size: 14px; font-weight: 500; flex: 1; padding: 12px; border-radius: 12px;">
                    <button type="button" class="btn webhook-copy-btn-base" data-copy="{{ $baseUrl }}"
                            style="background: linear-gradient(135deg, var(--accent), var(--accent-strong)); color: var(--accent-contrast); padding: 12px 25px; border: none; border-radius: 12px; min-width: 120px; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 14px var(--accent-soft);">
                        <i class="fa fa-copy"></i> <span class="copy-text">{{ __('admin.Copy') }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Webhook Categories -->
        @foreach($webhookCategories as $index => $category)
        <div class="as-section" style="animation: fadeIn 0.5s ease-in {{$index * 0.1}}s backwards;">
            <div class="as-section-header">
                <div class="as-section-icon" style="background: {{ $category['gradient'] }};">
                    <i class="fa {{ $category['icon'] }}"></i>
                </div>
                <div>
                    <h5>{{ $category['name'] }}</h5>
                    <span>{{ count($category['webhooks']) }} {{ __('admin.events') }}</span>
                </div>
            </div>

            <div class="as-fields-grid">
                @foreach($category['webhooks'] as $webhook)
                <div class="as-field-card webhook-item">
                    <div class="as-field-top">
                        <div class="as-field-icon" style="background: {{ $category['gradient'] }};">
                            <i class="fa fa-bolt"></i>
                        </div>
                        <label class="as-field-label">{{ $webhook['name'] }}</label>
                    </div>
                    <div style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 14px;">
                        {{ $webhook['description'] }}
                    </div>
                    <div style="background: var(--box-background-color, #f8f9fa); padding: 10px; border-radius: 8px; margin-bottom: 12px;">
                        <div style="font-size: 11px; color: #94a3b8; margin-bottom: 5px; text-transform: uppercase; font-weight: 600;">Event Type:</div>
                        <code style="color: #475569; font-size: 12px; background: transparent; font-weight: 500;">{{ $webhook['event'] }}</code>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="text"
                               value="{{ $baseUrl }}/{{ $webhook['event'] }}"
                               class="form-control"
                               readonly
                               style="background-color: #fff; border: 1px solid #e2e8f0; color: #475569; font-family: 'Courier New', monospace; font-size: 11px; flex: 1; padding: 10px; border-radius: 8px;">
                        <button type="button" class="btn btn-sm webhook-copy-btn-item" data-copy="{{ $baseUrl }}/{{ $webhook['event'] }}"
                                style="background: linear-gradient(135deg, var(--accent), var(--accent-strong)); color: var(--accent-contrast); padding: 10px 15px; border: none; border-radius: 8px; transition: all 0.3s; white-space: nowrap; box-shadow: 0 2px 6px var(--accent-soft);">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <script>
        function initZegoSwitch() {
            $('input[data-bootstrap-switch]').each(function () {
                $(this).bootstrapSwitch('state', $(this).prop('checked'), true);
            });
        }

        function initIsPreviewSwitch() {
            const $switch = $('input[name="is_auto_preview"][data-bootstrap-switch]');

            $switch.each(function () {
                $(this).bootstrapSwitch('state', $(this).prop('checked'), true);
            });

            $switch.on('switchChange.bootstrapSwitch', function (event, state) {
                const form = $('#autoPreviewForm');
                const formData = form.serializeArray();
                const newValue = state ? 1 : 0;
                formData.push({name: 'is_auto_preview', value: newValue});

                $.ajax({
                    url: form.attr('action'),
                    method: form.attr('method'),
                    data: formData,
                    success: function () {
                        console.log('is_auto_preview updated to', newValue);
                    },
                    error: function (xhr) {
                        console.error('Error updating is_auto_preview:', xhr.responseText);
                    }
                });
            });
        }

        // Radio card active state toggle
        document.querySelectorAll('.rt-radio-card input[type="radio"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                const name = this.name;
                document.querySelectorAll('.rt-radio-card input[name="' + name + '"]').forEach(function(r) {
                    r.closest('.rt-radio-card').classList.remove('active');
                });
                this.closest('.rt-radio-card').classList.add('active');
            });
        });

        $(document).ready(function() {
            initZegoSwitch();
            initIsPreviewSwitch();
        });
        $(document).on('pjax:success', function() {
            initZegoSwitch();
            initIsPreviewSwitch();
        });

        function copyWebhookUrl(event) {
            const webhookInput = document.getElementById('utd_stream_webhook_url');
            const btn = event.currentTarget;

            const tempInput = document.createElement('input');
            tempInput.value = webhookInput.value;
            document.body.appendChild(tempInput);
            tempInput.select();
            tempInput.setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');
                document.body.removeChild(tempInput);

                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fa fa-check"></i>';
                btn.style.color = '#10b981';

                setTimeout(function() {
                    btn.innerHTML = originalHTML;
                    btn.style.color = '';
                }, 2000);
            } catch (err) {
                console.error('Failed to copy: ', err);
                document.body.removeChild(tempInput);
                alert('Failed to copy URL');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Handle base URL copy button
            document.querySelectorAll('.webhook-copy-btn-base').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const textToCopy = this.getAttribute('data-copy');
                    copyText(this, textToCopy);
                });
            });

            // Handle webhook item copy buttons
            document.querySelectorAll('.webhook-copy-btn-item').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const textToCopy = this.getAttribute('data-copy');
                    copyText(this, textToCopy);
                });
            });

            function copyText(button, text) {
                const originalHTML = button.innerHTML;
                const originalBg = button.style.background;

                // Create temporary textarea
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                textarea.style.top = '0';
                document.body.appendChild(textarea);

                try {
                    // Select and copy
                    textarea.focus();
                    textarea.select();
                    const successful = document.execCommand('copy');

                    document.body.removeChild(textarea);

                    if (successful) {
                        // Success feedback
                        button.innerHTML = '<i class="fa fa-check"></i> تم النسخ';
                        button.style.background = 'linear-gradient(135deg, #10b981, #059669)';

                        setTimeout(() => {
                            button.innerHTML = originalHTML;
                            button.style.background = originalBg;
                        }, 2000);
                    } else {
                        throw new Error('Copy failed');
                    }
                } catch (err) {
                    console.error('Copy error:', err);
                    document.body.removeChild(textarea);

                    // Error feedback
                    button.innerHTML = '<i class="fa fa-times"></i> فشل';
                    button.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';

                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.style.background = originalBg;
                    }, 2000);
                }
            }
        });
    </script>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     SCOPED STYLES
═══════════════════════════════════════════════════════════════ --}}
<style>
    /* ── Provider Cards Grid ───────────────────────────────────── */
    .rt-providers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 20px;
        margin-bottom: 28px;
    }
    .rt-providers-grid form {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: auto !important;
    }

    /* ── Provider Card ─────────────────────────────────────────── */
    .rt-provider-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .dark-mode .rt-provider-card {
        background: #1e293b;
        border-color: rgba(255,255,255,0.06);
    }
    .rt-provider-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .dark-mode .rt-provider-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.25);
    }

    /* ── Provider Header ───────────────────────────────────────── */
    .rt-provider-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px 20px;
        color: #fff;
        position: relative;
    }
    .rt-provider-logo {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        backdrop-filter: blur(10px);
    }
    .rt-provider-title h5 {
        margin: 0 0 2px 0;
        font-size: 16px;
        font-weight: 700;
        color: #fff;
    }
    .rt-provider-title span {
        font-size: 12px;
        opacity: 0.85;
    }
    .rt-provider-badge {
        position: absolute;
        top: 12px;
        right: 14px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .rtl .rt-provider-badge {
        right: auto;
        left: 14px;
    }
    .rt-badge-soon {
        background: rgba(255,255,255,0.25);
        color: #fff;
        backdrop-filter: blur(10px);
    }

    /* ── Provider Body ─────────────────────────────────────────── */
    .rt-provider-body {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .rt-input-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .rt-input-group label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
    }
    .dark-mode .rt-input-group label {
        color: #94a3b8;
    }
    .rt-input-group label i {
        font-size: 11px;
        opacity: 0.7;
    }
    .rt-input {
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        padding: 10px 14px !important;
        font-size: 13px !important;
        font-weight: 500;
        transition: all 0.3s ease;
        background: #f8fafc !important;
    }
    .dark-mode .rt-input {
        background: #0f172a !important;
        border-color: rgba(255,255,255,0.08) !important;
        color: #e2e8f0 !important;
    }
    .rt-input:focus {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px var(--accent-soft) !important;
        outline: none;
    }
    .rt-input-readonly {
        background: #f1f5f9 !important;
        cursor: not-allowed;
        color: #64748b !important;
    }
    .dark-mode .rt-input-readonly {
        background: rgba(255,255,255,0.04) !important;
        color: #94a3b8 !important;
    }
    .rt-input-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .rt-hint {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 4px;
    }

    /* ── Webhook Wrap ──────────────────────────────────────────── */
    .rt-webhook-wrap {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .rt-webhook-wrap .rt-input {
        flex: 1;
    }
    .rt-copy-btn {
        width: 40px !important;
        height: 40px;
        border-radius: 10px !important;
        border: 1px solid #e2e8f0 !important;
        background: #f8fafc !important;
        color: #64748b !important;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        flex-shrink: 0;
        padding: 0 !important;
    }
    .dark-mode .rt-copy-btn {
        background: #0f172a !important;
        border-color: rgba(255,255,255,0.08) !important;
        color: #94a3b8 !important;
    }
    .rt-copy-btn:hover {
        background: #e2e8f0 !important;
        color: #1e293b !important;
    }

    /* ── Switch Inline ─────────────────────────────────────────── */
    .rt-switch-inline {
        padding-top: 6px;
    }

    /* ── Provider Footer ───────────────────────────────────────── */
    .rt-provider-footer {
        padding: 14px 20px;
        border-top: 1px solid #f1f5f9;
    }
    .dark-mode .rt-provider-footer {
        border-top-color: rgba(255,255,255,0.06);
    }
    .rt-btn-save {
        display: inline-flex !important;
        align-items: center;
        gap: 8px;
        padding: 10px 24px !important;
        background: linear-gradient(135deg, var(--accent), var(--accent-strong)) !important;
        color: var(--accent-contrast) !important;
        border: none !important;
        border-radius: 10px !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 3px 10px var(--accent-soft);
        width: auto !important;
    }
    .rt-btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px var(--accent-soft);
        background: linear-gradient(135deg, var(--accent-strong), var(--accent)) !important;
    }

    /* ── Selector Sections ─────────────────────────────────────── */
    .rt-selector-section {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .dark-mode .rt-selector-section {
        background: #1e293b;
        border-color: rgba(255,255,255,0.06);
    }
    .rt-selector-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dark-mode .rt-selector-header {
        border-bottom-color: rgba(255,255,255,0.06);
    }
    .rt-selector-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .rt-selector-header h5 {
        margin: 0 0 2px 0;
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
    }
    .dark-mode .rt-selector-header h5 {
        color: #f1f5f9;
    }
    .rt-selector-header > div span {
        font-size: 13px;
        color: #94a3b8;
    }

    /* ── Radio Card Grid ───────────────────────────────────────── */
    .rt-radio-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 12px;
    }
    .rt-radio-grid-3 {
        grid-template-columns: repeat(3, 1fr);
    }
    .rt-radio-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 18px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        background: #f8fafc;
        text-align: center;
    }
    .dark-mode .rt-radio-card {
        background: #0f172a;
        border-color: rgba(255,255,255,0.08);
    }
    .rt-radio-card input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .rt-radio-card:hover {
        border-color: var(--accent);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px var(--accent-soft);
    }
    .rt-radio-card.active {
        border-color: var(--accent);
        background: var(--accent-soft);
        box-shadow: 0 0 0 3px var(--accent-soft);
    }
    .dark-mode .rt-radio-card.active {
        border-color: var(--accent);
        background: var(--accent-soft);
    }
    .rt-radio-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    }
    .rt-radio-label {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }
    .dark-mode .rt-radio-label {
        color: #cbd5e1;
    }
    .rt-radio-check {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--accent);
        color: var(--accent-contrast);
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 10px;
    }
    .rtl .rt-radio-check {
        right: auto;
        left: 8px;
    }
    .rt-radio-card.active .rt-radio-check {
        display: flex;
    }

    /* ── Preview Switch ────────────────────────────────────────── */
    .rt-preview-switch {
        flex-shrink: 0;
    }

    /* ── Webhooks: Section Container ────────────────────────────── */
    .as-section {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: all 0.3s ease;
    }
    .dark-mode .as-section {
        background: #1e293b;
        border-color: rgba(255,255,255,0.06);
    }

    /* ── Webhooks: Section Header ───────────────────────────────── */
    .as-section-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dark-mode .as-section-header {
        border-bottom-color: rgba(255,255,255,0.06);
    }
    .as-section-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .as-section-header h5 {
        margin: 0 0 2px 0;
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    .dark-mode .as-section-header h5 {
        color: #f1f5f9;
    }
    .as-section-header span {
        font-size: 13px;
        color: #94a3b8;
    }

    /* ── Webhooks: Fields Grid ──────────────────────────────────── */
    .as-fields-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }

    /* ── Webhooks: Field Card ───────────────────────────────────── */
    .as-field-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px;
        transition: all 0.3s ease;
    }
    .dark-mode .as-field-card {
        background: #0f172a;
        border-color: rgba(255,255,255,0.06);
    }
    .as-field-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }
    .dark-mode .as-field-card:hover {
        border-color: rgba(255,255,255,0.12);
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    }
    .as-field-top {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }
    .as-field-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        flex-shrink: 0;
        box-shadow: 0 3px 8px rgba(0,0,0,0.12);
    }
    .as-field-label {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        margin: 0 !important;
        letter-spacing: 0.02em;
    }
    .dark-mode .as-field-label {
        color: #cbd5e1;
    }

    /* ── Webhooks: specific styles ──────────────────────────────── */
    .webhook-item:hover {
        transform: translateY(-2px);
    }

    .webhook-copy-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px var(--accent-soft) !important;
    }

    .webhook-copy-btn:active {
        transform: translateY(0);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Dark mode input styles */
    .dark-mode .as-field-card input.form-control {
        background-color: #1e293b !important;
        border-color: rgba(255,255,255,0.08) !important;
        color: #e2e8f0 !important;
    }

    /* ── Responsive ────────────────────────────────────────────── */
    @media (max-width: 992px) {
        .rt-providers-grid {
            grid-template-columns: 1fr;
        }
        .rt-radio-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    @media (max-width: 768px) {
        .rt-radio-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .rt-radio-grid-3 {
            grid-template-columns: repeat(3, 1fr);
        }
        .rt-input-row {
            grid-template-columns: 1fr;
        }
        .rt-selector-section {
            padding: 16px;
        }
        .as-fields-grid {
            grid-template-columns: 1fr !important;
        }
    }
    @media (max-width: 480px) {
        .rt-radio-grid,
        .rt-radio-grid-3 {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>
