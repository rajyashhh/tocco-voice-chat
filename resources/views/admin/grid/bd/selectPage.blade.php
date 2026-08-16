<style>
.switch {
  position: relative;
  display: inline-block;
  width: 80px;
  height: 34px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: #ccc;
  transition: .4s;
  border-radius: 34px;
}

.slider:before {
  position: absolute;
  content: attr(data-label-off);
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
  line-height: 26px;
  text-align: center;
  font-size: 12px;
  font-weight: bold;
  color: #000;
}

input:checked + .slider {
  background-color: #dc3545;
}

input:checked + .slider:before {
  transform: translateX(46px);
  content: attr(data-label-on);
  color: #dc3545;
}

.freeze-row {
  width: 60%;
  margin: 20px auto;
  text-align: center;
  padding: 16px;
  border: 1px solid #eee;
  border-radius: 10px;
}
</style>

{{-- BD layer (dollar wallet) — global freeze --}}
<div class="freeze-row">
    <label style="margin-bottom:10px; display:block; font-weight:bold;">
        {{ __('Freeze Wallet') }} — {{ __('BD') }}
    </label>

    <label class="switch">
        <input type="checkbox" class="freeze-wallet-switch" data-layer="bd"
               {{ $bdFrozen ? 'checked' : '' }}>
        <span class="slider" data-label-on="{{ __('Yes') }}" data-label-off="{{ __('No') }}"></span>
    </label>
</div>

{{-- Shipping Super Admin layer (coin wallet) — global freeze --}}
<div class="freeze-row">
    <label style="margin-bottom:10px; display:block; font-weight:bold;">
        {{ __('Freeze Wallet') }} — {{ __('Shipping Super Admin') }}
    </label>

    <label class="switch">
        <input type="checkbox" class="freeze-wallet-switch" data-layer="shipping"
               {{ $shippingFrozen ? 'checked' : '' }}>
        <span class="slider" data-label-on="{{ __('Yes') }}" data-label-off="{{ __('No') }}"></span>
    </label>
</div>

<script>
document.querySelectorAll('.freeze-wallet-switch').forEach(function (el) {
    el.addEventListener('change', function () {
        let self = this;
        let checked = self.checked;
        let layer = self.getAttribute('data-layer');

        fetch("{{ route('admin.bd.toggle-salary-transfer') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ enabled: checked ? 1 : 0, layer: layer })
        })
        .then(res => res.json())
        .then(data => {
            console.log(data.message);
        })
        .catch(() => {
            alert("{{ __('حدث خطأ، حاول مرة أخرى') }}");
            self.checked = !checked;
        });
    });
});
</script>
