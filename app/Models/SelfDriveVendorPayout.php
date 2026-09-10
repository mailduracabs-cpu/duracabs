<?php

namespace App\Models;

use App\Models\FleetManagement\TransporterProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SelfDriveVendorPayout extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'payout_no',
        'transporter_profile_id',
        'period_from',
        'period_to',
        'total_booking_units',
        'gross_booking_amount',
        'payout_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'payment_method',
        'payment_reference',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'total_booking_units' => 'integer',

        'gross_booking_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',

        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $payout): void {
            if (blank($payout->payout_no)) {
                $payout->payout_no = 'TMP' . strtoupper(bin2hex(random_bytes(6)));
            }

            $payout->status ??= self::STATUS_DRAFT;
            $payout->paid_amount ??= 0;
            $payout->remaining_amount ??= $payout->payout_amount ?? 0;
        });

        static::created(function (self $payout): void {
            if (
                blank($payout->payout_no)
                || str_starts_with((string) $payout->payout_no, 'TMP')
            ) {
                $payout->forceFill([
                    'payout_no' => 'SDVP' . str_pad(
                        (string) $payout->getKey(),
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),
                ])->saveQuietly();
            }
        });

        static::saving(function (self $payout): void {
            $payoutAmount = max(
                0,
                (float) ($payout->payout_amount ?? 0)
            );

            $paidAmount = max(
                0,
                (float) ($payout->paid_amount ?? 0)
            );

            if ($paidAmount > $payoutAmount) {
                $paidAmount = $payoutAmount;
            }

            $remaining = max(
                0,
                $payoutAmount - $paidAmount
            );

            $payout->paid_amount = round($paidAmount, 2);
            $payout->remaining_amount = round($remaining, 2);

            if ($payout->status === self::STATUS_CANCELLED) {
                return;
            }

            if ($paidAmount <= 0) {
                $payout->status = self::STATUS_PENDING;
                $payout->paid_at = null;
                return;
            }

            if ($remaining > 0.009) {
                $payout->status = self::STATUS_PARTIAL;
                $payout->paid_at = null;
                return;
            }

            $payout->status = self::STATUS_PAID;
            $payout->paid_at ??= now();
        });
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(
            TransporterProfile::class,
            'transporter_profile_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            SelfDriveVendorPayoutItem::class,
            'self_drive_vendor_payout_id'
        );
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID
            && (float) $this->remaining_amount <= 0.009;
    }

    public function isPartial(): bool
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function refreshTotals(): void
    {
        $this->loadMissing('items');

        $this->total_booking_units = (int) $this->items->sum(
            fn (SelfDriveVendorPayoutItem $item): int =>
                (int) $item->booking_units
        );

        $this->gross_booking_amount = round(
            (float) $this->items->sum(
                fn (SelfDriveVendorPayoutItem $item): float =>
                    (float) $item->customer_booking_amount
            ),
            2
        );

        $this->payout_amount = round(
            (float) $this->items->sum(
                fn (SelfDriveVendorPayoutItem $item): float =>
                    (float) $item->payout_amount
            ),
            2
        );

        $this->save();
    }

    public function receivePayment(
        float $amount,
        ?string $method = null,
        ?string $reference = null
    ): void {
        $amount = round(max(0, $amount), 2);

        if ($amount <= 0) {
            return;
        }

        $currentRemaining = max(
            0,
            (float) $this->remaining_amount
        );

        $amount = min($amount, $currentRemaining);

        $this->paid_amount = round(
            (float) $this->paid_amount + $amount,
            2
        );

        if ($method !== null) {
            $this->payment_method = $method;
        }

        if ($reference !== null) {
            $this->payment_reference = $reference;
        }

        $this->save();
    }
}