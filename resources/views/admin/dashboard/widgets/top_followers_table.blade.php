<div class="box box-success shadow-sm border-0">
    <div class="box-header with-border text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fa fa-users me-2"></i> {{ __('Top Followers') }}</h4>
    </div>

    {{-- <div class="box-body p-0"> --}}
    {{--        <div id="top-followers-loading" class="text-center py-3">--}}
    {{--            <i class="fa fa-spinner fa-spin fa-2x"></i>--}}
    {{--            <p>{{ __('Loading...') }}</p>--}}
    {{--        </div>--}}

    {{-- <div class="table-responsive d-none" id="top-followers-table-wrapper">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th class="text-center" style="width: 60px;">#</th>
                <th class="text-center">{{ __('User') }}</th>
                <th class="text-center th">{{ __('Followers Count') }}</th>
            </tr>
            </thead>
            <tbody id="top-followers-body"></tbody>
        </table>
    </div>
</div>
</div> --}}

    <div class="box-body p-0">
        <div class="table-responsive d-none" id="top-followers-table-wrapper">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th class="text-center" style="width: 60px;">#</th>
                    <th class="text-center">{{ __('User') }}</th>
                    <th class="text-center">{{ __('Followers Count') }}</th>
                </tr>
                </thead>
                <tbody id="top-followers-body"></tbody>
            </table>
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
    function loadTopFollowers() {
        $.ajax({
            url: '{{ url($prefix . "/statistics/top-followers") }}',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                const tbody = document.getElementById('top-followers-body');
                const tableWrapper = document.getElementById('top-followers-table-wrapper');
                tbody.innerHTML = '';

                data.forEach((user, index) => {
                    tbody.innerHTML += `
                        <tr>
                            <td class="text-center fw-bold">${index + 1}</td>
                            <td class="avatar-cell">
                                <img src="${user.avatar_url}" alt="avatar">
                                <span>${user.name}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success fs-6">${Number(user.followers_count).toLocaleString()}</span>
                            </td>
                        </tr>
                    `;
                });

                tableWrapper.classList.remove('d-none');
            },
            error: function() {
                console.error("Failed to load top followers data");
            }
        });
    }

    // Execute immediately - this is the key!
    if (!window.topFollowersLoaded) {
        window.topFollowersLoaded = true;
        loadTopFollowers();
    }
</script>

<style>
    .avatar-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .avatar-cell img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 50%;
    }
</style>
