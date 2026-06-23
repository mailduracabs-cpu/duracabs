<!DOCTYPE html >
<html  class="scroll-smooth focus:scroll-auto" lang="en" >

<head>
    <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0 maximum-scale=1, user-scalable=0">
     <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
       <link rel="canonical" href="<?php echo e(url()->current()); ?>">


</head>

<body class="bg-white dark:bg-slate-700">


    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('partials.navbar');

$__html = app('livewire')->mount($__name, $__params, 'lw-2412853951-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
    <main>
       
<div class="lg:mx-10 mx-2 my-4 ">
  <?php $__env->startSection('title', '404 - Page Not Found'); ?>
  
<div class=" flex justify-center items-center">
<img src="/img/404page.png" width="50%"  alt="Duracabs 404 Page"/> <br/>

</div>
<div class="text-center mt-6">
<h1 class="text-2xl">404 Page Not Found</h1>
<h2>You Will Redirect To homepage</h2>
</div>
<div class="flex justify-center items-center">
 <a wire:navigate
                            class="font-medium bg-sky-700 rounded-2xl text-white hover:text-black hover:bg-slate-400 px-4 py-3 md:py-3 mt-6 dark:text-blue-500 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/" aria-current="page">Back To Home</a>   
</div>
  


</div>
    </main>
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('partials.footer');

$__html = app('livewire')->mount($__name, $__params, 'lw-2412853951-1', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

   
   
</body>

</html>




<?php /**PATH /var/www/duracabs/duracabs/resources/views/errors/404.blade.php ENDPATH**/ ?>