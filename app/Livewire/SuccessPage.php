<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Stripe\Checkout\Session;
use Stripe\Stripe;

#[Title('Success - Duracabs')]
class SuccessPage extends Component
{
    #[Url]
    public $session_id;

    #[Url(history: true)]
    public $id;

    public $days = 0;

    public function createInvoice()
    {
        $view = view('pdf.invoice')->toHtml();

        $pdf = Pdf::loadHTML($view);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'invoice.pdf');
    }

    public function render()
    {
        $latest_order = $this->getSafeOrder();

        if (!$latest_order) {
            abort(404, 'Booking not found.');
        }

        if ((float) ($latest_order->grand_total ?? 0) <= 0) {
            abort(403, 'Invalid booking amount.');
        }

        if ($latest_order->payment_method === 'RazorPay') {
            if ($latest_order->payment_status !== 'paid' || $latest_order->status !== 'confirmed') {
                return redirect()->route('cancel');
            }
        }

        if ($this->session_id) {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $session_info = Session::retrieve($this->session_id);

            if ($session_info->payment_status !== 'paid') {
                $latest_order->payment_status = 'failed';
                $latest_order->status = 'payment_failed';
                $latest_order->save();

                return redirect()->route('cancel');
            }

            $latest_order->payment_status = 'paid';
            $latest_order->status = 'confirmed';
            $latest_order->save();
        }

        $categories = Category::where('is_active', 1)
            ->where('name', $latest_order->productName)
            ->get();

        $order_items = OrderItem::with('product')
            ->where('order_id', $latest_order->id)
            ->get();

        $product = optional($order_items->first())->product;

        if (!$product) {
            $product = (object) [
                'ride_type' => $latest_order->ride_type,
                'name' => $latest_order->productName,
                'km_limit' => $latest_order->total_km ?? 0,
                'hr_limit' => 0,
                'extra_hr_charge' => 0,
                'extra_km_charge' => 0,
            ];
        }

        $InvoiceSum = 0;

        foreach ($latest_order->invoices as $item) {
            $InvoiceSum += (float) ($item['ammount'] ?? 0);
        }

        $this->days = $this->dateDiffInDays($latest_order->date, $latest_order->dateTo);

        return view('livewire.success-page', [
            'order' => $latest_order,
            'product' => $product,
            'invoices' => $latest_order->invoices,
            'InvoiceSum' => $InvoiceSum,
            'driverCharge' => $categories[0]->driver_charge ?? 0,
            'perKm' => $categories[0]->km_charge ?? 0,
        ]);
    }

    private function getSafeOrder()
    {
        $query = Order::with('address', 'items', 'invoices');

        if ($this->id) {
            return $query
                ->where('id', $this->id)
                ->where('user_id', auth()->id())
                ->latest()
                ->first();
        }

        return $query
            ->where('user_id', auth()->id())
            ->latest()
            ->first();
    }

    private function dateDiffInDays($date1, $date2): int
    {
        if (!$date1 || !$date2) {
            return 0;
        }

        $diff = strtotime($date2) - strtotime($date1);

        return abs(round($diff / 86400));
    }
}
