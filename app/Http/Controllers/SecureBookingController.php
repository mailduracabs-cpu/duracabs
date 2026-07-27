<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\BookingAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SecureBookingController extends Controller
{
    public function __construct(
        private readonly BookingAccessService $bookingAccessService
    ) {
    }

    public function show(
        Request $request,
        string $booking
    ) {
        $order = $this->findOrder($booking);

        abort_if(
            ! $order,
            Response::HTTP_NOT_FOUND,
            'Booking not found.'
        );

        $this->bookingAccessService->authorizeOrder(
            $request->user(),
            $order
        );

        $order->loadMissing([
            'user',
            'items.product',
            'addresses',
            'invoices',
        ]);

        return view(
            'bookings.secure-show',
            [
                'order' => $order,
                'bookingNumber' => $this->bookingNumber($order),
            ]
        );
    }

    private function findOrder(
        string $booking
    ): ?Order {
        $value = trim($booking);

        if ($value === '') {
            return null;
        }

        if (
            Schema::hasColumn('orders', 'booking_no')
        ) {
            $record = Order::query()
                ->where('booking_no', $value)
                ->first();

            if ($record) {
                return $record;
            }
        }

        $id = $this->extractOrderId($value);

        if (! $id) {
            return null;
        }

        return Order::query()->find($id);
    }

    private function extractOrderId(
        string $value
    ): ?int {
        if (ctype_digit($value)) {
            return (int) $value;
        }

        if (
            preg_match(
                '/^DURA0*(\d+)$/i',
                $value,
                $matches
            ) === 1
        ) {
            return (int) $matches[1];
        }

        return null;
    }

    private function bookingNumber(
        Order $order
    ): string {
        if (
            isset($order->booking_no)
            && filled($order->booking_no)
        ) {
            return (string) $order->booking_no;
        }

        return 'DURA' . str_pad(
            (string) $order->id,
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}