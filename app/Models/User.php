<?php

namespace App\Models;

use App\Services\AdminLeadNotificationService;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;

    /*
    |--------------------------------------------------------------------------
    | Account Roles
    |--------------------------------------------------------------------------
    |
    | All account types remain in the same users table. Laravel session guards
    | are separate, while existing Spatie roles continue using guard_name=web.
    |
    */

    public const ROLE_CUSTOMER = 'Customer';

    public const ROLE_ADMIN = 'Admin';

    public const ROLE_TRANSPORTER = 'Transporter';

    public const ROLE_DRIVER = 'Driver';

    public const ROLE_MODERATOR = 'Moderator';

    /**
     * Keep compatibility with the existing Spatie roles/permissions records.
     */
    protected string $guard_name = 'web';

    /*
    |--------------------------------------------------------------------------
    | KYC Status Constants
    |--------------------------------------------------------------------------
    */

    public const KYC_NOT_UPLOADED = 'not_uploaded';

    public const KYC_UPLOADED = 'uploaded';

    public const KYC_VENDOR_APPROVED = 'vendor_approved';

    public const KYC_VENDOR_REJECTED = 'vendor_rejected';

    /**
     * Mass assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Basic details
        'name',
        'email',
        'email_verified_at',
        'password',
        'mobile',
        'photo',
        'is_active',
        'created_by',

        // Company details
        'company_name',
        'gst_number',
        'gst_image',
        'office_address',

        // Customer Aadhaar
        'aadhar_number',
        'aadhar_image',
        'aadhar_front',
        'aadhar_back',

        // Customer Driving Licence
        'driving_licence_number',
        'driving_licence_front',
        'driving_licence_back',

        // Customer KYC
        'kyc_status',

        // Social login
        'google_id',
        'whatsapp_id',
        'facebook_id',
        'apple_id',

        // Firebase
        'device_token',

        // Login type
        'login_type',

        // OTP
        'otp',
        'otp_expire_at',
    ];

    /**
     * Hidden attributes.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
    ];

    /**
     * Attribute casting.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expire_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $user): void {
            /*
             * Run after the current database transaction commits. Notification
             * failure must never roll back or interrupt account registration.
             */
            DB::afterCommit(function () use ($user): void {
                try {
                    $freshUser = self::query()->find($user->getKey());

                    if (! $freshUser) {
                        return;
                    }

                    app(AdminLeadNotificationService::class)
                        ->sendNewCustomerRegistration($freshUser);
                } catch (\Throwable $exception) {
                    Log::error(
                        'New customer registration admin notification failed.',
                        [
                            'customer_id' => $user->getKey(),
                            'message' => $exception->getMessage(),
                        ]
                    );
                }
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Customer orders.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Customer address.
     */
    public function address()
    {
        return $this->hasOne(Address::class);
    }

    /**
     * Customer activity and tracking history.
     *
     * Includes OTP verification, registration, login, searches,
     * vehicle views, checkout, payments and booking events.
     */
    public function customerActivities(): HasMany
    {
        return $this->hasMany(CustomerActivity::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Profile Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Check whether basic customer details are available.
     */
    public function hasBasicDetails(): bool
    {
        return filled($this->name) && filled($this->mobile);
    }

    /**
     * Check whether Aadhaar details are completely uploaded.
     */
    public function hasAadhaarDocuments(): bool
    {
        return filled($this->aadhar_number)
            && filled($this->aadhar_front)
            && filled($this->aadhar_back);
    }

    /**
     * Check whether Driving Licence details are completely uploaded.
     */
    public function hasDrivingLicenceDocuments(): bool
    {
        return filled($this->driving_licence_number)
            && filled($this->driving_licence_front)
            && filled($this->driving_licence_back);
    }

    /**
     * Check whether complete customer KYC is uploaded.
     */
    public function hasCompleteKyc(): bool
    {
        return $this->hasAadhaarDocuments()
            && $this->hasDrivingLicenceDocuments();
    }

    /**
     * Check whether vendor has approved customer KYC.
     */
    public function isKycApproved(): bool
    {
        return $this->kyc_status === self::KYC_VENDOR_APPROVED;
    }

    /**
     * Return masked Aadhaar number.
     */
    public function maskedAadhaarNumber(): ?string
    {
        if (blank($this->aadhar_number)) {
            return null;
        }

        $number = preg_replace(
            '/\D+/',
            '',
            (string) $this->aadhar_number
        );

        if (strlen($number) < 4) {
            return 'XXXX';
        }

        return 'XXXX XXXX ' . substr($number, -4);
    }

    /**
     * Return masked Driving Licence number.
     */
    public function maskedDrivingLicenceNumber(): ?string
    {
        if (blank($this->driving_licence_number)) {
            return null;
        }

        $number = trim((string) $this->driving_licence_number);

        if (strlen($number) <= 4) {
            return str_repeat('X', strlen($number));
        }

        return str_repeat('X', strlen($number) - 4)
            . substr($number, -4);
    }

    /**
     * Determine the next information required from customer.
     */
    public function nextRequiredStep(): ?string
    {
        if (blank($this->name)) {
            return 'name';
        }

        if (blank($this->mobile)) {
            return 'mobile';
        }

        if (! $this->hasAadhaarDocuments()) {
            return 'aadhaar';
        }

        if (! $this->hasDrivingLicenceDocuments()) {
            return 'driving_licence';
        }

        if ($this->kyc_status === self::KYC_VENDOR_REJECTED) {
            return 'kyc_reupload';
        }

        if ($this->kyc_status !== self::KYC_VENDOR_APPROVED) {
            return 'vendor_verification';
        }

        return null;
    }

    /**
     * Customer profile data for app/API.
     */
    public function customerProfileData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'photo' => $this->photo,

            'aadhar_number' => $this->maskedAadhaarNumber(),
            'aadhar_front' => $this->aadhar_front,
            'aadhar_back' => $this->aadhar_back,
            'aadhar_uploaded' => $this->hasAadhaarDocuments(),

            'driving_licence_number' =>
                $this->maskedDrivingLicenceNumber(),

            'driving_licence_front' =>
                $this->driving_licence_front,

            'driving_licence_back' =>
                $this->driving_licence_back,

            'driving_licence_uploaded' =>
                $this->hasDrivingLicenceDocuments(),

            'kyc_status' =>
                $this->kyc_status ?? self::KYC_NOT_UPLOADED,

            'kyc_complete' => $this->hasCompleteKyc(),
            'kyc_approved' => $this->isKycApproved(),
            'next_required_step' => $this->nextRequiredStep(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Account Type Helpers
    |--------------------------------------------------------------------------
    */

    public function isActiveAccount(): bool
    {
        return (bool) $this->is_active;
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(self::ROLE_CUSTOMER);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isModerator(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_MODERATOR,
            strtolower(self::ROLE_MODERATOR),
        ]);
    }

    public function isTransporter(): bool
    {
        return $this->hasRole(self::ROLE_TRANSPORTER);
    }

    public function isDriver(): bool
    {
        return $this->hasRole(self::ROLE_DRIVER);
    }

    public function canUseCustomerLogin(): bool
    {
        return $this->isActiveAccount()
            && $this->isCustomer();
    }

    public function canUseAdminLogin(): bool
    {
        return $this->isActiveAccount()
            && ($this->isAdmin() || $this->isModerator());
    }

    public function canUseTransporterLogin(): bool
    {
        return $this->isActiveAccount()
            && $this->isTransporter();
    }

    public function canUseDriverLogin(): bool
    {
        return $this->isActiveAccount()
            && $this->isDriver();
    }

    /*
    |--------------------------------------------------------------------------
    | Filament Access
    |--------------------------------------------------------------------------
    */

    /**
     * Restrict each Filament panel to its own account type.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->isActiveAccount()) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->canUseAdminLogin(),

            'transporter',
            'vendor',
            'partner' => $this->canUseTransporterLogin(),

            'driver' => $this->canUseDriverLogin(),

            default => false,
        };
    }
}