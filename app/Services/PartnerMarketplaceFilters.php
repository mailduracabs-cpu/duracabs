<?php
declare(strict_types=1);
namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Validation\Rule;

final class PartnerMarketplaceFilters
{
    public static function rules(): array
    {
        return [
            'vehicle_classes'=>['sometimes','array','max:20'],
            'vehicle_classes.*'=>['required','string','min:1','max:80','distinct'],
            'pickup_cities'=>['sometimes','array','max:10'],
            'pickup_cities.*'=>['required','string','min:2','max:150','distinct'],
            'pickup_place_ids'=>['sometimes','array','max:10'],
            'pickup_place_ids.*'=>['required','string','max:255','distinct'],
            'drop_cities'=>['sometimes','array','max:10'],
            'drop_cities.*'=>['required','string','min:2','max:150','distinct'],
            'drop_place_ids'=>['sometimes','array','max:10'],
            'drop_place_ids.*'=>['required','string','max:255','distinct'],
            'pickup_date'=>['nullable','date_format:Y-m-d'],
            'trip_type'=>['nullable',Rule::in(['one_way','round_trip'])],
            'post_status'=>['nullable',Rule::in(['open','payment_pending','assigned','closed','cancelled','canceled','expired'])],
            'search'=>['nullable','string','max:100'],
        ];
    }

    private static function text(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(fn($value)=>mb_strtolower(trim((string)$value)),$values),fn($value)=>$value!=='')));
    }

    private static function like(string $value): string
    {
        return str_replace(['!','%','_'],['!!','!%','!_'],$value);
    }

    private static function cities(Builder $query, string $side, array $labels, array $ids): void
    {
        $names = self::text(array_map(fn($label)=>explode(',',(string)$label)[0],$labels));
        $ids = array_values(array_unique(array_filter(array_map('trim',$ids))));
        if ($names===[] && $ids===[]) return;
        // Only server-defined pickup/drop column names are passed to this helper.
        $column = "LOWER(REPLACE(p.{$side}_city, ', ', ','))";
        $query->where(function(Builder $group) use($side,$names,$ids,$column): void {
            if ($ids!==[]) $group->whereIn('p.'.$side.'_place_id',$ids);
            foreach($names as $name) {
                $needle=self::like($name);
                // Match a complete comma-delimited city component, not substrings:
                // Agra matches 'Agra, UP, India' and 'Taj Road, Agra, UP'.
                $group->orWhere(function(Builder $city) use($column,$name,$needle): void {
                    $city->whereRaw($column.' = ?',[$name])
                        ->orWhereRaw($column." LIKE ? ESCAPE '!'",[$needle.',%'])
                        ->orWhereRaw($column." LIKE ? ESCAPE '!'",['%,'.$needle.',%'])
                        ->orWhereRaw($column." LIKE ? ESCAPE '!'",['%,'.$needle]);
                });
            }
        });
    }

    public static function apply(Builder $query, array $data): void
    {
        $vehicles=self::text($data['vehicle_classes']??[]);
        if ($vehicles!==[]) $query->whereIn(new Expression('LOWER(p.vehicle_class)'),$vehicles);
        self::cities($query,'pickup',$data['pickup_cities']??[],$data['pickup_place_ids']??[]);
        // Drop filtering applies to booking posts. Free-vehicle routes have a
        // separate multi-destination JSON shape; the UI does not send this filter there.
        if (!empty($data['drop_cities']) || !empty($data['drop_place_ids'])) {
            $query->where('p.kind','booking');
            self::cities($query,'drop',$data['drop_cities']??[],$data['drop_place_ids']??[]);
        }
        if (!empty($data['trip_type'])) $query->where('p.trip_type',$data['trip_type']);
        if (!empty($data['post_status'])) $query->where('p.status',$data['post_status']);
        if (!empty($data['pickup_date'])) {
            // Users select an Indian calendar date; rows are stored in app timezone.
            $start=CarbonImmutable::createFromFormat('!Y-m-d',$data['pickup_date'],'Asia/Kolkata');
            $timezone=(string)config('app.timezone','UTC');
            $query->where('p.pickup_at','>=',$start->setTimezone($timezone)->format('Y-m-d H:i:s'))
                ->where('p.pickup_at','<',$start->addDay()->setTimezone($timezone)->format('Y-m-d H:i:s'));
        }
        $search=mb_strtolower(trim((string)($data['search']??'')));
        if ($search!=='') {
            $needle='%'.self::like($search).'%';
            $query->where(function(Builder $group) use($search,$needle): void {
                $group->whereRaw("LOWER(p.pickup_city) LIKE ? ESCAPE '!'",[$needle])
                    ->orWhereRaw("LOWER(p.drop_city) LIKE ? ESCAPE '!'",[$needle])
                    ->orWhereRaw("LOWER(p.vehicle_class) LIKE ? ESCAPE '!'",[$needle]);
                if (preg_match('/^#?([0-9]+)$/',$search,$match)) $group->orWhere('p.id',(int)$match[1]);
            });
        }
    }
}
