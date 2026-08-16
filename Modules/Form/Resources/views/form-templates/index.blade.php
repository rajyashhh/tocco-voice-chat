@extends('Form::layouts.app')

@section('title', __('Form Templates'))

@push('styles')
<style>
    /* ========== Perfect Design System ========== */
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --accent: #8b5cf6;
        --success: #10b981;
        --success-dark: #059669;
        --warning: #f59e0b;
        --warning-dark: #d97706;
        --info: #3b82f6;
    }

    * { box-sizing: border-box; }

    /* ===== Page Background ===== */
    .templates-page {
        min-height: 100vh;
        background: #f0f2f5;
        padding-bottom: 3rem;
    }

    /* ===== Hero Section ===== */
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 2.5rem 0;
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }

    .hero-section::after {
        content: '';
        position: absolute;
        bottom: -40%;
        left: -5%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        border-radius: 50%;
    }

    .hero-inner {
        position: relative;
        z-index: 2;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .hero-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .hero-icon-box {
        width: 56px;
        height: 56px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        flex-shrink: 0;
    }

    .hero-text h1 {
        color: white;
        font-size: 1.75rem;
        font-weight: 800;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .hero-text p {
        color: rgba(255,255,255,0.75);
        font-size: 0.875rem;
        margin: 0.25rem 0 0 0;
    }

    .hero-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .hero-stat {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255,255,255,0.18);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: 100px;
        padding: 0.5rem 1rem;
        color: white;
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .hero-stat .count {
        background: rgba(255,255,255,0.25);
        padding: 0.125rem 0.5rem;
        border-radius: 100px;
        font-weight: 700;
        font-size: 0.8125rem;
        min-width: 24px;
        text-align: center;
    }

    /* ===== Main Content ===== */
    .main-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1.5rem 0;
    }

    /* ===== Cards Grid ===== */
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    @media (max-width: 768px) {
        .cards-grid {
            grid-template-columns: 1fr;
        }
    }

    /* ===== Template Card ===== */
    .t-card {
        background: #ffffff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 8px 24px rgba(0,0,0,0.04);
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        position: relative;
        display: flex;
        flex-direction: column;
        animation: cardUp 0.5s ease both;
    }

    @keyframes cardUp {
        from { opacity: 0; transform: translateY(24px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .t-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 40px rgba(102, 126, 234, 0.15), 0 4px 12px rgba(0,0,0,0.06);
    }

    /* Gradient top line */
    .t-card-line {
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    /* Card Content */
    .t-card-body {
        padding: 1.75rem 1.75rem 1.25rem;
        flex: 1;
    }

    .t-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1.25rem;
    }

    /* Card Icon */
    .t-card-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
        transition: transform 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }

    .t-card:hover .t-card-icon {
        transform: scale(1.08) rotate(-3deg);
    }

    .t-card-icon.purple { background: linear-gradient(145deg, #f0ecff, #e4deff); color: #7c3aed; }
    .t-card-icon.blue { background: linear-gradient(145deg, #e8f1ff, #d6e8ff); color: #2563eb; }
    .t-card-icon.emerald { background: linear-gradient(145deg, #e6faf2, #ccf5e4); color: #059669; }
    .t-card-icon.rose { background: linear-gradient(145deg, #fff0f1, #ffe0e3); color: #e11d48; }
    .t-card-icon.amber { background: linear-gradient(145deg, #fff8e6, #ffedba); color: #d97706; }

    /* Status Chip */
    .status-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 100px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        flex-shrink: 0;
    }

    .status-chip.active {
        background: #ecfdf5;
        color: #059669;
        border: 1.5px solid #a7f3d0;
    }

    .status-chip.inactive {
        background: #f9fafb;
        color: #6b7280;
        border: 1.5px solid #e5e7eb;
    }

    .status-chip .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .status-chip.active .dot {
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.2);
        animation: dotPulse 2s ease-in-out infinite;
    }

    .status-chip.inactive .dot {
        background: #9ca3af;
    }

    @keyframes dotPulse {
        0%, 100% { box-shadow: 0 0 0 3px rgba(16,185,129,0.2); }
        50% { box-shadow: 0 0 0 6px rgba(16,185,129,0.08); }
    }

    /* Card Title */
    .t-card-title {
        font-size: 1.375rem !important;
        font-weight: 800;
        color: #1a1a2e;
        margin: 0 0 0.5rem 0;
        line-height: 1.35;
        word-break: break-word;
    }

    /* Form Type Tag */
    .t-card-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        background: #f5f5f7;
        border-radius: 8px;
        font-size: 0.8125rem;
        color: #8b8fa3;
        font-weight: 500;
    }

    .t-card-tag i {
        font-size: 0.6875rem;
        color: #b0b4c8;
    }

    /* ===== Card Actions ===== */
    .t-card-actions {
        padding: 1.25rem 1.75rem 1.75rem;
        background: #fafbfd;
        border-top: 1px solid #f0f1f5;
    }

    /* ===== Buttons ===== */
    .t-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 20px;
        border-radius: 14px;
        font-size: 0.9375rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        text-decoration: none;
        position: relative;
        overflow: hidden;
    }

    .t-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 50%;
        background: linear-gradient(180deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 100%);
        pointer-events: none;
    }

    .t-btn:hover {
        transform: translateY(-2px);
        text-decoration: none;
    }

    .t-btn:active {
        transform: translateY(0);
    }

    .t-btn i {
        font-size: 0.875rem;
    }

    /* Copy - Full Width */
    .t-btn-copy {
        width: 100%;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff;
        box-shadow: 0 4px 16px -4px rgba(16,185,129,0.5);
    }

    .t-btn-copy:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        box-shadow: 0 8px 24px -4px rgba(16,185,129,0.55);
        color: #fff;
    }

    .t-btn-copy.copied {
        background: linear-gradient(135deg, #047857, #065f46) !important;
    }

    /* Preview */
    .t-btn-preview {
        background: linear-gradient(135deg, #667eea 0%, #5a67d8 100%);
        color: #fff;
        box-shadow: 0 4px 16px -4px rgba(102,126,234,0.5);
    }

    .t-btn-preview:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #4c51bf 100%);
        box-shadow: 0 8px 24px -4px rgba(102,126,234,0.55);
        color: #fff;
    }

    /* Edit */
    .t-btn-edit {
        background: linear-gradient(135deg, #f59e0b 0%, #e68a00 100%);
        color: #fff;
        box-shadow: 0 4px 16px -4px rgba(245,158,11,0.5);
    }

    .t-btn-edit:hover {
        background: linear-gradient(135deg, #e68a00 0%, #cc7a00 100%);
        box-shadow: 0 8px 24px -4px rgba(245,158,11,0.55);
        color: #fff;
    }

    /* Button Row */
    .t-btn-row {
        display: flex;
        gap: 10px;
    }

    .t-btn-row .t-btn {
        flex: 1;
    }

    /* ===== Empty State ===== */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 5rem 2rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    .empty-state-icon {
        width: 100px;
        height: 100px;
        margin: 0 auto 1.5rem;
        background: linear-gradient(145deg, #f0ecff, #e4deff);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: #7c3aed;
    }

    .empty-state h3 {
        font-size: 1.25rem !important;
        font-weight: 700;
        color: #1a1a2e;
        margin: 0 0 0.5rem 0;
    }

    .empty-state p {
        color: #8b8fa3;
        font-size: 0.9375rem;
        margin: 0;
    }

    /* ===== Toast ===== */
    .t-toast {
        position: fixed;
        top: 1.5rem;
        right: 1.5rem;
        padding: 1rem 1.5rem;
        border-radius: 14px;
        color: white;
        z-index: 9999;
        box-shadow: 0 12px 32px rgba(0,0,0,0.18);
        animation: toastIn 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        font-weight: 600;
        font-size: 0.9375rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    [dir="rtl"] .t-toast {
        right: auto;
        left: 1.5rem;
    }

    .t-toast.success { background: linear-gradient(135deg, #059669, #10b981); }
    .t-toast.error { background: linear-gradient(135deg, #dc2626, #ef4444); }

    @keyframes toastIn {
        from { opacity: 0; transform: translateY(-16px) scale(0.92); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* ===== RTL ===== */
    [dir="rtl"] .t-btn-row {
        flex-direction: row-reverse;
    }

    /* ===== Responsive ===== */
    @media (max-width: 640px) {
        .hero-section { padding: 1.5rem 0; }
        .hero-text h1 { font-size: 1.375rem; }
        .hero-icon-box { width: 44px; height: 44px; font-size: 1.25rem; border-radius: 12px; }
        .main-content { padding: 1.25rem 1rem 0; }
        .t-card-body { padding: 1.25rem; }
        .t-card-actions { padding: 1rem 1.25rem 1.25rem; }
        .t-card-title { font-size: 1.125rem !important; }
        .t-card-icon { width: 48px; height: 48px; font-size: 1.25rem; }
        .t-btn { padding: 12px 16px; font-size: 0.875rem; border-radius: 12px; }
        .t-btn-row { flex-direction: column; }
    }

    /* Global */
    h3, h2 { font-size: inherit !important; font-weight: inherit; }
</style>
@endpush

@section('content')
<div class="templates-page">

    {{-- Hero Section --}}
    <div class="hero-section">
        <div class="hero-inner">
            <div class="hero-left">
                <div class="hero-icon-box">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="hero-text">
                    <h1>{{ __('Form Templates') }}</h1>
                    <p>{{ __('Manage and organize your form templates') }}</p>
                </div>
            </div>
            <div class="hero-right">
                <div class="hero-stat">
                    <i class="fas fa-file-alt"></i>
                    {{ __('Templates') }}
                    <span class="count">{{ $templates->count() }}</span>
                </div>
                <div class="hero-stat">
                    <i class="fas fa-check-circle"></i>
                    {{ __('Active') }}
                    <span class="count">{{ $templates->where('is_active', true)->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="main-content">
        <div class="cards-grid">
            @forelse($templates as $index => $template)
            @php
                $iconStyles = ['purple', 'blue', 'emerald', 'rose', 'amber'];
                $icons = ['fa-file-lines', 'fa-clipboard-list', 'fa-rectangle-list', 'fa-list-check', 'fa-table-list'];
                $style = $iconStyles[$index % count($iconStyles)];
                $icon = $icons[$index % count($icons)];
            @endphp
            <div class="t-card" style="animation-delay: {{ $index * 0.08 }}s">
                {{-- Top Gradient Line --}}
                <div class="t-card-line"></div>

                {{-- Card Body --}}
                <div class="t-card-body">
                    <div class="t-card-header">
                        <div class="t-card-icon {{ $style }}">
                            <i class="fas {{ $icon }}"></i>
                        </div>
                        <span class="status-chip {{ $template->is_active ? 'active' : 'inactive' }}">
                            <span class="dot"></span>
                            {{ $template->is_active ? __('Active') : __('Inactive') }}
                        </span>
                    </div>

                    <h3 class="t-card-title">{{ $template->title }}</h3>

                    @if($template->form_type)
                    <div class="t-card-tag">
                        <i class="fas fa-tag"></i>
                        {{ $template->form_type }}
                    </div>
                    @endif
                </div>

                {{-- Card Actions --}}
                <div class="t-card-actions">
                    <button onclick="copyFormLink('{{ route('forms.showByType', ['type' => $template->form_type, 'token' => (auth()->user()?->api_token ?? '')]) }}', this)"
                            class="t-btn t-btn-copy">
                        <i class="fas fa-link"></i>
                        <span class="button-text">{{ __('Copy') }}</span>
                    </button>

                    <div class="t-btn-row">
                        @if (\Encore\Admin\Facades\Admin::user()->can('show-templates-form') || \Encore\Admin\Facades\Admin::user()->can('*'))
                        <a href="{{ admin_url('form-templates/' . $template->id) }}" class="t-btn t-btn-preview">
                            <i class="fas fa-eye"></i>
                            {{ __('Preview') }}
                        </a>
                        @endif

                        @if (\Encore\Admin\Facades\Admin::user()->can('edit-templates-form') || \Encore\Admin\Facades\Admin::user()->can('*'))
                        <a href="{{ admin_url('form-templates/' . $template->id . '/edit') }}" class="t-btn t-btn-edit">
                            <i class="fas fa-pen-to-square"></i>
                            {{ __('Edit') }}
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3>{{ __('No form templates yet') }}</h3>
                <p>{{ __('Start by creating your first form template') }}</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyFormLink(url, button) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function() {
            handleCopySuccess(button);
        }).catch(function(err) {
            fallbackCopy(url, button);
        });
    } else {
        fallbackCopy(url, button);
    }
}

function fallbackCopy(url, button) {
    var ta = document.createElement('textarea');
    ta.value = url;
    ta.style.cssText = 'position:fixed;opacity:0';
    document.body.appendChild(ta);
    ta.select();
    try {
        document.execCommand('copy');
        handleCopySuccess(button);
    } catch (e) {
        showToast('{{ __("Failed to copy link") }}', 'error');
    }
    document.body.removeChild(ta);
}

function handleCopySuccess(button) {
    var label = button.querySelector('.button-text');
    var icon = button.querySelector('i');
    var origLabel = label.textContent;
    var origIcon = icon.className;

    label.textContent = '{{ __("Link Copied!") }}';
    icon.className = 'fas fa-check';
    button.classList.add('copied');

    button.style.transform = 'scale(0.97)';
    setTimeout(function() { button.style.transform = ''; }, 150);

    setTimeout(function() {
        label.textContent = origLabel;
        icon.className = origIcon;
        button.classList.remove('copied');
    }, 2000);

    showToast('{{ __("Form link copied to clipboard!") }}', 'success');
}

function showToast(message, type) {
    var toast = document.createElement('div');
    toast.className = 't-toast ' + type;
    toast.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation') + '"></i><span>' + message + '</span>';
    document.body.appendChild(toast);

    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-16px) scale(0.92)';
        toast.style.transition = 'all 0.35s ease';
        setTimeout(function() {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 350);
    }, 3000);
}
</script>
@endpush
