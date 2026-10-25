<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><title>{{ $bill->bill_no }}</title>
<style>body{font-family:DejaVu Sans,Arial,sans-serif;color:#172536;margin:40px;font-size:14px}h1{font-size:25px;margin-bottom:4px}.muted{color:#526477}table{border-collapse:collapse;width:100%;margin-top:24px}td,th{border-bottom:1px solid #dbe3e9;padding:12px;text-align:left}.total{font-size:21px;font-weight:bold}footer{margin-top:35px;font-size:12px;color:#526477}</style></head>
<body>
@php($terms = json_decode($bill->terms, true))
<h1>DuraCabs</h1><p class="muted">Monthly vehicle agreement bill</p>
<p><strong>{{ $bill->bill_no }}</strong><br>Bill date: {{ $bill->bill_date }}</p>
<table>
<tr><th>Vendor</th><td>{{ $terms['vendor_name'] ?? ('Vendor #' . $bill->transporter_profile_id) }}</td></tr>
<tr><th>Vehicle</th><td>{{ $terms['vehicle_number'] ?? $bill->vehicle_id }}</td></tr>
<tr><th>Period</th><td>{{ $bill->period_start }} to {{ $bill->period_end }}</td></tr>
<tr><th>Monthly agreement</th><td>INR {{ number_format((float) $bill->monthly_amount, 2) }}</td></tr>
<tr><th>Amount payable to vendor</th><td class="total">INR {{ number_format((float) $bill->amount, 2) }}</td></tr>
<tr><th>Status</th><td>{{ ucfirst($bill->status) }}</td></tr>
@if($bill->paid_at)<tr><th>Payment recorded</th><td>{{ $bill->paid_at }} — {{ $bill->payment_reference }}</td></tr>@endif
</table>
<footer>Monthly agreement payment is independent of customer booking revenue. A pending bill is not a payment receipt.</footer>
</body></html>
