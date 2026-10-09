<?php
// Run from the Laravel root AFTER copying this package and running its migration.
require __DIR__.'/../vendor/autoload.php';

use App\Providers\TripReviewServiceProvider;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! Schema::hasTable('trip_reviews') || ! Schema::hasTable('trip_account_restrictions')) {
    fwrite(STDERR, "Run the trip-review migration first. Provider was not registered.\n"); exit(1);
}
$path = __DIR__.'/../bootstrap/providers.php';
if (! is_file($path) || ! method_exists(ServiceProvider::class, 'addProviderToBootstrapFile')) {
    fwrite(STDERR, "This installer requires bootstrap/providers.php (Laravel 11+). Send your current config/app.php for the older registration format. No files changed.\n"); exit(1);
}
if (! is_writable($path)) { fwrite(STDERR, "bootstrap/providers.php is not writable. No files changed.\n"); exit(1); }
$backup = $path.'.before-trip-reviews-'.date('Ymd-His');
if (! copy($path, $backup)) { fwrite(STDERR, "Unable to create provider backup. No files changed.\n"); exit(1); }
ServiceProvider::addProviderToBootstrapFile(TripReviewServiceProvider::class, $path);
fwrite(STDOUT, "Trip review provider registered. Run php artisan optimize:clear next.\n");
