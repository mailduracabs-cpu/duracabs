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
        <h3 class="card-header p-3">Duracabs Payments For Order ID #<?php echo e($id); ?></h3>
        <div class="card-body">

            <?php $__sessionArgs = ['error'];
if (session()->has($__sessionArgs[0])) :
if (isset($value)) { $__sessionPrevious[] = $value; }
$value = session()->get($__sessionArgs[0]); ?>
                <div class="alert alert-danger" role="alert"> 
                    <?php echo e($value); ?>

                </div>
            <?php unset($value);
if (isset($__sessionPrevious) && !empty($__sessionPrevious)) { $value = array_pop($__sessionPrevious); }
if (isset($__sessionPrevious) && empty($__sessionPrevious)) { unset($__sessionPrevious); }
endif;
unset($__sessionArgs); ?>

            <?php $__sessionArgs = ['success'];
if (session()->has($__sessionArgs[0])) :
if (isset($value)) { $__sessionPrevious[] = $value; }
$value = session()->get($__sessionArgs[0]); ?>
                <div class="alert alert-success" role="alert"> 
                    <?php echo e($value); ?>

                </div>
            <?php unset($value);
if (isset($__sessionPrevious) && !empty($__sessionPrevious)) { $value = array_pop($__sessionPrevious); }
if (isset($__sessionPrevious) && empty($__sessionPrevious)) { unset($__sessionPrevious); }
endif;
unset($__sessionArgs); ?>

<div class="lg:flex justify-around ">
 <form action="<?php echo e(route('razorpay.payment.store')); ?>" method="POST" class="text-center">
                <?php echo csrf_field(); ?>
                <script src="https://checkout.razorpay.com/v1/checkout.js"
                        data-key="<?php echo e(env('RAZORPAY_API_KEY')); ?>"
                        data-amount="<?php echo e(round($amount) *100); ?>"
                        data-buttontext="Pay 100% Ammount <?php echo e(round($amount)); ?> Now"
                        data-name="<?php echo e(env('APP_NAME')); ?>"
                        data-description="<?php echo e($id); ?>"
                        data-image="https://cdn.razorpay.com/logos/NSL3kbRT73axfn_medium.png"
                        data-prefill.name="<?php echo e($name); ?>"
                        data-prefill.email="<?php echo e($email); ?>"
                        data-theme.color="#1e9cfd">
                </script>
            </form>
            <form action="<?php echo e(route('razorpay.payment.store')); ?>" method="POST" class="text-center">
                <?php echo csrf_field(); ?>
                <script src="https://checkout.razorpay.com/v1/checkout.js"
                        data-key="<?php echo e(env('RAZORPAY_API_KEY')); ?>"
                        data-amount="<?php echo e((round($amount / 2) / 2) *100); ?>"
                        data-buttontext="Pay 50% Amount <?php echo e(round($amount / 2)); ?> Now"
                        data-name="<?php echo e(env('APP_NAME')); ?>"
                        data-description="<?php echo e($id); ?>"
                        data-image="https://cdn.razorpay.com/logos/NSL3kbRT73axfn_medium.png"
                        data-prefill.name="<?php echo e($name); ?>"
                        data-prefill.email="<?php echo e($email); ?>"
                        data-theme.color="#1e9cfd">
                </script>

                
            </form>
            <form action="<?php echo e(route('razorpay.payment.store')); ?>" method="POST" class="text-center">
                <?php echo csrf_field(); ?>
                <script src="https://checkout.razorpay.com/v1/checkout.js"
                        data-key="<?php echo e(env('RAZORPAY_API_KEY')); ?>"
                        data-amount="<?php echo e(round((15 / 100) * $amount)* 100); ?>"
                        data-buttontext="Pay 15% Amount <?php echo e(round((15 / 100) * $amount)); ?> Now"
                        data-name="<?php echo e(env('APP_NAME')); ?>"
                        data-description="<?php echo e($id); ?>"
                        data-image="https://cdn.razorpay.com/logos/NSL3kbRT73axfn_medium.png"
                        data-prefill.name="<?php echo e($name); ?>"
                        data-prefill.email="<?php echo e($email); ?>"
                        data-theme.color="#1e9cfd">
                </script>

                
            </form>
</div>
           
        </div>
    </div>
</div>
</body>
</html>
<?php /**PATH /var/www/duracabs/duracabs/resources/views/livewire/razore-pay.blade.php ENDPATH**/ ?>