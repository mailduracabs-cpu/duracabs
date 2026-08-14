<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self Drive Rental Agreement - {{ $agreement['booking_no'] ?? 'Booking' }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #f3f6f9;
            color: #111827;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.45;
        }

        .agreement {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 28px;
            background: #ffffff;
            border: 1px solid #dfe6ee;
        }

        .top-bar {
            height: 8px;
            margin: -28px -28px 24px;
            background: #009ffd;
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

        .agreement-list li {
            margin-bottom: 6px;
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

            .agreement {
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
    $money = static function ($value): string {
        return '₹' . number_format((float) ($value ?? 0), 2);
    };
@endphp

<div class="agreement">
    <div class="top-bar"></div>

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

@if(($isSharedView ?? false) === true || ($pdfPackageMissing ?? false) === true)
    <div class="print-actions">
        <button class="print-button" onclick="window.print()">
            Print / Save PDF
        </button>
    </div>
@endif
</body>
</html>