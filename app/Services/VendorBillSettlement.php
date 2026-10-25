<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VendorBillSettlement
{
    /** Accounting confirmation only; does not initiate a bank/wallet payment. */
    public function markPaid(int $id, User $actor, string $reference): void
    {
        abort_unless($actor->canUseAdminLogin(), 403);
        Validator::make(['reference' => trim($reference)], ['reference' => ['required', 'string', 'max:150']])->validate();
        DB::transaction(function () use ($id, $actor, $reference): void {
            $bill = DB::table('vendor_monthly_bills')->where('id', $id)->lockForUpdate()->first();
            abort_unless($bill, 404);
            if ($bill->status === 'paid') return;
            abort_unless($bill->status === 'pending', 409, 'Bill needs admin review.');
            DB::table('vendor_monthly_bills')->where('id', $id)->update([
                'status' => 'paid', 'paid_at' => now(), 'paid_by' => $actor->id,
                'payment_reference' => trim($reference), 'updated_at' => now(),
            ]);
        });
    }
}
