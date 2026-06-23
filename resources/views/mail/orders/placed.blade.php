<x-mail::message>
# Order Places Successfully!


Thank you for choosing Dura Cabs Services!

We are pleased to inform you that your booking has been successfully received. Your Booking ID is  {{ $order->id}}. Our team will reach out to you shortly via call or message to confirm the details and assist with any questions you may have.

For more information or immediate assistance, feel free to contact us at 0562-4069936.

You can also check your booking status anytime in your customer panel.

We look forward to serving you and hope you have a great experience with us!



<x-mail::button :url="$url">
Login
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
