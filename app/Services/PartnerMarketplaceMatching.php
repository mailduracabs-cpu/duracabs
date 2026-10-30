<?php
namespace App\Services;
/** Pure matching rules, shared by planning and send-time revalidation. */
class PartnerMarketplaceMatching {
 public static function distance(float $lat1,float $lon1,float $lat2,float $lon2): float {
  $a=sin(deg2rad($lat2-$lat1)/2)**2+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin(deg2rad($lon2-$lon1)/2)**2;
  return 6371*2*atan2(sqrt(min(1,$a)),sqrt(max(0,1-$a)));
 }
 public static function near(?float $a,?float $b,?float $c,?float $d,float $radius): bool { return $a!==null&&$b!==null&&$c!==null&&$d!==null&&self::distance($a,$b,$c,$d)<=$radius; }
 public static function tier(array $post,array $presence,array $free,int $now,float $radius=30,int $fresh=300): ?array {
  if ((int)$post['transporter_profile_id']===(int)$presence['vendor_id']) return null;
  $lat=isset($post['pickup_latitude'])?(float)$post['pickup_latitude']:null; $lon=isset($post['pickup_longitude'])?(float)$post['pickup_longitude']:null;
  if ($lat===null||$lon===null) return null;
  $pickup=strtotime($post['pickup_at']);
  foreach($free as $cab) {
   if (!$cab['is_free'] || (int)$cab['vendor_id']!==(int)$presence['vendor_id'] || strtotime($cab['available_until'])<=$now || strtotime($cab['available_from'])>$pickup || strtotime($cab['available_until'])<$pickup) continue;
   if (PartnerMarketplaceFleet::matches($post['vehicle_class'],$cab['car_classification'],$cab['taxi_name']) && self::near($lat,$lon,(float)$cab['latitude'],(float)$cab['longitude'],$radius)) return ['tier'=>0,'reason'=>'free_cab','free_cab_id'=>$cab['id']];
  }
  if (!empty($presence['gps_enabled']) && !empty($presence['located_at']) && strtotime($presence['located_at']) >= $now-$fresh && strtotime($presence['located_at']) <= $now+60 && (float)($presence['accuracy']??99999)<=1000
   && self::near($lat,$lon,isset($presence['latitude'])?(float)$presence['latitude']:null,isset($presence['longitude'])?(float)$presence['longitude']:null,$radius)) return ['tier'=>1,'reason'=>'nearby','free_cab_id'=>null];
  if (!empty($presence['search_until']) && strtotime($presence['search_until'])>$now && self::near($lat,$lon,isset($presence['search_latitude'])?(float)$presence['search_latitude']:null,isset($presence['search_longitude'])?(float)$presence['search_longitude']:null,$radius)) return ['tier'=>1,'reason'=>'search_city','free_cab_id'=>null];
  return null;
 }
}
