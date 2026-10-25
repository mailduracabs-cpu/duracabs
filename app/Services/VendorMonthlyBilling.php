<?php

namespace App\Services;

use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorMonthlyBilling
{
    /** Calendar periods with an explicit policy; no financial default is silently chosen. */
    public static function periods(string $joining, string $expiry, string $asOf, string $basis, int $day, string $partial): array
    {
        if (! in_array($basis, ['joining_date', 'fixed_day'], true) || ! in_array($partial, ['prorate', 'full'], true) || $day < 1 || $day > 28) {
            throw ValidationException::withMessages(['billing' => 'Choose joining_date/fixed_day, day 1–28 and prorate/full before enabling bills.']);
        }
        \Illuminate\Support\Facades\Validator::make(['joining' => $joining, 'expiry' => $expiry, 'as_of' => $asOf], [
            'joining' => ['required', 'date_format:Y-m-d'], 'expiry' => ['required', 'date_format:Y-m-d', 'after:joining'], 'as_of' => ['required', 'date_format:Y-m-d'],
        ])->validate();
        $join = VendorLifecycleClock::date($joining);
        $stop = VendorLifecycleClock::date($expiry);
        $today = VendorLifecycleClock::date($asOf);
        $anchor = $basis === 'joining_date' ? $join : $join->startOfMonth()->day($day);
        if ($anchor->gt($join)) $anchor = $anchor->subMonthNoOverflow();
        $periods = [];
        for ($n = 0; $n < 48; $n++) {
            $nominalStart = $anchor->addMonthsNoOverflow($n);
            $boundary = $anchor->addMonthsNoOverflow($n + 1);
            if ($boundary->gt($today) || $nominalStart->gte($stop)) break;
            $start = $nominalStart->max($join);
            $end = $boundary->min($stop);
            if ($end->lte($start)) continue;
            $periods[] = [
                'period_start' => $start->toDateString(), 'period_end' => $end->subDay()->toDateString(),
                'bill_date' => $boundary->toDateString(), 'billable_days' => (int) $start->diffInDays($end),
                'cycle_days' => (int) $nominalStart->diffInDays($boundary), 'partial_policy' => $partial,
            ];
        }
        return $periods;
    }

    public function generateForVehicle(int $vehicleId): int
    {
        $basis = (string) config('vendor_lifecycle.billing_basis');
        $partial = (string) config('vendor_lifecycle.partial_period');
        $since = config('vendor_lifecycle.billing_since');
        if (! $since) throw new \RuntimeException('Set VENDOR_MONTHLY_BILLING_SINCE before enabling bills.');
        \Illuminate\Support\Facades\Validator::make(['since' => $since], ['since' => ['required', 'date_format:Y-m-d']])->validate();
        return DB::transaction(function () use ($vehicleId, $basis, $partial, $since): int {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($vehicleId);
            if ($vehicle->partner_payout_mode !== 'monthly' || ! $vehicle->partner_joining_date || ! $vehicle->partner_agreement_expires_on || ! $vehicle->transporter_profile_id) return 0;
            $expiry = $vehicle->partner_agreement_expires_on->toDateString();
            if ($vehicle->getAttribute('partner_removed_at')) $expiry = min($expiry, substr((string) $vehicle->getRawOriginal('partner_removed_at'), 0, 10));
            if ($expiry <= $vehicle->partner_joining_date->toDateString()) return 0;
            $periods = self::periods($vehicle->partner_joining_date->toDateString(), $expiry, VendorLifecycleClock::today()->toDateString(), $basis, (int) config('vendor_lifecycle.billing_day'), $partial);
            $monthly = PartnerPayoutPaymentService::cents(PartnerRentalPayoutCalculator::monthly((string) $vehicle->partner_monthly_amount));
            $count = 0;
            foreach ($periods as $period) {
                if ($period['bill_date'] < $since) continue;
                $overlap = DB::table('vendor_monthly_bills')->where('vehicle_id', $vehicle->id)
                    ->where('period_start', '<=', $period['period_end'])->where('period_end', '>=', $period['period_start'])->first();
                if ($overlap) {
                    if ((int) $overlap->transporter_profile_id !== (int) $vehicle->transporter_profile_id) {
                        throw ValidationException::withMessages(['billing' => 'Vendor ownership changed within an existing bill period. Admin review required.']);
                    }
                    if ($overlap->period_start !== $period['period_start'] || $overlap->period_end !== $period['period_end']) {
                        throw ValidationException::withMessages(['billing' => 'Agreement dates overlap an existing bill. Admin review required.']);
                    }
                    continue;
                }
                $paise = $partial === 'full' ? $monthly : intdiv($monthly * $period['billable_days'] + intdiv($period['cycle_days'], 2), $period['cycle_days']);
                $amount = intdiv($paise, 100) . '.' . str_pad((string) ($paise % 100), 2, '0', STR_PAD_LEFT);
                $id = DB::table('vendor_monthly_bills')->insertGetId([
                    'bill_no' => 'VM-' . $vehicle->id . '-' . str_replace('-', '', $period['period_start']),
                    'vehicle_id' => $vehicle->id, 'transporter_profile_id' => $vehicle->transporter_profile_id,
                    'period_start' => $period['period_start'], 'period_end' => $period['period_end'], 'bill_date' => $period['bill_date'],
                    'monthly_amount' => $vehicle->partner_monthly_amount, 'amount' => $amount, 'status' => 'pending',
                    'terms' => json_encode($vehicle->partnerPayoutTerms() + $period + ['billing_basis' => $basis, 'vehicle_number' => $vehicle->vehicle_number, 'vendor_name' => $vehicle->transporter?->company_name ?: $vehicle->transporter?->contact_person]),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->queueBill($id, $vehicle);
                $count++;
            }
            return $count;
        });
    }

    private function queueBill(int $id, Vehicle $vehicle): void
    {
        $profile = $vehicle->transporter;
        if (! $profile) return;
        $bill = DB::table('vendor_monthly_bills')->where('id', $id)->first();
        $title = 'DuraCabs monthly bill ' . $bill->bill_no;
        $body = "Vehicle {$vehicle->vehicle_number}; period {$bill->period_start} to {$bill->period_end}; amount INR {$bill->amount}. View the bill in the Partner App. This is not a payment receipt.";
        $payload = ['title' => $title, 'body' => $body, 'bill_id' => $id];
        $delivery = app(VendorLifecycleDelivery::class);
        $delivery->admins('monthly-bill-admin:' . $id, $title, $body);
        if (config('vendor_lifecycle.email_enabled')) $delivery->queue('monthly-bill:' . $id, 'email', (string) ($profile->email ?: $profile->user?->email), $payload);
        if (config('vendor_lifecycle.whatsapp_enabled')) $delivery->queue('monthly-bill:' . $id, 'whatsapp', (string) ($profile->whatsapp_number ?: $profile->mobile ?: $profile->user?->mobile),
            $payload + ['template_key' => config('vendor_lifecycle.bill_template_key'), 'parameters' => [$bill->bill_no, $vehicle->vehicle_number, $bill->period_start . ' to ' . $bill->period_end, (string) $bill->amount, \Illuminate\Support\Facades\URL::temporarySignedRoute('vendor-lifecycle.shared-bill', now()->addHours(48), ['bill' => $id])]]);
    }

    public function billCopy(int $id): array
    {
        $bill = DB::table('vendor_monthly_bills')->where('id', $id)->first();
        abort_unless($bill, 404);
        $html = view('vendor-lifecycle.bill', ['bill' => $bill])->render();
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return ['bytes' => \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output(), 'mime' => 'application/pdf', 'filename' => $bill->bill_no . '.pdf'];
        }
        return ['bytes' => $html, 'mime' => 'text/html; charset=UTF-8', 'filename' => $bill->bill_no . '.html'];
    }
}
