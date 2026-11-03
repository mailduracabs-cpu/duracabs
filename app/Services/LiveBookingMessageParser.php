<?php
declare(strict_types=1);
namespace App\Services;
use Carbon\Carbon;
final class LiveBookingMessageParser {
 public static function parse(string $text, ?Carbon $receivedAt=null): ?array {
  $s=trim(preg_replace('/\s+/u',' ',strip_tags($text))); if($s===''||strlen($s)>5000)return null;
  $availabilityOnly=(bool)preg_match('/\b(?:free|empty|kh+a+l+i|available|avail+a?ble|avleval|avl)\b|खाली|\b(?:ja|jaa|j+a+n+i)\s+(?:rahi|raha|rha|hai)\b/iu',$s);
  $explicitRequest=(bool)preg_match('/\b(?:req(?:uirement|uired)?|need|required|booking|drop|pickup|urgent|urjent|wanted|chahiye)\b|चाहिए|जरूरत/iu',$s);
  if($availabilityOnly&&!$explicitRequest)return null;
  if(!preg_match('/\b(requirement|required|required\s+car|need\b|re[ qgp]\b|drop\b|gaadi\s+chahiye|car\s+chahiye|taxi\s+chahiye|booking\s+required|booking)\b/iu',$s))return null;
  $routeSource=trim(preg_replace('/[^\S\r\n]+/u',' ',strip_tags($text)));
  // Ignore common shorthand such as "w/c" (with carrier) while locating the route.
  $routeSource=preg_replace('/\bw[ \t]*\/[ \t]*c\b/iu',' ',$routeSource);
  // Labelled routes must stay on their own lines; never absorb fare/car/footer text.
  $labelled=str_replace(['*','_','~'],'',$routeSource);
  $cityLine='/^[^\p{L}\p{N}\r\n]*%s(?:[ \t]+(?:city|location))?[ \t:=-]*([\p{L}][\p{L} .\-]{1,79}?)(?:[ \t]*\+[ \t]*\d{1,2})?[ \t]*$/imu';
  if(preg_match(sprintf($cityLine,'pick[ \t]*up'),$labelled,$pick)&&preg_match(sprintf($cityLine,'drop'),$labelled,$drop)){
   $from=trim($pick[1]);$to=trim($drop[1]);$trip='one_way';
  }elseif(preg_match('/^[ \t]*([\p{L}\p{M} .-]{2,100}?)\s+pick[ \t]*up\s+(?:to|tu|se|से)\s+([\p{L}\p{M} .-]{2,100}?)\s+drop(?:\s+airport)?[ \t]*$/imu',$labelled,$route)){
   $from=trim($route[1]);$to=trim($route[2]);$trip='one_way';$routeImmediate=true;
  }elseif(preg_match('/^[^\p{L}\p{N}\r\n]*route[ \t:=-]*([\p{L}\p{M} .-]{2,60}?)\s+(?:to|se|से)\s+([\p{L}\p{M} .-]{2,60}?)(?:\s+drop)?[ \t]*$/imu',$labelled,$route)){
   $from=trim(preg_replace('/^(?:(?:req(?:uired)?|requirement|need|booking)\s+|(?:sedan|suv|car|taxi|ertiga|artiga|innova|dzire|swift|etios|traveller)\s+)+/iu','',$route[1]));$to=trim($route[2]);$trip='one_way';
  }elseif(preg_match('/^[^\p{L}\p{M}\p{N}\r\n]*([\p{L}][\p{L}\p{M} .-]{1,60}?)\s+(?:से|to|se)\s+([\p{L}][\p{L}\p{M} .-]{1,60}?)(?:\s*\([^\r\n)]{1,40}\))?\s+(?:ड्रॉप|drop)(?:[ \t].*)?[ \t]*$/imu',$labelled,$route)){
   $from=trim(preg_replace('/^(?:(?:req(?:uired)?|requirement|need|booking)\s+|(?:sedan|sadan|suv|car|taxi|ertiga|artiga|innova|dzire|swift|etios|traveller)\s+)+/iu','',$route[1]));$to=trim($route[2]);$trip='one_way';
  }elseif(preg_match('/^(?:[^\r\n]*?)(?:tom+or+ow|today|aaj|kal|आज|कल)(?:\s+\d{1,2}(?::\d{2})?\s*(?:am|pm))?\s+([\p{L}\p{M} .-]{2,60}?)\s+(?:to|se|से)\s+([\p{L}\p{M} .-]{2,60}?)(?:\s+drop)?[ \t]*$/imu',$labelled,$route)){
   $from=trim($route[1]);$to=trim($route[2]);$trip='one_way';
  }elseif(preg_match('/^[ \t]*(?:i[ \t]+)?need[ \t]+(?:(?:small|mini)[ \t]+car|sedan|suv|hatchback|ertiga|innova|dzire|swift)[ \t]+([\p{L}\p{M} .-]{2,80}?)\s+(?:to|se|से)\s+([\p{L}\p{M} .-]{2,80}?)(?:\s+drop)?[ \t]*$/imu',$labelled,$route)){
   $from=trim($route[1]);$to=trim($route[2]);$trip='one_way';
  }elseif(preg_match('/^[^\p{L}\p{M}\p{N}\r\n]*([\p{L}][\p{L}\p{M} .]{1,49}?)\s*[-–—]\s*([\p{L}][\p{L}\p{M} .]{1,49}?)[ \t]*$/mu',$labelled,$dash)){
   $from=trim($dash[1]);$to=trim($dash[2]);$trip='one_way';
  }elseif(preg_match('/^[^\p{L}\p{N}\r\n]*([\p{L}][\p{L}\p{M} .-]{1,59}?)\s+(?:to|se|से)\s+([\p{L}][\p{L}\p{M} .-]{1,59}?)[^\p{L}\p{N}\r\n]*$/imu',$labelled,$route)){
   $from=trim($route[1]);$to=trim($route[2]);$trip='one_way';
  }elseif(preg_match('/^[ \t]*([^\r\n]{2,100}?)[ .]*\R(?:[ \t]*\R)*[ \t]*to[ \t]*\R(?:[ \t]*\R)*[ \t]*([^\r\n]{2,300})[ \t]*$/imu',$labelled,$split)){
   $from=trim($split[1]);$destination=trim($split[2]);$parts=array_values(array_filter(array_map('trim',explode(',',$destination))));
   $to=$destination;
   if(count($parts)>=3)for($i=count($parts)-1;$i>=0;$i--)if(!preg_match('/\d|\+|\b(?:road|rd|avenue|nagar|colony|sector|college|hospital|airport)\b/iu',$parts[$i])){$to=$parts[$i];break;}
   $trip='one_way';
  }elseif(preg_match('/\b([\p{L}][\p{L} .\-]{1,40})\s+(?:to|se|से)\s+([\p{L}][\p{L} .\-]{1,40})\b/iu',$routeSource,$route)){$from=trim(preg_replace('/^(?:(?:req(?:uired)?|requirement|need|booking\s+required)\s+|(?:(?:toyota\s+)?(?:innova\s+)?hycross|(?:toyota\s+)?(?:innova\s+)?crysta|(?:force\s+)?urbania|(?:kia\s+)?(?:carens|carance)|sedan|sadan|suv|car|taxi|ertiga|artiga|innova|dzire|swift|etios|traveller)\s+)+/iu','',$route[1]));$to=trim(preg_replace('/\s+(?:drop|one\s*way|round\s*trip|morning|evening|urgent|time|sedan|sadan|suv|hatchback|ertiga|innova|hycross|crysta|urbania|kia|carens|carance|dzire|swift|etios|tempo|traveller|bus|car|taxi|today|tomorrow|aaj|kal)\b.*$/iu','',$route[2]));$trip='one_way';}elseif(preg_match('/\b([\p{L}][\p{L} .\-]{1,40})\s+local\b/iu',$routeSource,$local)){$from=trim(preg_replace('/^(?:(?:req(?:uired)?|requirement|need|booking\s+required)\s+|(?:(?:toyota\s+)?(?:innova\s+)?hycross|(?:toyota\s+)?(?:innova\s+)?crysta|(?:force\s+)?urbania|(?:kia\s+)?(?:carens|carance)|sedan|sadan|suv|car|taxi)\s+)+/iu','',$local[1]));$to=$from;$trip='local';}else return null;
  // In compact messages such as "Ertiga.3.30.pm Agra to Aligarh",
  // the generic route matcher may retain the AM/PM token before the city.
  $from=trim((string)preg_replace('/^(?:am|pm)\s+/iu','',$from));
  foreach(['nodia'=>'Noida','jaladhar'=>'Jalandhar','gwlalio'=>'Gwalior'] as $typo=>$correct){if(strtolower($from)===$typo)$from=$correct;if(strtolower($to)===$typo)$to=$correct;}
  $vehicle=null;
  // A requested small cab is a Sedan even when a different free/exchange car is mentioned later.
  if(preg_match('/(?<![\p{L}\p{N}])small[ \t]+ca(?:r|b|p)(?![\p{L}\p{N}])/iu',$s))$vehicle='Sedan';
  if(preg_match('/(?:dzire|dezire|dzaar)\s+(?:ya|or|aur|\/)\s+aura|aura\s+(?:ya|or|aur|\/)\s+(?:dzire|dezire|dzaar)/iu',$s))$vehicle='Sedan';
  if($vehicle===null) foreach (['Innova Hycross'=>['innova hycross','hycross'], 'Innova Crysta'=>['innova crysta','crysta'], 'Force Urbania'=>['force urbania','urbania'], 'Kia Carens'=>['kia carens','carens','kia carance','carance']] as $label=>$aliases) {
   foreach ($aliases as $alias) if (preg_match('/\b'.preg_quote($alias,'/').'\b/iu',$s)) { $vehicle=$label; break 2; }
  }
  if ($vehicle===null) foreach(['Sedan'=>['sedan','sadan','small car','mini car','aura'],'SUV'=>['suv'],'Hatchback'=>['hatchback'],'Innova'=>['innova'],'Dzire'=>['dzire','dzaar','dezire','डिजायर','डिज़ायर'],'Swift'=>['swift'],'Etios'=>['etios'],'Tempo Traveller'=>['tempo traveller'],'Traveller'=>['traveller'],'Bus'=>['bus'],'Ertiga'=>['ertiga','artiga','आर्टिगा','एर्टिगा']] as $label=>$aliases) foreach($aliases as $alias) if(preg_match('/(?<![\p{L}\p{M}\p{N}])'.preg_quote($alias,'/').'(?![\p{L}\p{M}\p{N}])/iu',$s)){$vehicle=$label;break 2;}
  if(!$vehicle)return null;
  $base=($receivedAt??now())->copy(); $date=$base->copy();
  $hasExplicitDate=(bool)preg_match('/\b(?:tom+or+ow|today|aaj|kal)\b|(?<!\p{L})(?:आज|कल)(?!\p{L})|\b\d{1,2}[\/\-.=]\d{1,2}(?:[\/\-.=]\d{2,4})?\b(?!\s*[.\/-]?\s*(?:am|pm)\b)/iu',$s);
  $immediate=(bool)preg_match('/^[^\p{L}\p{N}\r\n]*(?:(?:time|reporting)[ \t:=-]*)?(?:urgent|urjent|immediate|now|abhi|तुरंत|अभी)[ \t]*$/imu',$labelled);
  if($immediate){}
  elseif(preg_match('/\b(?:tom+or+ow|kal)\b|(?<!\p{L})कल(?!\p{L})/iu',$s))$date->addDay();
  elseif(preg_match('/\b(?:today|aaj)\b|(?<!\p{L})आज(?!\p{L})/iu',$s)){}
  elseif(preg_match('/\b(\d{1,2})[\/\-.=](\d{1,2})(?:[\/\-.=](\d{2,4}))?\b(?!\s*[.\/-]?\s*(?:am|pm)\b)/i',$s,$d)){ $y=isset($d[3])?(int)$d[3]:(int)$base->format('Y'); if($y<100)$y+=2000; if(!checkdate((int)$d[2],(int)$d[1],$y))return null; try{$date=Carbon::createFromDate($y,(int)$d[2],(int)$d[1],$base->timezone);}catch(\Throwable){return null;} }
  elseif(preg_match('/^[^\p{L}\p{N}\r\n]*(?:(?:time|reporting)[ \t:=-]*)?(?:urgent|urjent|current|cr+e?n+t|now|abhi|तुरंत|अभी)(?:[ \t].*)?$/imu',$labelled)){}
  elseif(preg_match('/\b(?:time[ \t:.-]*|morning[ \t:.-]*|subh+a+h?[ \t:.-]*|सुबह[ \t:.-]*)?\d{1,2}(?::\d{2})?[ \t]*(?:am|pm)\b/iu',$labelled)){$implicitDate=true;}
  elseif(!empty($routeImmediate)){$implicitImmediate=true;}
  elseif(preg_match('/\b(?:ready|reddy)\b/iu',$s)){$implicitImmediate=true;}
  // Missing date means the message receipt date.
  else $date=$base->copy();
  if($immediate||!empty($implicitImmediate)){$date=$base->copy();$hour=(int)$base->format('H');$minute=(int)$base->format('i');$ampm='';$urgent=true;}
  elseif(preg_match('/\b(\d{1,2})[.:](\d{2})\s*[.\/-]?\s*(am|pm)\b/iu',$s,$tm)) { $hour=(int)$tm[1]; $minute=(int)$tm[2]; $ampm=strtolower($tm[3]); }
  elseif(preg_match('/\b(\d{1,2})[ \t]+(\d{2})[ \t]*(am|pm)\b/iu',$s,$tm)) { $hour=(int)$tm[1]; $minute=(int)$tm[2]; $ampm=strtolower($tm[3]); }
  elseif(preg_match('/\b([01]\d|2[0-3])[.:](\d{2})\b/u',$s,$tm)) { $hour=(int)$tm[1]; $minute=(int)$tm[2]; $ampm=''; $explicit24Hour=true; }
  elseif(preg_match('/\b(\d{1,2}):(\d{2})\s*(am|pm)?/iu',$s,$tm)) { $hour=(int)$tm[1]; $minute=(int)$tm[2]; $ampm=strtolower($tm[3]??''); }
  elseif(preg_match('/\b(\d{1,2}):\s*(am|pm)\b/iu',$s,$tm)) { $hour=(int)$tm[1]; $minute=0; $ampm=strtolower($tm[2]); }
  elseif(preg_match('/\b(\d{1,2})\s*(am|pm)\b/iu',$s,$tm)) { $hour=(int)$tm[1]; $minute=0; $ampm=strtolower($tm[2]); }
  elseif(preg_match('/(?<!\d)(\d{1,2})(?::(\d{2}))?\s*(?:बजे|baje)\b/iu',$s,$tm)){
   $hour=(int)$tm[1];$minute=(int)($tm[2]??0);$morning=(bool)preg_match('/सुबह|प्रातः/u',$s);$evening=(bool)preg_match('/शाम|रात/u',$s);
   if($morning&&$hour===12)$hour=0;elseif($evening&&!$morning&&$hour<12)$hour+=12;$ampm='';$hindiClock=true;
  }
  elseif(preg_match('/^[^\p{L}\p{N}\r\n]*(?:(?:time|reporting)[ \t:=-]*)?(?:urgent|urjent|immediate|now|abhi|तुरंत|अभी)[ \t]*$/imu',$labelled)){
   // Urgent always means the message receipt date and minute.
   $date=$base->copy();
   $hour=(int)$base->format('H');$minute=(int)$base->format('i');$ampm='';$urgent=true;
  }
  elseif(preg_match('/^[^\p{L}\p{N}\r\n]*time[ \t:=-]*(?:current|cr+e?n+t)[ \t].*$/imu',$labelled)){
   $hour=(int)$base->format('H');$minute=(int)$base->format('i');$ampm='';$urgent=true;
  }
  // Missing time means the message receipt hour and minute.
  else {$hour=(int)$base->format('H');$minute=(int)$base->format('i');$ampm='';$urgent=true;}
  if($hour>23||$minute>59||($ampm!==''&&($hour<1||$hour>12)))return null;
  if($ampm==='pm'&&$hour<12)$hour+=12; if($ampm==='am'&&$hour===12)$hour=0; if(empty($urgent)&&empty($hindiClock)&&empty($explicit24Hour)&&$ampm===''&&$hour<=12)return null;
  $date->setTime($hour,$minute);if($date->copy()->startOfMinute()->lt($base->copy()->startOfMinute())){
   if(!empty($implicitDate)||!$hasExplicitDate)$date->addDay();
   elseif(!empty($hindiClock)&&preg_match('/आज/u',$s)&&preg_match('/रात/u',$s)&&preg_match('/सुबह/u',$s))$date->addDay();
   else return null;
  }
  preg_match('/(?:\+?91[\s-]?)?[6-9]\d{9}\b/',$s,$phone); if(!isset($phone[0]))return null;
  $trip=preg_match('/round\s*trip|return|आना\s+जाना/iu',$s)?'round_trip':($trip??'one_way'); $returnAt=null; if($trip==='round_trip'&&!preg_match('/same[ \t-]*day|उसी[ \t]+दिन/iu',$s)){if(preg_match_all('/\b(\d{1,2})[\/\-.=](\d{1,2})(?:[\/\-.=](\d{2,4}))?\b/',$s,$dates,PREG_SET_ORDER)<2)return null;$rd=$dates[1];$ry=isset($rd[3])?(int)$rd[3]:(int)$base->format('Y');if($ry<100)$ry+=2000;if(!checkdate((int)$rd[2],(int)$rd[1],$ry))return null;try{$returnAt=Carbon::createFromDate($ry,(int)$rd[2],(int)$rd[1],$base->timezone)->setTime($hour,$minute);}catch(\Throwable){return null;}if(!$returnAt->gt($date))return null;}
  return ['from_city'=>$from,'to_city'=>$to,'trip_type'=>$trip,'car_type'=>$vehicle,'pickup_at'=>$date->toDateTimeString(),'return_at'=>$returnAt?->toDateTimeString(),'requester_phone'=>isset($phone[0])?(function($n){$d=preg_replace('/\D/','',$n);return strlen($d)===10?'91'.$d:$d;})($phone[0]):null];
 }
}
