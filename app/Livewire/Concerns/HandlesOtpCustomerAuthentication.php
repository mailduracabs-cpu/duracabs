<?php

namespace App\Livewire\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait HandlesOtpCustomerAuthentication
{
    /**
     * Authenticate the customer after Homepage OTP verification.
     *
     * The Homepage component already validates the entered OTP before calling
     * this method. This method only resolves or creates the customer account,
     * logs the customer in, and regenerates the session.
     */
    protected function authenticateOtpCustomer(?string $mobileNumber = null): User
    {
        $mobile = $this->resolveOtpCustomerMobile($mobileNumber);

        if ($mobile === '') {
            throw new \RuntimeException(
                'Customer authentication failed because the mobile number is missing.'
            );
        }

        try {
            /** @var User $user */
            $user = DB::transaction(function () use ($mobile): User {
                $existingUser = User::query()
                    ->where('mobile', $mobile)
                    ->lockForUpdate()
                    ->first();

                if ($existingUser) {
                    $this->ensureCustomerAccountType($existingUser);

                    return $existingUser;
                }

                $payload = [
                    'name' => $mobile,
                    'mobile' => $mobile,
                ];

                if (Schema::hasColumn('users', 'email')) {
                    $payload['email'] = $this->generateOtpCustomerEmail($mobile);
                }

                if (Schema::hasColumn('users', 'password')) {
                    $payload['password'] = bcrypt(Str::random(40));
                }

                $this->appendCustomerAccountType($payload);

                $user = User::query()->create($payload);

                /*
                 * The old project used Spatie roles in some authentication
                 * flows. Assign the customer role only when the model supports
                 * it and the role exists. Login must not fail if roles are not
                 * configured.
                 */
                $this->assignCustomerRoleIfAvailable($user);

                return $user;
            });

            Auth::login($user, true);
            request()->session()->regenerate();

            /*
             * Keep the normalized mobile number in the Livewire component so
             * CustomerSearchActivity and later booking logic receive the same
             * 10-digit value.
             */
            if (property_exists($this, 'mobileNumber')) {
                $this->mobileNumber = $mobile;
            }

            request()->session()->forget('otp_customer_mobile');

            Log::info('Homepage OTP customer authenticated', [
                'user_id' => $user->getKey(),
                'mobile' => $mobile,
            ]);

            return $user;
        } catch (\Throwable $exception) {
            Log::error('Homepage OTP customer authentication failed', [
                'mobile' => $mobile,
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]);

            throw $exception;
        }
    }


    /**
     * Store the mobile number before the OTP verification request.
     *
     * Livewire can rehydrate public properties between requests, so the session
     * copy provides a reliable fallback for all Homepage OTP flows.
     */
    protected function rememberOtpCustomerMobile(mixed $mobile): string
    {
        $mobile = $this->normalizeOtpCustomerMobile($mobile);

        if ($mobile !== '') {
            request()->session()->put('otp_customer_mobile', $mobile);
        }

        return $mobile;
    }

    /**
     * Resolve the OTP customer mobile from the safest available source.
     */
    protected function resolveOtpCustomerMobile(?string $mobileNumber = null): string
    {
        $candidates = [
            $mobileNumber,
            property_exists($this, 'mobileNumber') ? $this->mobileNumber : null,
            request()->session()->get('otp_customer_mobile'),
            Auth::user()?->mobile,
        ];

        foreach ($candidates as $candidate) {
            $mobile = $this->normalizeOtpCustomerMobile($candidate);

            if ($mobile !== '') {
                return $mobile;
            }
        }

        return '';
    }

    protected function normalizeOtpCustomerMobile(mixed $mobile): string
    {
        $mobile = preg_replace('/[^0-9]/', '', (string) $mobile) ?? '';

        if (strlen($mobile) > 10) {
            $mobile = substr($mobile, -10);
        }

        return $mobile;
    }

    protected function generateOtpCustomerEmail(string $mobile): string
    {
        $email = $mobile . '@duracabs.local';

        if (!User::query()->where('email', $email)->exists()) {
            return $email;
        }

        return $mobile . '-' . Str::lower(Str::random(6)) . '@duracabs.local';
    }

    protected function appendCustomerAccountType(array &$payload): void
    {
        foreach (['account_type', 'user_type', 'type', 'role'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $payload[$column] = 'customer';

                return;
            }
        }
    }

    protected function ensureCustomerAccountType(User $user): void
    {
        foreach (['account_type', 'user_type', 'type', 'role'] as $column) {
            if (!Schema::hasColumn('users', $column)) {
                continue;
            }

            $value = Str::lower(trim((string) $user->{$column}));

            if ($value === '') {
                $user->forceFill([$column => 'customer'])->save();
            }

            return;
        }
    }

    protected function assignCustomerRoleIfAvailable(User $user): void
    {
        if (!method_exists($user, 'assignRole')) {
            return;
        }

        try {
            /*
             * The legacy Register component assigned role ID 5. Using the same
             * value preserves compatibility with the existing project.
             */
            $user->assignRole(5);
        } catch (\Throwable $exception) {
            Log::warning('Customer role assignment skipped', [
                'user_id' => $user->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}