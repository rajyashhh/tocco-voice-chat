<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body, html {
        height: 100%;
        background-color: #f8f9fa;
    }

    .paypal-container {
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .paypal-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        padding: 40px 30px;
        max-width: 400px;
        width: 100%;
        text-align: center;
        transition: transform 0.3s;
    }

    .paypal-card:hover {
        transform: translateY(-5px);
    }

    .paypal-logo {
        max-width: 150px;
        margin-bottom: 25px;
    }

    .btn-paypal {
        font-size: 1.15rem;
        padding: 14px;
        border-radius: 10px;
        font-weight: bold;
        transition: all 0.3s;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }

    .btn-paypal i {
        margin-left: 10px;
        font-size: 1.2rem;
    }

    .btn-create {
        background-color: #0070ba;
        border: none;
        color: white;
    }

    .btn-create:hover {
        background-color: #00457c;
    }

    .btn-quick {
        background-color: #28a745;
        border: none;
        color: white;
    }

    .btn-quick:hover {
        background-color: #198754;
    }

    @media (max-width: 576px) {
        .paypal-card {
            padding: 30px 20px;
        }
        .btn-paypal {
            font-size: 1rem;
            padding: 12px;
        }
        .btn-paypal i {
            margin-left: 5px;
        }
        .paypal-logo {
            max-width: 120px;
            margin-bottom: 20px;
        }
    }
</style>

<div class="paypal-container">
    <div class="paypal-card">
        {{-- شعار باي بال --}}
        <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg" alt="PayPal" class="paypal-logo">

        <h2 class="mb-3" style="color:#003087;">اختر طريقة الدفع</h2>
        <p class="text-muted mb-4">يمكنك الدفع بإنشاء حساب أو الدفع السريع بدون تسجيل</p>

        {{-- زر إنشاء حساب والدفع --}}
        <form action="{{ $createAccountUrl }}" method="POST" class="mb-3">
            @csrf
            <button type="submit" class="btn btn-paypal btn-create">
                إنشاء حساب والدفع <i class="bi bi-person-plus-fill"></i>
            </button>
        </form>

        {{-- زر الدفع السريع --}}
        <form action="{{ $quickPayUrl }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-paypal btn-quick">
                الدفع السريع <i class="bi bi-lightning-fill"></i>
            </button>
        </form>

        <p class="text-muted mt-4">مع PayPal، الدفع آمن وسريع</p>
    </div>
</div>