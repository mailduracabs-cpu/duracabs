<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WalletTransaction extends Model
{
    use HasFactory;

    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERSED = 'reversed';

    public const METHOD_WALLET = 'wallet';
    public const METHOD_RAZORPAY = 'razorpay';
    public const METHOD_REFUND = 'refund';
    public const METHOD_CASHBACK = 'cashback';
    public const METHOD_MANUAL = 'manual';

    protected $table = 'wallet_transactions';

    protected $fillable = [
        'wallet_id',
        'user_id',
        'booking_id',
        'transaction_id',
        'type',
        'payment_method',
        'amount',
        'opening_balance',
        'closing_balance',
        'status',
        'remarks',
        'reference',
        'meta',
    ];

    protected $casts = [
        'wallet_id' => 'integer',
        'user_id' => 'integer',
        'booking_id' => 'integer',
        'amount' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $transaction): void {
            if (blank($transaction->transaction_id)) {
                $transaction->transaction_id = self::generateTransactionId();
            }

            if (blank($transaction->status)) {
                $transaction->status = self::STATUS_SUCCESS;
            }

            if (blank($transaction->payment_method)) {
                $transaction->payment_method = self::METHOD_WALLET;
            }

            $transaction->type = strtolower((string) $transaction->type);
            $transaction->status = strtolower((string) $transaction->status);
            $transaction->payment_method = strtolower(
                (string) $transaction->payment_method
            );
        });
    }

    public static function generateTransactionId(): string
    {
        return 'WTX-' . now()->format('YmdHis') . '-'
            . strtoupper(Str::random(8));
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Legacy/general booking relationship.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    /**
     * Self Drive and Bike Rental booking relationship.
     * Use this relation only when booking_id belongs to self_drive_bookings.
     */
    public function selfDriveBooking(): BelongsTo
    {
        return $this->belongsTo(SelfDriveBooking::class, 'booking_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForBooking(Builder $query, int $bookingId): Builder
    {
        return $query->where('booking_id', $bookingId);
    }

    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_CREDIT);
    }

    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DEBIT);
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByReference(Builder $query, string $reference): Builder
    {
        return $query->where('reference', $reference);
    }

    /**
     * Find an already-created transaction using a stable external reference.
     * This supports duplicate-payment/refund protection without requiring a
     * new idempotency column.
     */
    public static function findByReference(?string $reference): ?self
    {
        $reference = trim((string) $reference);

        if ($reference === '') {
            return null;
        }

        return static::query()->byReference($reference)->first();
    }

    public function markSuccessful(array $meta = []): bool
    {
        return $this->update([
            'status' => self::STATUS_SUCCESS,
            'meta' => $this->mergedMeta($meta),
        ]);
    }

    public function markFailed(array $meta = []): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'meta' => $this->mergedMeta($meta),
        ]);
    }

    public function markReversed(array $meta = []): bool
    {
        return $this->update([
            'status' => self::STATUS_REVERSED,
            'meta' => $this->mergedMeta($meta),
        ]);
    }

    public function putMeta(string $key, mixed $value): bool
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        $meta[$key] = $value;

        return $this->update(['meta' => $meta]);
    }

    public function metaValue(string $key, mixed $default = null): mixed
    {
        $meta = is_array($this->meta) ? $this->meta : [];

        return data_get($meta, $key, $default);
    }

    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->type === self::TYPE_DEBIT;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    public function isRefund(): bool
    {
        return $this->payment_method === self::METHOD_REFUND;
    }

    public function amountAsFloat(): float
    {
        return round((float) $this->amount, 2);
    }

    private function mergedMeta(array $meta): array
    {
        return array_replace_recursive(
            is_array($this->meta) ? $this->meta : [],
            $meta
        );
    }
}