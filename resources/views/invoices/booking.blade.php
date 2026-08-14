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


        .agreement-page {
            page-break-before: always;
            break-before: page;
            padding: 28px;
            color: #111827;
            background: #ffffff;
        }

        .agreement-title {
            margin: 0 0 16px;
            text-align: center;
            font-size: 22px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .agreement-subtitle {
            margin: 18px 0 8px;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .agreement-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .agreement-table td {
            padding: 6px 8px;
            border: 1px solid #dfe6ee;
            vertical-align: top;
        }

        .agreement-label {
            width: 24%;
            font-weight: 700;
            background: #f8fafc;
        }

        .agreement-text {
            margin: 0 0 10px;
            text-align: justify;
        }

        .agreement-list {
            margin: 0 0 10px;
            padding-left: 18px;
        }

        .agreement-signatures {
            width: 100%;
            margin-top: 34px;
            border-collapse: collapse;
        }

        .agreement-signatures td {
            width: 50%;
            padding-top: 42px;
            vertical-align: bottom;
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
    $agreement = $invoice['agreement'] ?? [];

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

                    <tr>
                        <td>
                            Taxable Amount
                            <span style="color: #69778a;">
                                {{ $isSelfDrive ? '(Rent + Extras - Coupon)' : '(Base Fare + Extras + Extra Hours + Extra KM)' }}
                            </span>
                        </td>
                        <td>{{ $money($taxableAmount) }}</td>
                    </tr>

                    @if($showRow($gstAmount))
                        <tr>
                            <td>GST @ {{ number_format($gstRate, 2) }}%</td>
                            <td>{{ $money($gstAmount) }}</td>
                        </tr>
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
                        <td>{{ $money($fare['paid_amount'] ?? 0) }}</td>
                    </tr>

                    @if($showRow($fare['remaining_amount'] ?? 0))
                        <tr class="balance-row">
                            <td>Remaining Amount</td>
                            <td>{{ $money($fare['remaining_amount']) }}</td>
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
                    <li>Self Drive GST rate is 18% where applicable.</li>
                    <li>If a manual price is used, GST is included within that manual price and is not added again.</li>
                    <li>Security deposit is shown separately when applicable.</li>
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

@if($isSelfDrive)
    <div class="invoice agreement-page">
        <h1 class="agreement-title">SELF DRIVE CAR RENTAL AGREEMENT</h1>

        <p class="agreement-text">
            This Self Drive Car Rental Agreement is entered into between
            <strong>Dura Cabs Services – Shop No.16 Kripadham Complax, Tajganj Agra-282001</strong>
            (“Owner”) and
            <strong>{{ $agreement['renter_name'] ?? '____________________________' }}</strong>
            (“Renter”) (collectively the “Parties”) and outlines the respective rights and obligations
            of the Parties relating to the rental of a car.
        </p>

        <h2 class="agreement-subtitle">CUSTOMER DETAIL</h2>
        <table class="agreement-table">
            <tr>
                <td class="agreement-label">Name</td>
                <td>{{ $agreement['renter_name'] ?? '' }}</td>
                <td class="agreement-label">ID Number</td>
                <td>{{ $agreement['id_number'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="agreement-label">Address</td>
                <td colspan="3">{{ $agreement['address'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="agreement-label">Hotel</td>
                <td>{{ $agreement['hotel'] ?? '' }}</td>
                <td class="agreement-label">Room No.</td>
                <td>{{ $agreement['room_no'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="agreement-label">Mobile Number</td>
                <td>{{ $agreement['mobile'] ?? '' }}</td>
                <td class="agreement-label">Alternate Mobile</td>
                <td>{{ $agreement['secondary_mobile'] ?? '' }}</td>
            </tr>
        </table>

        <h2 class="agreement-subtitle">1. IDENTIFICATION OF THE RENTAL VEHICLE</h2>
        <p class="agreement-text">
            Owner hereby agrees to rent to Renter a passenger vehicle identified as follows
            (hereinafter referred to as “Rental Vehicle”).
        </p>
        <table class="agreement-table">
            <tr>
                <td class="agreement-label">Car Number</td>
                <td>{{ $agreement['car_number'] ?? '' }}</td>
                <td class="agreement-label">Car Name</td>
                <td>{{ $agreement['car_name'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="agreement-label">Color</td>
                <td>{{ $agreement['car_color'] ?? '' }}</td>
                <td class="agreement-label">Booking No.</td>
                <td>{{ $agreement['booking_no'] ?? '' }}</td>
            </tr>
        </table>

        <h2 class="agreement-subtitle">2. RENTAL TERM</h2>
        <table class="agreement-table">
            <tr>
                <td class="agreement-label">Trip Plan</td>
                <td colspan="3">{{ $agreement['trip_plan'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="agreement-label">Estimated Start Date</td>
                <td>{{ $agreement['start_date'] ?? '' }}</td>
                <td class="agreement-label">Time</td>
                <td>{{ $agreement['start_time'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="agreement-label">Estimated End Date</td>
                <td>{{ $agreement['end_date'] ?? '' }}</td>
                <td class="agreement-label">Time</td>
                <td>{{ $agreement['end_time'] ?? '' }}</td>
            </tr>
        </table>

        <p class="agreement-text">
            This travel plan is given by the tenant which ensures that the car will not be taken anywhere
            other than the mentioned travel plan. If the car has to be taken anywhere then the owner has to
            inform about it. In case of not giving information, the company will have to pay a fine of Rs.1000
            to the tenant.
        </p>

        <p class="agreement-text">
            The term of this Car Rental Agreement runs from the date and hour of vehicle pickup as indicated
            just above the signature line at the bottom of this agreement until the return of the vehicle to
            Owner, and completion of all terms of this agreement by both Parties. The parties may, with mutual
            consent, reduce or extend the estimated period of the rental. But this information has to be given
            to the tenant one day before the expected date of travel.
        </p>

        <h2 class="agreement-subtitle">3. SCOPE OF USE</h2>
        <p class="agreement-text">
            The renter will use the Rented Vehicle only for personal or routine business use, and operate the
            Rented Vehicle only on properly maintained roads and parking lots. The renter will comply with all
            applicable laws relating to holding licensure to operate the vehicle and pertaining to the operation
            of motor vehicles. The renter will not sublease the Rental Vehicle or use it as a vehicle for hire.
        </p>
        <p class="agreement-text">
            Renter will not allow any other person to operate the Rented Vehicle unless identified here:
        </p>
        <table class="agreement-table">
            <tr>
                <td class="agreement-label">Primary Vehicle Operator</td>
                <td>{{ $agreement['primary_operator'] ?? '' }}</td>
                <td class="agreement-label">Mobile</td>
                <td>{{ $agreement['operator_mobile'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="agreement-label">DL #</td>
                <td colspan="3">{{ $agreement['driving_licence_number'] ?? '' }}</td>
            </tr>
        </table>

        <h2 class="agreement-subtitle">5. RENTAL FEES</h2>
        <table class="agreement-table">
            <tr>
                <td class="agreement-label">Booking Amount</td>
                <td>{{ $money($agreement['booking_amount'] ?? 0) }}</td>
            </tr>
        </table>

        <h2 class="agreement-subtitle">6. SECURITY DEPOSIT</h2>
        <p class="agreement-text">
            The Renter will be required to provide the Owner with a security deposit in the amount of
            <strong>{{ $money($agreement['security_deposit'] ?? 0) }}</strong> (“Security Deposit”) to be used
            in the event of loss or damage to the Rental Vehicle during the term of this Agreement. The Owner
            may, in lieu of the collection of the Security Deposit, place a hold on a credit card in the same
            amount. In the event of damage to the Rental Vehicle, the Owner will apply this Security Deposit to
            defray the cost of necessary repairs or replacement. If the cost of repair or replacement of the
            damage to the Rental Vehicle exceeds the amount of the Security Deposit, the Renter will be
            responsible for paying the remainder of this cost to the Owner. If any repairs or replacement of the
            Vehicle are required, this will be performed at the car’s official brand service center and not by a
            local vendor.
        </p>

        <h2 class="agreement-subtitle">7. INSURANCE</h2>
        <p class="agreement-text">
            If the rented vehicle is damaged or destroyed while in the possession of the renter, the renter
            agrees to make any necessary payments and assigns all rights to the owner to recover the amount.
            But the renter cannot use the car insurance unless the car is damaged to the extent of at least
            Rs 50,000/-. If the loss due to the damage caused to the car is less than Rs 50,000/-, the renter
            will have to pay out of his own pocket.
        </p>

        <h2 class="agreement-subtitle">8. INDEMNIFICATION</h2>
        <ul class="agreement-list">
            <li>
                The Renter agrees to indemnify, defend and hold the Owner harmless for any loss, damage or legal
                action brought against the Owner as a result of the operation or use of the rented vehicle by the
                Renter during the term of this Car Rental Agreement. This includes any attorney's fees required
                for these purposes. The Renter will also pay any tolls, parking, challans, traffic violations or
                other expenses incurred during the possession of the rented vehicle.
            </li>
            <li>
                If any illegal activity is done by the renter with or without the car after renting the car, the
                customer will be fully responsible for the same. It will have no connection with the company Dura
                Cabs Services and any expenses or fines incurred during the activity will have to be paid by the
                customer and there will be no connection with any illegal activity or legal proceedings between
                the company and the car.
            </li>
            <li>
                This car is offered to the customer to enjoy traveling and touring with family and friends. The
                customer cannot do any commercial activity with it. Nor can he mortgage the car to any company
                or individual as security. If he is found doing so, the car will be taken back from the customer
                and the customer will also have to pay a fine of Rs 50,000.
            </li>
        </ul>

        <h2 class="agreement-subtitle">9. REPRESENTATIONS AND WARRANTIES</h2>
        <ul class="agreement-list">
            <li>
                Owner represents and warrants that to Owner’s knowledge, the Rental Vehicle is in good condition
                and is safe for ordinary operation of the vehicle.
            </li>
            <li>
                Renter represents and warrants that Renter is legally entitled to operate a motor vehicle under
                the laws of this jurisdiction and will not operate it in violation of any laws, or in any negligent
                or illegal manner.
            </li>
            <li>
                Renter has been given an opportunity to examine the Rental Vehicle in advance of taking possession
                of it, and upon such inspection, is not aware of any damage existing on the vehicle other than
                that notated by separate Existing Damage document.
            </li>
        </ul>

        <h2 class="agreement-subtitle">10. JURISDICTION AND VENUE</h2>
        <p class="agreement-text">
            In the event of any dispute arising out of this Agreement, this Car Rental Agreement shall be governed
            by the laws of the State of Uttar Pradesh, and any litigation or arbitration shall be brought under the
            jurisdiction of the State of Agra, Uttar Pradesh. If any part of this Agreement is found by a court of
            competent jurisdiction to be unenforceable, the remaining part of this Agreement shall still have full
            force and effect.
        </p>

        <h2 class="agreement-subtitle">11. ENTIRE AGREEMENT</h2>
        <p class="agreement-text">
            This Car Rental Agreement constitutes the entire agreement between the Parties with respect to this
            rental arrangement. No modification to this agreement can be made unless in writing signed by both
            Parties. Any notice required to be given to the other party will be made to the contact information below.
        </p>

        <ul class="agreement-list">
            <li>If the tenant drives the car at a speed more than the prescribed speed of 100 km, then he will have to pay a fine of Rs 1000.</li>
            <li>If the renter uses the car for more than the stipulated time, he/she will have to pay additional car rent per hour which is as follows: hatchback: Rs. 250 per hour, Sedan: Rs. 300 per hour and SUV Rs. 400 per hour.</li>
            <li>If the tenant drives the car more than the prescribed 300 kilometres per day, he will have to pay at the rate of Rs 7 per kilometre.</li>
            <li>Please note that petrol and Fastag money will not be refunded. Please recharge only as much as you need to use.</li>
        </ul>

        <table class="agreement-table">
            <tr>
                <td class="agreement-label">TIME OF VEHICLE PICKUP</td>
                <td>Date: {{ $agreement['pickup_date'] ?? '' }} &nbsp; Time: {{ $agreement['pickup_time'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="agreement-label">TIME OF VEHICLE RETURN</td>
                <td>Date: {{ $agreement['return_date'] ?? '' }} &nbsp; Time: {{ $agreement['return_time'] ?? '' }}</td>
            </tr>
        </table>

        <table class="agreement-signatures">
            <tr>
                <td>
                    Date: ___________________________<br><br>
                    Renter Signature: ___________________________
                </td>
                <td style="text-align:right;">
                    Authorized Signatory<br><br>
                    Dura Cabs Services: ___________________________
                </td>
            </tr>
        </table>
    </div>
@endif

@if(($isSharedView ?? false) === true || ($pdfPackageMissing ?? false) === true)
    <div class="print-actions">
        <button class="print-button" onclick="window.print()">
            Print / Save PDF
        </button>
    </div>
@endif
</body>
</html>