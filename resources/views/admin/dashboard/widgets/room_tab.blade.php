<style>
    .info-box:hover .info-box-more {
        background: rgba(0, 0, 0, 0.3);
    }
</style>

<div class="stats-container">
    <div class="row g-3">
        <div class="col-md-3">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-headphones"></i></div>
                <h3>{{ __('Audio Rooms') }}</h3>
                <p class="amount" id="audioRooms">--</p>
                <a href="{{ admin_url('rooms?online=1') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('more') }}
                </a>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-microphone"></i></div>
                <h3>{{ __('Live Rooms') }}</h3>
                <p class="amount" id="liveRooms">--</p>
                <a href="{{ admin_url('live-rooms?online=1') }}"><i
                        class="fa fa-arrow-circle-right"></i> {{ __('more') }}</a>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-microphone"></i></div>
                <h3>{{ __('Live Rooms (Active)') }}</h3>
                <p class="amount" id="activeRooms">--</p>
                <a href="{{ admin_url('live-rooms?is_live=1') }}"><i
                        class="fa fa-arrow-circle-right"></i> {{ __('more') }}</a>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-microphone-slash"></i></div>
                <h3>{{ __('Live Rooms (Inactive)') }}</h3>
                <p class="amount" id="inactiveRooms">--</p>
                <a href="{{ admin_url('live-rooms?is_live=0') }}"><i
                        class="fa fa-arrow-circle-right"></i> {{ __('more') }}</a>
            </div>
        </div>
    </div>
</div>
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
    function updateRoomStats() {
        if (!window.roomStatsLoaded) {
            window.roomStatsLoaded = true;
            fetch('{{ url($prefix . "/statistics/room-stats") }}')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('audioRooms').innerText = data.audio;
                    document.getElementById('liveRooms').innerText = data.live;
                    document.getElementById('activeRooms').innerText = data.active;
                    document.getElementById('inactiveRooms').innerText = data.inactive;
                })
                .catch(err => console.error('Error loading room stats:', err));
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        updateRoomStats();
    });
</script>
