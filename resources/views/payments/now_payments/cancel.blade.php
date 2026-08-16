{{-- resources/views/payments/now_payments/checkout.blade.php --}}

    <div class="container text-center mt-5">
        <h2 class="text-danger">❌ تم إلغاء الدفع</h2>
        <p class="mt-3">لم يتم إتمام عملية الدفع. يمكنك المحاولة مرة أخرى لاحقًا.</p>
        <a href="{{ url('/') }}" class="btn btn-secondary mt-4">العودة إلى الصفحة الرئيسية</a>
    </div>
