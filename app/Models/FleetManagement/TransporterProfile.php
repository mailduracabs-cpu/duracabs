<?php

namespace App\Models\FleetManagement;

use App\Models\Order;
use App\Models\SelfDriveBooking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AdminLeadNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransporterProfile extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_HOST = 'host';
    public const TYPE_VENDOR = 'vendor';
    public const TYPE_BOTH = 'both';

    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_REJECTED = 'rejected';

    protected $table = 'fleet_transporter_profiles';

    protected $fillable = [
        'user_id',
        'partner_type',
        'company_name',
        'contact_person',
        'mobile',
        'whatsapp_number',
        'email',
        'aadhaar_number',
        'pan_number',
        'gst_number',
        'office_address',
        'city',
        'state',
        'pincode',
        'pickup_place_id',
        'pickup_latitude',
        'pickup_longitude',
        'service_radius_km',
        'aadhaar_image',
        'pan_image',
        'gst_image',
        'company_document',
        'office_photo',
        'status',
        'verification_status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'pickup_latitude' => 'decimal:7',
        'pickup_longitude' => 'decimal:7',
        'service_radius_km' => 'decimal:2',
    ];

    protected $attributes = [
        'partner_type' => self::TYPE_HOST,
        'verification_status' => self::VERIFICATION_PENDING,
        'status' => true,
        'service_radius_km' => 40,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $profile): void {
            $profile->partner_type ??= self::TYPE_HOST;
            $profile->verification_status ??=
                self::VERIFICATION_PENDING;

            if (is_null($profile->status)) {
                $profile->status = true;
            }

            $profile->service_radius_km ??= 40;
        });

        static::created(function (self $profile): void {
            /*
             * Send only after the registration transaction commits.
             * WhatsApp failure must never interrupt vendor registration.
             */
            DB::afterCommit(function () use ($profile): void {
                try {
                    $freshProfile = self::query()
                        ->with('user')
                        ->find($profile->getKey());

                    if (! $freshProfile) {
                        return;
                    }

                    app(AdminLeadNotificationService::class)
                        ->sendNewVendorRegistration($freshProfile);
                } catch (\Throwable $exception) {
                    Log::error(
                        'New vendor registration admin notification failed.',
                        [
                            'vendor_profile_id' => $profile->getKey(),
                            'message' => $exception->getMessage(),
                        ]
                    );
                }
            });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fleets(): HasMany
    {
        return $this->hasMany(
            Fleet::class,
            'transporter_profile_id'
        );
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(
            Vehicle::class,
            'transporter_profile_id'
        );
    }

    public function services(): HasMany
    {
        return $this->hasMany(
            TransporterService::class,
            'transporter_profile_id'
        );
    }

    public function cities(): HasMany
    {
        return $this->hasMany(
            TransporterCity::class,
            'transporter_profile_id'
        );
    }

    public function selfDriveBookings(): HasMany
    {
        return $this->hasMany(
            SelfDriveBooking::class,
            'transporter_profile_id'
        );
    }

    public function taxiBookings(): HasMany
    {
        return $this->hasMany(
            Order::class,
            'transporter_id',
            'user_id'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where(
            'verification_status',
            self::VERIFICATION_VERIFIED
        );
    }

    public function scopeHosts(Builder $query): Builder
    {
        return $query->whereIn('partner_type', [
            self::TYPE_HOST,
            self::TYPE_BOTH,
        ]);
    }

    public function scopeVendors(Builder $query): Builder
    {
        return $query->whereIn('partner_type', [
            self::TYPE_VENDOR,
            self::TYPE_BOTH,
        ]);
    }

    public function isHost(): bool
    {
        return in_array($this->partner_type, [
            self::TYPE_HOST,
            self::TYPE_BOTH,
        ], true);
    }

    public function isVendor(): bool
    {
        return in_array($this->partner_type, [
            self::TYPE_VENDOR,
            self::TYPE_BOTH,
        ], true);
    }

    public function isVerified(): bool
    {
        return $this->verification_status ===
            self::VERIFICATION_VERIFIED;
    }

    public function isActive(): bool
    {
        return (bool) $this->status;
    }

    public function partnerTypeLabel(): string
    {
        return match ($this->partner_type) {
            self::TYPE_HOST => 'Host',
            self::TYPE_VENDOR => 'Vendor',
            self::TYPE_BOTH => 'Host and Vendor',
            default => 'Unknown',
        };
    }

    public function verificationLabel(): string
    {
        return match ($this->verification_status) {
            self::VERIFICATION_VERIFIED => 'Verified',
            self::VERIFICATION_REJECTED => 'Rejected',
            default => 'Pending',
        };
    }

    public function totalVehicleCount(): int
    {
        if (array_key_exists(
            'vehicles_count',
            $this->getAttributes()
        )) {
            return (int) $this->vehicles_count;
        }

        return $this->vehicles()->count();
    }

    public function taxiBookingCount(): int
    {
        if (array_key_exists(
            'taxi_bookings_count',
            $this->getAttributes()
        )) {
            return (int) $this->taxi_bookings_count;
        }

        return $this->taxiBookings()->count();
    }

    public function selfDriveBookingCount(): int
    {
        if (array_key_exists(
            'self_drive_bookings_count',
            $this->getAttributes()
        )) {
            return (int) $this->self_drive_bookings_count;
        }

        return $this->selfDriveBookings()->count();
    }

    public function totalBookingCount(): int
    {
        return $this->taxiBookingCount()
            + $this->selfDriveBookingCount();
    }

    public function canBeDeletedSafely(): bool
    {
        return ! $this->vehicles()->exists()
            && ! $this->taxiBookings()->exists()
            && ! $this->selfDriveBookings()->exists()
            && ! $this->fleets()->exists();
    }

    public function deletionBlockReason(): ?string
    {
        $reasons = [];

        if ($this->vehicles()->exists()) {
            $reasons[] = 'linked vehicles';
        }

        if ($this->taxiBookings()->exists()) {
            $reasons[] = 'taxi bookings';
        }

        if ($this->selfDriveBookings()->exists()) {
            $reasons[] = 'self-drive bookings';
        }

        if ($this->fleets()->exists()) {
            $reasons[] = 'fleet records';
        }

        if ($reasons === []) {
            return null;
        }

        return 'This partner cannot be deleted because it has '
            . implode(', ', $reasons)
            . '.';
    }
}