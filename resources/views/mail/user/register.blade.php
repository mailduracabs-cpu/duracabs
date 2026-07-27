<x-mail::message>
# User Registered Succefully 

Dear {{$user->name}},

We are happy to inform you that your registration request has been approved. Below are your login details:

User ID: duracabs@gmail.com


If you have any questions or need assistance, feel free to contact our support team.

<x-mail::button :url="$url">
Click here to log in.
</x-mail::button>


Best regards,<br>
Dura Cabs Services Team
</x-mail::message>
