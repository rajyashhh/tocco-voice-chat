@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ admin_asset('vendor/fontawesome6/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ admin_asset('vendor/fonts/inter/inter.css') }}">

    {{-- Central design system: single source of truth for colors/surfaces. --}}
    @include('css.theme-tokens')
    @include('css.room_profile')
</head>
<body>

<div class="room-profile-page">

    {{-- ═════════════ HEADER CARD ═════════════ --}}
    <div class="room-header-card">

        {{-- Top: Avatar + Info + Actions --}}
        <div class="room-header-top">
            <div class="room-avatar">
                <img src="{{ getImagePath(@$room->room_cover) ?? asset('images/room.jpg') }}" alt="Room Cover">
            </div>

            <div class="room-header-info">
                <h1 class="room-name">{{ @$room->room_name ?? '' }}</h1>

                <div class="room-meta-row">
                    <span class="room-meta-tag"><i class="fas fa-hashtag"></i> Room ID: <strong>{{ @$room->id }}</strong></span>
                    <span class="room-meta-tag"><i class="fas fa-fingerprint"></i> UID: <strong>{{ @$room->uid }}</strong></span>
                    <span class="room-meta-tag"><i class="fas fa-user-shield"></i> Owner UID: <strong>{{ @$room->user->uuid }}</strong></span>
                    <span class="room-meta-tag"><i class="fas fa-layer-group"></i> Type: <strong>{{ @$room->roomCategory->name ?? 'N/A' }}</strong></span>
                    <span class="room-meta-tag"><i class="fas fa-th-large"></i> Mode: <strong>{{ @$roomModes[$room->mode] ?? '?' }} {{ __('seats') }}</strong></span>
                </div>

                <div class="room-owner-row">
                    <i class="fas fa-crown" style="color:var(--warning);"></i>
                    <span class="owner-label">{{ __('Room Owner') }}:</span>
                    <a href="{{ admin_url('users/' . @$room->owner->id) }}">{{ @$room->owner->name }} ({{ @$room->owner->uuid }})</a>
                </div>

                <div class="room-status-row">
                    @if($room->room_status)
                        <span class="status-pill active">{{ __('Active') }}</span>
                    @else
                        <span class="status-pill inactive">{{ __('Inactive') }}</span>
                    @endif

                    @if($room->is_popular == 1)
                        <span class="feature-tag popular"><i class="fas fa-fire"></i> {{ __('Popular') }}</span>
                    @endif
                    @if($room->is_top == 1)
                        <span class="feature-tag top"><i class="fas fa-arrow-up"></i> {{ __('Top') }}</span>
                    @endif
                    @if($room->is_recommended == 1)
                        <span class="feature-tag recommended"><i class="fas fa-star"></i> {{ __('Recommended') }}</span>
                    @endif
                    @if($room->secret_chat == 1)
                        <span class="feature-tag secret"><i class="fas fa-lock"></i> {{ __('Secret Chat') }}</span>
                    @endif
                    @if($room->is_live)
                        <span class="feature-tag live"><i class="fas fa-circle"></i> {{ __('Live') }}</span>
                    @endif
                </div>
            </div>

            <div class="room-header-actions">
                <a href="{{ url('admin/rooms') }}" class="btn-back">
                    <i class="fas fa-arrow-left"></i> {{ __('Go Back') }}
                </a>
                @if(Admin::user()->can('edit-rooms') || Admin::user()->can('*'))
                    <button type="button" class="btn-edit-room" onclick="openEditModal()">
                        <i class="fas fa-pen-to-square"></i> {{ __('Edit Room') }}
                    </button>
                @endif
            </div>
        </div>

        {{-- Error messages --}}
        @if(isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any())
            <div class="room-alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Stats row --}}
        <div class="room-stats-row">
            <div class="room-stat-item">
                <div class="room-stat-icon c-blue"><i class="fas fa-eye"></i></div>
                <div class="room-stat-value">{{ number_format(@$room->room_visitors_count) }}</div>
                <div class="room-stat-label">{{ __('Visitors') }}</div>
            </div>
            <div class="room-stat-item">
                <div class="room-stat-icon c-green"><i class="fas fa-users"></i></div>
                <div class="room-stat-value">{{ @$room->count_room_socket }}</div>
                <div class="room-stat-label">{{ __('Online Now') }}</div>
            </div>
            <div class="room-stat-item">
                <div class="room-stat-icon c-amber"><i class="fas fa-user-tie"></i></div>
                <div class="room-stat-value">{{ @$room->max_admin }}</div>
                <div class="room-stat-label">{{ __('Max Admins') }}</div>
            </div>
            <div class="room-stat-item">
                <div class="room-stat-icon c-purple"><i class="fas fa-trophy"></i></div>
                <div class="room-stat-value">
                    <img src="{{ getImagePath(@$room->roomLevel->img) }}" alt="Level">
                </div>
                <div class="room-stat-label">{{ __('Level') }}</div>
            </div>
        </div>
    </div>

    {{-- ═════════════ TABS ═════════════ --}}
    <div class="agency-tabs">
        <a href="?tab=admins" class="tab-btn {{ request('tab') == 'admins' ? 'active' : '' }}" data-target="admins-tab">
            <i class="fas fa-user-shield"></i> {{ __('Room Admins') }}
        </a>
        <a href="?tab=gifts" class="tab-btn {{ request('tab') == 'gifts' ? 'active' : '' }}" data-target="gifts-tab">
            <i class="fas fa-gift"></i> {{ __('Room Gifts') }}
        </a>
        <a href="?tab=visitors" class="tab-btn {{ request('tab') == 'visitors' ? 'active' : '' }}" data-target="visitors-tab">
            <i class="fas fa-user-group"></i> {{ __('Room Visitors') }}
        </a>
        <a href="?tab=pk" class="tab-btn {{ request('tab') == 'pk' ? 'active' : '' }}" data-target="pk-tab">
            <i class="fas fa-bolt"></i> {{ __('Pk') }}
        </a>
        <a href="?tab=boxes" class="tab-btn {{ request('tab') == 'boxes' ? 'active' : '' }}" data-target="boxes-tab">
            <i class="fas fa-box-open"></i> {{ __('Boxes') }}
        </a>
    </div>
</div>

{{-- ═════════════ EDIT ROOM MODAL ═════════════ --}}
<div class="modal fade" id="editRoomModal" tabindex="-1" role="dialog" aria-labelledby="editRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRoomModalLabel">
                    <i class="fas fa-pen-to-square" style="color:var(--primary-color);margin-inline-end:8px;"></i>{{ __('Edit Room') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="editRoomForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('Room Name') }}</label>
                        <input type="text" class="form-control" name="room_name" value="{{ $room->room_name }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('Room Type') }}</label>
                        <select class="form-control" name="room_type">
                            <option value="">{{ __('Select Type') }}</option>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->id }}" {{ $room->room_type == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('Max Admins') }}</label>
                        <input type="number" class="form-control" name="max_admin" value="{{ $room->max_admin }}">
                    </div>
                    <div class="form-group">
                        <label style="margin-bottom:10px;display:block;">{{ __('Features') }}</label>
                        <div style="display:flex;flex-wrap:wrap;gap:16px;">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_popular" name="is_popular" {{ $room->is_popular == 1 ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_popular">{{ __('Popular') }}</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_top" name="is_top" {{ $room->is_top == 1 ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_top">{{ __('Top') }}</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_recommended" name="is_recommended" {{ $room->is_recommended == 1 ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_recommended">{{ __('Recommended') }}</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="secret_chat" name="secret_chat" {{ $room->secret_chat == 1 ? 'checked' : '' }}>
                                <label class="custom-control-label" for="secret_chat">{{ __('Secret Chat') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-info">{{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Loading --}}
<div id="tab-loading" style="display:none;position:fixed;top:50%;left:50%;z-index:9999;">
    <i class="fas fa-spinner fa-spin" style="margin-inline-end:8px;"></i> {{ __('Loading...') }}
</div>

{{-- ═════════════ TAB: ADMINS ═════════════ --}}
<div class="tab-content {{ request('tab') == 'admins' ? 'active' : '' }}" id="admins-tab">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title"><i class="fas fa-user-shield"></i> {{ __('Room Administrators') }}</h4>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th style="width:60px;">#</th>
                    <th>{{ __('Operator') }}</th>
                    <th style="width:140px;">{{ __('Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($admins as $index => $admin)
                    @php
                        $path = @$admin->profile?->avatar;
                        $defaultImage = asset("images/businessman-icon.jpg");
                        $url = getImagePath($path) ?? $defaultImage;
                        if (!isImageExists($url)) { $url = $defaultImage; }
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <a href="{{ admin_url('users/' . $admin->id) }}" target="_blank" class="user-cell">
                                <img src="{{ $url }}" alt="">
                                <div class="user-cell-info">
                                    <span class="user-cell-name">{{ $admin->name }}</span>
                                    <span class="user-cell-meta">ID: {{ $admin->id }} · UID: {{ $admin->uuid }}</span>
                                </div>
                            </a>
                        </td>
                        <td>
                            @if(Admin::user()->can('actions-switch-rooms') || Admin::user()->can('*'))
                                <button class="btn btn-danger btn-sm remove-admin" data-room-id="{{ $room->id }}" data-admin-id="{{ $admin->id }}">
                                    <i class="fas fa-trash-can"></i> {{ __('Remove') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <p>{{ __('No data available') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ═════════════ TAB: GIFTS ═════════════ --}}
<div class="tab-content {{ request('tab') == 'gifts' ? 'active' : '' }}" id="gifts-tab">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title"><i class="fas fa-gift"></i> {{ __('Room Gift Reports') }}</h4>
        </div>
        <div class="p-3">
            {{-- Filter --}}
            <div class="card" style="margin-bottom:20px;">
                <div class="p-3">
                    <form action="" class="form-horizontal gift-log-form" method="GET">
                        <input type="hidden" name="tab" value="gifts">
                        <div class="container-fluid">
                            <div class="row g-2 align-items-end justify-content-between">
                                <div class="col-md-2">
                                    <label for="sender-select" class="form-label fw-bold">{{ __('Sender') }}</label>
                                    <select class="form-control" name="sender_id" id="sender-select"></select>
                                </div>
                                <div class="col-md-2">
                                    <label for="receiver-select" class="form-label fw-bold">{{ __('Receiver') }}</label>
                                    <select class="form-control" name="receiver_id" id="receiver-select"></select>
                                </div>
                                <div class="col-md-2">
                                    <label for="date-from" class="form-label fw-bold">{{ __('From Date') }}</label>
                                    <input type="date" class="form-control" name="start_at" id="date-from" value="{{ request('start_at') }}">
                                </div>
                                <div class="col-md-2">
                                    <label for="date-to" class="form-label fw-bold">{{ __('To Date') }}</label>
                                    <input type="date" class="form-control" name="end_at" id="date-to" value="{{ request('end_at') }}">
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="submit" class="btn btn-info"><i class="fa fa-search"></i> {{ __('Search') }}</button>
                                        <a href="?tab=gifts" class="btn btn-default"><i class="fa fa-undo"></i> {{ __('Reset') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="diamond-summary-container">
                <div class="diamond-summary-box">
                    <div class="diamond-title">{{ __('Total Diamonds') }}</div>
                    <div class="diamond-count">
                        <span>{{ number_format($totalDiamonds) }}</span>
                        <div class="diamond-icon-container">
                            <img src="{{ asset('images/diamond.jpg') }}" alt="Diamond" class="diamond-icon">
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Sender') }}</th>
                        <th>{{ __('Receiver') }}</th>
                        <th>{{ __('Gift') }}</th>
                        <th>{{ __('Quantity') }}</th>
                        <th>{{ __('Price') }}</th>
                        <th>{{ __('Created at') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($gifts as $index => $gift)
                        <tr>
                            <td>{{ $gift->id }}</td>
                            <td>
                                <a href="{{ admin_url('users/' . @$gift->sender->id) }}" target="_blank" class="user-cell">
                                    <img src="{{ getImagePath(@$gift->sender->profile?->avatar) ?? asset('images/businessman-icon.jpg') }}" alt="">
                                    <div class="user-cell-info">
                                        <span class="user-cell-name">{{ @$gift->sender->name }}</span>
                                        <span class="user-cell-meta">UUID: {{ @$gift->sender->uuid }}</span>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <a href="{{ admin_url('users/' . @$gift->receiver->id) }}" target="_blank" class="user-cell">
                                    <img src="{{ getImagePath(@$gift->receiver->profile?->avatar) ?? asset('images/businessman-icon.jpg') }}" alt="">
                                    <div class="user-cell-info">
                                        <span class="user-cell-name">{{ @$gift->receiver->name }}</span>
                                        <span class="user-cell-meta">UUID: {{ @$gift->receiver->uuid }}</span>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <div class="user-cell">
                                    <img src="{{ getImagePath(@$gift->gift->img ?? '') }}" alt="" style="width:28px;height:28px;border-radius:6px;">
                                    <span>{{ @$gift->gift?->giftName ?? '' }}</span>
                                </div>
                            </td>
                            <td><strong>{{ $gift->giftNum }}</strong></td>
                            <td><strong>{{ number_format($gift->giftPrice) }}</strong></td>
                            <td><span class="user-cell-meta">{{ Carbon::parse($gift->created_at)->format('Y-m-d H:i') }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-gift"></i>
                                    <p>{{ __('No data available') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $gifts->appends(request()->all())->links('vendor.pagination.bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

{{-- ═════════════ TAB: VISITORS ═════════════ --}}
<div class="tab-content {{ request('tab') == 'visitors' ? 'active' : '' }}" id="visitors-tab">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title"><i class="fas fa-user-group"></i> {{ __('Room Visitors') }}</h4>
            @if(Admin::user()->can('actions-switch-rooms') || Admin::user()->can('*'))
                <button class="btn btn-success btn-sm" id="add-visitor-btn"><i class="fas fa-plus"></i> {{ __('Add Visitor') }}</button>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th style="width:60px;">#</th>
                    <th>{{ __('Visitor') }}</th>
                    <th>{{ __('Mic Position') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Join Time') }}</th>
                    <th style="width:120px;">{{ __('Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($visitors as $index => $visitor)
                    <tr>
                        <td>{{ ($visitors->currentPage() - 1) * $visitors->perPage() + $loop->iteration }}</td>
                        <td>
                            @if($visitor->user)
                                <a href="{{ admin_url('users/' . $visitor->user->id) }}" target="_blank" class="user-cell">
                                    <img src="{{ getImagePath($visitor->user->profile?->avatar) ?? asset('images/businessman-icon.jpg') }}" alt="">
                                    <div class="user-cell-info">
                                        <span class="user-cell-name">{{ $visitor->user->name }}</span>
                                        <span class="user-cell-meta">ID: {{ $visitor->user->id }} · UID: {{ $visitor->user->uuid }}</span>
                                    </div>
                                </a>
                            @else
                                <div class="user-cell">
                                    <img src="{{ asset('images/businessman-icon.jpg') }}" alt="">
                                    <div class="user-cell-info">
                                        <span class="user-cell-name">{{ __('Unknown User') }}</span>
                                        <span class="user-cell-meta">{{ __('User Missing/Deleted') }}</span>
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($visitor->mic_position)
                                <span class="badge badge-info"><i class="fas fa-microphone"></i> #{{ $visitor->mic_position }}</span>
                            @else
                                <span class="badge badge-secondary">{{ __('Not on mic') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($visitor->kick_info)
                                <span class="badge badge-danger">{{ __('Kicked') }} ({{ $visitor->kick_info['remaining'] }} {{ __('min remaining') }})</span>
                            @else
                                <span class="badge badge-success">{{ __('Active') }}</span>
                            @endif
                        </td>
                        <td><span class="user-cell-meta">{{ Carbon::parse($visitor->created_at)->format('Y-m-d H:i:s') }}</span></td>
                        <td>
                            @if(Admin::user()->can('actions-switch-rooms') || Admin::user()->can('*'))
                                @if($visitor->kick_info)
                                    <button class="btn btn-warning btn-sm unban-visitor" data-room-id="{{ $room->id }}" data-user-id="{{ $visitor->user_id }}">
                                        <i class="fas fa-unlock"></i> {{ __('Unban') }}
                                    </button>
                                @else
                                    <button class="btn btn-danger btn-sm kick-visitor" data-room-id="{{ $room->id }}" data-user-id="{{ $visitor->user_id }}">
                                        <i class="fas fa-ban"></i> {{ __('Kick') }}
                                    </button>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <p>{{ __('No data available') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3" style="padding:16px;">
            {{ $visitors->appends(request()->all())->links('vendor.pagination.bootstrap-4') }}
        </div>
    </div>
</div>

{{-- ═════════════ TAB: PK ═════════════ --}}
<div class="tab-content {{ request('tab') == 'pk' ? 'active' : '' }}" id="pk-tab">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title"><i class="fas fa-bolt"></i> {{ __('Room PK') }}</h4>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Team 1') }}</th>
                    <th>{{ __('Team 2') }}</th>
                    <th>{{ __('Score') }}</th>
                    <th>{{ __('Prize') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Time') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($pks as $index => $pk)
                    <tr>
                        <td>{{ $pk->id }}</td>
                        <td>
                            <div class="team-info">
                                <div class="team-title">{{ $pk->team_1_title }}</div>
                                @if($pk->team1Boss)
                                    <a href="{{ admin_url('users/' . $pk->team1Boss->id) }}" class="team-boss">
                                        <img src="{{ getImagePath($pk->team1Boss->profile?->avatar) ?? asset('images/businessman-icon.jpg') }}" class="boss-avatar">
                                        <span>{{ $pk->team1Boss->name }}</span>
                                    </a>
                                @endif
                                <a href="javascript:void(0)" class="show-team-members" data-team-members="{{ $pk->team_1 }}" data-team-name="{{ $pk->team_1_title }}">
                                    <small class="members-count"><i class="fas fa-users"></i> {{ __('Members') }}: {{ count(array_filter(explode(',', $pk->team_1), function($value) { return $value !== '' && $value !== '0' && $value > 0; })) }}</small>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div class="team-info">
                                <div class="team-title">{{ $pk->team_2_title }}</div>
                                @if($pk->team2Boss)
                                    <a href="{{ admin_url('users/' . $pk->team2Boss->id) }}" class="team-boss">
                                        <img src="{{ getImagePath($pk->team2Boss->profile?->avatar) ?? asset('images/businessman-icon.jpg') }}" class="boss-avatar">
                                        <span>{{ $pk->team2Boss->name }}</span>
                                    </a>
                                @endif
                                <a href="javascript:void(0)" class="show-team-members" data-team-members="{{ $pk->team_2 }}" data-team-name="{{ $pk->team_2_title }}">
                                    <small class="members-count"><i class="fas fa-users"></i> {{ __('Members') }}: {{ count(array_filter(explode(',', $pk->team_2), function($value) { return $value !== '' && $value !== '0' && $value > 0; })) }}</small>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div class="score-info">
                                <div class="team-score"><span class="score-label">T1:</span> <span class="score-value">{{ $pk->t1_score }}</span></div>
                                <div class="team-score"><span class="score-label">T2:</span> <span class="score-value">{{ $pk->t2_score }}</span></div>
                            </div>
                        </td>
                        <td><strong>{{ $pk->prize_value }}</strong></td>
                        <td>
                            <div class="status-container">
                                @php $statusBadge = match($pk->status) { 'active' => 'success', 'ended' => 'danger', default => 'secondary' }; @endphp
                                <span class="badge badge-{{ $statusBadge }}">{{ ucfirst($pk->status) }}</span>
                                @if($pk->show_status) <span class="badge badge-info">{{ __('Visible') }}</span> @endif
                                @if($pk->winner) <span class="winner-badge"><i class="fas fa-trophy"></i> {{ __('Winner') }}: {{ __('Team') }} {{ $pk->winner }}</span> @endif
                            </div>
                        </td>
                        <td>
                            <div class="time-info">
                                <div><i class="fas fa-play" style="color:var(--success);margin-inline-end:4px;"></i>{{ Carbon::parse($pk->start_at)->format('Y-m-d H:i') }}</div>
                                <div><i class="fas fa-stop" style="color:var(--danger);margin-inline-end:4px;"></i>{{ Carbon::parse($pk->end_at)->format('Y-m-d H:i') }}</div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-bolt"></i>
                                <p>{{ __('No data available') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3" style="padding:16px;">
            {{ $pks->appends(request()->all())->links('vendor.pagination.bootstrap-4') }}
        </div>
    </div>
</div>

{{-- ═════════════ TAB: BOXES ═════════════ --}}
<div class="tab-content {{ request('tab') == 'boxes' ? 'active' : '' }}" id="boxes-tab">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title"><i class="fas fa-box-open"></i> {{ __('Boxes in Room') }}</h4>
        </div>
        <div class="p-3">
            <div class="card" style="margin-bottom:20px;">
                <div class="p-3">
                    <form action="" class="form-horizontal gift-log-form" method="GET">
                        <input type="hidden" name="tab" value="boxes">
                        <div class="container-fluid">
                            <div class="row g-2 align-items-end justify-content-between">
                                <div class="col-md-4">
                                    <label for="type-select" class="form-label fw-bold">{{ __('Box Type') }}</label>
                                    <select class="form-control" name="type" id="type-select">
                                        <option value="">{{ __('All Types') }}</option>
                                        <option value="0" {{ request('type') === '0' ? 'selected' : '' }}>{{ __('Normal') }}</option>
                                        <option value="1" {{ request('type') === '1' ? 'selected' : '' }}>{{ __('Super') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="status-select" class="form-label fw-bold">{{ __('Status') }}</label>
                                    <select class="form-control" name="status" id="status-select">
                                        <option value="">{{ __('All Status') }}</option>
                                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>{{ __('Closed') }}</option>
                                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</option>
                                    </select>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="submit" class="btn btn-info"><i class="fa fa-search"></i> {{ __('Search') }}</button>
                                        <a href="?tab=boxes" class="btn btn-default"><i class="fa fa-undo"></i> {{ __('Reset') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Box Type') }}</th>
                    <th>{{ __('Owner') }}</th>
                    <th>{{ __('Coins') }}</th>
                    <th>{{ __('Users Picked') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Created At') }}</th>
                    <th>{{ __('Expires At') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($boxes as $index => $box)
                    <tr>
                        <td>{{ $box->id }}</td>
                        <td>
                            <span class="badge badge-{{ $box->type == 0 ? 'primary' : 'warning' }}">
                                {{ $box->type == 0 ? __('Normal') : __('Super') }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ admin_url('users/' . $box->user->id) }}" target="_blank" class="user-cell">
                                <img src="{{ getImagePath($box->user->profile?->avatar) ?? asset('images/businessman-icon.jpg') }}" alt="">
                                <div class="user-cell-info">
                                    <span class="user-cell-name">{{ $box->user->name }}</span>
                                    <span class="user-cell-meta">UUID: {{ $box->user->uuid }}</span>
                                </div>
                            </a>
                        </td>
                        <td>
                            <div style="line-height:1.6;">
                                <div><strong>{{ number_format($box->coins) }}</strong> <img src="{{ asset('images/coin.jpg') }}" alt="" class="diamond-icon"></div>
                                <div style="font-size:12px;color:var(--profile-muted);">{{ __('Used') }}: {{ number_format($box->used_coins) }}</div>
                                <div style="font-size:12px;color:var(--success);">{{ __('Remains') }}: {{ number_format($box->unused_coins) }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="progress-info">
                                @php $percentage = ($box->used_num / $box->users_num) * 100; @endphp
                                <div class="progress" style="height:6px;margin-bottom:6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width:{{ $percentage }}%"></div>
                                </div>
                                <div style="text-align:center;font-size:11.5px;color:var(--profile-muted);">
                                    <strong>{{ number_format($percentage, 1) }}%</strong> · {{ $box->used_num }}/{{ $box->users_num }}
                                    <br>
                                    <a href="javascript:void(0)" class="show-picked-users" data-picked-users="{{ $box->picks->pluck('user_id')->implode(',') }}">
                                        <span class="show-users-text"><i class="fas fa-eye"></i> {{ __('Show Users') }}</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $now = Carbon::now()->timestamp;
                                $isExpired = $box->end_at < $now;
                                $isClosed = $box->is_closed;
                            @endphp
                            <span class="badge badge-{{ $isExpired || $isClosed ? 'danger' : 'success' }}">
                                {{ $isExpired ? __('Expired') : ($isClosed ? __('Closed') : __('Active')) }}
                            </span>
                        </td>
                        <td><span class="user-cell-meta">{{ Carbon::createFromTimestamp($box->start_at)->format('Y-m-d H:i:s') }}</span></td>
                        <td><span class="user-cell-meta">{{ Carbon::createFromTimestamp($box->end_at)->format('Y-m-d H:i:s') }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <p>{{ __('No boxes available') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3" style="padding:16px;">
            {{ $boxes->appends(request()->all())->links('vendor.pagination.bootstrap-4') }}
        </div>
    </div>
</div>

{{-- ═════════════ SCRIPTS ═════════════ --}}
<link href="{{ admin_asset('vendor/laravel-admin/AdminLTE/plugins/select2/select2.min.css') }}" rel="stylesheet"/>
<script src="{{ admin_asset('vendor/laravel-admin/AdminLTE/plugins/select2/select2.full.min.js') }}"></script>

<script>
    function openEditModal() {
        $('#editRoomModal').modal('show');
    }

    $(document).ready(function () {
        $('#editRoomForm').on('submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: '{{ route("admin.rooms.basic_update", $room->id) }}',
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    if (response.success) {
                        Swal.fire({ icon: 'success', title: '{{ __("Success") }}', text: response.message, showConfirmButton: false, timer: 1500 }).then(() => { location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: response.message });
                    }
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: xhr.responseJSON.message || '{{ __("Something went wrong") }}' });
                }
            });
        });
    });

    $(document).on('click', '.show-picked-users', function () {
        const pickedUsers = $(this).data('picked-users');
        if (!pickedUsers) { Swal.fire('{{ __("Error") }}', '{{ __("No users found") }}', 'error'); return; }
        const userIds = pickedUsers.toString().split(',').filter(id => id.trim());
        Swal.fire({
            title: '{{ __("Loading...") }}', allowOutsideClick: false,
            onOpen: () => {
                Swal.showLoading();
                $.ajax({
                    url: '{{ route("admin.get.users") }}', type: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { user_ids: userIds },
                    success: function (response) {
                        let membersList = '';
                        response.forEach(function (user) {
                            membersList += `<div class="member-item"><a href="{{ admin_url('users') }}/${user.id}" class="member-info" target="_blank"><img src="${user.avatar_url || '{{ asset("images/businessman-icon.jpg") }}'}" class="member-avatar"><div class="member-details"><div class="member-name">${user.name}</div><div class="member-id"><span>ID: ${user.id}</span><br><span>UID: ${user.uuid}</span></div></div></a></div>`;
                        });
                        Swal.fire({ title: '{{ __("Box Picked Users") }}', html: `<div class="team-members-list">${membersList}</div>`, width: '800px', showConfirmButton: false, showCloseButton: true });
                    },
                    error: function () { Swal.fire('{{ __("Error") }}', '{{ __("Failed to load users") }}', 'error'); }
                });
            }
        });
    });

    $(document).on('click', '.show-team-members', function () {
        const teamMembers = $(this).data('team-members');
        const teamName = $(this).data('team-name');
        if (!teamMembers) { Swal.fire('{{ __("Error") }}', '{{ __("No team members found") }}', 'error'); return; }
        const memberIds = teamMembers.split(',').filter(id => id.trim());
        Swal.fire({
            title: '{{ __("Loading...") }}', allowOutsideClick: false,
            onOpen: () => {
                Swal.showLoading();
                $.ajax({
                    url: '{{ route("admin.get.users") }}', type: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { user_ids: memberIds },
                    success: function (response) {
                        let membersList = '';
                        response.forEach(function (user) {
                            membersList += `<div class="member-item"><a href="{{ admin_url('users') }}/${user.id}" class="member-info" target="_blank"><img src="${user.avatar_url || '{{ asset("images/businessman-icon.jpg") }}'}" class="member-avatar"><div class="member-details"><div class="member-name">${user.name}</div><div class="member-id"><span>ID: ${user.id}</span><br><span>UID: ${user.uuid}</span></div></div></a></div>`;
                        });
                        Swal.fire({ title: `${teamName} {{ __("Members") }}`, html: `<div class="team-members-list">${membersList}</div>`, width: '800px', showConfirmButton: false, showCloseButton: true });
                    },
                    error: function () { Swal.fire('{{ __("Error") }}', '{{ __("Failed to load team members") }}', 'error'); }
                });
            }
        });
    });

    $(document).on('click', '#add-visitor-btn', function () {
        Swal.fire({
            title: '{{ __("Add Visitor") }}', html: '<select id="visitor-select" style="width: 100%"></select>',
            showCancelButton: true, confirmButtonText: '{{ __("Add") }}', cancelButtonText: '{{ __("Cancel") }}',
            onOpen: () => {
                $('#visitor-select').select2({
                    dropdownParent: $(".swal2-container"), width: '100%',
                    ajax: {
                        url: '{{ route("search.users") }}', dataType: 'json', delay: 250,
                        data: function (params) { return { search: params.term || '', type: 'public' }; },
                        processResults: function (response) {
                            let data = response.data || response || [];
                            return { results: data.map(function (user) { return { id: user.id, text: user.name + (user.uuid ? ' - ' + user.uuid : '') }; }) };
                        }
                    },
                    minimumInputLength: 1, placeholder: '{{ __("Search for user...") }}'
                });
            },
            preConfirm: () => {
                const selectedId = $('#visitor-select').val();
                if (!selectedId) { Swal.showValidationMessage('{{ __("Please select a user") }}'); return false; }
                return new Promise((resolve) => {
                    $.ajax({
                        url: '{{ admin_url("rooms/{$room->id}/add-visitor") }}', type: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, data: { user_id: selectedId },
                        success: function (response) { resolve(response); },
                        error: function (xhr) { resolve({ success: false, message: xhr.responseJSON?.message || '{{ __("An error occurred") }}' }); }
                    });
                });
            }
        }).then((result) => {
            if (result.value) {
                if (result.value.success) { window.location.href = window.location.href; }
                else { Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: result.value.message }); }
            }
        });
    });

    $(document).on('click', '.kick-visitor', function () {
        const userId = $(this).data('user-id');
        Swal.fire({
            title: '{{ __("Kick Visitor") }}',
            html: `<div class="form-group"><label>{{ __("Duration (minutes)") }}</label><input type="number" id="kick-duration" class="form-control" value="5" min="1"></div>`,
            icon: 'warning', showCancelButton: true, confirmButtonText: '{{ __("Yes, kick them") }}', cancelButtonText: '{{ __("Cancel") }}',
            preConfirm: () => {
                const duration = $('#kick-duration').val();
                if (!duration || duration < 1) { Swal.showValidationMessage('{{ __("Please enter a valid duration") }}'); return false; }
                return new Promise((resolve) => {
                    $.ajax({
                        url: '{{ admin_url("rooms/{$room->id}/kick-visitor") }}', type: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, data: { user_id: userId, minutes: duration },
                        success: function (response) { resolve(response); },
                        error: function (xhr) { resolve({ success: false, message: xhr.responseJSON?.message || '{{ __("An error occurred") }}' }); }
                    });
                });
            }
        }).then((result) => {
            if (result.value) {
                if (result.value.success) { window.location.href = window.location.href; }
                else { Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: result.value.message }); }
            }
        });
    });

    $(document).on('click', '.unban-visitor', function() {
        const btn = $(this);
        const roomId = btn.data('room-id');
        const userId = btn.data('user-id');
        Swal.fire({
            title: '{{ __("Unban Visitor") }}', text: '{{ __("Are you sure you want to remove the ban for this visitor?") }}',
            icon: 'warning', showCancelButton: true, confirmButtonText: '{{ __("Yes, unban them") }}', cancelButtonText: '{{ __("Cancel") }}',
            preConfirm: () => {
                return new Promise((resolve) => {
                    $.ajax({
                        url: '{{ admin_url("rooms") }}/' + roomId + '/unban-visitor', type: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, data: { user_id: userId },
                        success: function(response) { resolve(response); },
                        error: function(xhr) { resolve({ success: false, message: xhr.responseJSON?.message || '{{ __("An error occurred") }}' }); }
                    });
                });
            }
        }).then((result) => {
            if (result.value) {
                if (result.value.success) {
                    Swal.fire({ icon: 'success', title: '{{ __("Success") }}', text: result.value.message, timer: 1500, showConfirmButton: false }).then(() => { window.location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: result.value.message });
                }
            }
        });
    });

    $(document).ready(function () {
        $('#sender-select, #receiver-select').select2({
            ajax: {
                url: '{{ route('search.users') }}', dataType: 'json', delay: 250,
                data: function (params) { return { search: params.term, type: 'public', page: params.page || 1 }; },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    var users = Array.isArray(data) ? data : (data.data || []);
                    return { results: $.map(users, function (user) { return { id: user.id, text: user.name + (user.uuid ? ' - ' + user.uuid : '') }; }), pagination: { more: false } };
                },
                cache: true
            },
            placeholder: "{{ __('Search for user...') }}", minimumInputLength: 1
        });
    });

    $(document).ready(function () {
        $('.remove-admin').click(function () {
            const roomId = $(this).data('room-id');
            const adminId = $(this).data('admin-id');
            $.ajax({
                url: '{{ admin_url('rooms') }}/' + roomId + '/remove-admin', type: 'POST',
                headers: { 'X-CSRF-TOKEN': LA.token }, data: { admin_id: adminId },
                success: function (response) {
                    if (response.success) { Swal.fire({ title: '{{ __("Success!") }}', text: response.message, type: 'success' }).then(() => { window.location.reload(); }); }
                    else { Swal.fire({ title: '{{ __("Error!") }}', text: response.message || '{{ __("Something went wrong!") }}', type: 'error' }); }
                },
                error: function () { Swal.fire({ title: '{{ __("Error!") }}', text: '{{ __("Something went wrong!") }}', type: 'error' }); }
            });
        });
    });

    $(document).ready(function () {
        $('#add_form').on('submit', function (e) {
            e.preventDefault();
            let form = $(this);
            $.ajax({
                url: form.attr('action'), method: 'POST', data: form.serialize(),
                success: function () { $('#Add_model').modal('hide'); location.reload(); },
                error: function (xhr) { let errors = xhr.responseJSON.errors; let msg = ''; for (let key in errors) { msg += errors[key][0] + '\n'; } alert(msg || 'Something went wrong!'); }
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedTab = urlParams.get('tab') || 'packs';
        const allTabs = document.querySelectorAll('.tab-btn');
        let targetElement = null;

        allTabs.forEach(tab => {
            const target = tab.getAttribute('data-target');
            const content = document.getElementById(target);
            if (target.startsWith(selectedTab)) {
                tab.classList.add('active');
                if (content) { content.style.display = 'block'; content.classList.add('active'); }
                targetElement = content;
            } else {
                tab.classList.remove('active');
                if (content) { content.style.display = 'none'; content.classList.remove('active'); }
            }

            // Client-side tab switching — no page reload
            tab.addEventListener('click', function (e) {
                e.preventDefault();

                // Update active tab button
                allTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Show/hide panels
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

                // Update URL without reload
                const url = new URL(window.location.href);
                const tabParam = new URLSearchParams(this.getAttribute('href').replace('?', ''));
                url.searchParams.set('tab', tabParam.get('tab'));
                window.history.replaceState({}, '', url.toString());
            });
        });
    });

    $(document).ready(function () {
        $('#agency_id').select2({
            placeholder: 'Select agency', allowClear: true,
            ajax: {
                url: '/api/search/host-agency', dataType: 'json', delay: 250,
                data: function (params) { return { q: params.term, page: params.page || 1 }; },
                processResults: function (data) { return { results: data.data.map(item => ({ id: item.id, text: item.name })), pagination: { more: data.next_page_url !== null } }; },
                cache: true
            }
        });

        function showLoader() { Swal.fire({ title: 'Loading...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } }); }
        function showSuccess(message, callback = null) { Swal.fire({ icon: 'success', title: message, confirmButtonText: 'OK' }).then(() => { if (callback) callback(); }); }
        function showError(message) { Swal.fire({ icon: 'error', title: message, confirmButtonText: 'OK' }); }
        function confirmAction(message, onConfirm) { Swal.fire({ title: message, icon: 'question', showCancelButton: true, confirmButtonText: 'Yes', cancelButtonText: 'Cancel' }).then(result => { if (result.value) onConfirm(); }); }

        $('.accept-btn').click(function () {
            const id = $(this).data('id');
            confirmAction('{{ __("are_you_sure_accept") }}', () => {
                showLoader();
                $.post(`/admin/agencies/accept_join/${id}`, { _token: '{{ csrf_token() }}' }, function (response) {
                    Swal.close();
                    if (response.status) { showSuccess(response.message, () => { const url = new URL(window.location.href); url.searchParams.set('tab', 'requests'); window.location.href = url.toString(); }); }
                    else { showError(response.message); }
                }).fail(function (xhr) { Swal.close(); showError(xhr.responseJSON?.message ?? '{{ __("failed_accept_request") }}'); });
            });
        });

        $(document).on('click', '.edit_item_model_btn', function () { let itemId = $(this).data('id'); $('#add_form')[0].reset(); $('.item_id').val(itemId); $('#Add_model').modal('show'); });
        $(document).on('click', '.close-modal-btn', function () { $('#Add_model').modal('hide'); });

        $(document).on('click', '.delete-btn', function () {
            let itemId = $(this).data('id');
            Swal.fire({ title: 'Are you sure?', text: "This action cannot be undone!", showCancelButton: true, confirmButtonText: 'Yes, delete it!', cancelButtonText: 'Cancel', reverseButtons: true }).then((result) => {
                if (result.value) {
                    $.ajax({ url: '/admin/delete-pack/' + itemId, type: 'POST', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function (response) { Swal.fire('Deleted!', response.message, 'success').then(() => { location.reload(); }); },
                        error: function (xhr) { Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'An error occurred.' }); }
                    });
                }
            });
        });

        $(document).on('click', '.delete-vip-btn', function () {
            let itemId = $(this).data('id');
            Swal.fire({ title: 'Are you sure?', text: "This action cannot be undone!", showCancelButton: true, confirmButtonText: 'Yes, delete it!', cancelButtonText: 'Cancel', reverseButtons: true }).then((result) => {
                if (result.value) {
                    $.ajax({ url: '/admin/delete-user-vip/' + itemId, type: 'POST', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function (response) { Swal.fire('Deleted!', response.message, 'success').then(() => { location.reload(); }); },
                        error: function (xhr) { Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'An error occurred.' }); }
                    });
                }
            });
        });

        $('.reject-btn').click(function () {
            const id = $(this).data('id');
            confirmAction('{{ __("are_you_sure_reject") }}', () => {
                showLoader();
                $.post(`/admin/agencies/reject_join/${id}`, { _token: '{{ csrf_token() }}' }, function (response) {
                    Swal.close();
                    if (response.status) { showSuccess(response.message, () => { const url = new URL(window.location.href); url.searchParams.set('tab', 'requests'); window.location.href = url.toString(); }); }
                    else { showError(response.message); }
                }).fail(function (xhr) { Swal.close(); showError(xhr.responseJSON?.message ?? '{{ __("failed_reject_request") }}'); });
            });
        });

        $('.make-admin-btn').click(function () {
            const id = $(this).data('id');
            confirmAction('{{ __("are_you_sure_make_admin") }}', () => {
                showLoader();
                $.post(`/admin/agencies/admin/${id}`, { _token: '{{ csrf_token() }}' }, function (response) {
                    Swal.close();
                    if (response.status) { showSuccess(response.message, () => { location.reload(); }); }
                    else { showError(response.message); }
                }).fail(function (xhr) { Swal.close(); showError(xhr.responseJSON?.message ?? '{{ __("failed_make_admin") }}'); });
            });
        });
    });
</script>
