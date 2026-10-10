<?php

namespace App\Services;

/** Reuses payout money validation: amounts must be nonnegative, whole paise. */
class ReturnBillMoney
{
    public static function rental(mixed $base, array $charges): string
    {
        $total = PartnerPayoutPaymentService::cents($base);
        foreach ($charges as $charge) $total += PartnerPayoutPaymentService::cents($charge);
        return PartnerPayoutPaymentService::money($total);
    }
}
