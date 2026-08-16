<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

    :root {
        --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --gradient-4: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        --gradient-5: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
        --shadow-md: 0 4px 20px rgba(0,0,0,0.1);
        --radius: 16px;
    }

    .sa-container { max-width: 1280px; margin: 0 auto; padding: 24px; font-family: 'Inter', 'Segoe UI', sans-serif; color: #1a1a2e; }

    /* Header Card */
    .sa-header { background: white; border-radius: var(--radius); box-shadow: var(--shadow-md); overflow: hidden; margin-bottom: 24px; }
    .sa-header-banner { background: var(--gradient-1); height: 120px; position: relative; }
    .sa-header-body { padding: 0 32px 28px; display: flex; align-items: flex-end; gap: 24px; margin-top: -50px; position: relative; flex-wrap: wrap; }

    .sa-avatar { width: 110px; height: 110px; border-radius: 20px; overflow: hidden; border: 4px solid white; box-shadow: var(--shadow-md); flex-shrink: 0; background: white; }
    .sa-avatar img { width: 100%; height: 100%; object-fit: cover; }

    .sa-info { flex: 1; min-width: 200px; padding-top: 10px; }
    .sa-name { margin: 0 0 8px; font-size: 26px; font-weight: 800; color: #1a1a2e; letter-spacing: -0.5px; }

    .sa-meta { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 12px; }
    .sa-meta-item { display: flex; align-items: center; gap: 6px; font-size: 13px; background: #f8f9fa; padding: 5px 12px; border-radius: 8px; }
    .sa-meta-label { font-weight: 600; color: #6c757d; }
    .sa-meta-value { color: #1a1a2e; font-weight: 500; }

    /* Stats Cards */
    .sa-stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .sa-stat-card { background: white; border-radius: var(--radius); box-shadow: var(--shadow-sm); padding: 20px 24px; display: flex; align-items: center; gap: 16px; border: 1px solid #f0f0f0; transition: all 0.3s; }
    .sa-stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .sa-stat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; flex-shrink: 0; }
    .sa-stat-icon.charges { background: var(--gradient-5); }
    .sa-stat-icon.spent { background: var(--gradient-2); }
    .sa-stat-icon.salary { background: var(--gradient-3); }
    .sa-stat-value { font-size: 24px; font-weight: 800; background: var(--gradient-1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .sa-stat-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-top: 2px; }

    /* Tabs */
    .sa-tabs { display: flex; background: white; border-radius: var(--radius); box-shadow: var(--shadow-sm); padding: 6px; margin-bottom: 24px; overflow-x: auto; gap: 4px; }
    .sa-tab-btn { padding: 10px 20px; background: none; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s; white-space: nowrap; display: flex; align-items: center; gap: 7px; font-size: 13px; color: #6c757d; text-decoration: none; }
    .sa-tab-btn i { font-size: 14px; }
    .sa-tab-btn.active { background: var(--gradient-1); color: white !important; box-shadow: 0 4px 15px rgba(102,126,234,0.4); }
    .sa-tab-btn:hover:not(.active) { background: #f8f9fa; color: #1a1a2e; }

    .sa-tab-content { display: none; }
    .sa-tab-content.active { display: block; }

    /* Cards */
    .sa-card { background: white; border-radius: var(--radius); box-shadow: var(--shadow-sm); margin-bottom: 24px; border: 1px solid #f0f0f0; overflow: hidden; }
    .sa-card-header { padding: 20px 24px; border-bottom: 2px solid #f0f2f5; display: flex; align-items: center; justify-content: space-between; }
    .sa-card-header-left { display: flex; align-items: center; gap: 12px; }
    .sa-card-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; }
    .sa-card-title { margin: 0; font-size: 18px; font-weight: 800; color: #1a1a2e; }
    .sa-card-subtitle { font-size: 12px; color: #6c757d; }
    .sa-count-badge { background: var(--gradient-1); color: white; padding: 6px 16px; border-radius: 50px; font-size: 13px; font-weight: 700; }

    /* Tables */
    .sa-table { width: 100%; border-collapse: collapse; }
    .sa-table thead th { background: #f8f9fa; padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e9ecef; }
    .sa-table td { padding: 14px 16px; border-bottom: 1px solid #f5f5f5; vertical-align: middle; font-size: 13px; }
    .sa-table tbody tr { transition: background 0.2s; }
    .sa-table tbody tr:hover { background: #fafbfc; }
    .sa-table tr:last-child td { border-bottom: none; }

    .sa-user-cell { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
    .sa-user-avatar { width: 42px; height: 42px; border-radius: 50%; overflow: hidden; border: 2px solid #f0f2f5; flex-shrink: 0; }
    .sa-user-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .sa-user-name { font-weight: 700; font-size: 13px; color: #1a1a2e; }
    .sa-user-meta { font-size: 11px; color: #adb5bd; }

    .sa-status-active { color: #10b981; font-weight: 700; }
    .sa-status-inactive { color: #ef4444; font-weight: 700; }

    /* Empty State */
    .sa-empty { padding: 60px 24px; text-align: center; }
    .sa-empty-icon { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #f0f2f5, #e9ecef); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .sa-empty-icon i { font-size: 32px; color: #adb5bd; }
    .sa-empty p { font-size: 16px; font-weight: 600; color: #6c757d; margin: 0 0 4px; }
    .sa-empty small { font-size: 13px; color: #adb5bd; }

    /* Reward Sub-tabs */
    .sa-reward-pills { display: flex; gap: 6px; margin-bottom: 16px; padding: 16px 24px 0; }
    .sa-reward-pill { padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; color: #6c757d; background: #f0f2f5; transition: all 0.3s; }
    .sa-reward-pill.active { background: var(--gradient-1); color: white; box-shadow: 0 3px 10px rgba(102,126,234,0.3); }
    .sa-reward-pill:hover:not(.active) { background: #e9ecef; }

    /* Action buttons */
    .sa-btn { padding: 6px 14px; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 5px; }
    .sa-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .sa-btn-primary { background: var(--gradient-1); color: white; }
    .sa-btn-danger { background: linear-gradient(135deg, #ff6b6b, #ee5a24); color: white; }

    /* Responsive */
    @media (max-width: 768px) {
        .sa-header-body { padding: 0 16px 20px; gap: 16px; }
        .sa-name { font-size: 20px; }
        .sa-stats-row { grid-template-columns: 1fr; }
    }
</style>

<div class="sa-container">

    <!-- Go Back -->
    <div style="margin-bottom:16px; display:flex; justify-content:flex-end;">
        <a href="{{ url('admin/usersBd') }}" style="display:inline-flex; align-items:center; gap:8px; padding:10px 22px; background:white; color:#667eea; border-radius:50px; font-weight:700; font-size:14px; text-decoration:none; box-shadow:0 4px 15px rgba(0,0,0,0.1); border:2px solid #e9ecef;">
            <i class="fas fa-arrow-left"></i> {{ __("Go Back") }}
        </a>
    </div>

    <!-- Header Card -->
    <div class="sa-header">
        <div class="sa-header-banner"></div>
        <div class="sa-header-body">
            <div class="sa-avatar">
                <img src="{{ $superAdmin->display_image }}" alt="Avatar">
            </div>
            <div class="sa-info">
                <h1 class="sa-name">{{ $superAdmin->name ?? '' }}</h1>
                <div class="sa-meta">
                    <div class="sa-meta-item">
                        <i class="fas fa-id-badge" style="color:#667eea;"></i>
                        <span class="sa-meta-label">{{ __("ID") }}:</span>
                        <span class="sa-meta-value">{{ $superAdmin->id }}</span>
                    </div>
                    <div class="sa-meta-item">
                        <i class="fas fa-user" style="color:#43e97b;"></i>
                        <span class="sa-meta-label">{{ __("username") }}:</span>
                        <span class="sa-meta-value">{{ $superAdmin->username ?? '' }}</span>
                    </div>
                    @if(@$superAdmin->country)
                        <div class="sa-meta-item">
                            <img src="{{ getImagePath(@$superAdmin->country->flag) }}" style="width:20px; height:14px; border-radius:3px; object-fit:cover;">
                            <span class="sa-meta-value">{{ app()->getLocale() === 'ar' ? @$superAdmin->country->name : @$superAdmin->country->e_name }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="sa-stats-row">
        <div class="sa-stat-card">
            <div class="sa-stat-icon charges"><i class="fas fa-coins"></i></div>
            <div>
                <div class="sa-stat-value">{{ number_format(truncateAndTrim($totalCharges, 2)) }}</div>
                <div class="sa-stat-label"><i class="fas fa-arrow-up"></i> {{ __('total charges') }}</div>
            </div>
        </div>
        <div class="sa-stat-card">
            <div class="sa-stat-icon spent"><i class="fas fa-shopping-cart"></i></div>
            <div>
                <div class="sa-stat-value" style="background:var(--gradient-2); -webkit-background-clip:text;">{{ number_format(truncateAndTrim($totalSpent, 2)) }}</div>
                <div class="sa-stat-label"><i class="fas fa-arrow-down"></i> {{ __('total spent') }}</div>
            </div>
        </div>
        <div class="sa-stat-card">
            <div class="sa-stat-icon salary"><i class="fas fa-money-bill-wave"></i></div>
            <div>
                <div class="sa-stat-value" style="background:var(--gradient-3); -webkit-background-clip:text;">{{ number_format(@$superAdmin->di ?? 0) }}</div>
                <div class="sa-stat-label"><i class="fas fa-wallet"></i> {{ __('salary') }}</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="sa-tabs">
        <a href="?tab=agencies" class="sa-tab-btn" data-target="agencies-tab"><i class="fas fa-building"></i> {{ __('agencies') }}</a>
        <a href="?tab=rewards" class="sa-tab-btn" data-target="rewards-tab"><i class="fas fa-gift"></i> {{ __('rewards') }}</a>
        <a href="?tab=bds" class="sa-tab-btn" data-target="bds-tab"><i class="fas fa-headset"></i> {{ __('bds') }}</a>
        <a href="?tab=sub_super_admin" class="sa-tab-btn" data-target="sub_super_admin-tab"><i class="fas fa-user-shield"></i> {{ __('super admins') }}</a>
    </div>

    <!-- Loading -->
    <div id="tab-loading" style="display:none;"></div>

    <!-- ===== TAB: AGENCIES ===== -->
    <div class="sa-tab-content" id="agencies-tab">
        <div class="sa-card">
            <div class="sa-card-header" style="background:linear-gradient(135deg,#f8f9ff 0%,#ffffff 100%);">
                <div class="sa-card-header-left">
                    <div class="sa-card-icon" style="background:var(--gradient-3);"><i class="fas fa-building"></i></div>
                    <div>
                        <h3 class="sa-card-title">{{ __('Agencies') }}</h3>
                        <span class="sa-card-subtitle">{{ __('Managed agencies') }}</span>
                    </div>
                </div>
                <span class="sa-count-badge">{{ optional($agencies)->total() ?? 0 }}</span>
            </div>
            @if($agencies && $agencies->count())
                <div class="table-responsive">
                    <table class="sa-table">
                        <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th><i class="fas fa-building" style="color:#4facfe;margin-right:6px;"></i>{{ __('Name') }}</th>
                            <th><i class="fas fa-crown" style="color:#f59e0b;margin-right:6px;"></i>{{ __('owner') }}</th>
                            <th><i class="fas fa-circle-check" style="color:#10b981;margin-right:6px;"></i>{{ __('Status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($agencies as $index => $agency)
                            <tr>
                                <td style="font-weight:600; color:#adb5bd;">{{ $index + 1 + (($agencies->currentPage() - 1) * $agencies->perPage()) }}</td>
                                <td>
                                    <a href="{{ url($prefix.'/agencies/profile/' .$agency->id) }}" class="sa-user-cell">
                                        <div class="sa-user-avatar"><img src="{{ getImagePath($agency->img) }}" alt=""></div>
                                        <div>
                                            <div class="sa-user-name">{{ $agency->name ?? '' }}</div>
                                            <div class="sa-user-meta">ID: {{ $agency->id }}</div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ url($prefix.'/users/'.$agency->owner?->id) }}" class="sa-user-cell">
                                        <div class="sa-user-avatar"><img src="{{ getImagePath($agency->owner?->profile?->avatar) ?? $defaultImage }}" alt=""></div>
                                        <div>
                                            <div class="sa-user-name">{{ $agency->owner?->name ?? '' }}</div>
                                            <div class="sa-user-meta">UUID: {{ $agency->owner?->uuid ?? 'N/A' }}</div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    @if($agency->status == 1)
                                        <span class="sa-status-active"><i class="fas fa-circle" style="font-size:8px;margin-right:4px;"></i> {{ __('Active') }}</span>
                                    @else
                                        <span class="sa-status-inactive"><i class="fas fa-circle" style="font-size:8px;margin-right:4px;"></i> {{ __('Inactive') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:16px 24px; display:flex; justify-content:center; background:#fafbfc; border-top:1px solid #f0f2f5;">
                    {{ $agencies->appends(['tab' => 'agencies'])->links('vendor.pagination.default') }}
                </div>
            @else
                <div class="sa-empty">
                    <div class="sa-empty-icon"><i class="fas fa-building"></i></div>
                    <p>{{ __('No agencies found') }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- ===== TAB: REWARDS ===== -->
    <div class="sa-tab-content" id="rewards-tab">
        <div class="sa-card">
            <div class="sa-card-header" style="background:linear-gradient(135deg,#fff5f5 0%,#ffffff 100%);">
                <div class="sa-card-header-left">
                    <div class="sa-card-icon" style="background:var(--gradient-2);"><i class="fas fa-gift"></i></div>
                    <div>
                        <h3 class="sa-card-title">{{ __('rewards') }}</h3>
                        <span class="sa-card-subtitle">{{ __('Reward items') }}</span>
                    </div>
                </div>
                <span class="sa-count-badge" style="background:var(--gradient-2);">{{ optional($rewards)->total() ?? 0 }}</span>
            </div>

            <!-- Reward type pills -->
            <div class="sa-reward-pills">
                @foreach($types as $name)
                    <a href="{{ request()->fullUrlWithQuery(['type' => $name, 'reward_page' => 1]) }}"
                       class="sa-reward-pill {{ $type == $name ? 'active' : '' }}">
                        @if($name == 'vip') <i class="fas fa-crown" style="margin-right:4px;"></i>
                        @elseif($name == 'badge') <i class="fas fa-certificate" style="margin-right:4px;"></i>
                        @elseif($name == 'ware') <i class="fas fa-box" style="margin-right:4px;"></i>
                        @endif
                        {{ __($name) }}
                    </a>
                @endforeach
            </div>

            @if($rewards && $rewards->count())
                <div class="table-responsive">
                    <table class="sa-table">
                        <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th><i class="fas fa-tag" style="color:#f5576c;margin-right:6px;"></i>{{ __('Name') }}</th>
                            <th><i class="fas fa-image" style="color:#764ba2;margin-right:6px;"></i>{{ __('image') }}</th>
                            <th><i class="fas fa-sort-numeric-up" style="color:#4facfe;margin-right:6px;"></i>{{ __('count') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rewards as $index => $reward)
                            @php
                                $rName = '';
                                $rImage = '';
                                if (request('type') == 'ware') { $rName = @$reward->ware->name ?? ''; $rImage = @$reward->ware->image ?? ''; }
                                elseif (request('type') == 'vip') { $rName = @$reward->vip->name ?? ''; $rImage = @$reward->vip->image ?? ''; }
                                elseif (request('type') == 'badge') { $rName = @$reward->badge->name ?? ''; $rImage = @$reward->badge?->images?->firstWhere('language', app()->getLocale())?->image ?? ''; }
                            @endphp
                            <tr>
                                <td style="font-weight:600; color:#adb5bd;">{{ $index + 1 + (($rewards->currentPage() - 1) * $rewards->perPage()) }}</td>
                                <td><span style="font-weight:700; color:#1a1a2e;">{{ $rName }}</span></td>
                                <td>
                                    @if($rImage)
                                        <div style="width:40px; height:40px; border-radius:10px; overflow:hidden; border:2px solid #f0f2f5;">
                                            <img src="{{ getImagePath($rImage) }}" style="width:100%; height:100%; object-fit:cover;">
                                        </div>
                                    @else
                                        <span style="color:#adb5bd;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="background:linear-gradient(135deg,#e8f5e9,#c8e6c9); color:#2e7d32; padding:4px 12px; border-radius:8px; font-weight:700; font-size:12px;">
                                        {{ $reward->no_reward }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:16px 24px; display:flex; justify-content:center; background:#fafbfc; border-top:1px solid #f0f2f5;">
                    {{ $rewards->appends(['tab' => 'rewards', 'type' => $type])->links('vendor.pagination.default') }}
                </div>
            @else
                <div class="sa-empty">
                    <div class="sa-empty-icon"><i class="fas fa-gift"></i></div>
                    <p>{{ __('No reward found') }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- ===== TAB: BDS ===== -->
    <div class="sa-tab-content" id="bds-tab">
        <div class="sa-card">
            <div class="sa-card-header" style="background:linear-gradient(135deg,#f0fff4 0%,#ffffff 100%);">
                <div class="sa-card-header-left">
                    <div class="sa-card-icon" style="background:var(--gradient-4);"><i class="fas fa-headset"></i></div>
                    <div>
                        <h3 class="sa-card-title">{{ __('Bds') }}</h3>
                        <span class="sa-card-subtitle">{{ __('Business developers') }}</span>
                    </div>
                </div>
                <span class="sa-count-badge" style="background:var(--gradient-4); color:#1a1a2e;">{{ optional($bds)->total() ?? 0 }}</span>
            </div>
            @if($bds && $bds->count())
                <div class="table-responsive">
                    <table class="sa-table">
                        <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th><i class="fas fa-user-tie" style="color:#43e97b;margin-right:6px;"></i>{{ __('Name') }}</th>
                            <th><i class="fas fa-user" style="color:#4facfe;margin-right:6px;"></i>{{ __('user') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($bds as $index => $bd)
                            <tr>
                                <td style="font-weight:600; color:#adb5bd;">{{ $index + 1 + (($bds->currentPage() - 1) * $bds->perPage()) }}</td>
                                <td>
                                    <a href="{{ url($prefix.'/usersBd/' .$bd->id) }}" class="sa-user-cell">
                                        <div class="sa-user-avatar"><img src="{{ $bd->avatar ? getImagePath($bd->avatar) : $defaultImage }}" alt=""></div>
                                        <div>
                                            <div class="sa-user-name">{{ $bd->username ?? '' }}</div>
                                            <div class="sa-user-meta">ID: {{ $bd->id }}</div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ url($prefix.'/users/' . ($bd->appUser?->id ?? 0)) }}" class="sa-user-cell">
                                        <div class="sa-user-avatar"><img src="{{ $bd->appUser?->profile?->avatar ? getImagePath($bd->appUser->profile->avatar) : $defaultImage }}" alt=""></div>
                                        <div>
                                            <div class="sa-user-name" style="display:flex; align-items:center; gap:6px;">
                                                @if(@$bd->appUser->country->flag)
                                                    <img src="{{ getImagePath($bd->appUser->country->flag) }}" style="width:18px; height:13px; border-radius:2px; object-fit:cover;">
                                                @endif
                                                {{ $bd->appUser?->name ?? '' }}
                                            </div>
                                            <div class="sa-user-meta">UUID: {{ $bd->appUser?->uuid ?? 'N/A' }}</div>
                                        </div>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:16px 24px; display:flex; justify-content:center; background:#fafbfc; border-top:1px solid #f0f2f5;">
                    {{ $bds->appends(['tab' => 'bds'])->links('vendor.pagination.default') }}
                </div>
            @else
                <div class="sa-empty">
                    <div class="sa-empty-icon"><i class="fas fa-headset"></i></div>
                    <p>{{ __('No bds found') }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- ===== TAB: SUB SUPER ADMINS ===== -->
    <div class="sa-tab-content" id="sub_super_admin-tab">
        <div class="sa-card">
            <div class="sa-card-header" style="background:linear-gradient(135deg,#fef5ff 0%,#ffffff 100%);">
                <div class="sa-card-header-left">
                    <div class="sa-card-icon" style="background:var(--gradient-1);"><i class="fas fa-user-shield"></i></div>
                    <div>
                        <h3 class="sa-card-title">{{ __('Super Admins') }}</h3>
                        <span class="sa-card-subtitle">{{ __('Sub super admin accounts') }}</span>
                    </div>
                </div>
                <span class="sa-count-badge">{{ optional($subSuperAdmins)->total() ?? 0 }}</span>
            </div>
            @if($subSuperAdmins && $subSuperAdmins->count())
                <div class="table-responsive">
                    <table class="sa-table">
                        <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th><i class="fas fa-user-shield" style="color:#667eea;margin-right:6px;"></i>{{ __('Name') }}</th>
                            <th><i class="fas fa-user" style="color:#4facfe;margin-right:6px;"></i>{{ __('user') }}</th>
                            <th><i class="fas fa-bolt" style="color:#f59e0b;margin-right:6px;"></i>{{ __('Role') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($subSuperAdmins as $index => $subSuperAdmin)
                            <tr>
                                <td style="font-weight:600; color:#adb5bd;">{{ $index + 1 + (($subSuperAdmins->currentPage() - 1) * $subSuperAdmins->perPage()) }}</td>
                                <td>
                                    <a href="{{ url($prefix.'/auth-users/' .$subSuperAdmin->id) }}" class="sa-user-cell">
                                        <div class="sa-user-avatar"><img src="{{ $subSuperAdmin->avatar ? getImagePath($subSuperAdmin->avatar) : $defaultImage }}" alt=""></div>
                                        <div>
                                            <div class="sa-user-name">{{ $subSuperAdmin->username ?? '' }}</div>
                                            <div class="sa-user-meta">ID: {{ $subSuperAdmin->id }}</div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ url($prefix.'/users/' . ($subSuperAdmin->appUser?->id ?? 0)) }}" class="sa-user-cell">
                                        <div class="sa-user-avatar"><img src="{{ $subSuperAdmin->appUser?->profile?->avatar ? getImagePath($subSuperAdmin->appUser->profile->avatar) : $defaultImage }}" alt=""></div>
                                        <div>
                                            <div class="sa-user-name" style="display:flex; align-items:center; gap:6px;">
                                                @if(@$subSuperAdmin->appUser->country->flag)
                                                    <img src="{{ getImagePath($subSuperAdmin->appUser->country->flag) }}" style="width:18px; height:13px; border-radius:2px; object-fit:cover;">
                                                @endif
                                                {{ $subSuperAdmin->appUser?->name ?? '' }}
                                            </div>
                                            <div class="sa-user-meta">UUID: {{ $subSuperAdmin->appUser?->uuid ?? 'N/A' }}</div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        @if (\Encore\Admin\Facades\Admin::user()->can('delete-auth-users') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                            <button class="sa-btn sa-btn-danger kick-member-btn" data-id="{{ $subSuperAdmin->id }}">
                                                <i class="fas fa-trash"></i> {{ __('delete') }}
                                            </button>
                                        @endif
                                        @if (\Encore\Admin\Facades\Admin::user()->can('edit-auth-users') || \Encore\Admin\Facades\Admin::user()->can('*'))
                                            <button class="sa-btn sa-btn-primary edit_user_item_model_btn" data-id="{{ $subSuperAdmin->id }}">
                                                <i class="fas fa-pen"></i> {{ __('edit') }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:16px 24px; display:flex; justify-content:center; background:#fafbfc; border-top:1px solid #f0f2f5;">
                    {{ $subSuperAdmins->appends(['tab' => 'sub_super_admin'])->links('vendor.pagination.default') }}
                </div>
            @else
                <div class="sa-empty">
                    <div class="sa-empty-icon"><i class="fas fa-user-shield"></i></div>
                    <p>{{ __('No super admins found') }}</p>
                </div>
            @endif
        </div>
    </div>

</div>

<!-- Edit Modal -->
<div class="modal fade" id="item_modal_update" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg mt-6" role="document">
        <div class="modal-content border-0">
            <div class="modal-content position-relative">
                <div class="position-absolute top-0 end-0 mt-2 me-2 z-index-1">
                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ url(request()->segment(1) . '/update-sub-super-admin') }}" id="country_update_form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-0">
                        <div class="p-4">
                            <div class="row flex-evenly">
                                <input type="hidden" name="id" class="item_id">
                                <div class="col-lg-6 mb-3 form-group">
                                    <label class="form-label">{{ __('admin.name') }}</label>
                                    <input type="text" name="name" class="form-control" id="name">
                                </div>
                                <div class="col-lg-6 mb-3 form-group">
                                    <label class="form-label">{{ __('admin.username') }}</label>
                                    <input type="text" name="username" class="form-control" id="username">
                                </div>
                                <div class="col-lg-6 mb-3 form-group">
                                    <label for="user_id" class="form-label">{{ __('admin.users') }}</label>
                                    <select class="form-control" id="user_id" name="user_id">
                                        <option value="">{{ __('admin.selectUser') }}</option>
                                    </select>
                                </div>
                                <div class="col-lg-6 mb-3 form-group">
                                    <label class="form-label">{{ __('admin.password') }}</label>
                                    <input type="password" name="password" class="form-control" id="password" placeholder="Leave blank if not changing">
                                </div>
                                <div class="col-lg-6 form-group mb-3">
                                    <label class="form-label">{{ __('image') }}</label>
                                    <input class="form-control" name="image" accept="image/*" type="file"/>
                                    <div class="mt-2"><img src="" class="w-40" style="width: 100px" id="img_edit" alt=""></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary cancel_user_item_model_btn" type="button" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button class="btn btn-primary" type="submit">{{ __('edit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
<script>
    const DASHBOARD_PREFIX = "{{ request()->segment(1) }}";

    // ===== Client-side Tab Switching =====
    document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedTab = urlParams.get('tab') || 'agencies';
        const allTabs = document.querySelectorAll('.sa-tab-btn');

        allTabs.forEach(tab => {
            const target = tab.getAttribute('data-target');
            const content = document.getElementById(target);

            if (target.startsWith(selectedTab)) {
                tab.classList.add('active');
                if (content) { content.style.display = 'block'; content.classList.add('active'); }
            } else {
                tab.classList.remove('active');
                if (content) { content.style.display = 'none'; content.classList.remove('active'); }
            }

            tab.addEventListener('click', function (e) {
                e.preventDefault();
                allTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                allTabs.forEach(t => {
                    const panelId = t.getAttribute('data-target');
                    const panel = document.getElementById(panelId);
                    if (!panel) return;
                    if (panelId === target) {
                        panel.style.display = 'block';
                        panel.classList.add('active');
                    } else {
                        panel.style.display = 'none';
                        panel.classList.remove('active');
                    }
                });

                const url = new URL(window.location.href);
                const tabParam = new URLSearchParams(this.getAttribute('href').replace('?', ''));
                url.searchParams.set('tab', tabParam.get('tab'));
                window.history.replaceState({}, '', url.toString());
            });
        });
    });

    // ===== jQuery Logic =====
    $(document).ready(function () {
        $('#user_id').select2({
            dropdownParent: $('#item_modal_update'), width: '100%', placeholder: 'Select user', allowClear: true, minimumInputLength: 1,
            ajax: {
                url: '/api/search/users-superadmin', dataType: 'json', delay: 250,
                data: function (params) { return { q: params.term || '', page: params.page || 1, selected_id: $('#user_id').val() || null }; },
                processResults: function (data) { return { results: data.data.map(item => ({ id: item.id, text: item.name })), pagination: { more: data.current_page < data.last_page } }; }
            }
        });

        (function () {
            var preId = $('#user_id').data('selected-id');
            var preText = $('#user_id').data('selected-text');
            if (preId) { $('#user_id').val(null).trigger('change'); var sel = new Option(preText || preId, preId, true, true); $('#user_id').append(sel).trigger('change'); }
        })();

        $(document).on('click', '.edit_user_item_model_btn', function () {
            $('#item_modal_update').modal('show');
            const id = $(this).data('id');
            $.ajax({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                type: 'get', dataType: 'json', url: '/' + DASHBOARD_PREFIX + "/show-sub-super-admin/" + id,
                success: function (response) {
                    if (response.status == 404) { Swal.fire({ icon: 'error', title: 'Sorry', text: response.message }); }
                    else {
                        const image = "{{ getImagePath('__IMAGE_PATH__') }}".replace('__IMAGE_PATH__', response.item.avatar);
                        $('#name').val(response.item.name); $('#username').val(response.item.username); $('#password').val(response.item.password);
                        if (response.item.app_id) {
                            $('#user_id').val(null).trigger('change');
                            var displayText = response.item.app_user_name || null;
                            if (!displayText && response.item.appUser) { var au = response.item.appUser; displayText = (au.name || '') + ' - ' + (au.uuid || ''); }
                            if (!displayText) displayText = response.item.name || response.item.app_id;
                            var sel = new Option(displayText, response.item.app_id, true, true);
                            $('#user_id').append(sel).trigger('change');
                        }
                        $('#img_edit').attr('src', image); $('.item_id').val(response.item.id);
                        $('#item_modal_update').modal('show');
                    }
                }
            });
        });

        $(document).on('click', '.cancel_user_item_model_btn', function () { $('#item_modal_update').modal('hide'); });

        function showLoader() { Swal.fire({ title: 'Loading...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } }); }
        function showSuccess(msg, cb) { Swal.fire({ icon: 'success', title: msg, confirmButtonText: 'OK' }).then(() => { if (cb) cb(); }); }
        function showError(msg) { Swal.fire({ icon: 'error', title: msg, confirmButtonText: 'OK' }); }
        function confirmAction(msg, onConfirm) { Swal.fire({ title: msg, icon: 'question', showCancelButton: true, confirmButtonText: 'Yes', cancelButtonText: 'Cancel' }).then(r => { if (r.value) onConfirm(); }); }

        $('.kick-member-btn').click(function () {
            const id = $(this).data('id');
            confirmAction('{{ __("are_you_sure_delete") }}', () => {
                showLoader();
                $.post('/' + DASHBOARD_PREFIX + '/delete-sub-admin/' + id, { _token: '{{ csrf_token() }}' }, function (response) {
                    Swal.close();
                    if (response.status) { showSuccess(response.message, () => { location.reload(); }); }
                    else { showError(response.message); }
                }).fail(function (xhr) { Swal.close(); showError(xhr.responseJSON?.message ?? '{{ __("failed_make_admin") }}'); });
            });
        });
    });
</script>
