<?php
declare(strict_types=1);
namespace App\Services;
use Carbon\Carbon;
final class LiveBookingMessageParser {
  private static function normalizeBookingShorthand(string $text): string {
   // WhatsApp emphasis is formatting, including an underscore immediately before a clock.
   $text=LiveBookingEvidenceValidator::normalizeSource($text);
   $text=(string)preg_replace('/\b(?:picup|pikup)\b/iu','pickup',$text);
   $text=(string)preg_replace('/\b(?:drope)\b/iu','drop',$text);
   $text=(string)preg_replace('/\b(?:disire|dezire|desire)\b/iu','dzire',$text);
   $text=(string)preg_replace('/\b(?:saden)\b/iu','sedan',$text);
   $text=(string)preg_replace('/\bT\s*[.:-]\s*1\b/iu','one way',$text);
   return $text;
  }


 // Read explicit Pickup/Drop fields independently of presentation and line breaks.
 // Digits are valid location content (e.g. Rohini Sector 10), not a time.
 private static function labelledLocations(string $text): ?array {
  $label='(?:pick\\s*up|drop|cab(?:\\s+type)?|car(?:\\s+type)?|vehicle(?:\\s+type)?|taxi|trip(?:\\s+type)?|time|date|fare|amount|note|not|contact|mobile|phone)';
  $value='[\\s.=:>\\-–—]*([^\\r\\n]+?)(?=\\s*[^\\p{L}\\p{M}\\p{N}\\r\\n]*\\b'.$label.'\\b|\\r?\\n|$)';
  if(!preg_match('/\\bpick\\s*up(?:\\s+(?:city|location))?\\b'.$value.'/iu',$text,$pick)
   ||!preg_match('/\\bdrop(?:\\s+(?:city|location))?\\b'.$value.'/iu',$text,$drop))return null;
  $cities=[];
  foreach([$pick[1],$drop[1]] as $city){
   // Emoji and punctuation around fields are separators, not location content.
   $city=trim((string)preg_replace('/^[^\\p{L}\\p{M}\\p{N}]+|[^\\p{L}\\p{M}\\p{N}]+$/u','',$city));
   $city=trim((string)preg_replace('/\\s+/u',' ',$city));
   if(!preg_match('/[\\p{L}\\p{M}]/u',$city)||mb_strlen($city)>100
    ||!preg_match("/^[\\p{L}\\p{M}\\p{N} .,'’()\\/\\-–—]+$/u",$city))return null;
   $cities[]=$city;
  }
  return $cities;
 }
 public static function hasMinimumExplicitFields(string $text): bool {
  $s=self::normalizeBookingShorthand($text);
  return LiveBookingEvidenceValidator::eligible($s)
   &&LiveBookingEvidenceValidator::hasRouteEvidence($s)
   &&LiveBookingEvidenceValidator::hasVehicleEvidence($s)
   &&LiveBookingEvidenceValidator::hasClockEvidence($s);
 }
 public static function parse(string $text, ?Carbon $receivedAt=null): ?array {
  $s=self::normalizeBookingShorthand(trim(preg_replace('/\s+/u',' ',strip_tags($text)))); if($s===''||strlen($s)>5000)return null;
  if(!self::hasMinimumExplicitFields($text))return null;
  $availabilityOnly=(bool)preg_match('/\b(?:free|empty|kh+a+l+i|available|avail+a?ble|avleval|avl)\b|खाली|\b(?:ja|jaa|j+a+n+i)\s+(?:rahi|raha|rha|hai)\b/iu',$s);
  $explicitRequest=(bool)preg_match('/\b(?:req(?:uirement|uired)?|need|required|booking|drop|pickup|urgent|urjent|arjent|anjent|wanted|chahiye)\b|चाहिए|जरूरत/iu',$s);
  if($availabilityOnly&&!$explicitRequest)return null;
  if(!preg_match('/\b(req|requirement|required|required\s+car|need\b|re[ qgp]\b|drop\b|gaadi\s+chahiye|car\s+chahiye|taxi\s+chahiye|booking\s+required|booking)\b/iu',$s)&&!LiveBookingEvidenceValidator::completeRequirement($s))return null;
  $routeSource=self::normalizeBookingShorthand(trim(preg_replace('/[^\S\r\n]+/u',' ',strip_tags($text))));
  // Ignore common shorthand such as "w/c" (with carrier) while locating the route.
  $routeSource=preg_replace('/\bw[ \t]*\/[ \t]*c\b/iu',' ',$routeSource);
  // Labelled routes must stay on their own lines; never absorb fare/car/footer text.
  $labelled=str_replace(['*','_','~'],'',$routeSource);
  $cityLine='/^[^\p{L}\p{N}\r\n]*%s(?:[ \t]+(?:city|location))?[ \t:=-]*([\p{L}][\p{L} .\-]{1,79}?)(?:[ \t]*\+[ \t]*\d{1,2})?[ \t]*$/imu';
   $explicitLocations=self::labelledLocations($routeSource);
   if($explicitLocations!==null){
    [$from,$to]=$explicitLocations;$trip='one_way';
   }elseif(preg_match('/\bpick\s*up(?:\s+location)?\b[\s:=-]*([\p{L}\p{M}][\p{L}\p{M} .-]{1,60}?)\s+drop(?:\s+location)?\b[\s:=-]*([\p{L}\p{M}][\p{L}\p{M} .-]{1,60}?)(?=\s*(?:\+\s*\d+\s*(?:km|kms)\b|\b(?:cab|car|sedan|suv|hatchback|time|amount|note|call|mobile|phone|abhi|urgent|today|tomorrow)\b|\d{1,2}\s*(?:am|pm)\b|$))/iu',$s,$locations)){
    $from=trim($locations[1]);$to=trim($locations[2]);$trip='one_way';
   }elseif(preg_match('/\b(?:required|requirement|need)\s+(?:required\s+)?([\p{L}][\p{L}\p{M} .-]{1,60}?)\s+(?:to|se|से)\s+([\p{L}][\p{L}\p{M} .-]{1,60}?)\s+one\s*way\s+drop\b/iu',$s,$compact)){
   $from=trim(preg_replace('/^(?:(?:required|requirement|need)\s+)+/iu','',$compact[1]));$to=trim($compact[2]);$trip='one_way';
  }elseif(preg_match(sprintf($cityLine,'pick[ \t]*up'),$labelled,$pick)&&preg_match(sprintf($cityLine,'drop'),$labelled,$drop)){
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
  }elseif(preg_match('/\b([\p{L}][\p{L} .\-]{1,40})\s+(?:to|se|से)\s+([\p{L}][\p{L} .\-]{1,40})\b/iu',$routeSource,$route)){$from=trim(preg_replace('/^(?:(?:req(?:uired)?|requirement|need|booking\s+required)\s+|(?:(?:toyota\s+)?(?:innova\s+)?hycross|(?:toyota\s+)?(?:innova\s+)?crysta|(?:force\s+)?urbania|(?:kia\s+)?(?:carens|carance)|sedan|sadan|suv|car|taxi|ertiga|artiga|innova|dzire|swift|etios|traveller)\s+)+/iu','',$route[1]));$to=trim(preg_replace('/\s+(?:(?:small|mini)\s+(?:car|cab)|drop|one\s*way|round\s*trip|morning|evening|urgent|time|sedan|sadan|suv|hatchback|ertiga|innova|hycross|crysta|urbania|kia|carens|carance|dzire|swift|etios|tempo|traveller|bus|cab|car|taxi|today|tomorrow|aaj|kal)\b.*$/iu','',$route[2]));$trip='one_way';}elseif(preg_match('/\b([\p{L}][\p{L} .\-]{1,40})\s+local\b/iu',$routeSource,$local)){$from=trim(preg_replace('/^(?:(?:req(?:uired)?|requirement|need|booking\s+required)\s+|(?:(?:toyota\s+)?(?:innova\s+)?hycross|(?:toyota\s+)?(?:innova\s+)?crysta|(?:force\s+)?urbania|(?:kia\s+)?(?:carens|carance)|sedan|sadan|suv|car|taxi)\s+)+/iu','',$local[1]));$to=$from;$trip='local';}else return null;
  // In compact messages such as "Ertiga.3.30.pm Agra to Aligarh",
  // the generic route matcher may retain the AM/PM token before the city.
  $from=trim((string)preg_replace('/^(?:am|pm)\s+/iu','',$from));
  // Remove repeated request/immediate prefixes from Hindi or Hinglish routes,
  // e.g. "Required required Time Redi me Ajmer se Kishangarh drop".
  $from=trim((string)preg_replace('/^(?:(?:req(?:uirement|uired)?|need|booking|required)\b[\s:.-]*|(?:time\s*)?(?:ready|redi|reddy)(?:\s+me)?[\s:.-]*)+/iu','',$from));
  foreach(['nodia'=>'Noida','jaladhar'=>'Jalandhar','gwlalio'=>'Gwalior','merath'=>'Meerut'] as $typo=>$correct){if(strtolower($from)===$typo)$from=$correct;if(strtolower($to)===$typo)$to=$correct;}
  $vehicle=null;
  // A requested small cab is a Sedan even when a different free/exchange car is mentioned later.
  if(preg_match('/(?<![\p{L}\p{N}])small[ \t]+ca(?:r|b|p)(?![\p{L}\p{N}])|छोटी\s+(?:गाड़ी|गाड़ी|कार|टैक्सी)/iu',$s))$vehicle='Sedan';
  if(preg_match('/(?:dzire|dezire|dzaar)\s+(?:ya|or|aur|\/)\s+aura|aura\s+(?:ya|or|aur|\/)\s+(?:dzire|dezire|dzaar)/iu',$s))$vehicle='Sedan';
  if($vehicle===null) foreach (['Innova Hycross'=>['innova hycross','hycross'], 'Innova Crysta'=>['innova crysta','crysta'], 'Force Urbania'=>['force urbania','urbania'], 'Kia Carens'=>['kia carens','carens','kia carance','carance']] as $label=>$aliases) {
   foreach ($aliases as $alias) if (preg_match('/\b'.preg_quote($alias,'/').'\b/iu',$s)) { $vehicle=$label; break 2; }
  }
  if ($vehicle===null) foreach(['Sedan'=>['sedan','sadan','small car','mini car','aura'],'SUV'=>['suv'],'Hatchback'=>['hatchback'],'Innova'=>['innova'],'Dzire'=>['dzire','dzaar','dezire','डिजायर','डिज़ायर'],'Swift'=>['swift'],'Etios'=>['etios'],'Tempo Traveller'=>['tempo traveller'],'Traveller'=>['traveller'],'Bus'=>['bus'],'Ertiga'=>['ertiga','artiga','आर्टिगा','एर्टिगा']] as $label=>$aliases) foreach($aliases as $alias) if(preg_match('/(?<![\p{L}\p{M}\p{N}])'.preg_quote($alias,'/').'(?![\p{L}\p{M}\p{N}])/iu',$s)){$vehicle=$label;break 2;}
  if(!$vehicle)return null;
  // One shared date/clock resolver for the rule parser and the Gemini validator.
  $base=($receivedAt??Carbon::now('Asia/Kolkata'))->copy()->timezone('Asia/Kolkata');
  $date=LiveBookingEvidenceValidator::schedule($s,$base);
  if($date===null||$date->lt($base->copy()->startOfMinute()))return null;
  $hour=(int)$date->format('H');$minute=(int)$date->format('i');
  preg_match('/(?:\+?91[\s-]?)?[6-9]\d{9}\b/',$s,$phone); if(!isset($phone[0]))return null;
  $trip=preg_match('/round\s*trip|return|आना\s+जाना/iu',$s)?'round_trip':($trip??'one_way'); $returnAt=null; if($trip==='round_trip'&&!preg_match('/same[ \t-]*day|उसी[ \t]+दिन/iu',$s)){if(preg_match_all('/\b(\d{1,2})[\/\-.=](\d{1,2})(?:[\/\-.=](\d{2,4}))?\b/',$s,$dates,PREG_SET_ORDER)<2)return null;$rd=$dates[1];$ry=isset($rd[3])?(int)$rd[3]:(int)$base->format('Y');if($ry<100)$ry+=2000;if(!checkdate((int)$rd[2],(int)$rd[1],$ry))return null;try{$returnAt=Carbon::createFromDate($ry,(int)$rd[2],(int)$rd[1],$base->timezone)->setTime($hour,$minute);}catch(\Throwable){return null;}if(!$returnAt->gt($date))return null;}
   $amount=null;
   if(preg_match('/\b(?:amount|fare|price|rate|rent|kiraya)\b\s*[:=₹rs. ]*([\d,]+(?:\.\d{1,2})?)/iu',$s,$money))$amount=(float)str_replace(',','',$money[1]);
   $extraKm=null;
   if(preg_match('/\+\s*(\d+(?:\.\d+)?)\s*(?:km|kms)\b/iu',$s,$km))$extraKm=(float)$km[1];
   $note=null;
   if(preg_match('/\b(?:note|remarks?|extra\s+requirements?)\b\s*[:=-]?\s*(.+?)(?=\s+\b(?:call|mobile|phone|contact|amount|fare)\b|$)/iu',$s,$noteMatch))$note=trim($noteMatch[1]);
   return ['booking_amount'=>$amount,'extra_distance_km'=>$extraKm,'extra_requirements'=>$note,'from_city'=>$from,'to_city'=>$to,'trip_type'=>$trip,'car_type'=>$vehicle,'pickup_at'=>$date->toDateTimeString(),'return_at'=>$returnAt?->toDateTimeString(),'requester_phone'=>isset($phone[0])?(function($n){$d=preg_replace('/\D/','',$n);return strlen($d)===10?'91'.$d:$d;})($phone[0]):null];
 }
}
