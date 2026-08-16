
<style>
    body {
        font-family: 'Cairo', sans-serif;
        background: #f4f6f9;
        margin: 0;
        padding: 0;
    }

    .container {
        max-width: 500px;
        margin: 50px auto;
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        padding: 30px;
    }

    h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #333;
    }

    label {
        font-weight: bold;
        margin-bottom: 8px;
        display: block;
        color: #555;
    }

    input[type="number"],
    select {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        margin-bottom: 10px;
        transition: border-color 0.3s;
        font-size: 16px;
    }

    input[type="number"]:focus,
    select:focus {
        border-color: #007bff;
        outline: none;
    }

    button {
        width: 100%;
        padding: 14px;
        border: none;
        background-color: #007bff;
        color: #fff;
        font-size: 17px;
        border-radius: 10px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #0056b3;
    }

    option {
        font-size: 15px;
    }

    #min-amount-info {
        color: #888;
        margin-bottom: 20px;
        font-size: 14px;
    }
</style>

<div class="container">
    <h2>إجراء الدفع</h2>

<form action="{{ route('now_payment_create') }}" method="POST">
    @csrf

    <label for="currency">اختر العملة الرقمية</label>
    <select name="currency" id="currency" required>
        <option value="">-- اختر عملة --</option>
        @foreach($currencies as $currency)
            <option 
                value="{{ $currency['currency'] }}" 
                data-min="{{ $currency['min_amount'] }}" 
                data-max="{{ $currency['max_amount'] }}">
                {{ strtoupper($currency['currency']) }}
            </option>
        @endforeach
    </select>

    <label for="amount">المبلغ (بالدولار الأمريكي)</label>
    <input type="number" name="amount" id="amount" required min="1" step="0.01">

    <small id="min-amount-info" style="color: #555; display: block; margin-top: 5px;"></small>

    <br>

    <button style="margin-top: 36px;" type="submit">إنشاء الدفع</button>
</form>

<script>
 
    const currencySelect = document.getElementById('currency');
    const minAmountInfo = document.getElementById('min-amount-info');
    const amountInput = document.getElementById('amount');

    function getDecimalPlaces(number) {
        const str = number.toString();
        if (str.includes('.')) {
            return str.split('.')[1].length;
        }
        return 0;
    }

    currencySelect.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const minAmount = parseFloat(selectedOption.getAttribute('data-min'));
        const maxAmount = parseFloat(selectedOption.getAttribute('data-max'));

        if (!isNaN(minAmount) && !isNaN(maxAmount)) {
            amountInput.min = minAmount;
            amountInput.max = maxAmount;

            // تحديد step بناءً على عدد الأرقام العشرية في الحد الأدنى
            const decimals = Math.max(
                getDecimalPlaces(minAmount),
                getDecimalPlaces(maxAmount)
            );
            amountInput.step = (1 / Math.pow(10, decimals)).toFixed(decimals);

            minAmountInfo.textContent = `يمكنك الدفع بين ${minAmount} و ${maxAmount} دولار أمريكي.`;

            // تعديل القيمة لتكون ضمن النطاق
            if (!amountInput.value || parseFloat(amountInput.value) < minAmount || parseFloat(amountInput.value) > maxAmount) {
                amountInput.value = minAmount;
            }
        } else {
            amountInput.min = 1;
            amountInput.removeAttribute('max');
            amountInput.step = "0.01";
            minAmountInfo.textContent = '';
        }
    });
</script>