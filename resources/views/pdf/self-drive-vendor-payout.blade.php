<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Vendor Payout {{ $payout->payout_no }}
    </title>

    <style>
        @page {
            margin: 25px 28px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.4;
        }

        .header {
            border-bottom: 3px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .brand {
            font-size: 24px;
            font-weight: bold;
            color: #111827;
        }

        .subtitle {
            font-size: 13px;
            font-weight: bold;
            margin-top: 3px;
            color: #374151;
        }

        .right {
            text-align: right;
        }

        .header-table,
        .info-table,
        .summary-table,
        .booking-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .info-box {
            margin-bottom: 18px;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            padding: 10px;
        }

        .info-table td {
            padding: 4px 6px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            color: #6b7280;
            width: 20%;
        }

        .value {
            font-weight: bold;
            color: #111827;
            width: 30%;
        }

        .section-title {
            margin-top: 16px;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: bold;
            color: #111827;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        table.data-table th {
            background: #111827;
            color: white;
            padding: 7px 5px;
            border: 1px solid #111827;
            font-size: 8.5px;
            text-align: left;
        }

        table.data-table td {
            padding: 6px 5px;
            border: 1px solid #d1d5db;
            vertical-align: top;
            font-size: 8.5px;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
        }

        .status-partial {
            background: #fef3c7;
            color: #92400e;
        }

        .status-pending {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .total-box {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .totals-table {
            width: 48%;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 6px 7px;
        }

        .totals-table .total-label {
            font-weight: bold;
        }

        .totals-table .grand-total td {
            border-top: 2px solid #111827;
            border-bottom: 2px solid #111827;
            font-size: 12px;
            font-weight: bold;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .balance-row td {
            font-size: 12px;
            font-weight: bold;
        }

        .payment-box {
            margin-top: 18px;
            padding: 10px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            page-break-inside: avoid;
        }

        .notes {
            margin-top: 12px;
            padding: 9px;
            border: 1px solid #e5e7eb;
        }

        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            text-align: center;
            color: #6b7280;
            font-size: 8px;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    <div class="header">

        <table class="header-table">

            <tr>

                <td>
                    <div class="brand">
                        DURA CABS
                    </div>

                    <div class="subtitle">
                        Self Drive Vendor Payout Statement
                    </div>
                </td>

                <td class="right">

                    <strong>
                        {{ $payout->payout_no }}
                    </strong>

                    <br>

                    Generated:
                    {{ now()->format('d M Y, h:i A') }}

                </td>

            </tr>

        </table>

    </div>

    <div class="info-box">

        <table class="info-table">

            <tr>

                <td class="label">
                    Vendor
                </td>

                <td class="value">
                    {{ $vendorName }}
                </td>

                <td class="label">
                    Status
                </td>

                <td class="value">

                    <span class="status status-{{ $payout->status }}">
                        {{ strtoupper($payout->status) }}
                    </span>

                </td>

            </tr>

            <tr>

                <td class="label">
                    Period From
                </td>

                <td class="value">
                    {{ $payout->period_from?->format('d M Y') ?? '-' }}
                </td>

                <td class="label">
                    Period To
                </td>

                <td class="value">
                    {{ $payout->period_to?->format('d M Y') ?? '-' }}
                </td>

            </tr>

        </table>

    </div>

    <div class="section-title">
        Vehicle-wise Summary
    </div>

    <table class="data-table">

        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Registration</th>
                <th class="text-center">Bookings</th>
                <th class="text-center">Hours</th>
                <th class="text-center">24H Units</th>
                <th class="text-right">Customer Amount</th>
                <th class="text-right">Vendor Payout</th>
            </tr>
        </thead>

        <tbody>

            @forelse($vehicleSummary as $row)

                <tr>

                    <td>
                        <strong>
                            {{ $row['vehicle_name'] }}
                        </strong>
                    </td>

                    <td>
                        {{ $row['registration'] }}
                    </td>

                    <td class="text-center">
                        {{ $row['booking_count'] }}
                    </td>

                    <td class="text-center">
                        {{ $row['booked_hours'] }}
                    </td>

                    <td class="text-center">
                        <strong>
                            {{ $row['booking_units'] }}
                        </strong>
                    </td>

                    <td class="text-right">
                        ₹{{ number_format(
                            $row['customer_amount'],
                            2
                        ) }}
                    </td>

                    <td class="text-right">
                        <strong>
                            ₹{{ number_format(
                                $row['vendor_payout'],
                                2
                            ) }}
                        </strong>
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="7" class="text-center">
                        No payout items found.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

    <div class="section-title">
        Booking Details
    </div>

    <table class="data-table">

        <thead>

            <tr>
                <th>Booking</th>
                <th>Vehicle</th>
                <th>Rental Period</th>
                <th class="text-center">Hours</th>
                <th class="text-center">Units</th>
                <th class="text-right">24H Price</th>
                <th class="text-center">Comm.</th>
                <th class="text-right">Vendor / 24H</th>
                <th class="text-right">Payout</th>
            </tr>

        </thead>

        <tbody>

            @foreach($payout->items as $item)

                @php
                    $vehicleName = trim(
                        ($item->vehicle?->car_company_name ?? '')
                        . ' '
                        . ($item->vehicle?->model_name ?? '')
                    );

                    if ($vehicleName === '') {
                        $vehicleName =
                            'Vehicle #' . $item->vehicle_id;
                    }

                    $registration =
                        $item->vehicle?->registration_number
                        ?? $item->vehicle?->vehicle_number
                        ?? '-';
                @endphp

                <tr>

                    <td>
                        <strong>
                            {{
                                $item->booking?->booking_no
                                ?? ('#' . $item->self_drive_booking_id)
                            }}
                        </strong>
                    </td>

                    <td>
                        {{ $vehicleName }}

                        <br>

                        <small>
                            {{ $registration }}
                        </small>
                    </td>

                    <td>

                        {{
                            $item->start_datetime
                                ?->format('d M y h:i A')
                            ?? '-'
                        }}

                        <br>

                        to

                        <br>

                        {{
                            $item->end_datetime
                                ?->format('d M y h:i A')
                            ?? '-'
                        }}

                    </td>

                    <td class="text-center">
                        {{ $item->booked_hours }}
                    </td>

                    <td class="text-center">
                        <strong>
                            {{ $item->booking_units }}
                        </strong>
                    </td>

                    <td class="text-right">
                        ₹{{ number_format(
                            (float) $item->customer_daily_rate,
                            2
                        ) }}
                    </td>

                    <td class="text-center">
                        {{
                            number_format(
                                (float) $item->commission_percentage,
                                2
                            )
                        }}%
                    </td>

                    <td class="text-right">
                        ₹{{ number_format(
                            (float) $item->vendor_rate_per_24h,
                            2
                        ) }}
                    </td>

                    <td class="text-right">

                        <strong>
                            ₹{{ number_format(
                                (float) $item->payout_amount,
                                2
                            ) }}
                        </strong>

                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

    <div class="total-box">

        <table class="totals-table">

            <tr>
                <td class="total-label">
                    Total Bookings
                </td>

                <td class="text-right">
                    {{ $payout->items->count() }}
                </td>
            </tr>

            <tr>
                <td class="total-label">
                    Total 24H Units
                </td>

                <td class="text-right">
                    {{ $payout->total_booking_units }}
                </td>
            </tr>

            <tr>
                <td class="total-label">
                    Customer Booking Amount
                </td>

                <td class="text-right">
                    ₹{{ number_format(
                        (float) $payout->gross_booking_amount,
                        2
                    ) }}
                </td>
            </tr>

            <tr class="grand-total">
                <td>
                    Vendor Payout
                </td>

                <td class="text-right">
                    ₹{{ number_format(
                        (float) $payout->payout_amount,
                        2
                    ) }}
                </td>
            </tr>

            <tr>
                <td class="total-label">
                    Paid Amount
                </td>

                <td class="text-right">
                    ₹{{ number_format(
                        (float) $payout->paid_amount,
                        2
                    ) }}
                </td>
            </tr>

            <tr class="balance-row">
                <td>
                    Balance Payable
                </td>

                <td class="text-right">
                    ₹{{ number_format(
                        (float) $payout->remaining_amount,
                        2
                    ) }}
                </td>
            </tr>

        </table>

    </div>

    @if(
        $payout->payment_method
        || $payout->payment_reference
        || $payout->paid_at
    )

        <div class="payment-box">

            <strong>
                Payment Information
            </strong>

            <br><br>

            Payment Method:
            <strong>
                {{ strtoupper(
                    str_replace(
                        '_',
                        ' ',
                        $payout->payment_method ?? '-'
                    )
                ) }}
            </strong>

            <br>

            Transaction / UTR:
            <strong>
                {{ $payout->payment_reference ?: '-' }}
            </strong>

            <br>

            Payment Date:
            <strong>
                {{
                    $payout->paid_at
                        ?->format('d M Y, h:i A')
                    ?? '-'
                }}
            </strong>

        </div>

    @endif

    @if(filled($payout->notes))

        <div class="notes">

            <strong>
                Notes
            </strong>

            <br>

            {!! nl2br(e($payout->notes)) !!}

        </div>

    @endif

    <div class="footer">
        This is a computer-generated vendor payout statement from Dura Cabs.
    </div>

</body>
</html>