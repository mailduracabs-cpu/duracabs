<?php
namespace App\Console\Commands;
use App\Models\SelfDriveBooking;
use App\Models\Order;
use App\Models\TaxiVendorPayout;
use App\Models\FleetManagement\TransporterProfile;
use App\Services\SelfDriveVendorPayoutService;
use App\Services\PartnerPayoutPaymentService as Money;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
class SyncPartnerPayouts extends Command {
    protected $signature = 'partner:sync-payouts {--dry-run : Preview without changing bookings or payouts}';
    protected $description = 'Mark due returns and generate unpaid partner settlements without duplicate booking entries';
    public function handle(): int {
        foreach (['self_drive_vendor_payouts','self_drive_vendor_payout_items','taxi_vendor_payouts','partner_payout_payments'] as $table) {
            if (!Schema::hasTable($table)) { $this->error('Run the payout migrations first. Missing: '.$table); return self::FAILURE; }
        }
        if (!Schema::hasColumn('self_drive_vendor_payout_items','received_amount')) return self::FAILURE;
        $dry = (bool)$this->option('dry-run'); $changed = 0;
        SelfDriveBooking::query()->when(Schema::hasColumn('self_drive_bookings','deleted_at'),fn ($q) => $q->whereNull('deleted_at'))->where('end_datetime','<=',now())
            ->where(function ($q) { $q->where('status','running')->orWhere('booking_status','running'); })
            ->orderBy('id')->chunkById(200,function ($bookings) use ($dry,&$changed): void {
                foreach ($bookings as $booking) {
                    DB::transaction(function () use ($booking,$dry,&$changed): void {
                        $b = SelfDriveBooking::query()->lockForUpdate()->find($booking->id);
                        if (!$b || !$b->isRunning() || $b->isReturnPending() || !$b->end_datetime || $b->end_datetime->isFuture()) return;
                        // Legacy primary status is an enum without return_pending; workflow status is a string.
                        if (!$dry) { $b->booking_status = 'return_pending'; $b->save(); }
                        $changed++;
                    });
                }
            });
        $generated = 0; $skipped = 0;
        // Only actual completed/returned trips are candidates for the existing payout calculation.
        SelfDriveBooking::query()->when(Schema::hasColumn('self_drive_bookings','deleted_at'),fn ($q) => $q->whereNull('deleted_at'))->whereNotNull('transporter_profile_id')->whereNotNull('start_datetime')
            ->where(function ($q) { $q->where('status','completed')->orWhere('booking_status','completed'); })
            ->whereNotIn('status',['cancelled','rejected','failed'])->whereNotIn('booking_status',['cancelled','rejected','failed'])
            ->whereIn('payment_status',['paid','partial'])->where('paid_amount','>',0)
            ->whereNotNull('return_otp_verified_at')->whereNotNull('final_bill_generated_at')->whereNotNull('trip_end_datetime')
            ->when(Schema::hasColumn('self_drive_bookings','booking_type'),fn ($q) => $q->where('booking_type','car'))
            ->whereDoesntHave('vendorPayoutItem')->orderBy('id')->chunkById(200,function ($bookings) use ($dry,&$generated,&$skipped): void {
                $service = app(SelfDriveVendorPayoutService::class);
                foreach ($bookings as $booking) {
                    if ($booking->hasVendorPayout()) continue;
                    $from = $booking->start_datetime->copy()->startOfMonth(); $to = $from->copy()->endOfMonth();
                    try {
                        if ($dry) { if ($service->calculateBookingPayout($booking) !== null) $generated++; else $skipped++; }
                        else { $service->generate((int)$booking->transporter_profile_id,$from,$to,'Automatically generated from completed, returned bookings.'); $generated++; }
                    } catch (ValidationException $e) { $skipped++; $this->warn('Self Drive booking #'.$booking->id.': '. $e->getMessage()); }
                }
            });
        $taxi = 0;
        Order::query()->when(Schema::hasColumn('orders','deleted_at'),fn ($q) => $q->whereNull('deleted_at'))->whereNotNull('transporter_id')->where('partner_offer_status','accepted')
            ->where('partner_offer_amount','>',0)->whereIn('status',['confirm','running','closed','completed'])
            ->whereNotIn('ride_type',['self_drive','bike','bike_rental'])->orderBy('id')
            ->chunkById(200,function ($orders) use ($dry,&$taxi,&$skipped): void {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order,$dry,&$taxi,&$skipped): void {
                        $o = Order::query()->lockForUpdate()->find($order->id);
                        if (!$o || $o->partner_offer_status !== 'accepted' || !in_array($o->status,['confirm','running','closed','completed'],true)) return;
                        if (TaxiVendorPayout::where('order_id',$o->id)->exists()) return;
                        if (!in_array($o->status,['closed','completed'],true)) {
                            $endDate = $o->dateTo ?: $o->date;
                            $endTime = trim((string)$o->endTime);
                            if (!$endDate || !preg_match('/^\d{2}:\d{2}(?::\d{2})?$/D',$endTime)) { $skipped++; return; }
                            try {
                                $text = substr((string)$endDate,0,10).' '.$endTime;
                                $end = Carbon::createFromFormat(strlen($endTime) === 5 ? '!Y-m-d H:i' : '!Y-m-d H:i:s',$text,config('app.timezone'));
                                if (!$end || $end->format(strlen($endTime) === 5 ? 'Y-m-d H:i' : 'Y-m-d H:i:s') !== $text || $end->isFuture()) return;
                                if (!preg_match('/^\d{2}:\d{2}(?::\d{2})?$/D',trim((string)$o->time))) { $skipped++; return; }
                                $startText = substr((string)$o->date,0,10).' '.trim((string)$o->time);
                                $startFormat = strlen(trim((string)$o->time)) === 5 ? '!Y-m-d H:i' : '!Y-m-d H:i:s';
                                $start = Carbon::createFromFormat($startFormat,$startText,config('app.timezone'));
                                if (!$start || $start->format(substr($startFormat,1)) !== $startText) { $skipped++; return; }
                                if ($end->lte($start)) { $skipped++; return; }
                            } catch (\Throwable $e) { $skipped++; return; }
                        }
                        $profiles = TransporterProfile::query()->where('user_id',$o->transporter_id)->whereIn('partner_type',['vendor','both'])->get();
                        if ($profiles->count() !== 1 || !$o->date) { $skipped++; return; }
                        $amount = Money::money(Money::cents($o->partner_offer_amount));
                        if (Money::cents($amount) <= 0) { $skipped++; return; }
                        if (!$dry) {
                            if (!in_array($o->status,['closed','completed'],true)) { $o->status = 'closed'; $o->save(); }
                            TaxiVendorPayout::create([
                                'transporter_profile_id'=>$profiles->first()->id,'order_id'=>$o->id,
                                'payout_no'=>'TXVP'.str_pad((string)$o->id,6,'0',STR_PAD_LEFT),
                                'period_from'=>substr((string)$o->date,0,10),'period_to'=>substr((string)($o->dateTo ?: $o->date),0,10),
                                'payout_amount'=>$amount,'paid_amount'=>'0.00','remaining_amount'=>$amount,'status'=>'pending',
                                'notes'=>'Accepted admin partner offer; customer fare and deposits excluded.',
                            ]);
                        }
                        $taxi++;
                    });
                }
            });
        $this->info(($dry ? 'Preview: ' : 'Updated: ')."returns=$changed; self-drive ".($dry ? 'eligible bookings' : 'batches')."=$generated; taxi settlements=$taxi; skipped=$skipped");
        return self::SUCCESS;
    }
}
