<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="referrer" content="no-referrer"><title>DuraCabs Partner Checkout</title>
  <style>body{margin:0;background:#f0f1f5;color:#485163;font:17px system-ui}main{max-width:480px;margin:12vh auto;padding:28px;background:white;border-radius:22px}button{background:#ffb511;color:#30394a;border:0;border-radius:14px;padding:18px;width:100%;font:600 18px system-ui}p{line-height:1.6}</style>
</head>
<body><main><h1>DuraCabs Partner</h1><p>Pay the selected booking commission through Razorpay. Payment status is confirmed by the server after gateway verification.</p><p>@if ($bookingAmountPaise !== null) Agreed booking fare: ₹{{ number_format($bookingAmountPaise / 100, 2) }}<br>@endif<strong>Commission payable now: ₹{{ number_format($commissionPaise / 100, 2) }}</strong></p><button id="pay" type="button">Continue to secure payment</button><p id="countdown" role="timer"></p><p id="status" role="status">After payment, return to the app and refresh the booking.</p></main>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const options = {{ Illuminate\Support\Js::from($options) }};
const button = document.getElementById('pay');
const deadline = performance.now() + {{ (int) $remainingSeconds }} * 1000;
let activeCheckout;
const remaining = () => Math.max(0, Math.ceil((deadline - performance.now()) / 1000));
function tick() {
  const seconds = remaining();
  document.getElementById('countdown').textContent = `Payment window: ${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
  if (seconds === 0) {
    button.disabled = true;
    if (activeCheckout) { activeCheckout.close(); activeCheckout = null; }
    document.getElementById('status').textContent = 'Payment window ended. Return to the app for bank verification. Do not start another payment until confirmed.';
  }
}
setInterval(tick, 1000); tick();
button.addEventListener('click', () => {
  if (remaining() === 0) { tick(); return; }
  options.timeout = remaining();
  if (!window.Razorpay) { document.getElementById('status').textContent = 'Checkout could not load. Check your connection and reload.'; return; }
  button.disabled = true;
  options.modal = { ondismiss: () => { button.disabled = remaining() === 0; document.getElementById('status').textContent = 'Checkout closed. Return to the app and refresh to confirm payment status before retrying.'; }};
  const checkout = new Razorpay(options);
  checkout.on('payment.failed', () => { button.disabled = remaining() === 0; document.getElementById('status').textContent = 'Payment was not confirmed. Return to the app and refresh before trying again.'; });
  activeCheckout = checkout; checkout.open();
});
</script></body></html>
