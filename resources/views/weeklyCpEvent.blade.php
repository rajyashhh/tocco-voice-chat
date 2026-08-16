@php
    $isAr = $lang === 'ar';
    $t = $isAr ? [
        'title' => 'ترتيب CP',
        'daily' => 'يومي',
        'weekly' => 'أسبوعي',
        'monthly' => 'شهري',
        'empty_title' => 'كونوا أول ثنائي على العرش',
        'empty_body' => 'أرسلوا هدايا الحدث مع شريك الـCP لتتصدروا الترتيب وتفوزوا بالجوائز.',
        'bind_cp' => 'اربط CP',
        'my_rank' => 'ترتيبك',
        'not_ranked' => '99+',
        'with' => 'مع',
        'last_top' => 'أبطال الأسبوع الماضي',
        'last_reward' => 'جوائز الأسبوع',
        'no_prev_winners' => 'لم يُتوَّج أحد بعد — الأسبوع ده ممكن تكونوا إنتوا!',
        'rules_title' => 'القواعد',
        'rule_1' => '١ دايموند = ١ قيمة CP.',
        'rule_2' => 'قيمة الـCP بتزيد بتبادل هدايا الحدث بين طرفي العلاقة.',
        'rule_3' => 'الترتيب حسب قيمة الحميمية المتجمعة خلال الفترة.',
        'rule_4' => 'الجوائز تُمنح للطرفين معًا في العلاقة.',
        'know' => 'علمت',
        'top' => 'TOP',
    ] : [
        'title' => 'CP Ranking',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'empty_title' => 'Be the first couple on the throne',
        'empty_body' => 'Send event gifts with your CP partner to climb the ranking and win rewards.',
        'bind_cp' => 'Bind CP',
        'my_rank' => 'Rank',
        'not_ranked' => '99+',
        'with' => 'with',
        'last_top' => 'Last weekly top',
        'last_reward' => 'Last weekly reward',
        'no_prev_winners' => 'No couple crowned yet — this week it could be you!',
        'rules_title' => 'Rule',
        'rule_1' => '1 Diamond = 1 CP value.',
        'rule_2' => 'CP value grows by exchanging event gifts between the two partners.',
        'rule_3' => 'Ranking is based on intimacy value collected during the period.',
        'rule_4' => 'Rewards are granted to both partners of the relationship together.',
        'know' => 'Know',
        'top' => 'TOP',
    ];

    $avatarOf = fn ($userId) => ($profiles[$userId] ?? null)?->avatar ? getImagePath($profiles[$userId]->avatar) : '';
    $initial = fn ($name) => mb_substr(trim($name ?? '') !== '' ? trim($name) : '?', 0, 1);
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>{{ $t['title'] }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
    html { -webkit-text-size-adjust: 100%; }
    body {
        font-family: {{ $isAr ? "'Segoe UI', Tahoma" : "'Segoe UI', Arial" }}, sans-serif;
        color: #fff;
        min-height: 100vh;
        padding-bottom: 96px;
        background: linear-gradient(180deg, #f8c471 0%, #ff8fae 26%, #ff6fa5 55%, #c471ed 100%);
        background-attachment: fixed;
    }
    .wrap { max-width: 520px; margin: 0 auto; padding: 10px 12px 0; }

    /* header */
    .topbar { display: flex; align-items: center; gap: 10px; padding: 8px 2px; }
    .topbar .back {
        width: 34px; height: 34px; border: 0; background: transparent; color: #fff;
        font-size: 24px; line-height: 1; cursor: pointer;
    }
    [dir="rtl"] .topbar .back { transform: scaleX(-1); }
    .topbar h1 { flex: 1; text-align: center; font-size: 18px; font-weight: 800; text-shadow: 0 1px 6px rgba(160, 40, 90, .35); }
    .topbar .icon-btn {
        width: 34px; height: 34px; border: 0; border-radius: 50%;
        background: rgba(255, 255, 255, .22); color: #fff; font-size: 16px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
    }

    /* tabs */
    .tabs { display: flex; justify-content: center; gap: 8px; margin: 10px 0 14px; }
    .tab {
        border: 0; cursor: pointer; padding: 7px 22px; border-radius: 999px;
        font-size: 13.5px; font-weight: 700; color: #fff;
        background: rgba(255, 255, 255, .20);
    }
    .tab.active { background: #fff; color: #ff5f9e; box-shadow: 0 2px 10px rgba(150, 40, 90, .25); }

    /* ranking rows */
    .panel { display: none; }
    .panel.active { display: block; }
    .list { display: flex; flex-direction: column; gap: 9px; }
    .row {
        display: flex; align-items: center; gap: 6px;
        padding: 10px 10px 8px; border-radius: 16px;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .22);
        backdrop-filter: blur(3px);
    }
    .row .rank { width: 30px; text-align: center; font-weight: 800; font-size: 15px; color: #fff; font-variant-numeric: tabular-nums; text-shadow: 0 1px 4px rgba(150, 40, 90, .4); }
    .row .rank.r1 { color: #ffe27a; font-size: 17px; }
    .row .rank.r2 { color: #eef3ff; font-size: 16px; }
    .row .rank.r3 { color: #ffc39e; font-size: 16px; }

    .side { flex: 1; min-width: 0; display: flex; flex-direction: column; align-items: center; gap: 4px; }
    .ava-shell { position: relative; width: 52px; height: 52px; }
    .ava {
        width: 100%; height: 100%; border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, .75);
        background: linear-gradient(140deg, #b95c8a, #8a4472);
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 19px; color: #ffd9e8; overflow: hidden;
    }
    .ava img { width: 100%; height: 100%; object-fit: cover; }
    /* winged medal frames for top 3 */
    .ava-shell.w1 .ava { border-color: #ffd700; box-shadow: 0 0 12px rgba(255, 215, 0, .75); }
    .ava-shell.w2 .ava { border-color: #dfe8f5; box-shadow: 0 0 10px rgba(210, 225, 245, .7); }
    .ava-shell.w3 .ava { border-color: #e29a66; box-shadow: 0 0 10px rgba(226, 154, 102, .7); }
    .wing {
        position: absolute; top: 50%; width: 22px; height: 30px; transform: translateY(-58%);
        pointer-events: none; font-size: 20px; line-height: 30px;
        filter: drop-shadow(0 1px 3px rgba(120, 30, 70, .5));
    }
    .wing.l { left: -15px; }
    .wing.r { right: -15px; transform: translateY(-58%) scaleX(-1); }
    .crown { position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font-size: 14px; filter: drop-shadow(0 1px 3px rgba(120, 30, 70, .6)); z-index: 2; }
    .side .nm { max-width: 100%; font-size: 11.5px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-shadow: 0 1px 4px rgba(150, 40, 90, .4); }

    /* animated heart with points */
    .heart-box { position: relative; width: 88px; height: 76px; flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .heart {
        position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        font-size: 60px; line-height: 1; color: #ff4d94;
        animation: heartbeat 1.6s ease-in-out infinite;
    }
    @keyframes heartbeat {
        0%, 100% { transform: scale(1);    filter: drop-shadow(0 0 6px rgba(255, 77, 148, .7)); }
        12%      { transform: scale(1.14); filter: drop-shadow(0 0 16px rgba(255, 77, 148, 1)); }
        24%      { transform: scale(1.02); filter: drop-shadow(0 0 9px rgba(255, 77, 148, .8)); }
        36%      { transform: scale(1.10); filter: drop-shadow(0 0 13px rgba(255, 96, 160, .95)); }
        50%      { transform: scale(1);    filter: drop-shadow(0 0 7px rgba(255, 77, 148, .75)); }
    }
    .heart-pts {
        position: relative; z-index: 2; font-size: 12.5px; font-weight: 800; color: #fff;
        text-shadow: 0 1px 4px rgba(150, 20, 80, .85); margin-top: 4px;
        font-variant-numeric: tabular-nums;
    }

    /* empty state */
    .empty { text-align: center; padding: 52px 24px; }
    .empty .big { font-size: 52px; filter: drop-shadow(0 0 16px rgba(255, 255, 255, .5)); }
    .empty h2 { font-size: 18px; margin: 14px 0 8px; text-shadow: 0 1px 6px rgba(150, 40, 90, .4); }
    .empty p { font-size: 13.5px; color: rgba(255, 255, 255, .92); line-height: 1.7; }

    /* sticky bottom bar */
    .mybar {
        position: fixed; bottom: 0; left: 0; right: 0; z-index: 6;
        max-width: 520px; margin: 0 auto;
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px calc(12px + env(safe-area-inset-bottom));
        background: linear-gradient(180deg, rgba(150, 55, 145, .60), rgba(122, 36, 118, .94));
        backdrop-filter: blur(8px);
        border-top: 1px solid rgba(255, 255, 255, .25);
        border-radius: 18px 18px 0 0;
    }
    .mybar .ava-shell { width: 42px; height: 42px; flex: 0 0 auto; }
    .mybar .ava { font-size: 15px; }
    .mini-heart { font-size: 18px; color: #ff7ab0; animation: heartbeat 1.6s ease-in-out infinite; flex: 0 0 auto; }
    .mybar .info { flex: 1; min-width: 0; }
    .mybar .info .l1 { font-size: 13.5px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mybar .rankbox { text-align: center; min-width: 44px; }
    .mybar .rankbox b { display: block; font-size: 18px; font-weight: 800; color: #ffe27a; font-variant-numeric: tabular-nums; }
    .mybar .rankbox i { display: block; font-style: normal; font-size: 9.5px; color: rgba(255, 255, 255, .8); }
    .bind-btn {
        border: 0; cursor: pointer; padding: 9px 20px; border-radius: 999px;
        background: #fff; color: #ff5f9e; font-weight: 800; font-size: 13.5px;
        box-shadow: 0 2px 10px rgba(120, 20, 70, .35); flex: 0 0 auto;
    }

    /* overlays (rewards + rules) */
    .overlay {
        position: fixed; inset: 0; z-index: 20; display: none;
        background: rgba(60, 10, 45, .55); backdrop-filter: blur(3px);
    }
    .overlay.open { display: block; }
    .sheet {
        position: absolute; left: 0; right: 0; bottom: 0;
        max-width: 520px; margin: 0 auto; max-height: 84vh; overflow-y: auto;
        background: linear-gradient(180deg, #ffe9f2, #ffd7e8);
        border-radius: 22px 22px 0 0; padding: 18px 16px calc(20px + env(safe-area-inset-bottom));
        color: #8a2657;
    }
    .sheet .sh-head { display: flex; align-items: center; margin-bottom: 12px; }
    .sheet .sh-head h2 { flex: 1; text-align: center; font-size: 16.5px; font-weight: 800; color: #c22a6d; }
    .sheet .close {
        width: 30px; height: 30px; border: 0; border-radius: 50%; cursor: pointer;
        background: rgba(194, 42, 109, .12); color: #c22a6d; font-size: 14px; flex: 0 0 auto;
    }

    /* ornate gold-bordered cards */
    .gold-card {
        position: relative; border-radius: 16px; padding: 18px 12px 14px;
        background: #fff3f8;
        border: 2px solid #e8b86a;
        box-shadow: inset 0 0 0 3px #fff3f8, inset 0 0 0 4px rgba(232, 184, 106, .55), 0 3px 10px rgba(190, 90, 130, .18);
        margin-bottom: 14px;
    }
    .gold-card::before, .gold-card::after {
        content: '✦'; position: absolute; top: 4px; font-size: 13px; color: #d9a144;
    }
    .gold-card::before { left: 10px; }
    .gold-card::after { right: 10px; }
    .gold-title {
        text-align: center; font-size: 14.5px; font-weight: 800; color: #c22a6d;
        margin-bottom: 12px; letter-spacing: .4px;
    }

    /* last weekly top layout */
    .podium-prev { display: flex; flex-direction: column; align-items: center; gap: 16px; }
    .pcol { display: flex; flex-direction: column; align-items: center; }
    .prev-couple { display: flex; align-items: center; gap: 4px; }
    .prev-couple .ava-shell { width: 56px; height: 56px; }
    .prev-couple.small .ava-shell { width: 42px; height: 42px; }
    .heart-mini { font-size: 22px; color: #ff4d94; animation: heartbeat 1.6s ease-in-out infinite; margin: 0 3px; }
    .prev-couple.small .heart-mini { font-size: 16px; }
    .prev-couple .ava { border-color: #e8b86a; color: #c22a6d; background: linear-gradient(140deg, #ffd7e8, #ffc2dc); }
    .prev-names { text-align: center; font-size: 12px; font-weight: 700; color: #a03465; margin-top: 6px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .top-tag {
        display: inline-block; font-size: 10.5px; font-weight: 800; padding: 2px 10px; border-radius: 999px;
        color: #7a4a00; background: linear-gradient(120deg, #ffe27a, #f5b342); margin-bottom: 7px;
    }
    .top-tag.t2 { background: linear-gradient(120deg, #eef3ff, #c9d6ea); color: #46587a; }
    .top-tag.t3 { background: linear-gradient(120deg, #ffd0ad, #e29a66); color: #7a3d12; }
    .prev-sub { display: flex; justify-content: center; gap: 26px; width: 100%; }

    /* reward cards */
    .reward-grid { display: flex; flex-direction: column; gap: 10px; }
    .reward-level { display: flex; align-items: flex-start; gap: 10px; }
    .reward-level .lvl {
        flex: 0 0 auto; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 999px; margin-top: 4px;
        color: #7a4a00; background: linear-gradient(120deg, #ffe27a, #f5b342);
    }
    .reward-level.l2 .lvl { background: linear-gradient(120deg, #eef3ff, #c9d6ea); color: #46587a; }
    .reward-level.l3 .lvl { background: linear-gradient(120deg, #ffd0ad, #e29a66); color: #7a3d12; }
    .reward-items { flex: 1; display: flex; flex-wrap: wrap; gap: 6px; }
    .r-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: #fff; border: 1px solid rgba(232, 184, 106, .6); border-radius: 10px;
        padding: 5px 9px; font-size: 11.5px; font-weight: 700; color: #a03465; max-width: 100%;
    }
    .r-chip img { width: 20px; height: 20px; object-fit: contain; border-radius: 5px; }
    .r-chip span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* rules dialog */
    .dialog {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: min(88vw, 360px); background: #fff; border-radius: 18px;
        padding: 20px 18px; color: #5a3247;
    }
    .dialog h2 { text-align: center; font-size: 17px; font-weight: 800; color: #d6337a; margin-bottom: 12px; }
    .dialog ol { padding-{{ $isAr ? 'right' : 'left' }}: 18px; display: flex; flex-direction: column; gap: 9px; }
    .dialog li { font-size: 13px; line-height: 1.65; }
    .know-btn {
        display: block; width: 70%; margin: 16px auto 0; border: 0; cursor: pointer;
        padding: 11px 0; border-radius: 999px; font-size: 14.5px; font-weight: 800; color: #fff;
        background: linear-gradient(120deg, #35d07f, #1fae62);
        box-shadow: 0 3px 12px rgba(31, 174, 98, .4);
    }
</style>
</head>
<body>
<div class="wrap">
    <div class="topbar">
        <button class="back" onclick="if (history.length > 1) history.back()" aria-label="back">‹</button>
        <h1>{{ $t['title'] }}</h1>
        <button class="icon-btn" onclick="openOverlay('rules')" aria-label="rules">❓</button>
        <button class="icon-btn" onclick="location.href = '/weekly-cp-rewards'" aria-label="rewards">🎁</button>
    </div>

    <div class="tabs">
        <button class="tab" data-tab="daily" onclick="switchTab('daily')">{{ $t['daily'] }}</button>
        <button class="tab active" data-tab="weekly" onclick="switchTab('weekly')">{{ $t['weekly'] }}</button>
        <button class="tab" data-tab="monthly" onclick="switchTab('monthly')">{{ $t['monthly'] }}</button>
    </div>

    @foreach (['daily', 'weekly', 'monthly'] as $tabKey)
        <div class="panel {{ $tabKey === 'weekly' ? 'active' : '' }}" id="panel-{{ $tabKey }}">
            @if ($tabs[$tabKey]->isEmpty())
                <div class="empty">
                    <div class="big">👑💞</div>
                    <h2>{{ $t['empty_title'] }}</h2>
                    <p>{{ $t['empty_body'] }}</p>
                </div>
            @else
                <div class="list">
                    @foreach ($tabs[$tabKey] as $i => $row)
                        @php
                            $u1 = $row->cp?->fromUser; $u2 = $row->cp?->toUser;
                            $a1 = $avatarOf($row->cp?->user_one_id); $a2 = $avatarOf($row->cp?->user_two_id);
                            $pos = $i + 1;
                            $wingIcon = $pos <= 3 ? ['🪽', '🕊️', '🌿'][$pos - 1] : '';
                        @endphp
                        <div class="row">
                            <div class="rank {{ $pos <= 3 ? 'r' . $pos : '' }}">{{ $pos }}</div>
                            <div class="side">
                                <div class="ava-shell {{ $pos <= 3 ? 'w' . $pos : '' }}">
                                    @if ($pos === 1)<span class="crown">👑</span>@endif
                                    @if ($pos <= 3)<span class="wing l">{{ $wingIcon }}</span><span class="wing r">{{ $wingIcon }}</span>@endif
                                    <div class="ava">@if($a1)<img src="{{ $a1 }}" alt="" loading="lazy">@else{{ $initial($u1?->name) }}@endif</div>
                                </div>
                                <div class="nm">{{ $u1?->name ?? '—' }}</div>
                            </div>
                            <div class="heart-box">
                                <div class="heart">❤</div>
                                <div class="heart-pts">{{ numToString((int) $row->totalGiftNum) }}</div>
                            </div>
                            <div class="side">
                                <div class="ava-shell {{ $pos <= 3 ? 'w' . $pos : '' }}">
                                    @if ($pos <= 3)<span class="wing l">{{ $wingIcon }}</span><span class="wing r">{{ $wingIcon }}</span>@endif
                                    <div class="ava">@if($a2)<img src="{{ $a2 }}" alt="" loading="lazy">@else{{ $initial($u2?->name) }}@endif</div>
                                </div>
                                <div class="nm">{{ $u2?->name ?? '—' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>

@if ($my)
    <div class="mybar">
        <div class="ava-shell">
            <div class="ava">@if($my['avatar'])<img src="{{ $my['avatar'] }}" alt="">@else{{ $initial($my['name']) }}@endif</div>
        </div>
        @if ($my['participant'])
            <span class="mini-heart">❤</span>
            <div class="info">
                <div class="l1">{{ $my['name'] }} {{ $t['with'] }} {{ $my['partner_name'] }}</div>
            </div>
            <div class="rankbox">
                <b id="my-rank">{{ $my['ranks']['weekly'] ?? $t['not_ranked'] }}</b>
                <i>{{ $t['my_rank'] }}</i>
            </div>
        @else
            <span class="mini-heart">💔</span>
            <div class="info">
                <div class="l1">{{ $my['name'] }}</div>
            </div>
            <button class="bind-btn" onclick="bindCp()">{{ $t['bind_cp'] }}</button>
        @endif
    </div>
@endif

{{-- rewards overlay --}}
<div class="overlay" id="ov-rewards" onclick="if (event.target === this) closeOverlay('rewards')">
    <div class="sheet">
        <div class="sh-head">
            <button class="close" onclick="closeOverlay('rewards')">✕</button>
            <h2>🎁 {{ $t['last_reward'] }}</h2>
            <span style="width:30px"></span>
        </div>

        <div class="gold-card">
            <div class="gold-title">{{ $t['last_top'] }}</div>
            @if ($prevWinners->isEmpty())
                <p style="text-align:center; font-size:12.5px; color:#a03465; padding:8px 4px;">{{ $t['no_prev_winners'] }}</p>
            @else
                <div class="podium-prev">
                    @php $w1 = $prevWinners->firstWhere('level', 1); @endphp
                    @if ($w1)
                        <div class="pcol">
                            <span class="top-tag">{{ $t['top'] }}1</span>
                            <div class="prev-couple">
                                <div class="ava-shell w1">
                                    <span class="crown">👑</span>
                                    <div class="ava">@if($avatarOf($w1->user_one_id))<img src="{{ $avatarOf($w1->user_one_id) }}" alt="">@else{{ $initial($w1->userOne?->name) }}@endif</div>
                                </div>
                                <span class="heart-mini">❤</span>
                                <div class="ava-shell w1">
                                    <div class="ava">@if($avatarOf($w1->user_two_id))<img src="{{ $avatarOf($w1->user_two_id) }}" alt="">@else{{ $initial($w1->userTwo?->name) }}@endif</div>
                                </div>
                            </div>
                            <div class="prev-names">{{ $w1->userOne?->name ?? '—' }} 🤍 {{ $w1->userTwo?->name ?? '—' }}</div>
                        </div>
                    @endif
                    <div class="prev-sub">
                        @foreach ([2, 3] as $lvl)
                            @php $w = $prevWinners->firstWhere('level', $lvl); @endphp
                            @if ($w)
                                <div class="pcol">
                                    <span class="top-tag t{{ $lvl }}">{{ $t['top'] }}{{ $lvl }}</span>
                                    <div class="prev-couple small">
                                        <div class="ava-shell w{{ $lvl }}">
                                            <div class="ava">@if($avatarOf($w->user_one_id))<img src="{{ $avatarOf($w->user_one_id) }}" alt="">@else{{ $initial($w->userOne?->name) }}@endif</div>
                                        </div>
                                        <span class="heart-mini">❤</span>
                                        <div class="ava-shell w{{ $lvl }}">
                                            <div class="ava">@if($avatarOf($w->user_two_id))<img src="{{ $avatarOf($w->user_two_id) }}" alt="">@else{{ $initial($w->userTwo?->name) }}@endif</div>
                                        </div>
                                    </div>
                                    <div class="prev-names" style="max-width:130px;">{{ $w->userOne?->name ?? '—' }} 🤍 {{ $w->userTwo?->name ?? '—' }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="gold-card">
            <div class="gold-title">{{ $t['last_reward'] }}</div>
            <div class="reward-grid">
                @foreach ([1, 2, 3] as $lvl)
                    <div class="reward-level l{{ $lvl }}">
                        <span class="lvl">{{ $t['top'] }}{{ $lvl }}</span>
                        <div class="reward-items">
                            @forelse ($prevRewards[$lvl] as $chip)
                                <span class="r-chip">
                                    @if ($chip['image'])<img src="{{ $chip['image'] }}" alt="" loading="lazy">@endif
                                    <span>{{ $chip['label'] }}</span>
                                </span>
                            @empty
                                <span class="r-chip"><span>—</span></span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- rules dialog --}}
<div class="overlay" id="ov-rules" onclick="if (event.target === this) closeOverlay('rules')">
    <div class="dialog">
        <h2>{{ $t['rules_title'] }}</h2>
        <ol>
            <li>{{ $t['rule_1'] }}</li>
            <li>{{ $t['rule_2'] }}</li>
            <li>{{ $t['rule_3'] }}</li>
            <li>{{ $t['rule_4'] }}</li>
        </ol>
        <button class="know-btn" onclick="closeOverlay('rules')">{{ $t['know'] }}</button>
    </div>
</div>

<script>
var myRanks = @json($my['ranks'] ?? []);
var notRanked = @json($t['not_ranked']);

function switchTab(key) {
    document.querySelectorAll('.tab').forEach(function (b) { b.classList.toggle('active', b.dataset.tab === key); });
    document.querySelectorAll('.panel').forEach(function (p) { p.classList.toggle('active', p.id === 'panel-' + key); });
    var rankEl = document.getElementById('my-rank');
    if (rankEl) rankEl.textContent = myRanks[key] || notRanked;
}
function openOverlay(name) { document.getElementById('ov-' + name).classList.add('open'); }
function closeOverlay(name) { document.getElementById('ov-' + name).classList.remove('open'); }
function bindCp() {
    if (window.FlutterChannel && window.FlutterChannel.postMessage) {
        window.FlutterChannel.postMessage('bind_cp');
    } else if (window.flutter_inappwebview && window.flutter_inappwebview.callHandler) {
        window.flutter_inappwebview.callHandler('bind_cp');
    }
}
</script>
</body>
</html>