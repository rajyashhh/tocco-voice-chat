<form id="paymentForm">
    @csrf
    <div class="form-group">
        <label for="amount">{{ __('payment.amount') }}</label>
        <input type="number" class="form-control" id="amount" name="amount" required>
    </div>
    <div class="form-group">
        <label for="type">{{ __('payment.type') }}</label>
        <select name="type" id="type" class="form-control">
            <option value="game_type">{{ __('payment.game_type') }}</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="link_type">{{ __('payment.link_type') }}</label>
        <select name="link_type" id="link_type" class="form-control" required>
            <option value="">{{ __('payment.select_link_type') }}</option>
            <option value="fawry">{{ __('payment.fawry') }}</option>
            <option value="paymob">{{ __('payment.paymob') }}</option>
        </select>
    </div>

    <button type="submit" id="submitButton" class="btn btn-primary">{{ __('payment.save') }}</button>
</form>

<!-- Placeholder for the success message or payment URL -->
<div id="responseMessage"></div>


