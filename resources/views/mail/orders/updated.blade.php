<x-mail::message>
# Booking {{$order['status']}}


Dear {{$address->full_name}},

@if ($order['status'] === 'reconfirmation')

We are writing to reconfirm your booking with Dura Cabs Services. All details remain the same, and your reservation is secured. Below are the reconfirmed details:

@endif

@if ($order['status'] === 'confirm')

We are pleased to inform you that your booking has been confirmed. Below are the details of your confirmed service:
@endif

@if ($order['status'] === 'modification')

We would like to confirm that your booking has been successfully modified as per your request. Please find the updated details below:
@endif

@if ($order['status'] === 'start')

We are delighted to inform you that your trip has officially started. Here are the details for your reference:
@endif

@if ($order['status'] === 'new')

We are writing to reconfirm your booking with Dura Cabs Services. All details remain the same, and your reservation is secured. Below are the reconfirmed details:

@endif
@if ($order['status'] === 'cancelled')

Thank you for taking the time to consider our services. We understand that you are not interested at this time, and we fully respect your decision.


Should your needs change or if you have any questions in the future, please don't hesitate to reach out. We’re always here to assist you.

Wishing you the best, and thank you again for considering Dura Cabs Services.
@endif

@if ($order['status'] === 'closed')

We are pleased to inform you that your booking with Dura Cabs Services has been successfully completed. Here are the details for your reference:
@endif


@if ($order['status'] === 'start')

            Booking Id      : {{$order['id']}}
            Date & Time     : {{$order['date']}} & {{$order['time']}}
            Trip Type       : {{ $order['ride_type']}}
            Driver’s Name   : {{ $driver['name']}}
            Driver’s Number : {{ $driver['mobile']}}
            Vehicle Details : {{ $vehicle->vehicle_number}}
            Vehicle Details : {{ $vehicle->car_company_name}}
            

           
Our driver will be in touch with you shortly to confirm pickup and ensure a smooth start to your journey. Should you need any assistance during the trip or have any questions, please do not hesitate to contact us at 0562-123456 or reply to this email.

@else

            Booking Id      : {{$order['id']}}
            Date & Time     : {{$order['date']}} & {{$order['time']}}
            Pickup Location : {{ $address->pickup_address}}
            Drop Location   : {{ $address->drop_address}}
            Vehicle Type    : {{ $order['productName']}}
            Trip Type       : {{ $order['ride_type']}}
            Grand Total     : INR {{ $order['grand_total']}} 
    
@endif
           

If you have any changes or questions, please feel free to contact us at 0562-4069936 or reply to this email.

Thank you for choosing us, and we look forward to serving you!



<x-mail::button :url="$url">
Button Text
</x-mail::button>

Best regards,<br>
Dura Cabs Services Team
</x-mail::message>
