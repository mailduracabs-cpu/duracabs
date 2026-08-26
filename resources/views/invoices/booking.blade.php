<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dura Cabs Invoice - {{ $invoice['booking_no'] ?? 'Booking' }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #f3f6f9;
            color: #172033;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.45;
        }

        .invoice {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #dfe6ee;
        }

        .top-bar {
            height: 8px;
            background: #009ffd;
        }

        .content {
            padding: 28px;
        }

        .header-table,
        .info-table,
        .details-table,
        .fare-table,
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo {
            max-width: 215px;
            max-height: 70px;
        }

        .invoice-title {
            margin: 0;
            color: #009ffd;
            font-size: 30px;
            font-weight: 800;
            text-align: right;
        }

        .invoice-meta {
            margin-top: 7px;
            text-align: right;
            color: #4c5a6d;
        }

        .invoice-meta strong {
            color: #172033;
        }

        .status {
            display: inline-block;
            margin-top: 8px;
            padding: 5px 10px;
            border-radius: 14px;
            background: #eaf7ff;
            color: #0078bd;
            font-weight: 700;
            text-transform: uppercase;
        }

        .section {
            margin-top: 24px;
        }

        .section-title {
            margin: 0 0 10px;
            padding-bottom: 7px;
            border-bottom: 2px solid #009ffd;
            color: #172033;
            font-size: 16px;
            font-weight: 800;
        }

        .info-card {
            width: 48%;
            padding: 14px;
            vertical-align: top;
            border: 1px solid #dfe6ee;
            background: #fbfdff;
        }

        .info-gap {
            width: 4%;
        }

        .label {
            color: #69778a;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .value {
            margin-top: 3px;
            color: #172033;
            font-weight: 700;
            word-break: break-word;
        }

        .details-table th,
        .details-table td,
        .fare-table th,
        .fare-table td {
            padding: 10px 11px;
            border: 1px solid #dfe6ee;
            text-align: left;
            vertical-align: top;
        }

        .details-table th,
        .fare-table th {
            background: #edf7fd;
            color: #23405a;
            font-size: 12px;
            font-weight: 800;
        }

        .details-table th {
            width: 25%;
        }

        .fare-table th:last-child,
        .fare-table td:last-child {
            text-align: right;
        }

        .fare-table .negative {
            color: #c73535;
        }

        .fare-table .total-row td {
            background: #edf7fd;
            color: #0d3d5b;
            font-size: 15px;
            font-weight: 800;
        }

        .fare-table .paid-row td {
            color: #15803d;
            font-weight: 700;
        }

        .fare-table .balance-row td {
            color: #b45309;
            font-weight: 800;
        }

        .note {
            padding: 13px 14px;
            border-left: 4px solid #009ffd;
            background: #edf7fd;
            color: #334155;
        }

        .terms {
            margin: 0;
            padding-left: 18px;
            color: #5b6778;
        }

        .terms li {
            margin-bottom: 5px;
        }

        .footer {
            margin-top: 28px;
            padding-top: 18px;
            border-top: 1px solid #dfe6ee;
            color: #6b7280;
            font-size: 11px;
        }

        .footer-table td {
            vertical-align: bottom;
        }

        .footer-right {
            text-align: right;
        }

        .print-actions {
            max-width: 900px;
            margin: 16px auto 0;
            text-align: right;
        }

        .print-button {
            display: inline-block;
            padding: 10px 16px;
            border: 0;
            border-radius: 8px;
            background: #009ffd;
            color: #ffffff;
            cursor: pointer;
            font-weight: 700;
        }

        @media print {
            body {
                padding: 0;
                background: #ffffff;
            }

            .invoice {
                max-width: none;
                border: 0;
            }

            .print-actions {
                display: none;
            }
        }
    </style>
</head>
<body>
@php
    $companyName = config('app.name', 'Dura Cabs');
    $companyAddress = config('invoice.company_address', 'India');
    $companyPhone = config('invoice.company_phone', '');
    $companyEmail = config('invoice.company_email', '');
    $companyGstin = config('invoice.gstin', '');

    $customer = $invoice['customer'] ?? [];
    $trip = $invoice['trip'] ?? [];
    $fare = $invoice['fare'] ?? [];
    $payment = $invoice['payment'] ?? [];

    $serviceType = strtolower((string) ($invoice['record_type'] ?? 'taxi'));
    $isSelfDrive = $serviceType === 'self_drive';

    $pricingBreakdown = $fare['pricing_breakdown']
        ?? $invoice['pricing_breakdown']
        ?? [];

    if (is_string($pricingBreakdown)) {
        $decodedPricing = json_decode($pricingBreakdown, true);
        $pricingBreakdown = is_array($decodedPricing) ? $decodedPricing : [];
    }

    if (!is_array($pricingBreakdown)) {
        $pricingBreakdown = [];
    }

    $money = static function ($value): string {
        return '₹' . number_format((float) ($value ?? 0), 2);
    };

    $showRow = static function ($value): bool {
        return abs((float) ($value ?? 0)) > 0.0001;
    };

    $isOnline = in_array(
        strtolower((string) ($payment['method'] ?? '')),
        ['razorpay', 'online', 'upi', 'card', 'netbanking', 'net_banking'],
        true
    );

    /*
     |--------------------------------------------------------------------------
     | Stored pricing is the source of truth for Self Drive bookings.
     | Taxi invoices retain the legacy normalized fare payload as a fallback.
     |--------------------------------------------------------------------------
     */
    $baseFare = (float) (
        $isSelfDrive
            ? ($pricingBreakdown['base_amount'] ?? $pricingBreakdown['rent'] ?? $fare['base_fare'] ?? 0)
            : ($fare['base_fare'] ?? 0)
    );

    $planDiscount = (float) (
        $isSelfDrive
            ? ($pricingBreakdown['discount_amount'] ?? 0)
            : ($fare['plan_discount'] ?? 0)
    );

    $rentAfterPlanDiscount = (float) (
        $isSelfDrive
            ? ($pricingBreakdown['rent'] ?? max(0, $baseFare - $planDiscount))
            : $baseFare
    );

    $extraHours = (float) ($fare['extra_hour_amount'] ?? 0);
    $extraKm = (float) ($fare['extra_km_amount'] ?? 0);

    $specialServices = (float) (
        $isSelfDrive
            ? ($pricingBreakdown['extras_total'] ?? $fare['special_request_total'] ?? 0)
            : ($fare['special_request_total'] ?? 0)
    );

    $couponDiscount = (float) (
        $isSelfDrive
            ? ($pricingBreakdown['coupon_discount'] ?? $fare['coupon_discount'] ?? 0)
            : ($fare['coupon_discount'] ?? 0)
    );

    $taxableAmount = (float) (
        $isSelfDrive
            ? ($pricingBreakdown['taxable_amount'] ?? max(0, $rentAfterPlanDiscount + $specialServices - $couponDiscount))
            : ($fare['taxable_amount'] ?? ($baseFare + $extraHours + $extraKm + $specialServices))
    );

    $gstAmount = (float) (
        $isSelfDrive
            ? ($pricingBreakdown['gst_amount'] ?? $fare['gst_amount'] ?? 0)
            : ($fare['gst_amount'] ?? 0)
    );

    $gstRate = (float) (
        $pricingBreakdown['gst_percent']
        ?? $fare['gst_percent']
        ?? ($isSelfDrive ? 18 : 5)
    );

    $securityDeposit = (float) (
        $isSelfDrive
            ? ($pricingBreakdown['security_deposit'] ?? $fare['security_deposit'] ?? 0)
            : ($fare['security_deposit'] ?? 0)
    );

    $grandTotal = (float) (
        $isSelfDrive
            ? ($pricingBreakdown['payable_amount'] ?? $fare['grand_total'] ?? 0)
            : ($fare['grand_total'] ?? 0)
    );

    $onlineCharge = (float) ($fare['online_payment_charge'] ?? 0);

    // Browser-safe logo URL. public_path() is a filesystem path and breaks in shared HTML view.
    $logoUrl = asset('storage/images/Duracab-Logo-425x115.png');

    // Friendly date/time formatter; keeps original value if it cannot be parsed.
    $friendlyDateTime = static function ($date, $time = null): string {
        $raw = trim((string) ($date ?? ''));
        if ($raw === '') {
            return '--';
        }

        if (!empty($time) && !str_contains($raw, ':')) {
            $raw .= ' ' . trim((string) $time);
        }

        try {
            return \Carbon\Carbon::parse($raw)->format('d M Y • h:i A');
        } catch (\Throwable $e) {
            return trim((string) $date . (!empty($time) ? ' • ' . (string) $time : ''));
        }
    };

    // Self Drive admin pricing fields, when supplied by InvoiceService.
    $deliveryPrice = (float) ($pricingBreakdown['delivery_price'] ?? $fare['delivery_price'] ?? 0);
    $pickupPrice = (float) ($pricingBreakdown['pickup_price'] ?? $fare['pickup_price'] ?? 0);
    $manualPrice = (float) ($pricingBreakdown['manual_price'] ?? $fare['manual_price'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | Self Drive invoice: GST is INCLUDED in the rental amount
    |--------------------------------------------------------------------------
    | Example:
    | Rental Total (GST Included) = 5520
    | Taxable Value                = 4677.97
    | GST @ 18%                    = 842.03
    | Security Deposit             = 5000
    | Grand Total                  = 10520
    |
    | GST is displayed as a breakup only. It is NOT added again.
    */
    if ($isSelfDrive) {
        $gstRate = $gstRate > 0 ? $gstRate : 18.0;

        // Manual price, when present, is already GST-inclusive and becomes the rental total.
        // Otherwise build the GST-inclusive rental total from stored rental components.
        $rentalTotal = $manualPrice > 0
            ? $manualPrice
            : max(
                0,
                $rentAfterPlanDiscount
                + $deliveryPrice
                + $pickupPrice
                + $specialServices
                + $extraHours
                + $extraKm
                - $couponDiscount
            );

        // GST breakup from an inclusive amount.
        $taxableAmount = $gstRate > 0
            ? ($rentalTotal / (1 + ($gstRate / 100)))
            : $rentalTotal;

        $gstAmount = max(0, $rentalTotal - $taxableAmount);

        // Charges on which GST is not being calculated.
        $noGstCharges =
            (float) ($fare['toll_amount'] ?? 0)
            + (float) ($fare['parking_amount'] ?? 0)
            + (float) ($fare['tax_amount'] ?? 0)
            + (float) ($fare['damage_amount'] ?? 0)
            + (float) ($fare['other_charges'] ?? 0);

        // GST is already inside $rentalTotal, so do not add $gstAmount here.
        $grandTotal = max(
            0,
            $rentalTotal
            + $securityDeposit
            + $noGstCharges
            + $onlineCharge
        );

        // Recalculate payment figures from the corrected invoice grand total.
        $paidAmount = (float) ($fare['paid_amount'] ?? 0);
        $remainingAmount = max(0, $grandTotal - $paidAmount);
    } else {
        $rentalTotal = 0;
        $paidAmount = (float) ($fare['paid_amount'] ?? 0);
        $remainingAmount = (float) ($fare['remaining_amount'] ?? max(0, $grandTotal - $paidAmount));
    }
@endphp

<div class="invoice">
    <div class="top-bar"></div>

    <div class="content">
        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    <img
                        class="logo"
                        src="{{ $logoUrl }}"
                        alt="Dura Cabs"
                    >

                    <div style="margin-top: 10px;">
                        <strong>{{ $companyName }}</strong><br>
                        {{ $companyAddress }}

                        @if($companyPhone)
                            <br>Phone: {{ $companyPhone }}
                        @endif

                        @if($companyEmail)
                            <br>Email: {{ $companyEmail }}
                        @endif

                        @if($companyGstin)
                            <br>GSTIN: {{ $companyGstin }}
                        @endif
                    </div>
                </td>

                <td style="width: 45%;">
                    <h1 class="invoice-title">INVOICE</h1>

                    <div class="invoice-meta">
                        <strong>Invoice No:</strong>
                        {{ $invoice['invoice_no'] ?? '--' }}
                        <br>

                        <strong>Booking No:</strong>
                        {{ $invoice['booking_no'] ?? '--' }}
                        <br>

                        <strong>Invoice Date:</strong>
                        {{ $invoice['invoice_date'] ?? now()->format('d M Y') }}
                        <br>

                        <span class="status">
                            {{ $invoice['status'] ?? 'Pending' }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section">
            <h2 class="section-title">Customer & Service</h2>

            <table class="info-table">
                <tr>
                    <td class="info-card">
                        <div class="label">Customer</div>
                        <div class="value">{{ $customer['name'] ?? '--' }}</div>

                        <div style="margin-top: 9px;">
                            <span class="label">Mobile</span>
                            <div class="value">{{ $customer['mobile'] ?? '--' }}</div>
                        </div>

                        <div style="margin-top: 9px;">
                            <span class="label">Email</span>
                            <div class="value">{{ $customer['email'] ?? '--' }}</div>
                        </div>
                    </td>

                    <td class="info-gap"></td>

                    <td class="info-card">
                        <div class="label">Service Type</div>
                        <div class="value">{{ $invoice['service_type'] ?? '--' }}</div>

                        <div style="margin-top: 9px;">
                            <span class="label">Vehicle</span>
                            <div class="value">{{ $trip['vehicle_name'] ?? '--' }}</div>
                        </div>

                        @if(!$isSelfDrive && !empty($trip['vehicle_number']))
                            <div style="margin-top: 9px;">
                                <span class="label">Vehicle Number</span>
                                <div class="value">{{ $trip['vehicle_number'] }}</div>
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">Trip Details</h2>

            <table class="details-table">
                <tr>
                    <th>Pickup</th>
                    <td>{{ $trip['pickup'] ?? '--' }}</td>
                </tr>

                @if(!empty($trip['drop']))
                    <tr>
                        <th>Drop</th>
                        <td>{{ $trip['drop'] }}</td>
                    </tr>
                @endif

                <tr>
                    <th>Pickup Date & Time</th>
                    <td>
                        {{ $friendlyDateTime($trip['pickup_date'] ?? null, $trip['pickup_time'] ?? null) }}
                    </td>
                </tr>

                @if(!empty($trip['return_date']))
                    <tr>
                        <th>Return Date & Time</th>
                        <td>
                            {{ $friendlyDateTime($trip['return_date'] ?? null, $trip['return_time'] ?? null) }}
                        </td>
                    </tr>
                @endif

                @if(!empty($trip['driver_name']))
                    <tr>
                        <th>Driver</th>
                        <td>
                            {{ $trip['driver_name'] }}
                            @if(!empty($trip['driver_mobile']))
                                • {{ $trip['driver_mobile'] }}
                            @endif
                        </td>
                    </tr>
                @endif

                @if(!empty($trip['vendor_name']))
                    <tr>
                        <th>Vendor / Partner</th>
                        <td>{{ $trip['vendor_name'] }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">Final Bill</h2>

            <table class="fare-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>{{ $isSelfDrive ? 'Rental Base Amount' : 'Base Fare' }}</td>
                        <td>{{ $money($baseFare) }}</td>
                    </tr>

                    @if($isSelfDrive && $showRow($planDiscount))
                        <tr>
                            <td>Rental Plan Discount</td>
                            <td class="negative">-{{ $money($planDiscount) }}</td>
                        </tr>
                    @endif

                    @if($isSelfDrive && $showRow($rentAfterPlanDiscount) && $showRow($planDiscount))
                        <tr>
                            <td>Rent After Plan Discount</td>
                            <td>{{ $money($rentAfterPlanDiscount) }}</td>
                        </tr>
                    @endif

                    @if($isSelfDrive && $showRow($deliveryPrice))
                        <tr>
                            <td>Doorstep Delivery</td>
                            <td>{{ $money($deliveryPrice) }}</td>
                        </tr>
                    @endif

                    @if($isSelfDrive && $showRow($pickupPrice))
                        <tr>
                            <td>Vehicle Return Pickup</td>
                            <td>{{ $money($pickupPrice) }}</td>
                        </tr>
                    @endif

                    @if($isSelfDrive && $showRow($manualPrice))
                        <tr>
                            <td>Manual Price <span style="color:#69778a;">(GST Included)</span></td>
                            <td>{{ $money($manualPrice) }}</td>
                        </tr>
                    @endif

                    @if($showRow($specialServices))
                        <tr>
                            <td>Selected Extra Services</td>
                            <td>{{ $money($specialServices) }}</td>
                        </tr>
                    @endif

                    @if($showRow($extraHours))
                        <tr>
                            <td>Extra Hours</td>
                            <td>{{ $money($extraHours) }}</td>
                        </tr>
                    @endif

                    @if($showRow($extraKm))
                        <tr>
                            <td>Extra Kilometres</td>
                            <td>{{ $money($extraKm) }}</td>
                        </tr>
                    @endif

                    @if($showRow($fare['toll_amount'] ?? 0))
                        <tr>
                            <td>Toll (No GST)</td>
                            <td>{{ $money($fare['toll_amount']) }}</td>
                        </tr>
                    @endif

                    @if($showRow($fare['parking_amount'] ?? 0))
                        <tr>
                            <td>Parking (No GST)</td>
                            <td>{{ $money($fare['parking_amount']) }}</td>
                        </tr>
                    @endif

                    @if($showRow($fare['tax_amount'] ?? 0))
                        <tr>
                            <td>Government / Permit Tax (No GST)</td>
                            <td>{{ $money($fare['tax_amount']) }}</td>
                        </tr>
                    @endif

                    @if($showRow($securityDeposit))
                        <tr>
                            <td>Security Deposit</td>
                            <td>{{ $money($securityDeposit) }}</td>
                        </tr>
                    @endif

                    @if($showRow($fare['damage_amount'] ?? 0))
                        <tr>
                            <td>Damage Charges</td>
                            <td>{{ $money($fare['damage_amount']) }}</td>
                        </tr>
                    @endif

                    @if($showRow($fare['other_charges'] ?? 0))
                        <tr>
                            <td>Other Charges</td>
                            <td>{{ $money($fare['other_charges']) }}</td>
                        </tr>
                    @endif

                    @if($isSelfDrive)
                        <tr>
                            <td>Taxable Value <span style="color:#69778a;">(before GST)</span></td>
                            <td>{{ $money($taxableAmount) }}</td>
                        </tr>

                        <tr>
                            <td>GST @ {{ number_format($gstRate, 2) }}% <span style="color:#69778a;">(Included)</span></td>
                            <td>{{ $money($gstAmount) }}</td>
                        </tr>

                        <tr style="font-weight:800;">
                            <td>Rental Total <span style="color:#69778a;">(GST Included)</span></td>
                            <td>{{ $money($rentalTotal) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>
                                Taxable Amount
                                <span style="color: #69778a;">(Base Fare + Extras + Extra Hours + Extra KM)</span>
                            </td>
                            <td>{{ $money($taxableAmount) }}</td>
                        </tr>

                        @if($showRow($gstAmount))
                            <tr>
                                <td>GST @ {{ number_format($gstRate, 2) }}%</td>
                                <td>{{ $money($gstAmount) }}</td>
                            </tr>
                        @endif
                    @endif

                    @if($isOnline && $showRow($onlineCharge))
                        <tr>
                            <td>Online Payment Convenience Charge @ 3%</td>
                            <td>{{ $money($onlineCharge) }}</td>
                        </tr>
                    @endif

                    @if($showRow($couponDiscount))
                        <tr>
                            <td>Coupon Discount</td>
                            <td class="negative">
                                -{{ $money($couponDiscount) }}
                            </td>
                        </tr>
                    @endif

                    <tr class="total-row">
                        <td>Grand Total</td>
                        <td>{{ $money($grandTotal) }}</td>
                    </tr>

                    <tr class="paid-row">
                        <td>Paid Amount</td>
                        <td>{{ $money($paidAmount) }}</td>
                    </tr>

                    @if($showRow($remainingAmount))
                        <tr class="balance-row">
                            <td>Remaining Amount</td>
                            <td>{{ $money($remainingAmount) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">Payment Details</h2>

            <table class="details-table">
                <tr>
                    <th>Payment Method</th>
                    <td>{{ strtoupper((string) ($payment['method'] ?? '--')) }}</td>
                </tr>

                <tr>
                    <th>Payment Status</th>
                    <td>{{ strtoupper((string) ($payment['status'] ?? 'pending')) }}</td>
                </tr>

                @if(!empty($payment['reference']))
                    <tr>
                        <th>Payment Reference</th>
                        <td>{{ $payment['reference'] }}</td>
                    </tr>
                @endif
            </table>
        </div>

        @if(!empty($invoice['notes']))
            <div class="section">
                <h2 class="section-title">Notes</h2>

                <div class="note">
                    {{ $invoice['notes'] }}
                </div>
            </div>
        @endif

        <div class="section">
            <h2 class="section-title">Terms</h2>

            <ul class="terms">
                <li>This invoice is generated from the booking's stored billing data.</li>
                @if($isSelfDrive)
                    <li>Self Drive rental amount is GST-inclusive; GST is shown only as a tax breakup and is not added again.</li>
                    <li>If a manual price is used, that amount is also treated as GST-inclusive.</li>
                    <li>Security deposit is refundable and shown separately when applicable.</li>
                @else
                    <li>With Driver GST rate is 5% where applicable.</li>
                    <li>No GST is calculated on toll, parking or government/permit tax.</li>
                @endif
                <li>Online payment convenience charge is shown separately when applicable.</li>
            </ul>
        </div>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td>
                        Thank you for choosing Dura Cabs.<br>
                        This is a computer-generated invoice.
                    </td>

                    <td class="footer-right">
                        Booking No: {{ $invoice['booking_no'] ?? '--' }}<br>
                        Invoice No: {{ $invoice['invoice_no'] ?? '--' }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>


@if(($isSharedView ?? false) === true || ($pdfPackageMissing ?? false) === true)
    <div class="print-actions">
        <button class="print-button" onclick="window.print()">
            Print / Save PDF
        </button>
    </div>
@endif
</body>
</html>