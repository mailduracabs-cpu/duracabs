<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
  <div class="container mx-auto px-4">
    <h1 class="text-2xl font-semibold mb-4">Package Overview</h1>
    <div class="flex flex-col md:flex-row gap-4">
      <div class="md:w-3/4">
        <div class="bg-white overflow-x-auto rounded-lg shadow-md p-6 mb-4">
          <table class="w-full">
            <thead>
              <tr>
                <th class="text-left font-semibold">Package</th>
                <th class="text-left font-semibold">Price</th>
                
                <th class="text-left font-semibold">Total</th>
                <th class="text-left font-semibold">Remove</th>
              </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $cart_items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

           <tr wire:key='<?php echo e($item['product_id']); ?>'>
                <td class="py-4">
                  <div class="flex items-center">
                    <img class="h-16 w-16 mr-4" src="<?php echo e(url('storage')); ?>/<?php echo e($item['image']); ?>" alt="<?php echo e($item['name']); ?>">
                    <span class="font-semibold"><?php echo e($item['name']); ?></span>
                  </div>
                </td>
                <td class="py-4"><?php echo e(Number::currency($item['unit_ammount'], 'INR')); ?></td>
                
                <td class="py-4"><?php echo e(Number::currency($item['total_ammount'], 'INR')); ?></td>
                <td><button wire:click='removeItem(<?php echo e($item['product_id']); ?>)' class="bg-slate-300 border-2 border-slate-400 rounded-lg px-3 py-1 hover:bg-red-500 hover:text-white hover:border-red-700">
                <span wire:loading.remove wire.target='removeItem(<?php echo e($item['product_id']); ?>)'>Remove</span>
                <span wire:loading wire.target='removeItem(<?php echo e($item['product_id']); ?>)'>Removing..</span>
                
                </button></td>
              </tr>
              
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

            <tr>
              <td colspan='5' class='text-center py-4 text-4xl text-semibold text-slate-500'>No items available in cart!</td>
            </tr>
              
            <?php endif; ?>
              
              <!-- More product rows -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="md:w-1/4">
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-lg font-semibold mb-4">Summary</h2>
          <div class="flex justify-between mb-2">
            <span>Subtotal</span>
            <span><?php echo e(Number::currency($grand_total, 'INR')); ?></span>
          </div>
          <div class="flex justify-between mb-2">
            <span>Taxes</span>
            <span><?php echo e(Number::currency(0, 'INR')); ?></span>
          </div>
          <div class="flex justify-between mb-2">
            <span>Shipping</span>
            <span><?php echo e(Number::currency(0, 'INR')); ?></span>
          </div>
          <hr class="my-2">
          <div class="flex justify-between mb-2">
            <span class="font-semibold">Total</span>
            <span class="font-semibold"><?php echo e(Number::currency($grand_total, 'INR')); ?></span>
          </div>
          <?php if($cart_items): ?>
             <a href="/checkout" class="bg-blue-500 block text-center text-white py-2 px-4 rounded-lg mt-4 w-full">Checkout</a>
          <?php endif; ?>
         
        </div>
      </div>
    </div>
  </div>
</div><?php /**PATH /var/www/duracabs/duracabs/resources/views/livewire/cart-page.blade.php ENDPATH**/ ?>