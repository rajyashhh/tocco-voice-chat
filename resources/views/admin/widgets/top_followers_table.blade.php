@php
    $userService = app(\App\Admin\Services\UserService::class);
@endphp

<style>
    .shadow-sm {
        width: 100%;
        box-shadow: 0 0.15rem 1.75rem rgba(58,59,69,.15) !important;
        border-radius: 0.5rem;
    }

    .table thead th {
        vertical-align: middle;

    }

    .table thead .th {

        width: 21%;
    }

    .avatar-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .avatar-cell img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    .text-center{
        text-align: center !important;
    }
    .table {
        width: 100%;
        max-width: 100%;
        margin-bottom: 20px;
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
</style>

<div class="box box-success shadow-sm border-0">
    <div class="box-header with-border text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fa fa-users me-2"></i> {{ __('Top Followers') }}</h4>
    </div>

    <div class="box-body p-0">
        <div class="table-responsive">
            <table class="table table-hover  align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 60px;">#</th>
                        <th class="text-center">{{ __('User') }}</th>
                        <th class="text-center th">{{ __('Followers Count') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($top5 as $index => $user)
                        <tr>
                            <td class="text-center fw-bold">{{ $index + 1 }}</td>
                            <td class="avatar-cell">
                                {!! $userService->adminUserCard($user, withoutLevels: true) !!}
                            </td>
                                    'uuid'   => $user->uuid ,
                                    'name'   => $user->name ,
                                    'avatar' => $user->profile?->avatar ,
                                ], withoutLevels: true) !!}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success fs-6">
                                    {{ number_format($user->followers_count) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
