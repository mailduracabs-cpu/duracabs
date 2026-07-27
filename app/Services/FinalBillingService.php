<?php

namespace App\Services;

class FinalBillingService
{
    private const WITH_DRIVER_GST = 5.0;
    private const SELF_DRIVE_GST = 18.0;
    private const ONLINE_PAYMENT_CHARGE = 3.0;

    /**
     * Calculate the complete final bill and payment settlement.
     *
     * This method remains backward-compatible with the previous service while
     * also supporting advance, wallet, Razorpay, security-deposit adjustment,
     * refunds and final settlement.
     */
    public function calculate(array $data): array
    {
        $serviceType = $this->normalizeServiceType(
            $data['service_type']
                ?? $data['ride_type']
                ?? $data['booking_type']
                ?? 'with_driver'
        );

        $baseFare = $this->money(
            $data['base_fare']
                ?? $data['subtotal']
                ?? $data['total_amount']
                ?? 0
        );

        $extraHourAmount = $this->money($data['extra_hour_amount'] ?? 0);

        $unlimitedKmSelected = $this->boolValue(
            $data['unlimited_km_selected']
                ?? $data['unlimited_kms']
                ?? false
        );

        $extraKmAmount = $unlimitedKmSelected
            ? 0.0
            : $this->money($data['extra_km_amount'] ?? 0);

        $specialRequestAmount = $this->money(
            $data['special_request_total']
                ?? $data['extra_service_amount']
                ?? 0
        );

        $tollAmount = $this->money($data['toll_amount'] ?? 0);
        $parkingAmount = $this->money($data['parking_amount'] ?? 0);

        $governmentTaxAmount = $this->money(
            $data['government_tax_amount']
                ?? $data['permit_tax_amount']
                ?? $data['tax_amount']
                ?? 0
        );

        $securityDeposit = $this->money($data['security_deposit'] ?? 0);
        $damageAmount = $this->money($data['damage_amount'] ?? 0);
        $fuelCharge = $this->money($data['fuel_charge'] ?? 0);
        $cleaningCharge = $this->money($data['cleaning_charge'] ?? 0);
        $lateReturnCharge = $this->money($data['late_return_charge'] ?? 0);
        $otherCharge = $this->money($data['other_charge'] ?? 0);

        $couponDiscount = $this->money(
            $data['coupon_discount']
                ?? $data['coupon_value']
                ?? 0
        );

        $taxableAmount = round(
            $baseFare
            + $extraHourAmount
            + $extraKmAmount
            + $specialRequestAmount,
            2
        );

        $gstPercent = $serviceType === 'self_drive'
            ? self::SELF_DRIVE_GST
            : self::WITH_DRIVER_GST;

        $gstAmount = round($taxableAmount * $gstPercent / 100, 2);

        $nonTaxableAmount = round(
            $tollAmount + $parkingAmount + $governmentTaxAmount,
            2
        );

        $tripAdjustmentCharges = round(
            $damageAmount
            + $fuelCharge
            + $cleaningCharge
            + $lateReturnCharge
            + $otherCharge,
            2
        );

        $fareBeforeDiscount = round(
            $taxableAmount + $gstAmount + $nonTaxableAmount,
            2
        );

        $discountApplied = min($couponDiscount, $fareBeforeDiscount);

        $fareAfterDiscount = max(
            0,
            round($fareBeforeDiscount - $discountApplied, 2)
        );

        $paymentMethod = $this->normalizePaymentMethod(
            $data['payment_method'] ?? 'cash'
        );

        $isOnline = $this->isOnlinePayment($paymentMethod);

        $onlineChargeEnabled = $this->boolValue(
            $data['apply_online_payment_charge'] ?? true
        );

        $onlinePaymentChargePercent = ($isOnline && $onlineChargeEnabled)
            ? $this->percentage(
                $data['online_payment_charge_percent']
                    ?? self::ONLINE_PAYMENT_CHARGE
            )
            : 0.0;

        // Security deposit and trip adjustment charges are excluded from the
        // gateway convenience charge by default.
        $onlinePaymentCharge = round(
            $fareAfterDiscount * $onlinePaymentChargePercent / 100,
            2
        );

        $bookingTotal = round(
            $fareAfterDiscount
            + $onlinePaymentCharge
            + $securityDeposit
            + $tripAdjustmentCharges,
            2
        );

        $advancePaid = $this->money($data['advance_paid'] ?? 0);
        $walletPaid = $this->money($data['wallet_paid'] ?? 0);
        $razorpayPaid = $this->money($data['razorpay_paid'] ?? 0);
        $cashPaid = $this->money($data['cash_paid'] ?? 0);
        $otherPaid = $this->money($data['other_paid'] ?? 0);

        $explicitPaidAmount = array_key_exists('paid_amount', $data)
            ? $this->money($data['paid_amount'])
            : null;

        $calculatedPaidAmount = round(
            $advancePaid
            + $walletPaid
            + $razorpayPaid
            + $cashPaid
            + $otherPaid,
            2
        );

        // paid_amount remains authoritative when supplied by an existing
        // controller/model; otherwise channel-wise values are used.
        $paidAmount = $explicitPaidAmount !== null
            ? $explicitPaidAmount
            : $calculatedPaidAmount;

        $alreadyRefunded = $this->money(
            $data['refunded_amount']
                ?? $data['already_refunded']
                ?? 0
        );

        $netPaidAmount = max(0, round($paidAmount - $alreadyRefunded, 2));

        $securityDepositPaid = $this->money(
            $data['security_deposit_paid']
                ?? min($securityDeposit, $netPaidAmount)
        );

        $adjustSecurityFromDeposit = $this->boolValue(
            $data['adjust_charges_from_security'] ?? true
        );

        $securityAdjustedAmount = $adjustSecurityFromDeposit
            ? min($securityDepositPaid, $tripAdjustmentCharges)
            : 0.0;

        $securityRefundableAmount = max(
            0,
            round($securityDepositPaid - $securityAdjustedAmount, 2)
        );

        $remainingAmount = max(
            0,
            round($bookingTotal - $netPaidAmount, 2)
        );

        $overpaidAmount = max(
            0,
            round($netPaidAmount - $bookingTotal, 2)
        );

        $requestedRefundAmount = $this->money(
            $data['requested_refund_amount']
                ?? $data['refund_requested']
                ?? 0
        );

        $systemRefundAmount = round(
            $overpaidAmount + $securityRefundableAmount,
            2
        );

        $refundAmount = $requestedRefundAmount > 0
            ? min($requestedRefundAmount, $netPaidAmount)
            : min($systemRefundAmount, $netPaidAmount);

        $refundPendingAmount = max(
            0,
            round($refundAmount - $alreadyRefunded, 2)
        );

        $paymentStatus = $this->paymentStatus(
            $bookingTotal,
            $netPaidAmount,
            $remainingAmount
        );

        $settlementStatus = $this->settlementStatus(
            $remainingAmount,
            $refundPendingAmount
        );

        return [
            'service_type' => $serviceType,
            'base_fare' => $baseFare,
            'special_request_total' => $specialRequestAmount,
            'extra_hour_amount' => $extraHourAmount,
            'extra_km_amount' => $extraKmAmount,
            'unlimited_km_selected' => $unlimitedKmSelected,
            'taxable_amount' => $taxableAmount,
            'gst_percent' => $gstPercent,
            'gst_amount' => $gstAmount,
            'toll_amount' => $tollAmount,
            'parking_amount' => $parkingAmount,
            'government_tax_amount' => $governmentTaxAmount,
            'non_taxable_amount' => $nonTaxableAmount,
            'security_deposit' => $securityDeposit,
            'damage_amount' => $damageAmount,
            'fuel_charge' => $fuelCharge,
            'cleaning_charge' => $cleaningCharge,
            'late_return_charge' => $lateReturnCharge,
            'other_charge' => $otherCharge,
            'trip_adjustment_charges' => $tripAdjustmentCharges,
            'coupon_discount' => $couponDiscount,
            'discount_applied' => $discountApplied,
            'fare_before_discount' => $fareBeforeDiscount,
            'fare_after_discount' => $fareAfterDiscount,
            'online_payment_charge_percent' => $onlinePaymentChargePercent,
            'online_payment_charge' => $onlinePaymentCharge,
            'payment_method' => $paymentMethod,
            'grand_total' => $bookingTotal,
            'booking_total' => $bookingTotal,
            'advance_paid' => $advancePaid,
            'wallet_paid' => $walletPaid,
            'razorpay_paid' => $razorpayPaid,
            'cash_paid' => $cashPaid,
            'other_paid' => $otherPaid,
            'paid_amount' => $paidAmount,
            'already_refunded' => $alreadyRefunded,
            'net_paid_amount' => $netPaidAmount,
            'remaining_amount' => $remainingAmount,
            'overpaid_amount' => $overpaidAmount,
            'security_deposit_paid' => $securityDepositPaid,
            'security_adjusted_amount' => $securityAdjustedAmount,
            'security_refundable_amount' => $securityRefundableAmount,
            'refund_amount' => $refundAmount,
            'refund_pending_amount' => $refundPendingAmount,
            'payment_status' => $paymentStatus,
            'settlement_status' => $settlementStatus,
            'is_fully_paid' => $remainingAmount <= 0,
            'requires_payment' => $remainingAmount > 0,
            'requires_refund' => $refundPendingAmount > 0,
        ];
    }

    private function normalizeServiceType(mixed $value): string
    {
        $type = strtolower(trim((string) $value));

        return in_array(
            $type,
            [
                'self_drive',
                'self-drive',
                'without_driver',
                'bike_rental',
                'bike-rental',
                'rental',
            ],
            true
        ) ? 'self_drive' : 'with_driver';
    }

    private function normalizePaymentMethod(mixed $value): string
    {
        $method = strtolower(trim((string) $value));

        return $method !== '' ? $method : 'cash';
    }

    private function isOnlinePayment(string $paymentMethod): bool
    {
        return in_array(
            $paymentMethod,
            [
                'razorpay',
                'online',
                'upi',
                'card',
                'netbanking',
                'net_banking',
                'wallet_razorpay',
                'wallet+razorpay',
            ],
            true
        );
    }

    private function paymentStatus(
        float $grandTotal,
        float $netPaidAmount,
        float $remainingAmount
    ): string {
        if ($grandTotal <= 0) {
            return 'not_required';
        }

        if ($remainingAmount <= 0) {
            return 'paid';
        }

        if ($netPaidAmount > 0) {
            return 'partially_paid';
        }

        return 'pending';
    }

    private function settlementStatus(
        float $remainingAmount,
        float $refundPendingAmount
    ): string {
        if ($refundPendingAmount > 0) {
            return 'refund_pending';
        }

        if ($remainingAmount > 0) {
            return 'payment_pending';
        }

        return 'settled';
    }

    private function percentage(mixed $value): float
    {
        return min(100, $this->money($value));
    }

    private function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return round(max(0, (float) $value), 2);
        }

        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return round(max(0, (float) ($cleaned ?: 0)), 2);
    }

    private function boolValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return in_array(
            strtolower(trim((string) $value)),
            ['1', 'true', 'yes', 'on', 'selected'],
            true
        );
    }
}