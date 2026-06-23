<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url>
<loc><?php echo e(url("/")); ?>/</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>1.00</priority>
</url>
<url>
<loc><?php echo e(url("/")); ?>/about-us</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>0.80</priority>
</url>
<url>
<loc><?php echo e(url("/")); ?>/terms-and-conditions</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>0.80</priority>
</url>
<url>
<loc><?php echo e(url("/")); ?>/contact-us</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>0.80</priority>
</url>
<url>
<loc><?php echo e(url("/")); ?>/page/event-planner</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>0.80</priority>
</url>
<url>
<loc><?php echo e(url("/")); ?>/page/b2b</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>0.80</priority>
</url>
<url>
<loc><?php echo e(url("/")); ?>/vendor-register</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>0.80</priority>
</url>
<?php $__currentLoopData = $routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <url>
    <loc><?php echo e(url("/")); ?>/route/<?php echo e($route->slug); ?></loc>
    <lastmod><?php echo e($route->created_at->tz('UTC')->toAtomString()); ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>.8</priority>
  </url>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <url>
    <loc><?php echo e(url("/")); ?>/pages/<?php echo e($page->slug); ?></loc>
    <lastmod><?php echo e($page->created_at->tz('UTC')->toAtomString()); ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>.8</priority>
  </url>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 
</urlset><?php /**PATH /var/www/duracabs/duracabs/resources/views/sitemap.blade.php ENDPATH**/ ?>