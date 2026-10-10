<?php

namespace App\Services;

use App\Models\SelfDriveBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Records an actual refund. Does not send money or change rental/payout totals. */
class SelfDriveSecurityRefundService
{
    public static function summary(object $booking): array
    {
        $ledger = $booking->security_refund_ledger ?? [];
        if (is_string($ledger)) $ledger = json_decode($ledger, true) ?: [];
        $refunded = 0;
        $history = [];
        foreach ($ledger as $entry) {
            $refunded += PartnerPayoutPaymentService::cents($entry['amount']);
            $history[] = array_intersect_key($entry, array_flip(['amount', 'method', 'reference', 'refunded_at', 'note']));
        }
        $deposit = PartnerPayoutPaymentService::cents($ledger[0]['security_deposit_snapshot'] ?? $booking->security_deposit ?? '0');
        $legacy = ! $ledger && (filled($booking->refunded_at ?? null)
            || in_array($booking->refund_status ?? null, ['refunded', 'completed', 'processed', 'success', 'initiated', 'processing', 'pending', 'requested'], true)
            || ($booking->payment_status ?? null) === 'refunded');
        return [
            'deposit' => PartnerPayoutPaymentService::money($deposit),
            'refunded' => PartnerPayoutPaymentService::money($refunded),
            'pending' => $legacy ? null : PartnerPayoutPaymentService::money(max(0, $deposit - $refunded)),
            'status' => $legacy ? 'legacy_review_required' : ($deposit === 0 ? 'not_applicable'
                : ($refunded >= $deposit ? 'refunded' : ($refunded > 0 ? 'partial' : 'pending'))),
            'history' => $history,
        ];
    }

    public function record(int $bookingId, array $input, int $actorId): void
    {
        $data = Validator::make($input, [
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000', 'decimal:0,2'],
            'method' => ['required', 'in:cash,online'],
            'reference' => ['required_if:method,online', 'nullable', 'string', 'max:200'],
            'refunded_at' => ['required', 'date', 'before_or_equal:now'],
            'note' => ['required', 'string', 'min:3', 'max:2000'],
            'request_key' => ['required', 'uuid'],
            'confirmed' => ['required', 'accepted'],
        ])->validate();
        $cents = PartnerPayoutPaymentService::cents($data['amount']);
        DB::transaction(function () use ($bookingId, $data, $actorId, $cents): void {
            $actor = User::findOrFail($actorId);
            abort_unless($actor->canUseAdminLogin(), 403);
            $booking = SelfDriveBooking::query()->lockForUpdate()->findOrFail($bookingId);
            $ledger = $booking->security_refund_ledger ?? [];
            $entry = [
                'amount' => PartnerPayoutPaymentService::money($cents), 'method' => $data['method'],
                'reference' => trim($data['reference'] ?? ''),
                'refunded_at' => Carbon::parse($data['refunded_at'])->toIso8601String(),
                'note' => trim($data['note']), 'request_key' => $data['request_key'],
            ];
            foreach ($ledger as $previous) {
                if (($previous['request_key'] ?? null) !== $data['request_key']) continue;
                foreach ($entry as $key => $value) {
                    if (($previous[$key] ?? null) !== $value) {
                        $this->fail('This refund request was already used with different details. Reopen the form.');
                    }
                }
                return; // Network retry / repeated submit must not record twice.
            }
            $summary = self::summary($booking);
            if ($data['method'] === 'online') {
                foreach ($ledger as $previous) {
                    if (($previous['method'] ?? '') === 'online'
                        && strcasecmp(trim($previous['reference'] ?? ''), trim($data['reference'])) === 0) {
                        $this->fail('This transaction reference is already recorded for this booking.');
                    }
                }
            }
            if ($summary['status'] === 'legacy_review_required') {
                $this->fail('An earlier refund is recorded. Admin must reconcile it before adding another refund.');
            }
            $verified = $booking->return_otp_verified_at || ($booking->return_admin_confirmed_at
                && $booking->return_admin_confirmed_by && filled($booking->return_admin_reason));
            if ($booking->booking_type !== 'car' || $booking->status !== 'completed'
                || in_array($booking->booking_status, ['cancelled', 'rejected', 'failed'], true)
                || ! $booking->trip_end_datetime || ! $booking->final_bill_generated_at || ! $verified
                || $booking->payment_status !== 'paid' || (float) $booking->remaining_amount > 0.009
                || PartnerPayoutPaymentService::cents($booking->paid_amount)
                    < PartnerPayoutPaymentService::cents($booking->effectiveRentalAmount())
                        + PartnerPayoutPaymentService::cents($booking->security_deposit)) {
                $this->fail('Confirm actual return, generate the final bill and collect the full customer balance first.');
            }
            if (Carbon::parse($data['refunded_at'])->lt($booking->trip_end_datetime)) {
                $this->fail('Security refund date cannot be before actual vehicle return.');
            }
            if ($cents > PartnerPayoutPaymentService::cents($summary['pending'])) {
                $this->fail('Refund amount exceeds the remaining security deposit.');
            }
            $ledger[] = $entry + ['recorded_by' => $actorId, 'recorded_at' => now()->toIso8601String(),
                'security_deposit_snapshot' => $summary['deposit']];
            $booking->security_refund_ledger = $ledger;
            $booking->save();
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['amount' => $message]);
    }
}
