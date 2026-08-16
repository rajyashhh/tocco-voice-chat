<div class="stats-container">
    <div class="row g-3">
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                <h3>{{ __('Users Count') }}</h3>
                <p class="amount" data-stat="usersCount">0</p>
                <a href="{{ admin_url('users') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-user"></i></div>
                <h3>{{ __('Online Users Count') }}</h3>
                <p class="amount" data-stat="onlineUser">0</p>
                <a href="{{ admin_url('users') }}?online=1"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-clock"></i></div>
                <h3>{{ __('Peak Hour') }}</h3>
                <p class="amount" data-stat="peakHour">0</p>
                <a href="{{ admin_url('users') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-user-plus"></i></div>
                <h3>{{ __('New Sign Ups Today') }}</h3>
                <p class="amount" data-stat="newSignUpsToday">0</p>
                <a href="{{ admin_url('users') }}?signups=today"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                <h3>{{ __('New Sign Ups This Week') }}</h3>
                <p class="amount" data-stat="newSignUpsThisWeek">0</p>
                <a href="{{ admin_url('users') }}?signups=week"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-calendar"></i></div>
                <h3>{{ __('New Sign Ups This Month') }}</h3>
                <p class="amount" data-stat="newSignUpsThisMonth">0</p>
                <a href="{{ admin_url('users') }}?signups=month"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-envelope"></i></div>
                <h3>{{ __('Messages Today') }}</h3>
                <p class="amount" data-stat="messagesToday">0</p>
                <a href="{{ admin_url('users') }}?messages=today"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-comments"></i></div>
                <h3>{{ __('Messages This Month') }}</h3>
                <p class="amount" data-stat="messagesThisMonth">0</p>
                <a href="{{ admin_url('users') }}?messages=month"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-user"></i></div>
                <h3>{{ __('Users Who Send Messages') }}</h3>
                <p class="amount" data-stat="usersWhoSend">0</p>
                <a href="{{ admin_url('users') }}?sent_messages=1"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-user-xmark"></i></div>
                <h3>{{ __('Users Who Never Send') }}</h3>
                <p class="amount" data-stat="usersWhoNeverSend">0</p>
                <a href="{{ admin_url('users') }}?never_send=1"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-comments"></i></div>
                <h3>{{ __('Open Conversations Today') }}</h3>
                <p class="amount" data-stat="openConversationsToday">0</p>
                <a href="{{ admin_url('users') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-clock"></i></div>
                <h3>{{ __('Avg Conversation Duration (min)') }}</h3>
                <p class="amount" data-stat="avgConversationDuration">0</p>
                <a href="{{ admin_url('users') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>
    </div>
</div>

<style>
    .info-box { position: relative; min-height: 100px; border-radius: 8px; overflow: hidden; padding: 12px; }
    .info-box .info-box-more { position: absolute; bottom: 0; left: 0; right: 0; display: flex;
        justify-content: center; align-items: center; background: rgba(0,0,0,0.15);
        height: 30px; font-weight: 600; color: #fff; text-decoration: none; transition: background 0.2s ease; }
    .info-box:hover .info-box-more { background: rgba(0,0,0,0.3); }
</style>

@php
    if (request()->is('superadmin*')) {
        $prefix = 'superadmin';
    } elseif (request()->is('areaManager*')) {
        $prefix = 'areaManager';
    } else {
        $prefix = 'admin';
    }
@endphp

<script>
    $(function() {
        function updateStats() {
            $.ajax({
                url: '{{ url($prefix . "/statistics/stats-data") }}',
                type: 'GET',
                success: function(data) {
                    if (data.success) data = data.data;

                    $('[data-stat="usersCount"]').text(data.usersCount.toLocaleString());
                    $('[data-stat="onlineUser"]').text(data.onlineUser.toLocaleString());
                    $('[data-stat="peakHour"]').text(data.peakHour);
                    $('[data-stat="newSignUpsToday"]').text(data.newSignUpsToday.toLocaleString());
                    $('[data-stat="newSignUpsThisWeek"]').text(data.newSignUpsThisWeek.toLocaleString());
                    $('[data-stat="newSignUpsThisMonth"]').text(data.newSignUpsThisMonth.toLocaleString());
                    $('[data-stat="messagesToday"]').text(data.messagesToday.toLocaleString());
                    $('[data-stat="messagesThisMonth"]').text(data.messagesThisMonth.toLocaleString());
                    $('[data-stat="usersWhoSend"]').text(data.usersWhoSend.toLocaleString());
                    $('[data-stat="usersWhoNeverSend"]').text(data.usersWhoNeverSend.toLocaleString());
                    $('[data-stat="openConversationsToday"]').text(data.openConversationsToday.toLocaleString());
                    $('[data-stat="avgConversationDuration"]').text(Math.round(data.avgConversationDuration));
                },
                error: function() {
                    alert('{{ __("Error loading stats") }}');
                }
            });
        }

        updateStats();
    });
</script>
