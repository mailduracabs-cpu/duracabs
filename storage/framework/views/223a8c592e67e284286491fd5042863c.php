<?php if (isset($component)) { $__componentOriginalaa758e6a82983efcbf593f765e026bd9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa758e6a82983efcbf593f765e026bd9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Illuminate\View\Factory::class)->make('mail::message'),'data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mail::message'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
# Booking <?php echo e($order['status']); ?>



Dear <?php echo e($address->full_name); ?>,

<?php if($order['status'] === 'reconfirmation'): ?>

We are writing to reconfirm your booking with Dura Cabs Services. All details remain the same, and your reservation is secured. Below are the reconfirmed details:

<?php endif; ?>

<?php if($order['status'] === 'confirm'): ?>

We are pleased to inform you that your booking has been confirmed. Below are the details of your confirmed service:
<?php endif; ?>

<?php if($order['status'] === 'modification'): ?>

We would like to confirm that your booking has been successfully modified as per your request. Please find the updated details below:
<?php endif; ?>

<?php if($order['status'] === 'start'): ?>

We are delighted to inform you that your trip has officially started. Here are the details for your reference:
<?php endif; ?>

<?php if($order['status'] === 'new'): ?>

We are writing to reconfirm your booking with Dura Cabs Services. All details remain the same, and your reservation is secured. Below are the reconfirmed details:

<?php endif; ?>
<?php if($order['status'] === 'cancelled'): ?>

Thank you for taking the time to consider our services. We understand that you are not interested at this time, and we fully respect your decision.


Should your needs change or if you have any questions in the future, please don't hesitate to reach out. We’re always here to assist you.

Wishing you the best, and thank you again for considering Dura Cabs Services.
<?php endif; ?>

<?php if($order['status'] === 'closed'): ?>

We are pleased to inform you that your booking with Dura Cabs Services has been successfully completed. Here are the details for your reference:
<?php endif; ?>


<?php if($order['status'] === 'start'): ?>

            Booking Id      : <?php echo e($order['id']); ?>

            Date & Time     : <?php echo e($order['date']); ?> & <?php echo e($order['time']); ?>

            Trip Type       : <?php echo e($order['ride_type']); ?>

            Driver’s Name   : <?php echo e($driver['name']); ?>

            Driver’s Number : <?php echo e($driver['mobile']); ?>

            Vehicle Details : <?php echo e($vehicle->vehicle_number); ?>

            Vehicle Details : <?php echo e($vehicle->car_company_name); ?>

            

           
Our driver will be in touch with you shortly to confirm pickup and ensure a smooth start to your journey. Should you need any assistance during the trip or have any questions, please do not hesitate to contact us at 0562-123456 or reply to this email.

<?php else: ?>

            Booking Id      : <?php echo e($order['id']); ?>

            Date & Time     : <?php echo e($order['date']); ?> & <?php echo e($order['time']); ?>

            Pickup Location : <?php echo e($address->pickup_address); ?>

            Drop Location   : <?php echo e($address->drop_address); ?>

            Vehicle Type    : <?php echo e($order['productName']); ?>

            Trip Type       : <?php echo e($order['ride_type']); ?>

            Grand Total     : INR <?php echo e($order['grand_total']); ?> 
    
<?php endif; ?>
           

If you have any changes or questions, please feel free to contact us at 0562-4069936 or reply to this email.

Thank you for choosing us, and we look forward to serving you!



<?php if (isset($component)) { $__componentOriginal15a5e11357468b3880ae1300c3be6c4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal15a5e11357468b3880ae1300c3be6c4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Illuminate\View\Factory::class)->make('mail::button'),'data' => ['url' => $url]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mail::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($url)]); ?>
Button Text
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal15a5e11357468b3880ae1300c3be6c4f)): ?>
<?php $attributes = $__attributesOriginal15a5e11357468b3880ae1300c3be6c4f; ?>
<?php unset($__attributesOriginal15a5e11357468b3880ae1300c3be6c4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal15a5e11357468b3880ae1300c3be6c4f)): ?>
<?php $component = $__componentOriginal15a5e11357468b3880ae1300c3be6c4f; ?>
<?php unset($__componentOriginal15a5e11357468b3880ae1300c3be6c4f); ?>
<?php endif; ?>

Best regards,<br>
Dura Cabs Services Team
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa758e6a82983efcbf593f765e026bd9)): ?>
<?php $attributes = $__attributesOriginalaa758e6a82983efcbf593f765e026bd9; ?>
<?php unset($__attributesOriginalaa758e6a82983efcbf593f765e026bd9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa758e6a82983efcbf593f765e026bd9)): ?>
<?php $component = $__componentOriginalaa758e6a82983efcbf593f765e026bd9; ?>
<?php unset($__componentOriginalaa758e6a82983efcbf593f765e026bd9); ?>
<?php endif; ?>
<?php /**PATH /var/www/duracabs/duracabs/resources/views/mail/orders/updated.blade.php ENDPATH**/ ?>