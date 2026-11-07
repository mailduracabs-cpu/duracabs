<?php

declare(strict_types=1);
namespace App\Services;

use Carbon\Carbon;

/** Shared source checks for both the rule parser and Gemini. Never trusts model confidence alone. */
final class LiveBookingEvidenceValidator
{
    /** Normalize only known writing variants, preserving line breaks and original geography. */
    public static function normalizeSource(string $text): string
    {
        $text=str_replace(['*','_','~'],' ',strip_tags($text));
        // Emoji presentation selectors are not Hindi vowel/combining marks.
        $text=(string)preg_replace('/[\x{FE0E}\x{FE0F}]/u','',$text);
        // Request is requirement shorthand, including repeated WhatsApp headings.
        $text=(string)preg_replace('/\brequests?(?:\s+requests?)*\b/iu','Required',$text);
        $text=(string)preg_replace('/\b([ap])\s*\.\s*m\.?(?!\p{L})/iu','$1m',$text);
        $text=(string)preg_replace('/(?<=\d)\s*\.\s*(am|pm)\b/iu',' $1',$text);
        // Hinglish schedule marker: Ludhiana to 4.30 PM means Time 4.30 PM.
        // Only a complete AM/PM clock or colon-form 24-hour clock qualifies.
        $text=(string)preg_replace('/\bto\s+(?=(?:\d{1,2}(?:[.:]\s*\d{2})?\s*(?:am|pm)\b|(?:[01]\d|2[0-3]):[0-5]\d(?![\d.:\/-])))/iu','Time ',$text);
        $text=(string)preg_replace('/\b(?:ch+h?oti|choti|chhoti)\s+(?:ga+di|ga+ri|car|cab)\b/iu','small car',$text);
        $text=(string)preg_replace('/\b(?:picup|pikup)\b/iu','pickup',$text);
        $text=(string)preg_replace('/\btraveler\b/iu','traveller',$text);
        $text=(string)preg_replace('/\btu\b/iu','to',$text);
        $text=(string)preg_replace('/\b(to|se)\s*[.:]\s*(?=\p{L})/iu','$1 ',$text);
        $text=(string)preg_replace('/\b(?:turant|turnt)\b/iu','urgent',$text);
        $text=(string)preg_replace('/\b(?:vage|vaje|bajey)\b/iu','baje',$text);
        return $text;
    }

    public static function plain(string $text): string
    {
        return trim((string)preg_replace('/\s+/u',' ',self::normalizeSource($text)));
    }

    private static function strongRequest(string $text): bool
    {
        return (bool)preg_match('/\b(?:req(?:uirement|uired)?|need|wanted|chahiye)\b|चाहिए|जरूरत|ड्रॉप\s*है|\bdrop\s+(?:hai|h)\b/iu',$text);
    }

    public static function availabilityOnly(string $text): bool
    {
        $text=self::plain($text);
        return !self::strongRequest($text)&&(bool)preg_match('/\b(?:free|empty|kh+a+l+i|available|avail+a?ble|avleval|avl|vacant|jayegi|jaayegi|jayega|jaayega|ja\s+raha|ja\s+rahi)\b|खाली|फ्री|जाएगी|जायेगी|जाएगा/iu',$text);
    }

    public static function hasRouteEvidence(string $text): bool
    {
        $text=self::plain($text);
        return (bool)preg_match('/\bpick\s*up\b[\s\S]{0,240}\bdrop\b|[\p{L}\p{M}][\s\S]{0,180}\s+(?:to|se|से)\s+[\s\S]{0,40}[\p{L}\p{M}]|[\p{L}\p{M}]\s+local\b/iu',$text);
    }

    public static function hasClockEvidence(string $text): bool
    {
        $text=self::plain($text);
        return (bool)preg_match('/\b\d{1,2}(?:[.:]\d{2})?\s*(?:am|pm|baje)\b|\d{1,2}(?::\d{2})?\s*बजे|\b(?:[01]\d|2[0-3])[.:][0-5]\d\b|\b(?:urgent|urjent|arjent|anjent|immediate|now|abhi|current|ready|redi|reddy)\b|तुरंत|अभी/iu',$text);
    }

    public static function completeRequirement(string $text): bool
    {
        return !self::availabilityOnly($text)&&self::phones($text)!==[]&&self::hasRouteEvidence($text)
            &&self::hasVehicleEvidence($text)&&self::hasClockEvidence($text);
    }

    public static function looksLikeRequirement(string $text): bool
    {
        $text=self::plain($text);
        if($text===''||strlen($text)>5000||self::availabilityOnly($text))return false;
        return self::strongRequest($text)
            ||(bool)preg_match('/\b(?:booking|pick\s*up|drop)\b|ड्रॉप|पिकअप/iu',$text)
            ||(self::hasRouteEvidence($text)&&self::hasVehicleEvidence($text));
    }

    public static function eligible(string $text): bool
    {
        return self::phones($text)!==[]&&self::looksLikeRequirement($text);
    }

    public static function phones(string $text): array
    {
        preg_match_all('/(?<!\d)(?:\+?91[\s.\/-]*)?[6-9](?:[\s.\/-]*\d){9}(?!\d)/u', $text, $matches);
        return array_values(array_unique(array_map(static fn ($v) => '91'.substr((string) preg_replace('/\D/', '', $v), -10), $matches[0])));
    }

    private static function words(string $text): string
    {
        return trim((string) preg_replace('/[^\p{L}\p{M}\p{N}]+/u', ' ', mb_strtolower(self::plain($text))));
    }

    private static function contains(string $source, string $value): bool
    {
        $source = ' '.self::words($source).' ';
        $value = self::words($value);
        return $value !== '' && str_contains($source, ' '.$value.' ');
    }

    private static function locationPresent(string $source, string $location): bool
    {
        if (self::contains($source, $location)) return true;
        foreach ([['Delhi','Dehli'], ['Ludhiana','LDH'], ['Chandigarh','CHD','C H D'], ['Noida','Nodia'],['Meerut','Merath']] as $aliases) {
            if (!in_array(self::words($location), array_map([self::class, 'words'], $aliases), true)) continue;
            foreach ($aliases as $alias) if (self::contains($source, $alias)) return true;
        }
        return false;
    }

    private static function locationForms(string $value): array
    {
        foreach ([['Delhi','Dehli'],['Ludhiana','LDH'],['Chandigarh','CHD','C H D'],['Noida','Nodia'],['Meerut','Merath']] as $aliases) {
            if(in_array(self::words($value),array_map([self::class,'words'],$aliases),true))return array_map([self::class,'words'],$aliases);
        }
        return [self::words($value)];
    }

    private static function routePresent(string $source, string $from, string $to, string $trip): bool
    {
        if($trip==='local')return self::words($from)===self::words($to);
        $source=self::words((string)preg_replace('/(?:->|[→➜➡])/u',' to ',$source));
        foreach(self::locationForms($from) as $a)foreach(self::locationForms($to) as $b){
            $pattern='/(?<![\p{L}\p{M}\p{N}])'.preg_quote($a,'/').'(?:\s+[\p{L}\p{M}\p{N}]+){0,8}\s+(?:to|se|से|tu|drop(?:\s+(?:city|location))?)\s+'.preg_quote($b,'/').'(?![\p{L}\p{M}\p{N}])/iu';
            if(preg_match($pattern,$source))return true;
        }
        return false;
    }

    private static function validLocation(string $text): bool
    {
        return $text !== '' && mb_strlen($text) <= 100
            && (bool) preg_match('/\p{L}/u', $text)
            && (bool) preg_match("/^[\p{L}\p{M}\p{N} .,'’()\/\-–—]+$/u", $text)
            && !preg_match('/\b(?:i|need|required|req|requests?|booking|free|khali|empty|available|call|please|plz|contact|sedan|ertiga|dzire|etios|innova|crysta|small\s+car|cab|taxi|one[ -]*way|round[ -]*trip|for|wc)\b|खाली|चाहिए/iu', $text);
    }

    private static function vehicle(string $source, string $value): ?string
    {
        $types = [
            'Innova Hycross'=>['innova hycross','hycross'], 'Innova Crysta'=>['innova crysta','crysta'],
            'Force Urbania'=>['force urbania','urbania'], 'Kia Carens'=>['kia carens','carens','carance'],
            'Sedan'=>['sedan','sadan','saden','small car','mini car','small cab','aura','छोटी गाड़ी','छोटी गाड़ी','छोटी कार'],
            'SUV'=>['suv'], 'Hatchback'=>['hatchback'], 'Dzire'=>['dzire','dezire','desire','disire','dzaar','डिजायर','डिज़ायर'],
            'Swift'=>['swift'], 'Etios'=>['etios'], 'Ertiga'=>['ertiga','artiga','आर्टिगा','एर्टिगा','अर्टिगा'],
            'Innova'=>['innova'], 'Tempo Traveller'=>['tempo traveller'], 'Traveller'=>['traveller'], 'Bus'=>['bus'],
        ];
        foreach ($types as $canonical=>$aliases) {
            if (!in_array(self::words($value), array_map([self::class,'words'], array_merge([$canonical],$aliases)), true)) continue;
            foreach ($aliases as $alias) if (self::contains($source,$alias)) return $canonical;
        }
        return null;
    }

    public static function hasVehicleEvidence(string $source): bool
    {
        foreach(['Sedan','SUV','Hatchback','Dzire','Swift','Etios','Ertiga','Innova','Innova Crysta','Innova Hycross','Force Urbania','Kia Carens','Tempo Traveller','Traveller','Bus'] as $type){
            if(self::vehicle($source,$type)!==null)return true;
        }
        return false;
    }

    /** matched=true/date=null means an explicit but invalid date; never default it. */
    public static function namedDate(string $source, Carbon $receipt): array
    {
        $months=[
            'january'=>1,'jan'=>1,'जनवरी'=>1,'february'=>2,'feb'=>2,'फरवरी'=>2,'फ़रवरी'=>2,
            'march'=>3,'mar'=>3,'मार्च'=>3,'april'=>4,'apr'=>4,'अप्रैल'=>4,
            'may'=>5,'मई'=>5,'june'=>6,'jun'=>6,'जून'=>6,'july'=>7,'jul'=>7,'जुलाई'=>7,
            'august'=>8,'aug'=>8,'अगस्त'=>8,'september'=>9,'sept'=>9,'sep'=>9,'सितंबर'=>9,'सितम्बर'=>9,
            'october'=>10,'oct'=>10,'अक्टूबर'=>10,'november'=>11,'nov'=>11,'नवंबर'=>11,'नवम्बर'=>11,
            'december'=>12,'dec'=>12,'दिसंबर'=>12,'दिसम्बर'=>12,
        ];
        $names=implode('|',array_map(static fn($v)=>preg_quote($v,'/'),array_keys($months)));
        $left='(?<![\p{L}\p{M}\p{N}])';$right='(?![\p{L}\p{M}\p{N}])';
        $year='(?:[\s,.\/\-]+(\d{4})(?!\d))?';
        $dayFirst=$left.'(\d{1,2})(?:st|nd|rd|th)?[\s.\/\-]+('.$names.')\.?'.$year.$right;
        $monthFirst=$left.'('.$names.')\.?[\s.\/\-]+(\d{1,2})(?:st|nd|rd|th)?'.$year.$right;
        if(preg_match('/'.$dayFirst.'/iu',$source,$m)){$day=(int)$m[1];$month=$months[mb_strtolower($m[2])];}
        elseif(preg_match('/'.$monthFirst.'/iu',$source,$m)){$day=(int)$m[2];$month=$months[mb_strtolower($m[1])];}
        else return ['matched'=>false,'date'=>null];
        $y=isset($m[3])?(int)$m[3]:(int)$receipt->format('Y');
        if(!checkdate($month,$day,$y))return ['matched'=>true,'date'=>null];
        return ['matched'=>true,'date'=>$receipt->copy()->setDate($y,$month,$day)];
    }

    /** Resolve date/clock from source, independently of the candidate's datetime. */
    public static function schedule(string $source, Carbon $receipt): ?Carbon
    {
        $source = self::plain($source);
        $receipt = $receipt->copy()->timezone('Asia/Kolkata');
        $date = $receipt->copy();
        $hour = null; $minute = 0; $clock = '';
        if (preg_match('/\b(\d{1,2})(?:[.:]\s*(\d{2}))?\s*[.\/-]?\s*(am|pm)\b/iu', $source, $m)) {
            $hour=(int)$m[1]; $minute=(int)($m[2]??0); if($hour<1||$hour>12||$minute>59)return null;
            $hour=$hour%12+(strtolower($m[3])==='pm'?12:0); $clock=$m[0];
        } elseif (preg_match('/(?<!\d)(\d{1,2})(?::(\d{2}))?\s*(?:बजे|baje)(?!\p{L})/iu', $source, $m)) {
            $hour=(int)$m[1];$minute=(int)($m[2]??0);$clock=$m[0];
            if($hour<1||$hour>12||$minute>59)return null;
            if(preg_match('/सुबह|प्रातः|\b(?:morning|subah|subha|savare|savere|saware)\b/iu',$source))$hour%=12;
            elseif(preg_match('/शाम|रात|\b(?:evening|night|raat|rat|shaam|sham)\b/iu',$source))$hour=$hour%12+12;
            else return null; // A bare 5 baje has an ambiguous AM/PM.
        } elseif (preg_match('/\b([01]\d|2[0-3])[.:]([0-5]\d)\b/u', $source, $m)) {
            $hour=(int)$m[1]; $minute=(int)$m[2]; $clock=$m[0];
        }
        // Exclude the clock before looking for numeric dates (e.g. 5.30 pm).
        $dates = $clock !== '' ? str_replace($clock, ' ', $source) : $source;
        $hasExplicitDate=(bool)preg_match('/\b(?:today|tom+or+ow|aaj|kal)\b|(?<![\p{L}\p{M}])(?:आज|कल)(?![\p{L}\p{M}])/iu',$dates);
        $named=self::namedDate($dates,$receipt);
        if($named['matched']){if($named['date']===null)return null;$date=$named['date'];$hasExplicitDate=true;}
        elseif(preg_match('/\b(?:tom+or+ow|kal)\b|(?<![\p{L}\p{M}])कल(?![\p{L}\p{M}])/u',mb_strtolower($dates)))$date->addDay();
        elseif(preg_match('/(?<!\d)(\d{4})-(\d{2})-(\d{2})(?!\d)/u',$dates,$iso)){
            if(!checkdate((int)$iso[2],(int)$iso[3],(int)$iso[1]))return null;
            $date->setDate((int)$iso[1],(int)$iso[2],(int)$iso[3]);$hasExplicitDate=true;
        }
        elseif(preg_match('/(?<!\d)(\d{1,2})[\/\-.=](\d{1,2})(?:[\/\-.=](\d{2,4}))?(?!\d)/u',$dates,$d)){
            $year=isset($d[3])?(int)$d[3]:(int)$receipt->format('Y');if($year<100)$year+=2000;
            if(!checkdate((int)$d[2],(int)$d[1],$year))return null;
            $date->setDate($year,(int)$d[2],(int)$d[1]);$hasExplicitDate=true;
        }
        // Do not interpret an unsupported explicit date/weekday as a missing date.
        if(!$hasExplicitDate&&preg_match('/\bdate[ \t:=-]+\S|\b(?:monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b|सोमवार|मंगलवार|बुधवार|गुरुवार|शुक्रवार|शनिवार|रविवार/iu',$dates))return null;
        if($hour===null){
            if(!preg_match('/\b(?:urgent|urjent|arjent|anjent|immediate|now|abhi|current|ready|redi|reddy)\b|तुरंत|अभी/iu',$source))return null;
            $hour=(int)$receipt->format('H');$minute=(int)$receipt->format('i');
        }
        return LiveBookingPickupTime::resolve($date->setTime($hour,$minute,0),$receipt,$hasExplicitDate,$clock!=='');
    }

    public static function check(array $candidate, string $source, Carbon $receipt): array
    {
        $reject = static fn (string $why) => ['data'=>null,'reason'=>$why];
        $source = self::plain($source);
        if(!self::eligible($source))return $reject('incomplete_booking');
        if(self::availabilityOnly($source))return $reject('availability_only');
        foreach(['from_city','to_city','car_type','pickup_at','requester_phone','trip_type'] as $key){
            if(!isset($candidate[$key])||!is_string($candidate[$key])||trim($candidate[$key])==='')return $reject('missing_'.$key);
        }
        $from=LiveBookingLocationVerifier::clean(trim($candidate['from_city']));$to=LiveBookingLocationVerifier::clean(trim($candidate['to_city']));
        if(!self::validLocation($from)||!self::validLocation($to))return $reject('invalid_location_text');
        if(!self::locationPresent($source,$from)||!self::locationPresent($source,$to))return $reject('location_not_in_message');
        $car=self::vehicle($source,trim($candidate['car_type']));if($car===null)return $reject('vehicle_not_in_message');
        $phone=(string)preg_replace('/\D/','',$candidate['requester_phone']);if(strlen($phone)===10)$phone='91'.$phone;
        if(!in_array($phone,self::phones($source),true))return $reject('phone_not_in_message');
        $trip=$candidate['trip_type'];
        if(!in_array($trip,['one_way','round_trip','local'],true))return $reject('invalid_trip_type');
        if($trip!=='round_trip'&&preg_match('/\b(?:[2-9]|\d{2,})\s*days?\b|(?:[2-9]|\d{2,})\s*दिन/iu',$source))return $reject('multi_day_trip_requires_details');
        if(!self::routePresent($source,$from,$to,$trip))return $reject('route_direction_not_supported');
        if($trip!=='round_trip'&&preg_match('/round\s*trip|आना\s*जाना/iu',$source))return $reject('trip_not_supported_by_message');
        if($trip==='round_trip'&&!preg_match('/round\s*trip|return|up\s*(?:down|dwon)|आना\s*जाना/iu',$source))return $reject('trip_not_in_message');
        if($trip==='local'&&!preg_match('/\blocal\b|लोकल/iu',$source))return $reject('trip_not_in_message');
        $expected=self::schedule($source,$receipt);if($expected===null)return $reject('missing_or_ambiguous_schedule');
        if($expected->lt($receipt->copy()->timezone('Asia/Kolkata')->startOfMinute()))return $reject('expired_booking');
        if(!preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(?::\d{2})?(?:Z|[+-]\d{2}:\d{2})?$/',$candidate['pickup_at']))return $reject('invalid_pickup_at');
        try{$pickup=Carbon::parse($candidate['pickup_at'],'Asia/Kolkata')->timezone('Asia/Kolkata');}catch(\Throwable){return $reject('invalid_pickup_at');}
        if($pickup->format('Y-m-d H:i')!==$expected->format('Y-m-d H:i'))return $reject('schedule_not_supported_by_message');
        $return=null;
        if($trip==='round_trip'&&!preg_match('/same[ \t-]*day|उसी\s+दिन/iu',$source)){
            if(!is_string($candidate['return_at']??null)||!preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/',$candidate['return_at']))return $reject('missing_return_at');
            try{$return=Carbon::parse($candidate['return_at'],'Asia/Kolkata')->timezone('Asia/Kolkata');}catch(\Throwable){return $reject('invalid_return_at');}
            preg_match_all('/(?<!\d)(\d{1,2})[\/\-.=](\d{1,2})(?:[\/\-.=](\d{2,4}))?(?!\d)/u',$source,$dates,PREG_SET_ORDER);
            if(count($dates)<2)return $reject('return_date_not_in_message');
            $d=$dates[1];$y=isset($d[3])?(int)$d[3]:(int)$receipt->format('Y');if($y<100)$y+=2000;
            if(!checkdate((int)$d[2],(int)$d[1],$y)||$return->format('Y-m-d')!==sprintf('%04d-%02d-%02d',$y,(int)$d[2],(int)$d[1])||!$return->gt($expected))return $reject('return_date_not_in_message');
        }
        $data=['from_city'=>$from,'to_city'=>$to,'car_type'=>$car,'trip_type'=>$trip,'pickup_at'=>$expected->toDateTimeString(),'return_at'=>$return?->toDateTimeString(),'requester_phone'=>$phone];
        // These are only supplied by the rule parser; the AI schema never returns them.
        foreach(['booking_amount','extra_distance_km','extra_requirements'] as $key)if(array_key_exists($key,$candidate))$data[$key]=$candidate[$key];
        return ['data'=>$data,'reason'=>'validated'];
    }
}
