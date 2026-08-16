<div class="diamond-summary-container">
    <div class="diamond-summary-box">
        <div class="diamond-title">
            {{ __('Total success charges') }}
        </div>
        <div class="diamond-count">
            <span>{{ number_format( @$total,2) }}</span>
            <div class="diamond-icon-container">
                <img src="{{ asset('images/dollar.jpg') }}" alt="Diamond" class="diamond-icon">
            </div>
        </div>
    </div>
</div>

<style>
    .diamond-summary-container {
        display: flex;
        justify-content: center;
        width: 100%;
        padding: 20px;
    }

    .diamond-summary-box {
        max-width: 600px;
        width: 100%;
        background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-color) 100%);
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        margin: 0 auto; /* This also helps with centering */
    }

    .diamond-summary-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }

    .diamond-title {
        font-size: 22px;
        font-weight: bold;
        color: var(--secondary-color);
        margin-bottom: 15px;
    }

    .diamond-count {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }

    .diamond-count span {
        margin-right: 10px;
    }

    .diamond-icon-container {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        padding: 8px;
        display: inline-flex;
    }

    .diamond-icon {
        width: 32px;
        height: 32px;
        filter: drop-shadow(0 0 3px rgba(255, 255, 255, 0.5));
    }
</style>
