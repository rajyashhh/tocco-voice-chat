@php
    use App\Models\FairLuckWallet;
    use App\Models\FairLuckWalletHistory;
    use App\Models\CoreWallet;
    use App\Services\FairLuck\V7\PoolManager;

    $settings = $fairLuckSettings;

    // ── Live wallet state (RAW reads — never getRedisBalance, which SETNX-seeds) ──
    $vaultRaw = FairLuckWallet::vaultRedis()->get(PoolManager::KEY_VAULT);
    $vaultBalance = $vaultRaw !== null
        ? (int) $vaultRaw
        : (int) (FairLuckWallet::where('wallet_type', FairLuckWallet::TYPE_UNIFIED_VAULT)->value('balance') ?? 0);

    // Master on/off switch (reuses the existing stop_luckyGift kill-switch in
    // settings.json — single source of truth). enabled = NOT stopped.
    $luckyEnabled = ((int) (settings()->get('stop_luckyGift') ?? 0)) !== 1;

    $zones = ['min' => 10000, 'tight' => 50000, 'target' => 200000, 'high' => 500000];
    $zone = $vaultBalance <= $zones['min'] ? 'CRITICAL' : ($vaultBalance <= $zones['tight'] ? 'TIGHT' : ($vaultBalance <= $zones['target'] ? 'NORMAL' : ($vaultBalance <= $zones['high'] ? 'GENEROUS' : 'DRAIN')));
    $zoneColor = ['CRITICAL' => '#dc3545', 'TIGHT' => '#e67e22', 'NORMAL' => '#3498db', 'GENEROUS' => '#28a745', 'DRAIN' => '#8e44ad'][$zone];

    // Live unified_vault history for the chart (the dead global_vault feed is gone).
    $vaultHistory = FairLuckWalletHistory::where('wallet_type', FairLuckWallet::TYPE_UNIFIED_VAULT)
        ->orderBy('created_at', 'desc')->limit(100)->get()->reverse()->values();

    $val = fn($k, $d = 0) => $settings[$k] ?? $d;
    $on = fn($k) => filter_var($settings[$k] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '';

    $appPct  = isset($settings['fair_luck_owner_fee_rate']) ? round((float) $settings['fair_luck_owner_fee_rate'] * 100, 2) : 1;
    $recvPct = isset($settings['fair_luck_receiver_fee_rate']) ? round((float) $settings['fair_luck_receiver_fee_rate'] * 100, 2) : 10;
    $rtpPct  = isset($settings['V7_target_rtp']) ? round((float) $settings['V7_target_rtp'] * 100, 1) : 89;
@endphp

<style>
    body.rtl .form-horizontal .control-label,
    [dir="rtl"] .form-horizontal .control-label { text-align: left !important; }
    body:not(.rtl):not([dir="rtl"]) .form-horizontal .control-label { text-align: right !important; }
    .form-horizontal .control-label { padding-top: 7px; margin-bottom: 0; }
    /* Self-contained toggle switch (the panel's built-in switch styling is broken). */
    .fl-switch { position: relative; display: inline-block; width: 54px; height: 28px; vertical-align: middle; margin: 4px 0; }
    .fl-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
    .fl-switch .fl-knob { position: absolute; inset: 0; cursor: pointer; background: #c9ced3; border-radius: 28px; transition: .25s; }
    .fl-switch .fl-knob:before { content: ""; position: absolute; height: 22px; width: 22px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: .25s; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
    .fl-switch input:checked + .fl-knob { background: #00a65a; }
    .fl-switch input:checked + .fl-knob:before { transform: translateX(26px); }
    .fl-hint { color: #8a8a8a; font-size: 12px; line-height: 1.7; }
    .fl-range { display: flex; align-items: center; gap: 12px; margin-bottom: 4px; }
    .fl-range input[type=range] { flex: 1; height: 6px; accent-color: #00a65a; cursor: pointer; min-width: 120px; }
    .fl-numwrap { width: 120px; flex: 0 0 120px; }
    .fl-save-bar { position: sticky; bottom: 0; z-index: 5; background: #f9fafb; border-top: 2px solid #e3e6ea; padding: 12px 18px; text-align: center; }
    .fl-save-bar .btn { padding: 10px 60px; font-size: 16px; font-weight: 600; }
    .fl-section-note { color:#555; background:#f4f8ff; border-right:3px solid #3c8dbc; padding:8px 12px; margin:0 0 12px; border-radius:3px; }
</style>

{{-- ═══════════════════════════════════════════════════════════════════════
     صفحة واحدة، حفظ واحد. كل إعدادات هدية الحظ مجمّعة في 4 مجموعات. كل حقل
     يُحفظ عبر FairLuckSettingsController::saveSettings (مصدر كتابة واحد).
     ═══════════════════════════════════════════════════════════════════════ --}}
<form id="luckyGiftForm" action="{{ admin_url('fairluck/save-settings') }}" method="post">
    @csrf

    {{-- ───────── السويتش الرئيسي (تشغيل/إيقاف هدايا الحظ) ───────── --}}
    <div class="box {{ $luckyEnabled ? 'box-success' : 'box-danger' }}">
        <div class="box-body form-horizontal">
            <div class="form-group" style="margin-bottom:0;">
                <label class="col-sm-3 control-label" style="font-size:15px;"><i class="fa fa-power-off"></i> {{ __('تشغيل هدايا الحظ') }}</label>
                <div class="col-sm-8">
                    <label class="fl-switch"><input type="checkbox" name="lucky_gift_enabled" value="1" {{ $luckyEnabled ? 'checked' : '' }}><span class="fl-knob"></span></label>
                    <span class="fl-hint">{{ __('السويتش الرئيسي. لو مقفول، أي حد يبعت هدية حظ في التطبيق هيشوف رسالة «هدايا الحظ معطلة الآن» ومش هيتخصم منه أي عملات.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ───────── 1) الاقتصاد الأساسي ───────── --}}
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-money"></i> {{ __('1) الاقتصاد الأساسي') }}</h3>
        </div>
        <div class="box-body form-horizontal">
            <div class="fl-section-note">{{ __('دي الأرقام اللي بتتحكم في الفلوس فعليًا. لازم: أرباح التطبيق + نسبة المستقبل + RTP ≤ 100%.') }}</div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('نسبة RTP') }}</label>
                <div class="col-sm-6">
                    <div class="fl-range">
                        <input type="range" min="50" max="100" step="0.1" value="{{ $rtpPct }}" data-target="fld_rtp">
                        <div class="fl-numwrap"><div class="input-group">
                            <input type="number" step="0.1" min="50" max="100" id="fld_rtp" name="V7_target_rtp" class="form-control" value="{{ $rtpPct }}">
                            <span class="input-group-addon">%</span>
                        </div></div>
                    </div>
                    <span class="fl-hint">{{ __('متوسط اللي بيرجع للّاعبين على المدى الطويل. مثال: 89% يعني من كل 1000 عملة اتبعتت، حوالي 890 ترجع مكاسب. أعلى = اللاعب يكسب ويفضل أكتر/ربحك أقل. أقصى حد متاح:') }} <b id="rtpCeiling">—</b>%</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('أرباح التطبيق') }}</label>
                <div class="col-sm-6">
                    <div class="fl-range">
                        <input type="range" min="0" max="20" step="0.1" value="{{ $appPct }}" data-target="fld_app">
                        <div class="fl-numwrap"><div class="input-group">
                            <input type="number" step="0.01" min="0" max="20" id="fld_app" name="fair_luck_owner_fee_rate" class="form-control" value="{{ $appPct }}">
                            <span class="input-group-addon">%</span>
                        </div></div>
                    </div>
                    <span class="fl-hint">{{ __('نسبتك الثابتة من كل هدية (سواء كسب المرسل أو خسر). مثال: 1% = من هدية 100 عملة تاخد 1 مضمونة. (0–20%)') }}</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('نسبة المستقبل') }}</label>
                <div class="col-sm-6">
                    <div class="fl-range">
                        <input type="range" min="0" max="50" step="0.1" value="{{ $recvPct }}" data-target="fld_recv">
                        <div class="fl-numwrap"><div class="input-group">
                            <input type="number" step="0.01" min="0" max="50" id="fld_recv" name="fair_luck_receiver_fee_rate" class="form-control" value="{{ $recvPct }}">
                            <span class="input-group-addon">%</span>
                        </div></div>
                    </div>
                    <span class="fl-hint">{{ __('نسبة ثابتة بتروح للشخص اللي اتبعتله الهدية، من كل هدية. مثال: 10% = المستقبل ياخد 10 من كل 100 مضمونة. (0–50%)') }}</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('حد الخزنة السالب') }}</label>
                <div class="col-sm-4">
                    <div class="input-group">
                        <input type="number" step="1" min="0" name="V7_negative_limit" class="form-control" value="{{ (int) $val('V7_negative_limit', 30000) }}">
                        <span class="input-group-addon">{{ __('عملة') }}</span>
                    </div>
                    <span class="fl-hint">{{ __('أقصى مبلغ تسمح لخزنة الحظ تنزل تحت الصفر عشان تدفع جايزة كبيرة لما تكون فاضية (زي سُلفة مؤقتة). رقم ثابت بالعملات — مش نسبة من التداول. أعلى = جوائز أكبر/مخاطرة أكبر؛ أقل = أأمن. الافتراضي 30000.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ───────── 2) حماية المبتدئين ───────── --}}
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-life-ring"></i> {{ __('2) حماية المبتدئين') }}</h3>
        </div>
        <div class="box-body form-horizontal">
            <div class="fl-section-note">{{ __('بتدّي اللاعب الجديد المؤهَّل تجربة فوز أكرم في أول أيامه لحد ما تخلص ميزانية الدعم. مقفولة = صفر تكلفة.') }}</div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('تفعيل الحماية') }}</label>
                <div class="col-sm-6">
                    <label class="fl-switch"><input type="checkbox" name="beginner_protection_enabled" value="1" {{ $on('beginner_protection_enabled') }}><span class="fl-knob"></span></label>
                    <span class="fl-hint">{{ __('زرار تشغيل/إيقاف للنظام كله. لما مقفول مفيش أي تكلفة وكل اللاعبين فرصهم عادية.') }}</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('RTP المعزّز') }}</label>
                <div class="col-sm-6">
                    <div class="fl-range">
                        <input type="range" min="50" max="100" step="0.1" value="{{ round((float) $val('RTP_boost', 0.92) * 100, 1) }}" data-target="fld_rtpboost">
                        <div class="fl-numwrap"><div class="input-group">
                            <input type="number" min="50" max="100" step="0.1" id="fld_rtpboost" name="RTP_boost" class="form-control" value="{{ round((float) $val('RTP_boost', 0.92) * 100, 1) }}">
                            <span class="input-group-addon">%</span>
                        </div></div>
                    </div>
                    <span class="fl-hint">{{ __('نسبة الرجوع الأكرم للّاعب الجديد. العادي ~89%؛ لو 95% بياخد جدول يرجّعله ~95، وكمان الجوائز الضخمة (فوق ×100) بتتشال من جدوله فيبقى فوز صغير متكرر يحبّبه في التطبيق. الفرق بيتخصم من ميزانية الدعم.') }}</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('ميزانية الدعم (عملات)') }}</label>
                <div class="col-sm-4">
                    <input type="number" min="0" step="1" name="beginner_budget_coins" class="form-control" value="{{ (int) $val('beginner_budget_coins', 0) }}">
                    <span class="fl-hint">{{ __('أقصى عملات مستعد تخسرها (فوق ربحه الطبيعي) على كل لاعب جديد. 0 = النظام خامل حتى لو مفعّل.') }}</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('عمر الحساب الأقصى (أيام)') }}</label>
                <div class="col-sm-6">
                    <div class="fl-range">
                        <input type="range" min="1" max="365" step="1" value="{{ (int) $val('beginner_max_age_days', 10) }}" data-target="fld_maxage">
                        <div class="fl-numwrap">
                            <input type="number" min="1" max="365" step="1" id="fld_maxage" name="beginner_max_age_days" class="form-control" value="{{ (int) $val('beginner_max_age_days', 10) }}">
                        </div>
                    </div>
                    <span class="fl-hint">{{ __('الحساب لازم يكون أصغر من كذا يوم + عمل شحن حقيقي مرة على الأقل، عشان يستاهل الدعم.') }}</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('السقف اليومي العام (عملات)') }}</label>
                <div class="col-sm-4">
                    <input type="number" min="0" step="1" name="beginner_global_daily_cap" class="form-control" value="{{ (int) $val('beginner_global_daily_cap', 0) }}">
                    <span class="fl-hint">{{ __('أقصى مجموع دعم لكل اللاعبين الجدد في اليوم (حماية من خسارة يوم واحد). 0 = بلا سقف.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ───────── 3) معدّلات شكل الفوز ───────── --}}
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-magic"></i> {{ __('3) معدّلات شكل الفوز') }}</h3>
        </div>
        <div class="box-body form-horizontal">
            <div class="fl-section-note">{{ __('دي بتغيّر شكل وتوقيت الفوز فقط — مابتغيّرش إجمالي المصروف (الـRTP يفضل زي ما حطيته). اللي بقيمة 0 = مقفول.') }}</div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('قوة مُعدِّل صحة الخزنة') }}</label>
                <div class="col-sm-6">
                    <div class="fl-range">
                        <input type="range" min="0" max="1" step="0.05" value="{{ (float) $val('mod_wallet_strength', 0) }}" data-target="fld_modwallet">
                        <div class="fl-numwrap">
                            <input type="number" min="0" max="1" step="0.05" id="fld_modwallet" name="mod_wallet_strength" class="form-control" value="{{ (float) $val('mod_wallet_strength', 0) }}">
                        </div>
                    </div>
                    <span class="fl-hint">{{ __('من 0 لـ1. الخزنة مليانة → جوائز كبيرة أكتر؛ فاضية → جوائز صغيرة كتير. 0 = مقفول، 1 = أقصى تأثير.') }}</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('قوة مُعدِّل RTP للمستخدم') }}</label>
                <div class="col-sm-6">
                    <div class="fl-range">
                        <input type="range" min="0" max="1" step="0.05" value="{{ (float) $val('mod_rtp_strength', 0) }}" data-target="fld_modrtp">
                        <div class="fl-numwrap">
                            <input type="number" min="0" max="1" step="0.05" id="fld_modrtp" name="mod_rtp_strength" class="form-control" value="{{ (float) $val('mod_rtp_strength', 0) }}">
                        </div>
                    </div>
                    <span class="fl-hint">{{ __('من 0 لـ1. بيراقب كل لاعب: حظه وحش → يدفعه لفوز، كويس أوي → يهدّيه (عدالة). بيشتغل بعد ما يراهن 500 عملة على الأقل. 0 = مقفول.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ───────── 4) أمان وعرض ───────── --}}
    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-shield"></i> {{ __('4) أمان وعرض') }}</h3>
        </div>
        <div class="box-body form-horizontal">
            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('تخفيض الملاءة عند الخطر (Taper)') }}</label>
                <div class="col-sm-6">
                    <label class="fl-switch"><input type="checkbox" name="solvency_taper_enabled" value="1" {{ $on('solvency_taper_enabled') }}><span class="fl-knob"></span></label>
                    <span class="fl-hint">{{ __('زرار أمان: لما الخزنة تقرب تفلس بيقلّل نسبة الاسترداد تدريجيًا (لحد 70% منها) ويرجع طبيعي لما تتعافى. ده الوحيد هنا اللي بيقلّل المصروف فعلًا — وقت الخطر بس.') }}</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('حد إعلان الفوز الكبير (عملات)') }}</label>
                <div class="col-sm-4">
                    <input type="number" min="0" step="1" name="lucky_gift_coins" class="form-control" value="{{ $config['lucky_gift_coins'] ?? 2000 }}">
                    <span class="fl-hint">{{ __('الرقم اللي يخلّي الفوز "كبير" فيتعلن ببانر لكل الغرف + صوت عملات. مثال: 2000 = أي فوز ≥ 2000 يتعلن والأقل يعدي بهدوء. صغير = بانرات كتير، كبير = نادر. (عرض فقط — ملوش علاقة بالاقتصاد).') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="fl-save-bar">
        <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> {{ __('حفظ كل الإعدادات') }}</button>
    </div>
</form>

{{-- ───────── حالة الخزنة الحيّة (عرض فقط) — أرباح المالك متشالت (موجودة في صفحتها) ───────── --}}
<div class="row" style="margin-top:18px;">
    <div class="col-md-12">
        <div class="small-box" style="background: {{ $zoneColor }}; color: #fff;">
            <div class="inner">
                <h3>{{ number_format($vaultBalance) }}</h3>
                <p>{{ __('خزنة الحظ') }} — {{ $zone }}</p>
            </div>
            <div class="icon"><i class="fa fa-diamond"></i></div>
        </div>
    </div>
</div>

<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-line-chart"></i> {{ __('رصيد الخزنة — مباشر') }}</h3>
        <div class="box-tools">
            <div class="btn-group btn-group-sm" id="chartRange">
                <button type="button" class="btn btn-default" data-range="50">{{ __('آخر 50') }}</button>
                <button type="button" class="btn btn-default active" data-range="100">{{ __('آخر 100') }}</button>
                <button type="button" class="btn btn-default" data-range="500">{{ __('آخر 500') }}</button>
                <button type="button" class="btn btn-default" data-range="all">{{ __('الكل') }}</button>
            </div>
            <span id="autoRefreshLabel" class="label label-success" style="margin-left:10px;">{{ __('تحديث تلقائي: 30 ثانية') }}</span>
        </div>
    </div>
    <div class="box-body">
        <canvas id="vaultChartV7" style="height: 350px;"></canvas>
    </div>
</div>

<script>
    // ربط كل شريط (slider) بخانة الرقم المقابلة له — السحب يحدّث الرقم والعكس،
    // والرقم هو اللي بيتبعت فعليًا (دقة كاملة). نبعث حدث input عشان مؤشّر الـRTP يتحدّث.
    (function () {
        document.querySelectorAll('input[type=range][data-target]').forEach(function (range) {
            var num = document.getElementById(range.dataset.target);
            if (!num) return;
            range.addEventListener('input', function () { num.value = range.value; num.dispatchEvent(new Event('input')); });
            num.addEventListener('input', function () { range.value = num.value; });
        });
    })();

    // مؤشّر حيّ لأقصى RTP متاح + منع الحفظ لو الإعداد غير مستدام (نفس قاعدة الكنترولر).
    (function () {
        var app = document.getElementById('fld_app');
        var recv = document.getElementById('fld_recv');
        var rtp = document.getElementById('fld_rtp');
        var ceilEl = document.getElementById('rtpCeiling');
        var form = document.getElementById('luckyGiftForm');
        function ceiling() { return Math.round((100 - (parseFloat(app.value) || 0) - (parseFloat(recv.value) || 0)) * 100) / 100; }
        function refresh() {
            var c = ceiling();
            ceilEl.textContent = c;
            ceilEl.style.color = (parseFloat(rtp.value) || 0) > c ? '#dc3545' : '#28a745';
        }
        [app, recv, rtp].forEach(function (el) { if (el) el.addEventListener('input', refresh); });
        refresh();
        if (form) form.addEventListener('submit', function (e) {
            if ((parseFloat(rtp.value) || 0) > ceiling()) {
                e.preventDefault();
                alert('{{ __('إعداد غير مستدام: أرباح التطبيق + نسبة المستقبل + RTP يجب ألا يتجاوز 100%.') }}');
            }
        });
    })();
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(function () {
        var historyData = {!! json_encode($vaultHistory->map(function ($h) {
            return [
                'date' => $h->created_at ? $h->created_at->format('m-d H:i:s') : '',
                'before' => (int) $h->balance_before,
                'change' => (int) $h->amount,
                'after' => (int) $h->balance_after,
                'desc' => $h->description ?? __('Transaction')
            ];
        })->toArray()) !!};

        var canvas = document.getElementById('vaultChartV7');
        if (!canvas) return;

        var ZONES = { min: 10000, tight: 50000, target: 200000, high: 500000, drain: 1000000 };
        var allData = historyData;
        var currentRange = 100;
        var vaultChart = null;

        function getZone(val) {
            if (val <= ZONES.min) return 'CRITICAL';
            if (val <= ZONES.tight) return 'TIGHT';
            if (val <= ZONES.target) return 'NORMAL';
            if (val <= ZONES.high) return 'GENEROUS';
            return 'DRAIN';
        }
        function getZoneColor(val) {
            var z = getZone(val);
            return {CRITICAL:'#dc3545',TIGHT:'#e67e22',NORMAL:'#3498db',GENEROUS:'#28a745',DRAIN:'#8e44ad'}[z];
        }

        function renderChart(range) {
            currentRange = range;
            var data = range === 'all' ? allData : allData.slice(-range);
            var labs = data.map(function(d) { return d.date; });
            var pts = data.map(function(d) { return d.after; });

            if (vaultChart) vaultChart.destroy();

            var zoneLines = [
                { label: 'CRITICAL (' + ZONES.min.toLocaleString() + ')', data: Array(pts.length).fill(ZONES.min), borderColor: '#dc3545', borderDash: [5,5], borderWidth: 1, pointRadius: 0, fill: false },
                { label: 'TIGHT (' + ZONES.tight.toLocaleString() + ')', data: Array(pts.length).fill(ZONES.tight), borderColor: '#e67e22', borderDash: [5,5], borderWidth: 1, pointRadius: 0, fill: false },
                { label: 'TARGET (' + ZONES.target.toLocaleString() + ')', data: Array(pts.length).fill(ZONES.target), borderColor: '#3498db', borderDash: [8,4], borderWidth: 2, pointRadius: 0, fill: false },
                { label: 'GENEROUS (' + ZONES.high.toLocaleString() + ')', data: Array(pts.length).fill(ZONES.high), borderColor: '#28a745', borderDash: [5,5], borderWidth: 1, pointRadius: 0, fill: false },
            ];

            vaultChart = new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labs,
                    datasets: [{
                        label: '{{ __("Vault Balance") }}',
                        data: pts,
                        borderColor: 'rgba(60,141,188,1)',
                        backgroundColor: 'rgba(60,141,188,0.15)',
                        fill: true, tension: 0.2, borderWidth: 2,
                        pointBackgroundColor: function(ctx) { return getZoneColor(pts[ctx.dataIndex] || 0); },
                        pointRadius: pts.length > 200 ? 0 : 3,
                        pointHoverRadius: 6
                    }].concat(zoneLines)
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'nearest', intersect: false },
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 } } },
                        tooltip: {
                            padding: 12, titleFont: { size: 13 }, bodyFont: { size: 12 },
                            callbacks: {
                                title: function(items) {
                                    var d = data[items[0].dataIndex];
                                    return d ? d.date : '';
                                },
                                label: function(ctx) {
                                    if (ctx.datasetIndex > 0) return null;
                                    var d = data[ctx.dataIndex];
                                    if (!d) return '';
                                    var zone = getZone(d.after);
                                    return [
                                        'Balance: ' + d.after.toLocaleString() + ' [' + zone + ']',
                                        'Change: ' + (d.change >= 0 ? '+' : '') + d.change.toLocaleString(),
                                        'Before: ' + d.before.toLocaleString(),
                                        d.desc
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { maxTicksLimit: 15, font: { size: 10 } } },
                        y: {
                            beginAtZero: false,
                            ticks: { callback: function(v) { return v >= 1000 ? (v/1000).toFixed(0) + 'k' : v; } },
                            title: { display: true, text: '{{ __("Coins") }}' }
                        }
                    }
                }
            });
        }

        renderChart(100);

        $('#chartRange button').on('click', function() {
            $('#chartRange button').removeClass('active');
            $(this).addClass('active');
            var r = $(this).data('range');
            renderChart(r === 'all' ? 'all' : parseInt(r));
        });

        setInterval(function() {
            $.getJSON('{{ admin_url("fairluck") }}?ajax=history', function(resp) {
                if (resp && resp.length) {
                    allData = resp;
                    renderChart(currentRange);
                }
            }).fail(function() {});
        }, 30000);
    });
</script>
