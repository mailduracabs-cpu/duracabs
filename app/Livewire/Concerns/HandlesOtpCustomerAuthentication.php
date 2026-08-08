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
     * Authenticate or automatically register a customer after successful OTP
     * verification.
     *
     * Customer rules:
     * - no password entry is required;
     * - no name/email form is required;
     * - new customer accounts are active immediately;
     * - existing inactive customer accounts are activated after valid OTP;
     * - Customer role is assigned automatically;
     * - staff and transporter accounts can never become customers here.
     */
    protected function authenticateOtpCustomer(
        ?string $mobileNumber = null
    ): User {
        $mobile = $this->resolveOtpCustomerMobile($mobileNumber);

        if ($mobile === '') {
            Log::warning(
                'OTP customer authentication mobile is missing.',
                [
                    'passed_mobile' => $mobileNumber,
                    'component_mobile' => property_exists(
                        $this,
                        'mobileNumber'
                    )
                        ? $this->mobileNumber
                        : null,
                    'otp_session_mobile' => request()
                        ->session()
                        ->get('otp_customer_mobile'),
                    'customer_search_mobile' => request()
                        ->session()
                        ->get('customer_search_mobile'),
                    'authenticated_user_id' =>
                        Auth::guard('customer')->id(),
                    'authenticated_user_mobile' =>
                        Auth::guard('customer')->user()?->mobile,
                ]
            );

            throw new \RuntimeException(
                'Customer authentication failed because the mobile number is missing.'
            );
        }

        try {
            /** @var User $user */
            $user = DB::transaction(function () use ($mobile): User {
                $user = User::query()
                    ->where('mobile', $mobile)
                    ->lockForUpdate()
                    ->first();

                if (! $user) {
                    $payload = [
                        'name' => 'Customer ' . substr($mobile, -4),
                        'mobile' => $mobile,
                        'is_active' => true,
                    ];

                    if (Schema::hasColumn('users', 'email')) {
                        $payload['email'] =
                            $this->generateOtpCustomerEmail($mobile);
                    }

                    if (Schema::hasColumn('users', 'password')) {
                        $payload['password'] = bcrypt(
                            Str::random(40)
                        );
                    }

                    $this->appendCustomerAccountType($payload);

                    $user = User::query()->create($payload);
                }

                if (
                    $user->isAdmin()
                    || $user->isModerator()
                    || $user->isTransporter()
                    || $user->isDriver()
                ) {
                    throw new \RuntimeException(
                        'This mobile number cannot be used for customer login.'
                    );
                }

                if (! $user->isActiveAccount()) {
                    $user->forceFill([
                        'is_active' => true,
                    ])->save();
                }

                $this->ensureCustomerAccountType($user);
                $this->assignCustomerRole($user);

                return $user->fresh();
            });

            /*
             * Clear only customer-compatible legacy sessions. Admin panel uses
             * its own guard and remains isolated.
             */
            Auth::guard('web')->logout();
            Auth::guard('vendor')->logout();
            Auth::guard('customer')->logout();

            Auth::guard('customer')->login($user, true);

            request()->session()->regenerate();

            request()->session()->put([
                'otp_customer_mobile' => $mobile,
                'customer_search_mobile' => $mobile,
                'rides_verified_mobile' => $mobile,
                'otp_customer_user_id' => $user->getKey(),
            ]);

            if (property_exists($this, 'mobileNumber')) {
                $this->mobileNumber = $mobile;
            }

            Log::info('OTP customer authenticated successfully.', [
                'user_id' => $user->getKey(),
                'mobile' => $mobile,
                'customer_guard_check' =>
                    Auth::guard('customer')->check(),
                'customer_guard_id' =>
                    Auth::guard('customer')->id(),
            ]);

            return $user;
        } catch (\Throwable $exception) {
            Log::error('OTP customer authentication failed.', [
                'mobile' => $mobile,
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]);

            throw $exception;
        }
    }

    /**
     * Store the mobile before the OTP verification Livewire request.
     */
    protected function rememberOtpCustomerMobile(mixed $mobile): string
    {
        $mobile = $this->normalizeOtpCustomerMobile($mobile);

        if ($mobile === '') {
            return '';
        }

        request()->session()->put([
            'otp_customer_mobile' => $mobile,
            'customer_search_mobile' => $mobile,
        ]);

        if (property_exists($this, 'mobileNumber')) {
            $this->mobileNumber = $mobile;
        }

        return $mobile;
    }

    /**
     * Resolve the customer mobile from every reliable OTP source.
     */
    protected function resolveOtpCustomerMobile(
        ?string $mobileNumber = null
    ): string {
        $componentMobile = property_exists($this, 'mobileNumber')
            ? $this->mobileNumber
            : null;

        $candidates = [
            $mobileNumber,
            $componentMobile,
            request()->session()->get('otp_customer_mobile'),
            request()->session()->get('customer_search_mobile'),
            request()->session()->get('rides_verified_mobile'),
            request()->input('mobileNumber'),
            request()->input('mobile'),
            Auth::guard('customer')->user()?->mobile,
        ];

        foreach ($candidates as $candidate) {
            $mobile = $this->normalizeOtpCustomerMobile($candidate);

            if ($mobile !== '') {
                return $mobile;
            }
        }

        return '';
    }

    /**
     * Return a valid normalized 10-digit Indian mobile number.
     */
    protected function normalizeOtpCustomerMobile(mixed $mobile): string
    {
        $mobile = preg_replace(
            '/\D+/',
            '',
            (string) $mobile
        ) ?? '';

        if (strlen($mobile) > 10) {
            $mobile = substr($mobile, -10);
        }

        if (! preg_match('/^[6-9]\d{9}$/', $mobile)) {
            return '';
        }

        return $mobile;
    }

    protected function generateOtpCustomerEmail(
        string $mobile
    ): string {
        $email = $mobile . '@duracabs.local';

        if (! User::query()->where('email', $email)->exists()) {
            return $email;
        }

        return $mobile
            . '-'
            . Str::lower(Str::random(6))
            . '@duracabs.local';
    }

    /**
     * Add the legacy customer account type when such a column exists.
     */
    protected function appendCustomerAccountType(
        array &$payload
    ): void {
        foreach (
            ['account_type', 'user_type', 'type', 'role']
            as $column
        ) {
            if (! Schema::hasColumn('users', $column)) {
                continue;
            }

            $payload[$column] = 'customer';

            return;
        }
    }

    /**
     * Backfill the legacy customer account type for old customer records.
     */
    protected function ensureCustomerAccountType(
        User $user
    ): void {
        foreach (
            ['account_type', 'user_type', 'type', 'role']
            as $column
        ) {
            if (! Schema::hasColumn('users', $column)) {
                continue;
            }

            $value = Str::lower(
                trim((string) $user->{$column})
            );

            if ($value === '') {
                $user->forceFill([
                    $column => 'customer',
                ])->save();
            }

            return;
        }
    }

    /**
     * Assign the existing website customer role.
     *
     * This project stores normal customers under the Spatie role `user`
     * with guard_name `web`. Do not create or expect a separate `Customer`
     * role here.
     */
    protected function assignCustomerRole(User $user): void
    {
        $customerRole = 'user';

        if ($user->hasRole($customerRole)) {
            return;
        }

        $user->syncRoles([
            $customerRole,
        ]);
    }
}