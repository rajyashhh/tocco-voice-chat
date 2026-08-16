@php
    $asset = fn ($file) => getImagePath('cp_rewards/' . $file);
    $avatarOf = fn ($userId) => ($profiles[$userId] ?? null)?->avatar ? getImagePath($profiles[$userId]->avatar) : '';
    $initial = fn ($name) => mb_substr(trim($name ?? '') !== '' ? trim($name) : '?', 0, 1);
    $w1 = $winners->firstWhere('level', 1);
    $w2 = $winners->firstWhere('level', 2);
    $w3 = $winners->firstWhere('level', 3);
    $fmtCoins = fn ($n) => number_format($n, 0, '.', '');
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Reward</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
    html { -webkit-text-size-adjust: 100%; }
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        color: #fff;
        min-height: 100vh;
        background: #ffd3e3 url('{{ $asset('bg_palace.jpg') }}') top center / 100% auto no-repeat;
        padding-bottom: 34px;
    }
    body::before {
        content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
        background: linear-gradient(180deg, rgba(255, 182, 210, 0) 0%, rgba(255, 174, 205, .28) 46%, rgba(255, 160, 198, .55) 100%);
    }
    .wrap { position: relative; z-index: 1; max-width: 520px; margin: 0 auto; padding: 8px 14px 0; }

    /* header */
    .topbar { display: flex; align-items: center; padding: 8px 0 4px; }
    .topbar .back {
        width: 36px; height: 36px; border: 0; background: transparent; color: #fff;
        font-size: 26px; line-height: 1; cursor: pointer; text-align: left;
        filter: drop-shadow(0 1px 4px rgba(190, 60, 120, .55));
    }
    .topbar h1 {
        flex: 1; text-align: center; font-size: 19px; font-weight: 800; letter-spacing: .5px;
        text-shadow: 0 1px 8px rgba(190, 60, 120, .55);
    }
    .topbar .sp { width: 36px; }

    /* ornate gold-bordered panel */
    .gold-panel {
        position: relative;
        margin: 34px 2px 8px;
        padding: 34px 14px 18px;
        background:
            radial-gradient(120% 90% at 50% 0%, rgba(255, 255, 255, .55) 0%, rgba(255, 255, 255, 0) 42%),
            linear-gradient(180deg, #ffe3ee 0%, #ffc9de 52%, #ffb9d4 100%);
        border: 22px solid transparent;
        border-image: url('{{ $asset('gold_border.png') }}') 118 / 30px 30px stretch;
        border-radius: 6px;
        box-shadow: 0 6px 22px rgba(190, 70, 130, .28);
    }

    /* ribbon title */
    .ribbon {
        position: absolute; top: -46px; left: 50%; transform: translateX(-50%);
        width: min(78%, 320px); height: 74px;
        background: url('{{ $asset('ribbon.png') }}') center / 100% 100% no-repeat;
        display: flex; align-items: center; justify-content: center;
        filter: drop-shadow(0 3px 8px rgba(170, 40, 100, .35));
        z-index: 3;
    }
    .ribbon span {
        font-size: 16px; font-weight: 800; color: #fff; letter-spacing: .4px;
        margin-top: -12px; padding: 0 26px; white-space: nowrap;
        text-shadow: 0 1px 4px rgba(150, 20, 80, .6);
    }

    /* ===== last weekly top ===== */
    .podium { display: flex; flex-direction: column; align-items: center; }

    .rank-num { display: block; margin: 0 auto; filter: drop-shadow(0 3px 7px rgba(170, 60, 20, .35)); }
    .rank-num.n1 { width: 132px; margin-bottom: -6px; }
    .rank-num.n2, .rank-num.n3 { width: 80px; margin-bottom: -2px; }

    /* couple inside winged frame */
    .couple { position: relative; }
    .couple img.frame { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 2; pointer-events: none; }
    .couple .hole { position: absolute; transform: translate(-50%, -50%); border-radius: 50%; overflow: hidden; z-index: 1; }
    .couple .hole .ava {
        width: 100%; height: 100%; border-radius: 50%; overflow: hidden;
        background: linear-gradient(140deg, #ffd7e8, #f5a9c9);
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; color: #c22a6d;
    }
    .couple .hole .ava img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .couple .heart {
        position: absolute; transform: translate(-50%, -50%); z-index: 3; pointer-events: none;
        animation: beat 1.5s ease-in-out infinite;
        filter: drop-shadow(0 0 8px rgba(255, 90, 155, .9));
    }
    @keyframes beat {
        0%, 100% { transform: translate(-50%, -50%) scale(1); }
        14% { transform: translate(-50%, -50%) scale(1.18); }
        28% { transform: translate(-50%, -50%) scale(1.03); }
        42% { transform: translate(-50%, -50%) scale(1.12); }
        58% { transform: translate(-50%, -50%) scale(1); }
    }

    /* TOP1 frame: 636x265, holes at (41.3%,68.5%) d15.6%w & (57.3%,68.5%) d17.1%w */
    .couple.c1 { width: min(100%, 300px); aspect-ratio: 636 / 265; }
    .couple.c1 .hole.h1 { left: 41.3%; top: 68.5%; width: 17.2%; aspect-ratio: 1; }
    .couple.c1 .hole.h2 { left: 57.3%; top: 68.5%; width: 18.4%; aspect-ratio: 1; }
    .couple.c1 .ava { font-size: 22px; }
    .couple.c1 .heart { left: 49.3%; top: 62%; width: 34px; }

    /* TOP2/3 frame: 206x144, holes at (28.2%,53.8%) d34%w & (66.7%,53.8%) d43.2%w */
    .couple.c2, .couple.c3 { width: min(100%, 146px); aspect-ratio: 206 / 144; }
    .couple.c2 .hole.h1, .couple.c3 .hole.h1 { left: 28.2%; top: 53.8%; width: 35.5%; aspect-ratio: 1; }
    .couple.c2 .hole.h2, .couple.c3 .hole.h2 { left: 66.7%; top: 53.8%; width: 44.6%; aspect-ratio: 1; }
    .couple.c2 .ava, .couple.c3 .ava { font-size: 17px; }
    .couple.c2 .heart, .couple.c3 .heart { left: 47.5%; top: 47%; width: 24px; }

    .names {
        margin-top: 7px; text-align: center; font-size: 12.5px; font-weight: 700; color: #fff;
        max-width: 230px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        text-shadow: 0 1px 5px rgba(180, 50, 110, .65);
    }
    .names.small { font-size: 11.5px; max-width: 160px; }

    .sub-row { display: flex; justify-content: center; gap: 4px; width: 100%; margin-top: 20px; }
    .sub-col { flex: 1; display: flex; flex-direction: column; align-items: center; min-width: 0; }

    .no-champ {
        margin-top: 6px; font-size: 11px; font-weight: 700; color: rgba(194, 42, 109, .85);
        text-align: center;
    }
    .sil { opacity: .5; }

    /* ===== reward cards ===== */
    .reward-card {
        position: relative;
        margin: 34px 4px 10px;
        padding: 30px 12px 14px;
        border-radius: 20px;
        background: linear-gradient(180deg, #ff9ec5 0%, #ff7fb2 100%);
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .55), 0 4px 14px rgba(190, 70, 130, .25);
    }
    .top-pill {
        position: absolute; top: -17px; left: 50%; transform: translateX(-50%);
        padding: 7px 30px; border-radius: 999px;
        font-size: 14.5px; font-weight: 800; color: #fff; letter-spacing: .6px; white-space: nowrap;
        box-shadow: 0 3px 9px rgba(160, 40, 100, .35), inset 0 0 0 2px rgba(255, 255, 255, .65);
    }
    .top-pill.p1 { background: linear-gradient(120deg, #ffcf4d, #f59b0b); text-shadow: 0 1px 3px rgba(140, 80, 0, .5); }
    .top-pill.p2 { background: linear-gradient(120deg, #7fb6ff, #3f7fe8); text-shadow: 0 1px 3px rgba(20, 60, 140, .5); }
    .top-pill.p3 { background: linear-gradient(120deg, #57d98a, #1fae62); text-shadow: 0 1px 3px rgba(10, 100, 55, .5); }

    .tiles { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .tile {
        background: linear-gradient(180deg, #fff6fa 0%, #ffe4ef 100%);
        border-radius: 14px; padding: 12px 8px 9px;
        display: flex; flex-direction: column; align-items: center;
        box-shadow: inset 0 0 0 1.5px rgba(255, 255, 255, .9), 0 2px 6px rgba(180, 60, 120, .16);
    }
    .tile .art { height: 84px; display: flex; align-items: center; justify-content: center; }
    .tile .art img { max-height: 84px; max-width: 108px; object-fit: contain; filter: drop-shadow(0 3px 6px rgba(170, 60, 120, .22)); }
    .tile .cap {
        margin-top: 7px; font-size: 11.5px; font-weight: 700; color: #b03068;
        text-align: center; line-height: 1.35; max-width: 100%;
    }
</style>
</head>
<body>
<div class="wrap">
    <div class="topbar">
        <button class="back" onclick="if (history.length > 1) { history.back(); } else { location.href = '/weekly-cp-view'; }" aria-label="back">‹</button>
        <h1>Reward</h1>
        <span class="sp"></span>
    </div>

    {{-- ===================== Last weekly top ===================== --}}
    <div class="gold-panel" style="margin-top: 52px;">
        <div class="ribbon"><span>Last weekly top</span></div>

        <div class="podium">
            {{-- TOP 1 --}}
            <img class="rank-num n1" src="{{ $asset('num1.png') }}" alt="1">
            <div class="couple c1 {{ $w1 ? '' : 'sil' }}">
                <div class="hole h1"><div class="ava">
                    @if ($w1 && $avatarOf($w1->user_one_id))<img src="{{ $avatarOf($w1->user_one_id) }}" alt="">@elseif($w1){{ $initial($w1->userOne?->name) }}@endif
                </div></div>
                <div class="hole h2"><div class="ava">
                    @if ($w1 && $avatarOf($w1->user_two_id))<img src="{{ $avatarOf($w1->user_two_id) }}" alt="">@elseif($w1){{ $initial($w1->userTwo?->name) }}@endif
                </div></div>
                <img class="heart" src="{{ $asset('heart_glow.png') }}" alt="">
                <img class="frame" src="{{ $asset('wing_frame_1.png') }}" alt="">
            </div>
            @if ($w1)
                <div class="names">{{ $w1->userOne?->name ?? '—' }} &nbsp;&amp;&nbsp; {{ $w1->userTwo?->name ?? '—' }}</div>
            @else
                <div class="no-champ">No champions yet</div>
            @endif

            {{-- TOP 2 & TOP 3 --}}
            <div class="sub-row">
                <div class="sub-col">
                    <img class="rank-num n2" src="{{ $asset('num2.png') }}" alt="2">
                    <div class="couple c2 {{ $w2 ? '' : 'sil' }}">
                        <div class="hole h1"><div class="ava">
                            @if ($w2 && $avatarOf($w2->user_one_id))<img src="{{ $avatarOf($w2->user_one_id) }}" alt="">@elseif($w2){{ $initial($w2->userOne?->name) }}@endif
                        </div></div>
                        <div class="hole h2"><div class="ava">
                            @if ($w2 && $avatarOf($w2->user_two_id))<img src="{{ $avatarOf($w2->user_two_id) }}" alt="">@elseif($w2){{ $initial($w2->userTwo?->name) }}@endif
                        </div></div>
                        <img class="heart" src="{{ $asset('heart_glow.png') }}" alt="">
                        <img class="frame" src="{{ $asset('wing_frame_2.png') }}" alt="">
                    </div>
                    @if ($w2)
                        <div class="names small">{{ $w2->userOne?->name ?? '—' }} &amp; {{ $w2->userTwo?->name ?? '—' }}</div>
                    @else
                        <div class="no-champ">No champions yet</div>
                    @endif
                </div>
                <div class="sub-col">
                    <img class="rank-num n3" src="{{ $asset('num3.png') }}" alt="3">
                    <div class="couple c3 {{ $w3 ? '' : 'sil' }}">
                        <div class="hole h1"><div class="ava">
                            @if ($w3 && $avatarOf($w3->user_one_id))<img src="{{ $avatarOf($w3->user_one_id) }}" alt="">@elseif($w3){{ $initial($w3->userOne?->name) }}@endif
                        </div></div>
                        <div class="hole h2"><div class="ava">
                            @if ($w3 && $avatarOf($w3->user_two_id))<img src="{{ $avatarOf($w3->user_two_id) }}" alt="">@elseif($w3){{ $initial($w3->userTwo?->name) }}@endif
                        </div></div>
                        <img class="heart" src="{{ $asset('heart_glow.png') }}" alt="">
                        <img class="frame" src="{{ $asset('wing_frame_3.png') }}" alt="">
                    </div>
                    @if ($w3)
                        <div class="names small">{{ $w3->userOne?->name ?? '—' }} &amp; {{ $w3->userTwo?->name ?? '—' }}</div>
                    @else
                        <div class="no-champ">No champions yet</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== Last weekly reward ===================== --}}
    <div class="gold-panel" style="margin-top: 64px;">
        <div class="ribbon"><span>Last weekly reward</span></div>

        {{-- TOP 1 --}}
        <div class="reward-card" style="margin-top: 12px;">
            <div class="top-pill p1">TOP 1</div>
            <div class="tiles">
                <div class="tile">
                    <div class="art"><img src="{{ $asset('train.png') }}" alt=""></div>
                    <div class="cap">Happiness train*30days</div>
                </div>
                <div class="tile">
                    <div class="art"><img src="{{ $asset('crest_gold.png') }}" alt=""></div>
                    <div class="cap">CP TOP1*30 days</div>
                </div>
                <div class="tile">
                    <div class="art"><img src="{{ $asset('frame_gold.png') }}" alt=""></div>
                    <div class="cap">CP TOP1*30 days</div>
                </div>
                <div class="tile">
                    <div class="art"><img src="{{ $asset('coins.png') }}" alt=""></div>
                    <div class="cap">{{ $fmtCoins($coins[1]) }}Coins</div>
                </div>
            </div>
        </div>

        {{-- TOP 2 --}}
        <div class="reward-card">
            <div class="top-pill p2">TOP 2</div>
            <div class="tiles">
                <div class="tile">
                    <div class="art"><img src="{{ $asset('train.png') }}" alt=""></div>
                    <div class="cap">Happiness train*20days</div>
                </div>
                <div class="tile">
                    <div class="art"><img src="{{ $asset('crest_blue.png') }}" alt=""></div>
                    <div class="cap">CP TOP2*20 days</div>
                </div>
                <div class="tile">
                    <div class="art"><img src="{{ $asset('frame_silver.png') }}" alt=""></div>
                    <div class="cap">CP TOP2*20 days</div>
                </div>
                <div class="tile">
                    <div class="art"><img src="{{ $asset('coins.png') }}" alt=""></div>
                    <div class="cap">{{ $fmtCoins($coins[2]) }}Coins</div>
                </div>
            </div>
        </div>

        {{-- TOP 3 --}}
        <div class="reward-card" style="margin-bottom: 2px;">
            <div class="top-pill p3">TOP 3</div>
            <div class="tiles">
                <div class="tile">
                    <div class="art"><img src="{{ $asset('train.png') }}" alt=""></div>
                    <div class="cap">Happiness train*10days</div>
                </div>
                <div class="tile">
                    <div class="art"><img src="{{ $asset('crest_green.png') }}" alt=""></div>
                    <div class="cap">CP TOP3*10 days</div>
                </div>
                <div class="tile">
                    <div class="art"><img src="{{ $asset('frame_bronze.png') }}" alt=""></div>
                    <div class="cap">CP TOP3*10 days</div>
                </div>
                <div class="tile">
                    <div class="art"><img src="{{ $asset('coins.png') }}" alt=""></div>
                    <div class="cap">{{ $fmtCoins($coins[3]) }}Coins</div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
