<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Secure Payment - Dura Cabs')]
class RazorePay extends Component
{
    #[Url(history: true)]
    public ?int $id = null;

    public function render()
    {
        abort_unless(Auth::check(), 401);

        $order = Order::query()
            ->with('address')
            ->whereKey($this->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $extraOptions = $this->normaliseExtraOptions($order->extraOptions);

        $reservation = Arr::get($extraOptions, 'reservation', []);
        $isSelfDrive = (string) $order->ride_type === 'self_drive';

        $paymentOption = $isSelfDrive
            ? (string) ($reservation['payment_option'] ?? 'full')
            : 'full';

        $bookingTotal = $this->money($order->grand_total);

        $paymentAmount = $isSelfDrive && $paymentOption === 'token'
            ? $this->money(
                $reservation['amount_payable_now']
                    ?? $reservation['reservation_token_amount']
                    ?? 0
            )
            : $bookingTotal;

        /*
         * Safe fallback for older self-drive orders where reservation metadata
         * may not yet contain amount_payable_now.
         */
        if ($isSelfDrive && $paymentOption === 'token' && $paymentAmount <= 0) {
            $paymentAmount = min(500.00, $bookingTotal);
        }

        $balanceAmount = $this->money(
            $reservation['balance_amount']
                ?? max(0, $bookingTotal - $paymentAmount)
        );

        abort_if($bookingTotal <= 0, 403, 'Invalid booking amount.');
        abort_if($paymentAmount <= 0, 403, 'Invalid payment amount.');
        abort_if($paymentAmount > $bookingTotal, 403, 'Payment amount exceeds the booking total.');

        return view('livewire.razore-pay', [
            'order' => $order,
            'isSelfDrive' => $isSelfDrive,
            'paymentOption' => $paymentOption,
            'paymentAmount' => $paymentAmount,
            'bookingTotal' => $bookingTotal,
            'balanceAmount' => $balanceAmount,
            'customerName' => $order->address?->full_name
                ?? Auth::user()?->name
                ?? '',
            'customerEmail' => $order->address?->email
                ?? Auth::user()?->email
                ?? '',
            'customerPhone' => $order->address?->phone
                ?? Auth::user()?->mobile
                ?? '',
        ]);
    }

    private function normaliseExtraOptions(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function money(mixed $value): float
    {
        return round(max(0, (float) $value), 2);
    }
}