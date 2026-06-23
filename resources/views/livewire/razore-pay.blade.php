<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel - Razorpay Payment Gateway Integration</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body>

<div class="container">

    <div class="card mt-5">
        <h3 class="card-header p-3">Duracabs Payments For Order ID #{{$id}}</h3>
        <div class="card-body">

            @session('error')
                <div class="alert alert-danger" role="alert"> 
                    {{ $value }}
                </div>
            @endsession

            @session('success')
                <div class="alert alert-success" role="alert"> 
                    {{ $value }}
                </div>
            @endsession

<div class="lg:flex justify-around ">
 <form action="{{ route('razorpay.payment.store') }}" method="POST" class="text-center">
                @csrf
                <script src="https://checkout.razorpay.com/v1/checkout.js"
                        data-key="{{ env('RAZORPAY_API_KEY') }}"
                        data-amount="{{round($amount) *100}}"
                        data-buttontext="Pay 100% Ammount {{round($amount)}} Now"
                        data-name="{{ env('APP_NAME') }}"
                        data-description="{{$id}}"
                        data-image="https://cdn.razorpay.com/logos/NSL3kbRT73axfn_medium.png"
                        data-prefill.name="{{$name}}"
                        data-prefill.email="{{$email}}"
                        data-theme.color="#1e9cfd">
                </script>
            </form>
            <form action="{{ route('razorpay.payment.store') }}" method="POST" class="text-center">
                @csrf
                <script src="https://checkout.razorpay.com/v1/checkout.js"
                        data-key="{{ env('RAZORPAY_API_KEY') }}"
                        data-amount="{{(round($amount / 2) / 2) *100}}"
                        data-buttontext="Pay 50% Amount {{round($amount / 2)}} Now"
                        data-name="{{ env('APP_NAME') }}"
                        data-description="{{$id}}"
                        data-image="https://cdn.razorpay.com/logos/NSL3kbRT73axfn_medium.png"
                        data-prefill.name="{{$name}}"
                        data-prefill.email="{{$email}}"
                        data-theme.color="#1e9cfd">
                </script>

                
            </form>
            <form action="{{ route('razorpay.payment.store') }}" method="POST" class="text-center">
                @csrf
                <script src="https://checkout.razorpay.com/v1/checkout.js"
                        data-key="{{ env('RAZORPAY_API_KEY') }}"
                        data-amount="{{round((15 / 100) * $amount)* 100 }}"
                        data-buttontext="Pay 15% Amount {{round((15 / 100) * $amount) }} Now"
                        data-name="{{ env('APP_NAME') }}"
                        data-description="{{$id}}"
                        data-image="https://cdn.razorpay.com/logos/NSL3kbRT73axfn_medium.png"
                        data-prefill.name="{{$name}}"
                        data-prefill.email="{{$email}}"
                        data-theme.color="#1e9cfd">
                </script>

                
            </form>
</div>
           
        </div>
    </div>
</div>
</body>
</html>
