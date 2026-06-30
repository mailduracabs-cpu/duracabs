<?php

namespace App\Http\Controllers;

use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Razorpay\Api\Api;

class RazorpayPaymentController extends Controller
{
    public function index(): View
    {
        return view('razorpay');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'nullable|string',
            'razorpay_signature' => 'nullable|string',
        ]);

        $api = new Api(env('RAZORPAY_API_KEY'), env('RAZORPAY_API_SECRET'));

        try {
            $payment = $api->payment->fetch($request->razorpay_payment_id);

            $orderId = $payment->description ?? null;

            if (!$orderId) {
                return redirect()->route('checkout')->with('error', 'Invalid payment reference.');
            }

            $order = Order::find($orderId);

            if (!$order) {
                return redirect()->route('checkout')->with('error', 'Booking not found.');
            }

            if ((float) $order->grand_total <= 0) {
                $order->update([
                    'payment_status' => 'failed',
                    'status' => 'payment_failed',
                ]);

                return redirect()->route('checkout')->with('error', 'Invalid booking amount.');
            }

            if ($order->payment_status === 'paid') {
                return redirect()->route('success', ['id' => $order->id]);
            }

            $expectedAmount = (int) round($order->grand_total * 100);

            if ((int) $payment->amount !== $expectedAmount) {
                $order->update([
                    'payment_status' => 'failed',
                    'status' => 'payment_failed',
                ]);

                return redirect()->route('checkout')->with('error', 'Payment amount mismatch.');
            }

            if ($payment->status === 'authorized') {
                $api->payment->fetch($request->razorpay_payment_id)
                    ->capture(['amount' => $payment->amount]);

                $payment = $api->payment->fetch($request->razorpay_payment_id);
            }

            if ($payment->status !== 'captured') {
                $order->update([
                    'payment_status' => 'failed',
                    'status' => 'payment_failed',
                ]);

                return redirect()->route('checkout')->with('error', 'Payment failed or not completed.');
            }

            $order->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);

            $this->sendSMS($order->id);

            return redirect()->route('success', ['id' => $order->id]);

        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('checkout')->with('error', 'Payment verification failed.');
        }
    }

    private function sendSMS($orderId): void
    {
        $mobileval = '7088873332,7088873331';
        $templateid = '1507166123259919760';

        $message = 'Dear Admin, You have received a new booking number ' . $orderId . ' please log in your account and check the booking status From DURACABS';

        $api_url = "http://manage.sambsms.com/app/smsapi/index.php?key=3627633B7AC9C6&entity=1701165124480381903&tempid=" . $templateid . "&campaign=0&routeid=7&type=text&contacts=" . $mobileval . "&senderid=DURACB&msg=" . urlencode($message);

        try {
            $client = new Client();
            $client->get($api_url, ['timeout' => 10]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
