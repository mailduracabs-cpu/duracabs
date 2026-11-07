<?php

declare(strict_types=1);
namespace App\Services;

use Carbon\Carbon;

/** Private source records for incomplete bookings and technical failures; not public bookings. */
final class LiveBookingIntakeReview
{
    public static function isCandidate(string $text): bool
    {
        return LiveBookingEvidenceValidator::looksLikeRequirement($text);
    }

    public static function record(array $message, string $reason, ?Carbon $receipt=null, string $status='needs_review', ?string $directory=null): bool
    {
        try {
            $directory??=storage_path('app/private/live-booking-review');
            if(!is_dir($directory)&&!mkdir($directory,0700,true)&&!is_dir($directory))return false;
            $key=hash('sha256',(string)($message['group_id']??'').'|'.(string)($message['id']??''));
            $file=$directory.DIRECTORY_SEPARATOR.$key.'.json';
            $lock=fopen($directory.DIRECTORY_SEPARATOR.$key.'.lock','c');if($lock===false)return false;
            try {
                if(!flock($lock,LOCK_EX))return false;
                $old=is_file($file)?json_decode((string)file_get_contents($file),true):null;
                // Successful imports do not create a review entry unless one already exists.
                if($status!=='needs_review'&&!is_array($old))return true;
                if(is_array($old)&&in_array($old['status']??'',['imported','duplicate'],true)&&$status==='needs_review')return true;
                $now=Carbon::now('Asia/Kolkata')->toIso8601String();
                $row=['message_id'=>(string)($message['id']??''),'group_id'=>(string)($message['group_id']??''),'whatsapp_timestamp'=>$message['timestamp']??null,
                    'received_at'=>($receipt??Carbon::now('Asia/Kolkata'))->copy()->timezone('Asia/Kolkata')->toIso8601String(),
                    'message'=>(string)($message['text']['body']??''),'status'=>$status,'reason'=>$reason,
                    'first_saved_at'=>$old['first_saved_at']??$now,'updated_at'=>$now];
                $json=json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
                $temp=tempnam($directory,'.review-');if($temp===false)return false;
                try {
                    if(file_put_contents($temp,$json,LOCK_EX)!==strlen($json))return false;
                    @chmod($temp,0600);
                    // Replace atomically. On failure keep the previous review record intact.
                    return rename($temp,$file);
                } finally {if(is_file($temp))@unlink($temp);}
            } finally {flock($lock,LOCK_UN);fclose($lock);}
        } catch(\Throwable){return false;}
    }
}
