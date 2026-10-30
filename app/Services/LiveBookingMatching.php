<?php
declare(strict_types=1);
namespace App\Services;
final class LiveBookingMatching {
 public static function city(string $a,string $b):bool { $n=static fn($s)=>preg_replace('/[^a-z0-9]/','',strtolower(trim($s)));$a=$n($a);$b=$n($b);return $a!==''&&$b!==''&&($a===$b||str_contains($a,$b)||str_contains($b,$a)); }
 public static function cab(object $booking,array $cab):bool { return !empty($cab['is_free'])&&strtotime((string)$cab['available_from'])<=strtotime((string)$booking->pickup_at)&&strtotime((string)$cab['available_until'])>=strtotime((string)$booking->pickup_at)&&self::city($booking->from_city,(string)$cab['city'])&&PartnerMarketplaceFleet::matches($booking->car_type,(string)$cab['car_classification'],(string)$cab['taxi_name']); }
}
