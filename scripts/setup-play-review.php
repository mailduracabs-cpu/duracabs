<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

$path = config_path('play_review.php');
$handle = fopen($path, 'c+');
if (!$handle || !flock($handle, LOCK_EX | LOCK_NB)) {
    throw new RuntimeException('Cannot lock config/play_review.php. Check file permissions or another running setup.');
}

try {
    $current = filesize($path) > 0 ? require $path : [];
    if (!is_array($current)) {
        throw new RuntimeException('Unexpected review configuration; no account was changed.');
    }

    // Deliberately never reuse or alter an existing real customer/admin account.
    if (User::query()->where('mobile', '0000000001')
        ->orWhere('email', 'play-review@duracabs.local')->exists()
        || !empty($current['user_id'])) {
        throw new RuntimeException('Review identity already exists. Setup stopped without changing it.');
    }

    $role = Role::query()->where('name', User::ROLE_CUSTOMER)
        ->where('guard_name', 'web')->first();
    if (!$role) {
        throw new RuntimeException('Customer role with guard web is missing. Setup stopped.');
    }

    $code = (string) random_int(1000, 9999);
    $hash = Hash::make($code);
    $user = DB::transaction(function () use ($role): User {
        // Suppress registration observers so provisioning sends no notifications.
        return User::withoutEvents(function () use ($role): User {
            $data = [
                'name' => 'DuraCabs App Review',
                'email' => 'play-review@duracabs.local',
                'mobile' => '0000000001',
                'password' => Hash::make(Str::random(64)),
                'is_active' => true,
                'login_type' => 'otp',
                'otp' => null,
                'otp_expire_at' => null,
            ];
            $user = new User();
            $user->forceFill(array_filter($data, fn ($key) => Schema::hasColumn('users', $key), ARRAY_FILTER_USE_KEY));
            $user->save();
            $user->syncRoles([$role]);
            $user->syncPermissions([]);
            return $user;
        });
    });

    $config = ['enabled' => true, 'user_id' => (int) $user->getKey(), 'code_hash' => $hash];
    $content = "<?php\n\n// Provisioned review account. Keep outside the public web directory.\nreturn "
        . var_export($config, true) . ";\n";
    $temporary = tempnam(dirname($path), '.play-review-');
    if (!$temporary || file_put_contents($temporary, $content) !== strlen($content)
        || !chmod($temporary, fileperms($path) & 0777)
        || (fileowner($temporary) !== fileowner($path) && !chown($temporary, fileowner($path)))
        || (filegroup($temporary) !== filegroup($path) && !chgrp($temporary, filegroup($path)))
        || !rename($temporary, $path)) {
        // The account exists but fixed-code login stays unavailable until config is saved.
        throw new RuntimeException('Account created, but config could not be saved. Stop and repair permissions before retrying.');
    }

    echo "Review customer created: ID {$user->getKey()}\n";
    echo "Mobile: 0000000001\nReview code: {$code}\n";
    echo "Save this code privately in Play Console App access. It is not printed again.\n";
    echo "Next run: php artisan config:clear\n";
} finally {
    flock($handle, LOCK_UN);
    fclose($handle);
}
