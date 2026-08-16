@php
    $gift = $gift;
    $defaultImage = asset('images/image.png');
    $imgPath = getImagePath($gift->img) ?: $defaultImage;
    $showImgPath = getImagePath($gift->show_img) ?: null;
    $showImg2Path = getImagePath($gift->show_img2) ?: null;
    $category = $gift->category;
    $locale = app()->getLocale();
    $categoryName = $category ? ($category->title[$locale] ?? $category->title['en'] ?? '-') : '-';
    $vip = $gift->vip;
    $coinIcon = asset('images/coin.jpg');
    $musicIcon = asset('images/music.jpg');
@endphp

<style>
    .gift-detail-wrapper {
        padding: 0;
    }
    /* Hero Card */
    .gift-hero-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 30px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 30px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    }
    .gift-hero-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 400px;
        height: 400px;
        background: rgba(255,255,255,0.06);
        border-radius: 50%;
    }
    .gift-hero-card::after {
        content: '';
        position: absolute;
        bottom: -60%;
        left: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255,255,255,0.04);
        border-radius: 50%;
    }
    .gift-hero-img-wrap {
        position: relative;
        z-index: 2;
        flex-shrink: 0;
    }
    .gift-hero-img {
        width: 140px;
        height: 140px;
        border-radius: 20px;
        object-fit: cover;
        border: 4px solid rgba(255,255,255,0.3);
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        background: rgba(255,255,255,0.1);
        transition: transform 0.3s ease;
    }
    .gift-hero-img:hover {
        transform: scale(1.05);
    }
    .gift-music-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        width: 32px;
        height: 32px;
        background: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .gift-music-badge img {
        width: 20px;
        height: 20px;
    }
    .gift-hero-info {
        position: relative;
        z-index: 2;
        flex: 1;
    }
    .gift-hero-name {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 4px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .gift-hero-ename {
        font-size: 16px;
        opacity: 0.85;
        margin: 0 0 16px;
        font-weight: 400;
    }
    .gift-hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }
    .gift-meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255,255,255,0.18);
        backdrop-filter: blur(4px);
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 500;
        border: 1px solid rgba(255,255,255,0.15);
        white-space: nowrap;
    }
    .gift-meta-chip img {
        width: 18px;
        height: 18px;
        border-radius: 50%;
    }
    .gift-id-badge {
        background: rgba(0,0,0,0.2);
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .gift-status-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 4px;
    }
    .gift-status-dot.active { background: #2ecc71; box-shadow: 0 0 8px rgba(46,204,113,0.5); }
    .gift-status-dot.inactive { background: #e74c3c; box-shadow: 0 0 8px rgba(231,76,60,0.5); }

    /* Stats Grid */
    .gift-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .gift-stat-card {
        background: #fff;
        border-radius: 14px;
        padding: 22px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        border: 1px solid #f0f0f0;
        transition: all 0.3s ease;
    }
    .gift-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .gift-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .gift-stat-icon.price { background: linear-gradient(135deg, #fff3e0, #ffe0b2); color: #e65100; }
    .gift-stat-icon.uses { background: linear-gradient(135deg, #e3f2fd, #bbdefb); color: #1565c0; }
    .gift-stat-icon.sort { background: linear-gradient(135deg, #f3e5f5, #e1bee7); color: #7b1fa2; }
    .gift-stat-icon.category { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); color: #2e7d32; }
    .gift-stat-content { flex: 1; }
    .gift-stat-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #999;
        margin: 0 0 4px;
        font-weight: 600;
    }
    .gift-stat-value {
        font-size: 20px;
        font-weight: 700;
        color: #333;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .gift-stat-value img {
        width: 20px;
        height: 20px;
    }

    /* Detail Sections */
    .gift-sections-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    @media (max-width: 768px) {
        .gift-sections-row { grid-template-columns: 1fr; }
        .gift-hero-card { flex-direction: column; text-align: center; }
        .gift-hero-meta { justify-content: center; }
    }
    .gift-detail-card {
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        border: 1px solid #f0f0f0;
    }
    .gift-detail-card-header {
        padding: 16px 22px;
        border-bottom: 1px solid #f5f5f5;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
        font-weight: 600;
        color: #444;
    }
    .gift-detail-card-header i {
        font-size: 18px;
        opacity: 0.7;
    }
    .gift-detail-table {
        width: 100%;
        border-collapse: collapse;
    }
    .gift-detail-table tr {
        border-bottom: 1px solid #fafafa;
        transition: background 0.2s;
    }
    .gift-detail-table tr:last-child { border-bottom: none; }
    .gift-detail-table tr:hover { background: #fafbff; }
    .gift-detail-table td {
        padding: 12px 22px;
        font-size: 14px;
        vertical-align: middle;
    }
    .gift-detail-table .td-label {
        color: #888;
        font-weight: 500;
        width: 40%;
        white-space: nowrap;
    }
    .gift-detail-table .td-value {
        color: #333;
        font-weight: 600;
    }

    /* Media Preview */
    .gift-media-section {
        margin-bottom: 24px;
    }
    .gift-media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 20px;
    }
    .gift-media-card {
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        border: 1px solid #f0f0f0;
        transition: all 0.3s ease;
    }
    .gift-media-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .gift-media-label {
        padding: 14px 20px;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #777;
        border-bottom: 1px solid #f5f5f5;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .gift-media-preview {
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 200px;
        background: repeating-conic-gradient(#f8f8f8 0% 25%, #fff 0% 50%) 50% / 20px 20px;
    }
    .gift-media-preview img, .gift-media-preview video {
        max-width: 100%;
        max-height: 240px;
        object-fit: contain;
        border-radius: 8px;
    }
    .gift-no-media {
        color: #ccc;
        font-size: 14px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    .gift-no-media i {
        font-size: 36px;
        opacity: 0.4;
    }

    /* Lucky Gift Section */
    .gift-lucky-card {
        background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(252, 182, 159, 0.3);
    }
    .gift-lucky-title {
        font-size: 18px;
        font-weight: 700;
        color: #e65100;
        margin: 0 0 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .gift-lucky-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
    }
    .gift-lucky-item {
        background: rgba(255,255,255,0.7);
        backdrop-filter: blur(4px);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
        border: 1px solid rgba(255,255,255,0.5);
    }
    .gift-lucky-item-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #bf360c;
        font-weight: 600;
        margin-bottom: 6px;
    }
    .gift-lucky-item-value {
        font-size: 22px;
        font-weight: 800;
        color: #d84315;
    }

    /* VIP Badge */
    .gift-vip-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #ffd700, #ffaa00);
        color: #5d4e00;
        padding: 4px 14px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 13px;
        box-shadow: 0 2px 10px rgba(255,215,0,0.3);
    }
    .gift-vip-badge img {
        width: 24px;
        height: 24px;
        border-radius: 50%;
    }

    /* Timestamps */
    .gift-timestamps {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }
    .gift-timestamp-item {
        background: #fff;
        border-radius: 10px;
        padding: 14px 20px;
        flex: 1;
        min-width: 200px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .gift-timestamp-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: #888;
    }
    .gift-timestamp-text {
        flex: 1;
    }
    .gift-timestamp-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #aaa;
        font-weight: 600;
    }
    .gift-timestamp-value {
        font-size: 13px;
        color: #555;
        font-weight: 500;
    }

    /* Image Type Badge */
    .gift-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .gift-type-badge.svga { background: #e8f5e9; color: #2e7d32; }
    .gift-type-badge.alpha { background: #e3f2fd; color: #1565c0; }
    .gift-type-badge.mp4 { background: #fce4ec; color: #c62828; }
    .gift-type-badge.vap { background: #f3e5f5; color: #7b1fa2; }
    .gift-type-badge.png { background: #fff3e0; color: #e65100; }
    .gift-type-badge.default { background: #f5f5f5; color: #616161; }

    /* Actions bar */
    .gift-actions-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
    }
    .gift-action-btn {
        padding: 10px 22px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }
    .gift-action-btn:hover { text-decoration: none; transform: translateY(-1px); }
    .gift-action-btn.edit {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: #fff;
        box-shadow: 0 4px 15px rgba(102,126,234,0.3);
    }
    .gift-action-btn.edit:hover { box-shadow: 0 6px 20px rgba(102,126,234,0.4); color: #fff; }
    .gift-action-btn.back {
        background: #f5f5f5;
        color: #555;
        border: 1px solid #e0e0e0;
    }
    .gift-action-btn.back:hover { background: #eee; color: #333; }
</style>

<div class="gift-detail-wrapper">

    {{-- Action Buttons --}}
    <div class="gift-actions-bar">
        <a href="{{ admin_url('gifts?filter=' . ($gift->gift_category_id ?? 'all')) }}" class="gift-action-btn back">
            <i class="fa fa-arrow-left"></i> {{ __('Back to List') }}
        </a>
        <a href="{{ admin_url('gifts/' . $gift->id . '/edit') }}" class="gift-action-btn edit">
            <i class="fa fa-pencil"></i> {{ __('Edit') }}
        </a>
    </div>

    {{-- Hero Card --}}
    <div class="gift-hero-card">
        <div class="gift-hero-img-wrap">
            <img src="{{ $imgPath }}" alt="{{ $gift->name }}" class="gift-hero-img">
            @if($gift->music_gift)
                <div class="gift-music-badge">
                    <img src="{{ $musicIcon }}" alt="Music">
                </div>
            @endif
        </div>
        <div class="gift-hero-info">
            <div class="gift-id-badge" style="display:inline-block; margin-bottom:10px;">ID #{{ $gift->id }}</div>
            <h2 class="gift-hero-name">{{ $gift->name ?: '-' }}</h2>
            <p class="gift-hero-ename">{{ $gift->e_name ?: '' }}</p>
            <div class="gift-hero-meta">
                <span class="gift-meta-chip">
                    <span class="gift-status-dot {{ $gift->enable ? 'active' : 'inactive' }}"></span>
                    {{ $gift->enable ? __('Enabled') : __('Disabled') }}
                </span>
                <span class="gift-meta-chip">
                    <img src="{{ $coinIcon }}" alt="coin">
                    {{ number_format($gift->price ?? 0) }}
                </span>
                @if($gift->image_type)
                    <span class="gift-meta-chip">
                        <i class="fa fa-file-image-o"></i>
                        {{ strtoupper($gift->image_type) }}
                    </span>
                @endif
                @if($gift->music_gift)
                    <span class="gift-meta-chip">
                        <i class="fa fa-music"></i> {{ __('Music Gift') }}
                    </span>
                @endif
                @if($vip)
                    <span class="gift-vip-badge">
                        <img src="{{ getImagePath($vip->img) ?: $defaultImage }}" alt="VIP">
                        VIP {{ $gift->vip_level }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="gift-stats-grid">
        <div class="gift-stat-card">
            <div class="gift-stat-icon price">
                <i class="fa fa-diamond"></i>
            </div>
            <div class="gift-stat-content">
                <p class="gift-stat-label">{{ __('Price') }}</p>
                <p class="gift-stat-value">
                    {{ number_format($gift->price ?? 0) }}
                    <img src="{{ $coinIcon }}" alt="coin">
                </p>
            </div>
        </div>
        <div class="gift-stat-card">
            <div class="gift-stat-icon uses">
                <i class="fa fa-line-chart"></i>
            </div>
            <div class="gift-stat-content">
                <p class="gift-stat-label">{{ __('Use Count') }}</p>
                <p class="gift-stat-value">{{ number_format($gift->use_count ?? 0) }}</p>
            </div>
        </div>
        <div class="gift-stat-card">
            <div class="gift-stat-icon sort">
                <i class="fa fa-sort-numeric-asc"></i>
            </div>
            <div class="gift-stat-content">
                <p class="gift-stat-label">{{ __('Sort Order') }}</p>
                <p class="gift-stat-value">{{ $gift->sort ?? '-' }}</p>
            </div>
        </div>
        <div class="gift-stat-card">
            <div class="gift-stat-icon category">
                <i class="fa fa-folder-open"></i>
            </div>
            <div class="gift-stat-content">
                <p class="gift-stat-label">{{ __('Category') }}</p>
                <p class="gift-stat-value" style="font-size:15px;">{{ $categoryName }}</p>
            </div>
        </div>
    </div>

    {{-- Media Previews --}}
    <div class="gift-media-section">
        <div class="gift-media-grid">
            {{-- Thumbnail Image --}}
            <div class="gift-media-card">
                <div class="gift-media-label">
                    <i class="fa fa-image"></i> {{ __('Thumbnail Image') }} (img)
                </div>
                <div class="gift-media-preview">
                    @if($imgPath && $imgPath !== $defaultImage)
                        <img src="{{ $imgPath }}" alt="{{ __('Thumbnail') }}">
                    @else
                        <div class="gift-no-media">
                            <i class="fa fa-picture-o"></i>
                            <span>{{ __('No image') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Show Image / Animation --}}
            <div class="gift-media-card">
                <div class="gift-media-label">
                    <i class="fa fa-play-circle"></i> {{ __('Animation / Show Image') }} (show_img)
                    @if($gift->image_type)
                        <span class="gift-type-badge {{ $gift->image_type ?? 'default' }}">
                            {{ strtoupper($gift->image_type) }}
                        </span>
                    @endif
                </div>
                <div class="gift-media-preview">
                    @if($showImgPath)
                        @if(in_array($gift->image_type, ['mp4', 'vap']))
                            <video src="{{ $showImgPath }}" autoplay loop muted playsinline style="max-height:240px;"></video>
                        @else
                            {!! handleShowImageWithTypes('gift_show_' . $gift->id, $showImgPath, 200, 200, 10, 'contain') !!}
                        @endif
                    @else
                        <div class="gift-no-media">
                            <i class="fa fa-film"></i>
                            <span>{{ __('No animation') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Show Image 2 --}}
            @if($showImg2Path)
            <div class="gift-media-card">
                <div class="gift-media-label">
                    <i class="fa fa-clone"></i> {{ __('Show Image 2') }} (show_img2)
                </div>
                <div class="gift-media-preview">
                    {!! handleShowImageWithTypes('gift_show2_' . $gift->id, $showImg2Path, 200, 200, 10, 'contain') !!}
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Detail Info Cards --}}
    <div class="gift-sections-row">
        {{-- Basic Info --}}
        <div class="gift-detail-card">
            <div class="gift-detail-card-header">
                <i class="fa fa-info-circle"></i> {{ __('Basic Information') }}
            </div>
            <table class="gift-detail-table">
                <tr>
                    <td class="td-label">{{ __('ID') }}</td>
                    <td class="td-value">{{ $gift->id }}</td>
                </tr>
                <tr>
                    <td class="td-label">{{ __('Name') }}</td>
                    <td class="td-value">{{ $gift->name ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="td-label">{{ __('English Name') }}</td>
                    <td class="td-value">{{ $gift->e_name ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="td-label">{{ __('Category') }}</td>
                    <td class="td-value">{{ $categoryName }}</td>
                </tr>
                <tr>
                    <td class="td-label">{{ __('Category Type') }}</td>
                    <td class="td-value">
                        @if($category && $category->type)
                            <span class="gift-type-badge default">{{ $category->type }}</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="td-label">{{ __('Status') }}</td>
                    <td class="td-value">
                        <span style="display:inline-flex;align-items:center;gap:6px;">
                            <span class="gift-status-dot {{ $gift->enable ? 'active' : 'inactive' }}"></span>
                            {{ $gift->enable ? __('Enabled') : __('Disabled') }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Technical Details --}}
        <div class="gift-detail-card">
            <div class="gift-detail-card-header">
                <i class="fa fa-cog"></i> {{ __('Technical Details') }}
            </div>
            <table class="gift-detail-table">
                <tr>
                    <td class="td-label">{{ __('Price') }}</td>
                    <td class="td-value">
                        <span style="display:inline-flex;align-items:center;gap:5px;">
                            {{ number_format($gift->price ?? 0) }}
                            <img src="{{ $coinIcon }}" alt="Coin" width="18" height="18">
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="td-label">{{ __('Image Type') }}</td>
                    <td class="td-value">
                        @if($gift->image_type)
                            <span class="gift-type-badge {{ $gift->image_type }}">{{ strtoupper($gift->image_type) }}</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="td-label">{{ __('Music Gift') }}</td>
                    <td class="td-value">
                        @if($gift->music_gift)
                            <span style="color:#2e7d32;"><i class="fa fa-check-circle"></i> {{ __('Yes') }}</span>
                        @else
                            <span style="color:#999;"><i class="fa fa-times-circle"></i> {{ __('No') }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="td-label">{{ __('Sort Order') }}</td>
                    <td class="td-value">{{ $gift->sort ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="td-label">{{ __('Use Count') }}</td>
                    <td class="td-value">{{ number_format($gift->use_count ?? 0) }}</td>
                </tr>
                @if($gift->vip_level)
                <tr>
                    <td class="td-label">{{ __('VIP Level') }}</td>
                    <td class="td-value">
                        @if($vip)
                            <span class="gift-vip-badge">
                                <img src="{{ getImagePath($vip->img) ?: $defaultImage }}" alt="VIP">
                                Level {{ $gift->vip_level }}
                            </span>
                        @else
                            {{ $gift->vip_level }}
                        @endif
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    {{-- Timestamps --}}
    <div class="gift-timestamps">
        <div class="gift-timestamp-item">
            <div class="gift-timestamp-icon">
                <i class="fa fa-plus-circle"></i>
            </div>
            <div class="gift-timestamp-text">
                <div class="gift-timestamp-label">{{ __('Created At') }}</div>
               <div class="gift-timestamp-value">
                    {{ $gift->created_at ? \Carbon\Carbon::parse($gift->created_at)->format('Y-m-d H:i:s') : '-' }}
                </div>
            </div>
        </div>
    </div>

</div>
