<?php

declare(strict_types=1);
namespace App\Services;

/** Google verifies the place; displayed text remains grounded in the booking source.
 * Never persists provider labels, formatted addresses, geometry or response bodies.
 * Only place IDs are kept. No list/API read performs a Google request.
 */
final class LiveBookingLocationVerifier
{
    private const ALIASES = [
        'Delhi'=>['delhi','dehli','new delhi','दिल्ली','नई दिल्ली'],
        'Gurugram'=>['gurugram','gurgaon','गुरुग्राम','गुड़गांव'],
        'Chandigarh'=>['chandigarh','chd','c h d','चंडीगढ़'],
        'Ludhiana'=>['ludhiana','ldh','लुधियाना'],
        'Noida'=>['noida','nodia','नोएडा'],
        'Meerut'=>['meerut','merath','मेरठ'],
        'Agra'=>['agra','आगरा'], 'Jaipur'=>['jaipur','जयपुर'],
        'Aligarh'=>['aligarh','अलीगढ़'], 'Dehradun'=>['dehradun','dehradoon','देहरादून'],
        'Zirakpur'=>['zirakpur','zirakhpur','zirakapur','जीरकपुर'],
        'Haldwani'=>['haldwani','हल्द्वानी'],
    ];

    public static function clean(string $value): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        // Strip only a complete, anchored booking prefix. Keep locality words,
        // digits, airport/terminal, sector, road and all source address detail.
        $value = (string) preg_replace('/^(?:(?:one[ -]*way|round[ -]*trip)\s+)?(?:cab|taxi|car)\s+(?:required\s+)?for\s+/iu', '', $value);
        return trim($value, " \t\n\r\0\x0B:.-");
    }

    private static function words(string $value): string
    {
        return trim((string) preg_replace('/[^\p{L}\p{M}\p{N}]+/u', ' ', mb_strtolower($value)));
    }

    private static function canonical(string $value): string
    {
        foreach (self::ALIASES as $canonical => $forms) {
            if (in_array(self::words($value), array_map([self::class,'words'], $forms), true)) return $canonical;
        }
        return self::words($value);
    }

    /** Return a name supported by BOTH this endpoint and Google's locality.
     * A district/state never substitutes for a missing locality.
     */
    private static function sourceCity(string $source, string $googleCity): ?string
    {
        $target = self::canonical($googleCity);
        $forms = [$googleCity];
        foreach (self::ALIASES as $name => $aliases) {
            if ($target === $name) { $forms = array_merge([$name], $aliases); break; }
        }
        usort($forms, static fn ($a,$b) => mb_strlen($b) <=> mb_strlen($a));
        foreach ($forms as $form) {
            // Match original source text (not a transliterated or geocoder label).
            $pattern = '/(?<![\p{L}\p{M}\p{N}])'.str_replace(' ', '\\s+', preg_quote($form,'/')).'(?![\p{L}\p{M}\p{N}])/iu';
            if (!preg_match($pattern, $source, $matched)) continue;
            // Known corrections come from our explicit alias table, not stored Google data.
            if (isset(self::ALIASES[$target])) return $target;
            return mb_convert_case($matched[0], MB_CASE_TITLE, 'UTF-8');
        }
        return null;
    }

    /** Pure selector, also used by offline fixtures. */
    public static function select(string $source, array $response): array
    {
        $source = self::clean($source);
        $choices = [];
        foreach (($response['results'] ?? []) as $row) {
            if (!is_array($row) || !empty($row['partial_match'])) continue;
            $types = (array) ($row['types'] ?? []);
            if (array_intersect($types, ['establishment','point_of_interest','store','lodging'])
                && !(in_array('airport',$types,true) && preg_match('/\b(?:airport|terminal)\b|हवाई/iu',$source))) continue;
            $country = null; $city = null; $state = null; $shortState = null;
            foreach (($row['address_components'] ?? []) as $component) {
                if (!is_array($component)) continue;
                $ct = (array) ($component['types'] ?? []);
                if (in_array('country', $ct, true)) $country = $component['short_name'] ?? null;
                if (in_array('locality', $ct, true) || in_array('postal_town', $ct, true)) $city = $component['long_name'] ?? null;
                if (in_array('administrative_area_level_1', $ct, true)) {
                    $state = $component['long_name'] ?? null; $shortState = $component['short_name'] ?? null;
                }
            }
            $placeId = $row['place_id'] ?? null;
            if ($country !== 'IN' || !is_string($city) || !is_string($state) || !is_string($placeId) || $placeId === '' || strlen($placeId)>255) continue;
            $display = self::sourceCity($source, $city);
            if ($display === null) continue;
            // Any explicitly supplied state must agree with the result.
            $states = [
                ['Uttar Pradesh','UP','U.P.','उत्तर प्रदेश'], ['Haryana','HR','हरियाणा'],
                ['Rajasthan','RJ','राजस्थान'], ['Punjab','PB','पंजाब'],
                ['Uttarakhand','UK','उत्तराखंड'], ['Delhi','DL','दिल्ली'],
                ['Madhya Pradesh','MP','मध्य प्रदेश'], ['Gujarat','GJ','गुजरात'],
                ['Maharashtra','MH','महाराष्ट्र'], ['Bihar','BR','बिहार'],
                ['West Bengal','WB','पश्चिम बंगाल'], ['Himachal Pradesh','HP','हिमाचल प्रदेश'],
            ];
            $conflict = false;
            foreach ($states as $forms) {
                // Delhi can be the city; compare it only when it is a trailing
                // comma-separated state, so Gurgaon -> Delhi doesn't become a state hint.
                foreach ($forms as $form) {
                    $pattern = '/(?:,\s*|\(\s*)'.preg_quote($form,'/').'\s*(?:\)|,|$)/iu';
                    if (preg_match($pattern,$source) && !in_array(self::words((string)$state),array_map([self::class,'words'],$forms),true)
                        && !in_array(self::words((string)$shortState),array_map([self::class,'words'],$forms),true)) $conflict = true;
                }
            }
            if ($conflict) continue;
            // Different localities/states remain ambiguous even when one ranks first.
            $identity = self::canonical($city).'|'.self::words($state);
            $choices[$identity] = ['city'=>$display,'address'=>$source,'place_id'=>$placeId];
        }
        if (count($choices)!==1) return ['data'=>null,'reason'=>count($choices)>1?'location_ambiguous':'location_not_verified'];
        return ['data'=>array_values($choices)[0],'reason'=>'location_verified'];
    }

    /** Calls only the existing server-side MapsService and only at intake. */
    public static function verify(array $booking, ?callable $lookup = null): array
    {
        $lookup ??= static fn (string $address) => MapsService::geocode($address, preg_match('/[\x{0900}-\x{097F}]/u',$address) ? 'hi' : 'en', true);
        $resolved = [];
        foreach (['from_city'=>'pickup','to_city'=>'drop'] as $key=>$role) {
            $address = self::clean((string)($booking[$key]??''));
            if ($address==='' || mb_strlen($address)>100 || preg_match('/https?:|\b(?:booking|call|need|required|free|khali|cab|taxi)\b/iu',$address)) {
                return ['data'=>null,'reason'=>'invalid_'.$role.'_location','retry'=>false];
            }
            try { $result = $lookup($address); }
            catch (\Throwable) { return ['data'=>null,'reason'=>'google_location_unavailable','retry'=>true]; }
            if (!($result['status']??false)) return ['data'=>null,'reason'=>'google_location_unavailable','retry'=>true];
            $data = (array)($result['data']??[]);
            if (($data['status']??'')!=='OK' && ($data['status']??'')!=='ZERO_RESULTS') return ['data'=>null,'reason'=>'google_location_unavailable','retry'=>true];
            $selection = self::select($address,$data);
            if ($selection['data']===null) return ['data'=>null,'reason'=>$role.'_'.$selection['reason'],'retry'=>false];
            $resolved[$role]=$selection['data'];
        }
        foreach (['from_city'=>'pickup','to_city'=>'drop'] as $key=>$role) {
            $booking[$key]=$resolved[$role]['city'];
            $booking[$role.'_address']=$resolved[$role]['address'];
            $booking[$role.'_place_id']=$resolved[$role]['place_id'];
        }
        $booking['location_verified_at']=now();
        return ['data'=>$booking,'reason'=>'locations_verified','retry'=>false];
    }
}
