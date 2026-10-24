<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use RuntimeException;

class SelfDriveDamageReview
{
    public function sources(object $booking): array
    {
        $decode = fn ($v) => is_array($v) ? $v : (json_decode($v ?: '[]',true) ?: []);
        $before = $decode($booking->pickup_images ?? null);
        $draft = $decode($booking->return_draft ?? null);
        $after = !empty($draft['photos']) ? $draft['photos'] : $decode($booking->drop_images ?? null);
        $sources = [];
        foreach ([['BEFORE',$before,'public','self-drive/inspections/pickup/'], ['AFTER',$after,!empty($draft['photos'])?'local':'public',!empty($draft['photos'])?'partner-return/'.$booking->id.'/':'self-drive/inspections/drop/']] as [$phase,$list,$disk,$prefix]) {
            if (!is_array($list) || count($list)>10) throw new RuntimeException('Too many or invalid inspection photos.');
            foreach ($list as $label=>$path) {
                if (!is_string($path) || !str_starts_with($path,$prefix) || str_contains($path,'..') || str_contains($path,'\\')) throw new RuntimeException('Invalid inspection photo path.');
                $sources[] = compact('phase','label','disk','path');
            }
        }
        return $sources;
    }

    public function read(array $source): string
    {
        $disk = Storage::disk($source['disk']);
        $root = realpath($disk->path(''));
        $path = realpath($disk->path($source['path']));
        if (!$root || !$path || !str_starts_with($path,$root.DIRECTORY_SEPARATOR) || !is_file($path) || filesize($path)>26*1024*1024) throw new RuntimeException('Inspection photo missing or too large.');
        $bytes = file_get_contents($path);
        $size = @getimagesizefromstring($bytes);
        if (!$size || !in_array($size['mime'],['image/jpeg','image/png','image/webp'],true) || $size[0]*$size[1]>24000000) throw new RuntimeException('Invalid inspection image.');
        // Keep snapshots manageable and strip metadata before sending to AI.
        if (!function_exists('imagecreatefromstring')) throw new RuntimeException('PHP GD extension required for private image preparation.');
        $image = @imagecreatefromstring($bytes);
        if (!$image) throw new RuntimeException('Image decoding failed.');
        $scale = min(1,1600/max($size[0],$size[1]));
        $out = imagecreatetruecolor(max(1,(int)($size[0]*$scale)),max(1,(int)($size[1]*$scale)));
        imagefill($out,0,0,imagecolorallocate($out,255,255,255));
        imagecopyresampled($out,$image,0,0,0,0,imagesx($out),imagesy($out),$size[0],$size[1]);
        ob_start(); imagejpeg($out,null,88); $jpeg = ob_get_clean();
        imagedestroy($out); imagedestroy($image);
        if (!is_string($jpeg) || strlen($jpeg)>5*1024*1024) throw new RuntimeException('Image preparation failed.');
        return $jpeg;
    }

    public function process(object $booking): void
    {
        $images=[]; $manifest=[]; $error=null; $sources=[];
        try {
            $sources=$this->sources($booking);
            foreach ($sources as $index=>$source) {
                $id = ($source['phase']==='BEFORE'?'before_':'after_').($index+1);
                $bytes=$this->read($source);
                $images[$id]=['phase'=>$source['phase'],'label'=>(string)$source['label'],'mime'=>'image/jpeg','bytes'=>$bytes];
                $manifest[$id]=['phase'=>$source['phase'],'label'=>(string)$source['label'],'sha256'=>hash('sha256',$bytes)];
            }
            $phases=array_count_values(array_column($manifest,'phase'));
            if (empty($phases['BEFORE']) || empty($phases['AFTER'])) throw new RuntimeException('Pickup or return photos missing; manual review required.');
        } catch (\Throwable $e) { $error='Inspection photos unavailable or invalid; manual review required.'; }
        // Include paths as well as content. Changed photo sets produce a new report.
        $fingerprint=hash('sha256',json_encode([$sources,$manifest,$error],JSON_THROW_ON_ERROR));
        $existing=DB::table('self_drive_damage_reports')->where('booking_id',$booking->id)->where('fingerprint',$fingerprint)->first();
        if ($existing) { $this->notify($existing); return; }
        $id=DB::table('self_drive_damage_reports')->insertGetId([
            'booking_id'=>$booking->id,'fingerprint'=>$fingerprint,'status'=>'pending','manifest'=>'[]',
            'model'=>config('self_drive_damage.model'),'created_at'=>now(),'updated_at'=>now(),
        ]);
        try {
            foreach ($images as $key=>$image) {
                $path='self-drive-damage/'.$id.'/'.$key.'.jpg';
                if (!Storage::disk('local')->put($path,$image['bytes'])) throw new RuntimeException('Private evidence storage failed.');
                $manifest[$key]['path']=$path;
            }
            DB::table('self_drive_damage_reports')->where('id',$id)->update(['manifest'=>json_encode($manifest,JSON_THROW_ON_ERROR)]);
            if ($error) throw new RuntimeException($error);
            $result=app(SelfDriveDamageVision::class)->compare($images);
            DB::table('self_drive_damage_reports')->where('id',$id)->update(['status'=>$result['status'],'result'=>json_encode($result,JSON_THROW_ON_ERROR),'updated_at'=>now()]);
        } catch (\Throwable $e) {
            // No raw provider exception or customer photo data in logs/report.
            DB::table('self_drive_damage_reports')->where('id',$id)->update(['status'=>'manual_review','error'=>$error ?: 'AI comparison unavailable or incomplete. Check AI configuration and inspect photos manually.','updated_at'=>now()]);
        }
        $this->notify(DB::table('self_drive_damage_reports')->find($id));
    }

    public function retry(int $id): void
    {
        $row=DB::table('self_drive_damage_reports')->find($id);
        if (!$row || $row->status !== 'manual_review') throw new RuntimeException('Only an existing manual-review report can be retried.');
        $manifest=json_decode($row->manifest ?: '[]',true); $images=[];
        foreach ($manifest as $key=>$image) {
            if (!preg_match('/^(before|after)_[0-9]+$/',$key) || ($image['path'] ?? '') !== 'self-drive-damage/'.$id.'/'.$key.'.jpg') throw new RuntimeException('Private evidence unavailable.');
            $bytes=Storage::disk('local')->get($image['path']);
            if (hash('sha256',$bytes)!==$image['sha256']) throw new RuntimeException('Evidence checksum changed.');
            $images[$key]=['phase'=>$image['phase'],'label'=>$image['label'],'mime'=>'image/jpeg','bytes'=>$bytes];
        }
        $phases=array_count_values(array_column($images,'phase'));
        if (empty($phases['BEFORE']) || empty($phases['AFTER'])) throw new RuntimeException('Both pickup and return snapshots required. Correct booking photos and run normal processing.');
        $result=app(SelfDriveDamageVision::class)->compare($images);
        DB::table('self_drive_damage_reports')->where('id',$id)->update(['status'=>$result['status'],'result'=>json_encode($result,JSON_THROW_ON_ERROR),'model'=>config('self_drive_damage.model'),'error'=>null,'notified_at'=>null,'updated_at'=>now()]);
        $this->notify(DB::table('self_drive_damage_reports')->find($id));
    }

    public function notify(object $report): void
    {
        if ($report->notified_at || !in_array($report->status,['possible_damage','manual_review'],true)) return;
        // Deterministic per-report notification ID prevents duplicates on retries.
        $count=0;
        User::query()->chunkById(100,function ($users) use ($report,&$count) {
            foreach ($users as $user) {
                if (!$user->canUseAdminLogin()) continue;
                $hex=md5('self-drive-damage:'.$report->id.':'.$user->getKey());
                $id=substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20);
                $data=Notification::make()->title('Self Drive #'.$report->booking_id.': '.str_replace('_',' ',$report->status))
                    ->body('Pickup / return inspection is ready for admin review. No charge has been applied.')
                    ->actions([Action::make('review')->label('Review photos')->url(route('self-drive-damage.show',$report->id))])->getDatabaseMessage();
                DB::table('notifications')->updateOrInsert(['id'=>$id],['type'=>'Filament\\Notifications\\DatabaseNotification',
                    'notifiable_type'=>$user->getMorphClass(),'notifiable_id'=>$user->getKey(),'data'=>json_encode($data,JSON_THROW_ON_ERROR),
                    'read_at'=>null,'created_at'=>now(),'updated_at'=>now()]);
                $count++;
            }
        });
        if ($count) DB::table('self_drive_damage_reports')->where('id',$report->id)->update(['notified_at'=>now()]);
    }
}
