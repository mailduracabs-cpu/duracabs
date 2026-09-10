<?php

namespace App\Http\Controllers;

use App\Models\SelfDriveVendorPayout;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class SelfDriveVendorPayoutPdfController extends Controller
{
    public function download(SelfDriveVendorPayout $payout): Response
    {
        $payout->loadMissing([
            'transporter.user',
            'items.booking.customer',
            'items.vehicle',
        ]);

        $vehicleSummary = $payout->items
            ->groupBy('vehicle_id')
            ->map(function ($items) {
                $first = $items->first();
                $vehicle = $first?->vehicle;

                $vehicleName = trim(
                    (string) ($vehicle?->car_company_name ?? '')
                    . ' '
                    . (string) ($vehicle?->model_name ?? '')
                );

                if ($vehicleName === '') {
                    $vehicleName = 'Vehicle #' . ($vehicle?->id ?? '-');
                }

                $registration = $vehicle?->registration_number
                    ?? $vehicle?->vehicle_number
                    ?? '-';

                return [
                    'vehicle_name' => $vehicleName,
                    'registration' => $registration,
                    'booking_count' => $items->count(),
                    'booking_units' => (int) $items->sum('booking_units'),
                    'booked_hours' => (int) $items->sum('booked_hours'),
                    'customer_amount' => round(
                        (float) $items->sum('customer_booking_amount'),
                        2
                    ),
                    'vendor_payout' => round(
                        (float) $items->sum('payout_amount'),
                        2
                    ),
                ];
            })
            ->values();

        $vendorName = $this->vendorName($payout);

        $pdf = Pdf::loadView(
            'pdf.self-drive-vendor-payout',
            [
                'payout' => $payout,
                'vehicleSummary' => $vehicleSummary,
                'vendorName' => $vendorName,
            ]
        )
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true);

        $filename = sprintf(
            'DuraCabs-Vendor-Payout-%s.pdf',
            $payout->payout_no
        );

        return $pdf->download($filename);
    }

    public function stream(SelfDriveVendorPayout $payout)
    {
        $payout->loadMissing([
            'transporter.user',
            'items.booking.customer',
            'items.vehicle',
        ]);

        $vehicleSummary = $payout->items
            ->groupBy('vehicle_id')
            ->map(function ($items) {
                $first = $items->first();
                $vehicle = $first?->vehicle;

                $vehicleName = trim(
                    (string) ($vehicle?->car_company_name ?? '')
                    . ' '
                    . (string) ($vehicle?->model_name ?? '')
                );

                if ($vehicleName === '') {
                    $vehicleName = 'Vehicle #' . ($vehicle?->id ?? '-');
                }

                return [
                    'vehicle_name' => $vehicleName,
                    'registration' =>
                        $vehicle?->registration_number
                        ?? $vehicle?->vehicle_number
                        ?? '-',

                    'booking_count' =>
                        $items->count(),

                    'booking_units' =>
                        (int) $items->sum('booking_units'),

                    'booked_hours' =>
                        (int) $items->sum('booked_hours'),

                    'customer_amount' =>
                        round(
                            (float) $items->sum(
                                'customer_booking_amount'
                            ),
                            2
                        ),

                    'vendor_payout' =>
                        round(
                            (float) $items->sum(
                                'payout_amount'
                            ),
                            2
                        ),
                ];
            })
            ->values();

        $pdf = Pdf::loadView(
            'pdf.self-drive-vendor-payout',
            [
                'payout' => $payout,
                'vehicleSummary' => $vehicleSummary,
                'vendorName' => $this->vendorName($payout),
            ]
        )->setPaper('a4', 'portrait');

        return $pdf->stream(
            'Vendor-Payout-' . $payout->payout_no . '.pdf'
        );
    }

    private function vendorName(
        SelfDriveVendorPayout $payout
    ): string {
        $vendor = $payout->transporter;

        if (! $vendor) {
            return 'Vendor #' . $payout->transporter_profile_id;
        }

        foreach ([
            $vendor->business_name ?? null,
            $vendor->company_name ?? null,
            $vendor->name ?? null,
            $vendor->vendor_name ?? null,
            $vendor->user?->name ?? null,
        ] as $name) {
            if (filled($name)) {
                return trim((string) $name);
            }
        }

        return 'Vendor #' . $vendor->id;
    }
}