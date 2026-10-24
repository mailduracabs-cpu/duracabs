<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\SelfDriveDamageReview;

class ProcessSelfDriveDamage extends Command
{
    protected $signature='self-drive-damage:process {--booking= : Explicit booking ID, including historical bookings} {--retry-report= : Retry an existing manual-review report using saved evidence} {--limit=10 : Maximum new photo sets per run}';
    protected $description='Compare self drive pickup/return photos and notify admins';
    public function handle(SelfDriveDamageReview $review): int
    {
        if (!config('self_drive_damage.enabled')) { $this->info('Damage AI is disabled.'); return self::SUCCESS; }
        if (!config('self_drive_damage.since') && !$this->option('booking') && !$this->option('retry-report')) { $this->error('Set SELF_DRIVE_DAMAGE_AI_SINCE to deployment date before automatic processing.'); return self::FAILURE; }
        $disk=Storage::disk('local'); $disk->makeDirectory('self-drive-damage');
        $lock=fopen($disk->path('self-drive-damage/process.lock'),'c');
        if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) { if ($lock) fclose($lock); $this->info('Another comparison worker is running.'); return self::SUCCESS; }
        try {
            // A killed process never reports a successful/clean comparison. Explicitly manual review.
            DB::table('self_drive_damage_reports')->where('status','pending')->update(['status'=>'manual_review','error'=>'Processing interrupted; inspect photos manually.','updated_at'=>now()]);
            if ($this->option('retry-report')) { $review->retry((int)$this->option('retry-report')); $this->info('Report retried.'); return self::SUCCESS; }
            $pending=DB::table('self_drive_damage_reports')->whereNull('notified_at')->whereIn('status',['possible_damage','manual_review'])->get();
            foreach ($pending as $report) $review->notify($report);
            $query=DB::table('self_drive_bookings')->where('booking_type','car')->where(function ($q) {
                $q->whereIn('status',['return_pending','completed','final_bill_pending'])->orWhereIn('booking_status',['return_pending','completed','final_bill_pending']);
            });
            if ($this->option('booking')) $query->where('id',(int)$this->option('booking'));
            else $query->where('updated_at','>=',config('self_drive_damage.since'));
            $processed=0; $limit=max(1,min(100,(int)$this->option('limit')));
            // Revisit all eligible returns, so an old ID returning later is never skipped by a cursor.
            foreach ($query->orderBy('id')->cursor() as $booking) {
                $before=(int)DB::table('self_drive_damage_reports')->where('booking_id',$booking->id)->count();
                $review->process($booking);
                $after=(int)DB::table('self_drive_damage_reports')->where('booking_id',$booking->id)->count();
                if ($after>$before && ++$processed >= $limit) break;
            }
            $this->info('New damage reports: '.$processed.'. Existing photo sets skipped.');
            return self::SUCCESS;
        } finally { flock($lock,LOCK_UN); fclose($lock); }
    }
}
