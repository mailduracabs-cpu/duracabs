<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\FleetManagement\TransporterProfile;
use App\Services\PartnerReviewLoginService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\Question;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$output = new ConsoleOutput();
$question = new Question('Choose a reusable 4-digit Partner review code (hidden): ');
$question->setHidden(true);
$question->setHiddenFallback(false);
$question->setValidator(function ($value) {
    if (!is_string($value) || !preg_match('/^\d{4}$/', $value)) {
        throw new RuntimeException('Enter exactly four digits.');
    }
    return $value;
});
$question->setMaxAttempts(3);

try {
    $code = (new QuestionHelper())->ask(new ArgvInput(), $output, $question);
    $hash = Hash::make($code);
    unset($code);
    $configPath = config_path('partner_play_review.php');
    if (!is_writable(dirname($configPath))
        || (file_exists($configPath) && !is_writable($configPath))) {
        throw new RuntimeException('The config file/directory must be writable by the deployment user.');
    }
    $user = DB::transaction(function () use ($hash, $configPath) {
        $role = Role::query()->where('name', User::ROLE_TRANSPORTER)
            ->where('guard_name', 'web')->firstOrFail();
        $matches = User::query()
            ->where('mobile', PartnerReviewLoginService::MOBILE)
            ->orWhere('email', PartnerReviewLoginService::EMAIL)
            ->lockForUpdate()->get();
        if ($matches->count() > 1) {
            throw new RuntimeException('Review identifiers conflict with existing accounts; nothing changed.');
        }
        $user = $matches->first();
        if ($user && ($user->mobile !== PartnerReviewLoginService::MOBILE
            || $user->email !== PartnerReviewLoginService::EMAIL
            || $user->roles()->count() !== 1 || !$user->hasRole($role)
            || $user->permissions()->exists())) {
            throw new RuntimeException('Existing account is not the isolated review partner; nothing changed.');
        }
        // Suppress registration notifications for this synthetic account.
        User::withoutEvents(function () use (&$user) {
            $user ??= new User();
            $user->forceFill([
                'name' => 'Dura Cabs Review Partner',
                'email' => PartnerReviewLoginService::EMAIL,
                'mobile' => PartnerReviewLoginService::MOBILE,
                'is_active' => true,
            ]);
            if (!$user->exists) {
                $user->password = Hash::make(bin2hex(random_bytes(32)));
            }
            $user->save();
        });
        $user->syncRoles([$role]);
        $profiles = TransporterProfile::withTrashed()->where('user_id', $user->id)->get();
        if ($profiles->count() > 1 || ($profiles->first() && $profiles->first()->trashed())) {
            throw new RuntimeException('Review profile needs admin inspection; nothing changed.');
        }
        TransporterProfile::withoutEvents(function () use ($profiles, $user) {
            $profile = $profiles->first() ?? new TransporterProfile();
            $profile->forceFill([
                'user_id' => $user->id, 'partner_type' => 'both',
                'company_name' => 'Dura Cabs Review Partner',
                'contact_person' => 'Review Partner',
                'mobile' => PartnerReviewLoginService::MOBILE,
                'email' => PartnerReviewLoginService::EMAIL,
                'office_address' => 'Review account — Agra',
                'city' => 'Agra', 'state' => 'Uttar Pradesh',
                'service_radius_km' => 40,
                'status' => true, 'verification_status' => 'verified',
            ])->save();
        });
        $contents = "<?php\n\nreturn " . var_export([
            'enabled' => true, 'user_id' => (int) $user->id, 'code_hash' => $hash,
        ], true) . ";\n";
        $temporary = tempnam(dirname($configPath), 'partner-review-');
        if ($temporary === false) {
            throw new RuntimeException('Could not create temporary config file.');
        }
        try {
            if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
                throw new RuntimeException('Could not write review config.');
            }
            chmod($temporary, 0644);
            if (!rename($temporary, $configPath)) {
                throw new RuntimeException('Could not replace review config.');
            }
        } finally {
            if (file_exists($temporary)) unlink($temporary);
        }
        return $user;
    });
    $user->tokens()->where('name', 'duracabs_partner_app')->delete();
    $app->make(Kernel::class)->call('config:clear');
    $output->writeln('Partner review account ready. Mobile: ' . PartnerReviewLoginService::MOBILE);
    $output->writeln('Code is stored as a hash. Enter your chosen code in Play Console; it is never sent by SMS.');
    $output->writeln('This account has its own empty fleet/bookings. Add review samples to this partner only.');
} catch (Throwable $exception) {
    $output->writeln('<error>Setup failed: ' . $exception->getMessage() . '</error>');
    exit(1);
}
