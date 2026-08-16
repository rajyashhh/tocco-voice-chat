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

<div id="utdStreamWebhooks" class="settings-section">
    <div class="section-header-bar">
        <div class="section-header-icon"><i class="fas fa-webhook"></i></div>
        <div class="section-header-text">
            <h3>{{ __('admin.UTD Stream Webhooks') }}</h3>
            <p>{{ __('Configure these webhook URLs in your UTD-STREAM dashboard to receive real-time events') }}</p>
        </div>
    </div>

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
</div>

<style>
    /* ── Section Container ─────────────────────────────────────── */
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

    /* ── Section Header ────────────────────────────────────────── */
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

    /* ── Fields Grid ───────────────────────────────────────────── */
    .as-fields-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }

    /* ── Field Card ────────────────────────────────────────────── */
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

    /* ── Webhook specific styles ─────────────────────────────── */
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

    @media (max-width: 768px) {
        .as-fields-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<script>
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
