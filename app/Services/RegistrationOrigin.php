<?php

namespace App\Services;

use App\Models\User;
use App\Models\FleetManagement\TransporterProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class RegistrationOrigin
{
    public const WEBSITE = 'website';
    public const APP = 'app';
    public const UNKNOWN = 'unknown';

    public static function detect(?Request $request = null): string
    {
        if ($request === null) {
            if (app()->runningInConsole() || ! app()->bound('request')) return self::UNKNOWN;
            $request = request();
        }
        // Channel is derived from the server entry point, never a client-supplied
        // registration_source, login_type, User-Agent or Referer value.
        return $request->is('api', 'api/*') ? self::APP : self::WEBSITE;
    }

    public static function label(?string $source): string
    {
        return match ($source) {
            self::WEBSITE => 'Website', self::APP => 'App', default => 'Unknown',
        };
    }

    private static function supported(Model $model): bool
    {
        $schema = Schema::connection($model->getConnectionName());
        $columns = ['registration_source', 'registration_admin_attempted_at'];
        if ($model instanceof User) $columns = array_merge($columns, ['registration_account_type', 'registration_completed_at']);
        foreach ($columns as $column) {
            if (! $schema->hasColumn($model->getTable(), $column)) return false;
        }
        return true;
    }

    public static function stamp(Model $model, ?string $source = null): void
    {
        if ($model->exists || ! self::supported($model)) return;
        // Force the server value, including when creation uses forceFill().
        $model->setAttribute('registration_source', in_array($source, [self::WEBSITE, self::APP], true)
            ? $source : self::detect());
    }

    public static function preserveCompletedSource(Model $model): void
    {
        if (! self::supported($model)) return;
        $completed = $model instanceof TransporterProfile
            || $model->getRawOriginal('registration_completed_at') !== null;
        if ($completed && $model->isDirty('registration_source')) {
            $model->setAttribute('registration_source', $model->getRawOriginal('registration_source'));
        }
    }

    /** Fallback for existing website/admin create flows after roles are assigned. */
    public static function deferUser(User $user): void
    {
        if (app()->runningInConsole() || ! self::supported($user)
            || $user->registration_source === self::APP) return;
        DB::connection($user->getConnectionName())->afterCommit(function () use ($user): void {
            app()->terminating(function () use ($user): void {
                try {
                    $fresh = User::query()->find($user->getKey());
                    if (! $fresh || $fresh->login_type === 'otp') return;
                    $fresh->load('roles');
                    $roles = $fresh->roles->pluck('name')->map(fn ($name) => strtolower((string) $name))->all();
                    if (array_intersect($roles, ['admin', 'super admin', 'super_admin', 'super-admin', 'moderator'])) return;
                    if (array_intersect($roles, ['transporter', 'vendor', 'partner']) || filled($fresh->company_name)) {
                        self::completeUser($fresh, 'vendor');
                    } elseif (in_array('driver', $roles, true)) {
                        self::completeUser($fresh, 'driver');
                    } else {
                        self::completeUser($fresh, 'customer');
                    }
                } catch (Throwable $e) { self::logFailure('deferred-user', $user->getKey(), $e); }
            });
        });
    }

    /** Only call after the relevant registration/OTP/token operation succeeds. */
    public static function completeUser(User $user, string $kind, ?string $source = null): void
    {
        try {
            if (! in_array($kind, ['customer', 'vendor', 'driver'], true) || ! self::supported($user)) return;
            // Historical accounts have NULL source. A later login is not evidence
            // of their original registration channel and must not send a signup alert.
            $fresh = $user->fresh();
            if (! $fresh || $fresh->registration_source === null) return;
            if (in_array((string) $fresh->mobile, ['0000000001', '0000000002'], true)) return;
            $detected = $source ?? self::detect();
            $channel = in_array($detected, [self::WEBSITE, self::APP], true)
                ? $detected : (string) $fresh->registration_source;
            $fresh->newQuery()->whereKey($fresh->getKey())->whereNull('registration_completed_at')->update([
                'registration_source' => $channel,
                'registration_account_type' => $kind,
                'registration_completed_at' => now(),
            ]);
            if ($kind === 'vendor') return; // Vendor profile owns that notification.
            DB::connection($fresh->getConnectionName())->afterCommit(function () use ($fresh, $kind): void {
                try {
                    $subject = $fresh->fresh();
                    if (! $subject || $subject->registration_account_type !== $kind) return;
                    $claimed = $subject->newQuery()->whereKey($subject->getKey())
                        ->whereNotNull('registration_completed_at')->whereNull('registration_admin_attempted_at')
                        ->update(['registration_admin_attempted_at' => now()]);
                    if ($claimed !== 1) return;
                    $service = app(AdminLeadNotificationService::class);
                    $kind === 'driver' ? $service->sendNewDriverRegistration($subject)
                        : $service->sendNewCustomerRegistration($subject);
                } catch (Throwable $e) { self::logFailure($kind, $fresh->getKey(), $e); }
            });
        } catch (Throwable $e) { self::logFailure($kind, $user->getKey(), $e); }
    }

    public static function completeVendor(TransporterProfile $profile): void
    {
        try {
            if (! self::supported($profile)) return;
            $fresh = $profile->fresh(['user']);
            if (! $fresh || $fresh->registration_source === null) return;
            if ($fresh->user) self::completeUser($fresh->user, 'vendor', $fresh->registration_source);
            DB::connection($fresh->getConnectionName())->afterCommit(function () use ($fresh): void {
                try {
                    $claimed = $fresh->newQuery()->whereKey($fresh->getKey())
                        ->whereNull('registration_admin_attempted_at')
                        ->update(['registration_admin_attempted_at' => now()]);
                    if ($claimed !== 1) return;
                    app(AdminLeadNotificationService::class)->sendNewVendorRegistration($fresh);
                } catch (Throwable $e) { self::logFailure('vendor', $fresh->getKey(), $e); }
            });
        } catch (Throwable $e) { self::logFailure('vendor', $profile->getKey(), $e); }
    }

    private static function logFailure(string $kind, mixed $id, Throwable $exception): void
    {
        // An attempted timestamp is not a delivery receipt. Do not automatically
        // retry an ambiguous send and risk duplicate WhatsApp notifications.
        Log::warning('Registration admin notification could not be completed.', [
            'account_type' => $kind, 'record_id' => $id, 'exception' => get_class($exception),
        ]);
    }
}
