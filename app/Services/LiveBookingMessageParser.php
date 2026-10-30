<?php
declare(strict_types=1);
namespace App\Services;
use Carbon\Carbon;
final class LiveBookingMessageParser {
 public static function parse(string $text, ?Carbon $receivedAt=null): ?array {
  $s=trim(preg_replace('/\s+/u',' ',strip_tags($text))); if($s===''||strlen($s)>5000)return null;
  if(!preg_match('/\b(required|required\s+car|need\s+(?:a\s+)?car|req\b|gaadi\s+chahiye|car\s+chahiye|taxi\s+chahiye|booking\s+required)\b/iu',$s))return null;
  if(preg_match('/\b([\p{L}][\p{L} .\-]{1,40})\s+(?:to|se|से)\s+([\p{L}][\p{L} .\-]{1,40})\b/iu',$s,$route)){$from=trim(preg_replace('/^(?:(?:req(?:uired)?|need|booking\s+required)\s+|(?:sedan|suv|car|taxi|ertiga|innova|dzire|swift|etios|carens|traveller)\s+)+/iu','',$route[1]));$to=trim(preg_replace('/\s+(?:drop|one\s*way|round\s*trip|morning|evening|urgent|time).*$/iu','',$route[2]));$trip='one_way';}elseif(preg_match('/\b([\p{L}][\p{L} .\-]{1,40})\s+local\b/iu',$s,$local)){$from=trim(preg_replace('/^(?:(?:req(?:uired)?|need|booking\s+required)\s+|(?:sedan|suv|car|taxi)\s+)+/iu','',$local[1]));$to=$from;$trip='local';}else return null;
  $vehicle=null; foreach(['sedan','suv','hatchback','ertiga','innova crysta','innova','dzire','swift','etios','carens','tempo traveller','traveller','bus'] as $v) if(preg_match('/\b'.preg_quote($v,'/').'\b/iu',$s)){$vehicle=ucwords($v);break;}
  if(!$vehicle)return null;
  $base=($receivedAt??now())->copy(); $date=$base->copy();
  if(preg_match('/\b(tomorrow|kal|कल)\b/iu',$s))$date->addDay();
  elseif(preg_match('/\b(today|aaj|आज)\b/iu',$s)){}
  elseif(preg_match('/\b(\d{1,2})[\/\-.](\d{1,2})(?:[\/\-.](\d{2,4}))?\b/',$s,$d)){ $y=isset($d[3])?(int)$d[3]:(int)$base->format('Y'); if($y<100)$y+=2000; try{$date=Carbon::createFromDate($y,(int)$d[2],(int)$d[1],$base->timezone);}catch(\Throwable){return null;} }
  else return null;
  if(preg_match('/\b(\d{1,2}):(\d{2})\s*(am|pm)?/iu',$s,$tm)) { $hour=(int)$tm[1]; $minute=(int)$tm[2]; $ampm=strtolower($tm[3]??''); }
  elseif(preg_match('/\b(\d{1,2})\s*(am|pm)\b/iu',$s,$tm)) { $hour=(int)$tm[1]; $minute=0; $ampm=strtolower($tm[2]); }
  else return null;
  if($hour>23||$minute>59)return null;
  if($ampm==='pm'&&$hour<12)$hour+=12; if($ampm==='am'&&$hour===12)$hour=0; if($ampm===''&&$hour<=12)return null;
  $date->setTime($hour,$minute); if($date->isPast())return null;
  preg_match('/(?:\+?91[\s-]?)?[6-9]\d{9}\b/',$s,$phone); if(!isset($phone[0]))return null;
  $trip=preg_match('/round\s*trip|return|आना\s+जाना/iu',$s)?'round_trip':($trip??'one_way'); $returnAt=null; if($trip==='round_trip'){if(preg_match_all('/\b(\d{1,2})[\/\-.](\d{1,2})(?:[\/\-.](\d{2,4}))?\b/',$s,$dates,PREG_SET_ORDER)<2)return null;$rd=$dates[1];$ry=isset($rd[3])?(int)$rd[3]:(int)$base->format('Y');if($ry<100)$ry+=2000;try{$returnAt=Carbon::createFromDate($ry,(int)$rd[2],(int)$rd[1],$base->timezone)->setTime($hour,$minute);}catch(\Throwable){return null;}if(!$returnAt->gt($date))return null;}
  return ['from_city'=>$from,'to_city'=>$to,'trip_type'=>$trip,'car_type'=>$vehicle,'pickup_at'=>$date->toDateTimeString(),'return_at'=>$returnAt?->toDateTimeString(),'requester_phone'=>isset($phone[0])?(function($n){$d=preg_replace('/\D/','',$n);return strlen($d)===10?'91'.$d:$d;})($phone[0]):null];
 }
}
