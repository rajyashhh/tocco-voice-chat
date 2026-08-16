@extends('Form::layouts.app')

@section('title', __('Form Details'))

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-4 " style="    margin: 5px 14px;">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-file-earmark-text text-primary"></i> {{ __('Form Details') }}
            </h2>
            <p class="text-muted mb-0">
                <i class="bi bi-hash"></i> {{ __('Form ID') }}: <strong>#{{ $formRequest->id }}</strong>
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-gradient text-white py-3" 
             style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <h5 class="mb-0" style="      margin: 0px 18px;position: relative;">
                <i class="bi bi-info-circle-fill"></i> {{ __('Full Form Information') }}
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 modern-table">
                    <tbody>
                        <tr>
                            <td class="table-label">
                                <i class="bi bi-hash text-primary"></i>
                                <strong>{{ __('Form ID') }}</strong>
                            </td>
                            <td class="table-value">
                                <span class="badge bg-dark px-3 py-2">#{{ $formRequest->id }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="table-label">
                                <i class="bi bi-person-circle text-primary"></i>
                                <strong>{{ __('Name') }}</strong>
                            </td>
                            <td class="table-value">
                                <span class="user-name">
                                    {{ $formRequest->name ?? __('Not specified') }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="table-label">
                                <i class="bi bi-person-circle text-primary"></i>
                                <strong>{{ __('User Name') }}</strong>
                            </td>
                            <td class="table-value">
                                <span class="user-name">
                                    {{ $formRequest->user->name ?? __('Not specified') }}
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td class="table-label">
                                <i class="bi bi-envelope text-primary"></i>
                                <strong>{{ __('Email') }}</strong>
                            </td>
                            <td class="table-value">
                                <a href="mailto:{{ $formRequest->user->email ?? '#' }}" class="text-decoration-none">
                                    {{ $formRequest->user->email ?? __('Not specified') }}
                                </a>
                            </td>
                        </tr>

                        <tr>
                            <td class="table-label">
                                <i class="bi bi-file-earmark-text text-primary"></i>
                                <strong>{{ __('Form Type') }}</strong>
                            </td>
                            <td class="table-value">
                                <span class="badge bg-info text-dark px-3 py-2 fs-6">
                                    <i class="bi bi-folder"></i>
                                    {{ __($formRequest->template->form_type) ?? __('Not specified') }}
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td class="table-label">
                                <i class="bi bi-flag text-primary"></i>
                                <strong>{{ __('Form Status') }}</strong>
                            </td>
                            <td class="table-value">
                                @if($formRequest->status === 'pending')
                                    <span class="badge bg-warning text-dark px-4 py-2 fs-6">
                                        <i class="bi bi-hourglass-split"></i> {{ __('Pending') }}
                                    </span>
                                    <small class="text-muted me-2">{{ __('Awaiting Review') }}</small>
                                @elseif($formRequest->status === 'approved')
                                    <span class="badge bg-success px-4 py-2 fs-6">
                                        <i class="bi bi-check-circle-fill"></i> {{ __('Approved') }}
                                    </span>
                                    <small class="text-muted me-2">{{ __('Approved Successfully') }}</small>
                                @elseif($formRequest->status === 'rejected')
                                    <span class="badge bg-danger px-4 py-2 fs-6">
                                        <i class="bi bi-x-circle-fill"></i> {{ __('Rejected') }}
                                    </span>
                                    <small class="text-muted me-2">{{ __('Request Rejected') }}</small>
                                @else
                                    <span class="badge bg-secondary px-4 py-2 fs-6">
                                        <i class="bi bi-question-circle"></i> {{ __('Unknown') }}
                                    </span>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td class="table-label">
                                <i class="bi bi-calendar-plus text-primary"></i>
                                <strong>{{ __('Created At') }}</strong>
                            </td>
                            <td class="table-value">
                                <i class="bi bi-calendar-event ms-1"></i>
                                {{ $formRequest->created_at->format('Y-m-d') }}
                                <span class="text-muted mx-2">|</span>
                                <i class="bi bi-clock ms-1"></i>
                                {{ $formRequest->created_at->format('h:i A') }}
                            </td>
                        </tr>

                        @if($formRequest->updated_at != $formRequest->created_at)
                        <tr>
                            <td class="table-label">
                                <i class="bi bi-clock-history text-primary"></i>
                                <strong>{{ __('Last Updated') }}</strong>
                            </td>
                            <td class="table-value">
                                <i class="bi bi-calendar-event ms-1"></i>
                                {{ $formRequest->updated_at->format('Y-m-d') }}
                                <span class="text-muted mx-2">|</span>
                                <i class="bi bi-clock ms-1"></i>
                                {{ $formRequest->updated_at->format('h:i A') }}
                            </td>
                        </tr>
                        @endif

                        <tr>
                            <td class="table-label">
                                <i class="bi bi-gear text-primary"></i>
                                <strong>{{ __('Available Actions') }}</strong>
                            </td>
                            <td class="table-value">
                            @if($formRequest->status === 'pending')
                                <form action="{{ route('forms.destroy.reqs', $formRequest->id) }}"
                                    method="POST"
                                    class="delete-request-form d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="token" value="{{ $token }}">
                                    <input type="hidden" name="lang" value="{{ $lang }}">
                                    <input type="hidden" name="user_id" value="{{ $user_id }}">

                                    <button type="submit" class="btn btn-del btn-danger btn-sm px-4 shadow-sm">
                                        <i class="bi bi-trash3"></i> {{ __('Delete Request') }}
                                    </button>
                                </form>
                            @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<style>

.btn-del {
    background-color: red;
    border-radius: 14px;
    height: 38px;
    color: white;
}
    .container {
            margin: auto;
        }
    .modern-table {
        font-size: 15px;
    }

    .modern-table tbody tr {
        transition: all 0.3s ease;
        border-bottom: 1px solid #f0f0f0;
    }

    .modern-table tbody tr:hover {
        background: linear-gradient(90deg, #f8f9fa 0%, #ffffff 100%);
        transform: scale(1.01);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .modern-table tbody tr:last-child {
        border-bottom: none;
    }

    .table-label {
        width: 30%;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 20px 25px;
        font-size: 16px;
        border-left: 4px solid #667eea;
        vertical-align: middle;
    }

    .table-label i {
        font-size: 20px;
        margin-left: 8px;
    }

    .table-value {
        padding: 20px 25px;
        font-size: 15px;
        color: #495057;
        vertical-align: middle;
    }

    .user-name {
        font-weight: 600;
        color: #2c3e50;
        font-size: 16px;
    }

    .card {
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    }

    .bg-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .btn {
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
    }

    .btn-outline-secondary {
        border-width: 2px;
    }

    .badge {
        font-weight: 500;
        border-radius: 10px;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }

    .badge:hover {
        transform: scale(1.05);
    }

    .data-display pre {
        background: white;
        padding: 20px;
        border-radius: 10px;
        border: 2px solid #e9ecef;
        font-size: 14px;
        direction: ltr;
        text-align: left;
        max-height: 400px;
        overflow-y: auto;
        font-family: 'Courier New', monospace;
        line-height: 1.6;
    }

    .data-display pre::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .data-display pre::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .data-display pre::-webkit-scrollbar-thumb {
        background: #667eea;
        border-radius: 10px;
    }

    a {
        transition: all 0.3s ease;
    }

    a:hover {
        color: #667eea !important;
    }

    .table-responsive {
        border-radius: 0 0 10px 10px;
    }

    @media (max-width: 768px) {
        .table-label {
            width: 40%;
            font-size: 14px;
            padding: 15px;
        }

        .table-value {
            padding: 15px;
            font-size: 14px;
        }

        .badge {
            font-size: 12px !important;
        }
    }

    .card-header h5 {
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .card {
        animation: fadeIn 0.6s ease-out;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.delete-request-form');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); 

            Swal.fire({
                title: '{{ __("Are you sure?") }}',
                text: '{{ __("You will not be able to recover this request!") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __("Yes, delete it!") }}',
                cancelButtonText: '{{ __("Cancel") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit(); 
                }
            });
        });
    });
});
</script>

@push('scripts')
<script>
    document.querySelectorAll('.modern-table tbody tr').forEach((row, index) => {
        row.style.animationDelay = `${index * 0.1}s`;
    });

    document.querySelectorAll('.modern-table tbody tr').forEach(row => {
        row.addEventListener('click', function() {
            this.style.background = 'linear-gradient(90deg, #e3f2fd 0%, #ffffff 100%)';
            setTimeout(() => {
                this.style.background = '';
            }, 500);
        });
    });
</script>
@endpush
@endsection