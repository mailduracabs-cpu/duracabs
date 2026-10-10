<?php
namespace App\Services;
use App\Models\SelfDriveVendorPayout;
use App\Models\TaxiVendorPayout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class PartnerPayoutPaymentService {
    public static function cents(mixed $amount): int {
        $value = is_float($amount) ? number_format($amount, 2, '.', '') : (string) $amount;
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $value)) {
            throw ValidationException::withMessages(['amount'=>'Enter a valid amount with at most two decimal places.']);
        }
        [$whole,$fraction] = array_pad(explode('.', $value, 2),2,'');
        if (strlen($whole) > 10) throw ValidationException::withMessages(['amount'=>'Amount is too large.']);
        return (int)$whole * 100 + (int)str_pad($fraction,2,'0');
    }
    public static function money(int $cents): string {
        return intdiv($cents,100).'.'.str_pad((string)($cents % 100),2,'0',STR_PAD_LEFT);
    }
    public function pay(string $account, int $id, mixed $amount, string $method,
        ?string $reference = null, ?string $date = null, ?string $notes = null, ?string $requestKey = null): void {
        if (!in_array($account,['host','vendor'],true)) abort(422);
        if (!in_array($method,['cash','upi','bank_transfer','cheque','other'],true)) {
            throw ValidationException::withMessages(['method'=>'Select a payment method.']);
        }
        $cents = self::cents($amount);
        if ($cents <= 0) throw ValidationException::withMessages(['amount'=>'Payment must be greater than zero.']);
        $paidAt = $date ? Carbon::parse($date,config('app.timezone')) : now();
        if ($paidAt->isFuture()) throw ValidationException::withMessages(['payment_date'=>'Actual payment date cannot be in the future.']);
        $key = $requestKey ?: (string)Str::uuid();
        DB::transaction(function () use ($account,$id,$cents,$method,$reference,$paidAt,$notes,$key): void {
            $class = $account === 'host' ? SelfDriveVendorPayout::class : TaxiVendorPayout::class;
            $payout = $class::query()->lockForUpdate()->findOrFail($id);
            $previous = DB::table('partner_payout_payments')->where('account',$account)->where('payout_id',$id)->where('request_key',$key)->first();
            if ($previous) {
                if (self::cents($previous->amount) !== $cents || $previous->method !== $method
                    || ($previous->reference ?? '') !== ($reference ?? '') || ($previous->notes ?? '') !== ($notes ?? '')
                    || Carbon::parse($previous->payment_date)->ne($paidAt)) {
                    throw ValidationException::withMessages(['amount'=>'This payment request was already recorded with different details.']);
                }
                return;
            }
            $total = self::cents($payout->payout_amount); $paid = self::cents($payout->paid_amount);
            if ($payout->status === 'cancelled' || $paid > $total || $cents > $total-$paid) {
                throw ValidationException::withMessages(['amount'=>'Payment exceeds the current balance or this payout is cancelled.']);
            }
            $allocations = null;
            if ($account === 'host') {
                $items = $payout->items()->orderBy('start_datetime')->orderBy('id')->lockForUpdate()->get();
                $known = $items->every(fn ($item) => $item->received_amount !== null);
                $itemTotal = $items->sum(fn ($item) => self::cents($item->payout_amount));
                $itemPaid = $known ? $items->sum(fn ($item) => self::cents($item->received_amount)) : null;
                // Legacy zero-paid batches can be initialized safely; partial legacy allocations remain unknown.
                if ($itemTotal === $total && ($paid === 0 || ($known && $itemPaid === $paid))) {
                    $left = $cents; $allocations = [];
                    foreach ($items as $item) {
                        $received = $paid === 0 ? 0 : self::cents($item->received_amount);
                        $part = min($left, max(0,self::cents($item->payout_amount)-$received));
                        $item->received_amount = self::money($received+$part); $item->save(); $left -= $part;
                        if ($part > 0) $allocations[] = ['booking_id'=>$item->self_drive_booking_id,'amount'=>self::money($part)];
                    }
                    if ($left !== 0) throw ValidationException::withMessages(['amount'=>'Booking allocations do not match the payout.']);
                }
            }
            DB::table('partner_payout_payments')->insert([
                'account'=>$account,'payout_id'=>$id,'transporter_profile_id'=>$payout->transporter_profile_id,
                'request_key'=>$key,'amount'=>self::money($cents),'payment_date'=>$paidAt,
                'method'=>$method,'reference'=>$reference,'notes'=>$notes,
                'allocations'=>$allocations === null ? null : json_encode($allocations),
                'recorded_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now(),
            ]);
            $payout->paid_amount = self::money($paid+$cents);
            $payout->remaining_amount = self::money($total-$paid-$cents);
            $payout->status = $paid+$cents === $total ? 'paid' : 'partial';
            $payout->payment_method = $method; $payout->payment_reference = $reference;
            // paid_at remains the full-settlement date; each partial payment has its own history date.
            if ($paid+$cents === $total) $payout->paid_at = $paidAt;
            $payout->save();
        },3);
    }
    public function paySelected(string $account, array $ids, array $data): void {
        if (!in_array($account,['host','vendor'],true)) abort(422);
        DB::transaction(function () use ($account,$ids,$data): void {
            $class = $account === 'host' ? SelfDriveVendorPayout::class : TaxiVendorPayout::class;
            $rows = $class::query()->whereIn('id',$ids)->orderBy('period_from')->orderBy('id')->lockForUpdate()->get();
            if ($rows->isEmpty() || $rows->pluck('transporter_profile_id')->unique()->count() !== 1) {
                throw ValidationException::withMessages(['amount'=>'Select payouts for one partner and one account only.']);
            }
            $left = self::cents($data['amount']);
            if ($left <= 0) throw ValidationException::withMessages(['amount'=>'Payment must be greater than zero.']);
            $balance = $rows->where('status','!=','cancelled')->sum(fn ($row) => self::cents($row->remaining_amount));
            if (DB::table('partner_payout_payments')->where('account',$account)->whereIn('payout_id',$ids)->where('request_key',$data['request_key'])->exists()) {
                throw ValidationException::withMessages(['amount'=>'This payment request was already recorded. Refresh the list.']);
            }
            if ($left > $balance) throw ValidationException::withMessages(['amount'=>'Amount exceeds the selected pending balance.']);
            foreach ($rows as $row) {
                if ($row->status === 'cancelled' || $left === 0) continue;
                $part = min($left,self::cents($row->remaining_amount));
                if ($part === 0) continue;
                $this->pay($account,$row->id,self::money($part),$data['method'],$data['reference'] ?? null,
                    $data['payment_date'],$data['notes'] ?? null,$data['request_key']);
                $left -= $part;
            }
            if ($left !== 0) throw ValidationException::withMessages(['amount'=>'Selected allocations did not cover the payment.']);
        },3);
    }
}
