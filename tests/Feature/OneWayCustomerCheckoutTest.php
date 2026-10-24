<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BookingPaymentCheckoutService;
use App\Services\BookingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

/** Isolated in-memory database only: never touches production tables. */
class OneWayCustomerCheckoutTest extends TestCase
{
    private BookingService $service;
    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.customer_fare_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'database.default' => 'customer_fare_test']);
        DB::purge('customer_fare_test');
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            foreach (['user_id', 'vehicle_id'] as $key) $t->integer($key)->nullable();
            foreach (['grand_total', 'coupon_value', 'tax', 'total_km', 'shipping_ammount'] as $key) $t->decimal($key, 10, 2)->nullable();
            foreach (['booking_no', 'coupon_name', 'payment_method', 'payment_status', 'date', 'dateTo',
                'time', 'endTime', 'booking_from', 'booking_to', 'status', 'plan', 'currency',
                'extraOptions', 'cityFrom', 'cityTo', 'productName', 'image', 'taxi_type', 'notes', 'ride_type'] as $key) $t->text($key)->nullable();
            $t->timestamps();
        });
        Schema::create('addresses', function (Blueprint $t) {
            $t->id(); $t->integer('order_id'); $t->integer('user_id')->nullable();
            foreach (['email', 'full_name', 'phone', 'state', 'city', 'pickup_address', 'drop_address',
                'number_travellers', 'number_luggage', 'comments'] as $key) $t->text($key)->nullable();
            $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id(); $t->integer('order_id'); $t->integer('product_id'); $t->integer('quantity');
            $t->decimal('unit_ammount', 10, 2); $t->decimal('total_ammount', 10, 2); $t->timestamps();
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->boolean('is_active')->default(true);
            $t->boolean('in_return')->default(true);
            $t->string('service_group')->default('with_driver');
            $t->decimal('range', 10, 2)->default(200);
            $t->decimal('km_charge', 10, 2)->default(11);
            $t->decimal('driver_charge', 10, 2)->default(250);
            $t->decimal('extra_km_charge', 10, 2)->default(11); $t->decimal('extra_hr_charge', 10, 2)->default(150);
            foreach (['pet_friendly_charge', 'roof_carrier_charge', 'extra_pickup_charge', 'extra_drop_charge'] as $key) $t->decimal($key, 10, 2)->nullable();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id(); $t->integer('category_id'); $t->decimal('price', 10, 2);
            $t->string('name'); $t->string('ride_type'); $t->string('service_group');
            $t->boolean('is_active'); $t->boolean('in_stock'); $t->softDeletes(); $t->string('plan')->nullable();
            foreach (['max_price', 'toll_tax', 'border_tax', 'driver_allowances', 'gst_percentage', 'night_charge', 'km_limit', 'hr_limit'] as $key) $t->decimal($key, 10, 2)->nullable();
            foreach (['toll_included', 'state_tax_included', 'gst_included', 'parking_included'] as $key) $t->boolean($key)->default(false);
        });
        Schema::create('prices', function (Blueprint $t) {
            $t->id(); $t->integer('product_id'); $t->integer('category_id');
            $t->decimal('price', 10, 2); $t->decimal('max_price', 10, 2)->nullable(); $t->softDeletes();
        });
        Schema::create('coupons', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->decimal('value', 10, 2); $t->string('status');
            $t->date('from_date'); $t->date('to_date');
        });
        DB::table('categories')->insert(['id' => 1, 'name' => 'Hatchback']);
        DB::table('products')->insert(['id' => 88, 'category_id' => 1, 'price' => 1650,
            'name' => 'Agra To Delhi', 'ride_type' => 'one_way', 'service_group' => 'with_driver',
            'is_active' => 1, 'in_stock' => 1]);
        DB::table('prices')->insert(['id' => 88, 'product_id' => 88, 'category_id' => 1, 'price' => 2519]);
        $user = new User(); $user->setRawAttributes(['id' => 7]);
        $this->actingAs($user);
        Cache::shouldReceive('add')->andReturn(true);
        Cache::shouldReceive('forget')->andReturn(true);
        $this->service = (new ReflectionClass(BookingService::class))->newInstanceWithoutConstructor();
        $this->payload = ['user_id' => 999, 'customer_id' => 999, 'name' => 'Test customer',
            'mobile' => '+447700900123', 'product_id' => 88, 'category_id' => 1, 'price_id' => 88,
            'ride_type' => 'one_way', 'service_type' => 'one_way',
            'pickup_address' => 'Agra', 'drop_address' => 'Delhi',
            'pickup_date' => '2027-01-02', 'pickup_time' => '10:00',
            'return_date' => '2027-01-03', 'return_time' => '10:00',
            'payment_method' => 'razorpay', 'payment_type' => 'advance', 'grand_total' => 2519];
    }

    private function expectStage(string $type, float $expectedAmount): void
    {
        $stage = Mockery::mock(BookingPaymentCheckoutService::class);
        $stage->shouldReceive('stage')->once()->withArgs(function ($owner, $source, $snapshot, $total, $choice, $amount) use ($type, $expectedAmount) {
            $this->assertSame(7, $owner);
            $this->assertSame('order', $source);
            $this->assertSame(2519.0, $total);
            $this->assertSame($expectedAmount, $amount);
            $this->assertSame($type, $choice);
            $this->assertSame('Hatchback', $snapshot['attributes']['productName']);
            $this->assertSame(7, $snapshot['attributes']['user_id']);
            $this->assertSame(0.0, (float) json_decode($snapshot['attributes']['extraOptions'], true)['parking_amount']);
            $this->assertNull($snapshot['attributes']['dateTo']);
            $this->assertNull($snapshot['attributes']['endTime']);
            $this->assertSame('+447700900123', $snapshot['address']['phone']);
            $this->assertSame(2519.0, $snapshot['items'][0]['unit_ammount']);
            return true;
        })->andReturn((object) ['id' => 31]);
        $this->app->instance(BookingPaymentCheckoutService::class, $stage);
    }

    public function test_category_price_and_authenticated_owner_are_staged_without_an_order(): void
    {
        $this->expectStage('advance', 500.0);
        $result = $this->service->create($this->payload);
        $this->assertTrue($result['status']);
        $this->assertSame(-31, $result['data']['booking_id']);
        $this->assertFalse($result['data']['booking_created']);
        $this->assertSame(0, DB::table('orders')->count());
    }

    public function test_full_payment_stages_the_same_server_total(): void
    {
        $this->payload['payment_type'] = 'full';
        $this->expectStage('full', 2519.0);
        $this->assertTrue($this->service->create($this->payload)['status']);
    }

    public function test_other_routes_price_id_cannot_be_used(): void
    {
        DB::table('prices')->insert(['id' => 99, 'product_id' => 99, 'category_id' => 1, 'price' => 100]);
        $this->payload['price_id'] = 99;
        $this->assertSame(422, $this->service->create($this->payload)['code']);
        $this->assertSame(0, DB::table('orders')->count());
    }

    public function test_changed_quote_is_rejected_before_payment(): void
    {
        $this->payload['grand_total'] = 1650;
        $this->assertSame(422, $this->service->create($this->payload)['code']);
    }

    public function test_cash_booking_is_saved_unpaid_despite_client_paid_flag(): void
    {
        $this->payload['payment_method'] = 'cash';
        $this->payload['payment_status'] = 'paid';
        $result = $this->service->create($this->payload);
        $this->assertTrue($result['status']);
        $this->assertTrue($result['data']['booking_created']);
        $this->assertSame('pending', $result['data']['payment_status']);
        $this->assertSame(0, $result['data']['paid_amount']);
        $this->assertSame(2519.0, $result['data']['remaining_amount']);
        $this->assertSame(1, DB::table('orders')->count());
    }

    public function test_all_four_optional_services_are_saved_at_admin_prices(): void
    {
        DB::table('categories')->where('id', 1)->update([
            'pet_friendly_charge' => 200, 'roof_carrier_charge' => 300,
            'extra_pickup_charge' => 500, 'extra_drop_charge' => 500,
        ]);
        $this->payload = array_merge($this->payload, [
            'payment_method' => 'cash', 'category_options' => ['pet_friendly', 'roof_carrier', 'extra_pickup', 'extra_drop'],
            'category_options_total' => 1500, 'grand_total' => 4019,
            'extra_pickup_address' => 'Additional stop on main route',
            'extra_drop_address' => 'Additional drop on main route',
        ]);
        $result = $this->service->create($this->payload);
        $this->assertTrue($result['status']);
        $this->assertSame(4019.0, $result['data']['grand_total']);
        $this->assertCount(4, $result['data']['category_options']);
        $this->assertSame(1500.0, $result['data']['category_options_total']);
        $this->assertSame('extra_as_actual', $result['data']['parking_policy']);
    }

    public function test_tampered_optional_service_price_is_rejected(): void
    {
        DB::table('categories')->where('id', 1)->update(['pet_friendly_charge' => 200]);
        $this->payload['category_options'] = ['pet_friendly'];
        $this->payload['category_options_total'] = 1;
        $this->assertSame(422, $this->service->create($this->payload)['code']);
        $this->assertSame(0, DB::table('orders')->count());
    }

    public function test_selected_extra_pickup_requires_address(): void
    {
        DB::table('categories')->where('id', 1)->update(['extra_pickup_charge' => 500]);
        $this->payload['category_options'] = ['extra_pickup'];
        $this->payload['category_options_total'] = 500;
        $this->payload['grand_total'] = 3019;
        $this->assertSame(422, $this->service->create($this->payload)['code']);
    }

    public function test_client_coupon_cannot_reduce_the_server_price(): void
    {
        $this->payload['coupon_value'] = 2000;
        $this->payload['grand_total'] = 519;
        $this->assertSame(422, $this->service->create($this->payload)['code']);
    }
    public function test_search_and_saved_booking_match_the_central_inclusive_quote(): void
    {
        DB::table('products')->where('id', 88)->update([
            'toll_tax' => 250, 'border_tax' => 100, 'driver_allowances' => 250, 'gst_percentage' => 5,
            'gst_included' => false, 'toll_included' => false, 'state_tax_included' => false,
            'parking_included' => true,
        ]);
        $quote = app(\App\Services\FareService::class)->estimate(['route_id' => 88, 'category_id' => 1, 'price_id' => 88]);
        $this->assertSame(2519.0, $quote['total_fare']);
        $this->assertTrue($quote['fare_breakup']['gst_included']);
        $this->assertSame(0.0, $quote['fare_breakup']['toll_payable']);
        $this->assertSame(0.0, $quote['fare_breakup']['driver_allowance_payable']);
        $this->assertFalse($quote['fare_breakup']['parking_included']);
        $this->assertNull($quote['distance_km']);
        $this->assertNull($quote['duration_hr']);
        $method = new \ReflectionMethod(\App\Http\Controllers\Api\V1\TaxiController::class, 'withCanonicalOneWayFare');
        $row = $method->invoke(new \App\Http\Controllers\Api\V1\TaxiController(), [
            'ride_type' => 'one_way', 'product_id' => 88, 'category_id' => 1, 'price_id' => 88,
        ]);
        $this->assertSame($quote['total_fare'], $row['price']);
        $this->assertSame(11.0, $row['extra_km_charge']);
        $this->payload['payment_method'] = 'cash';
        $booking = $this->service->create($this->payload);
        $this->assertTrue($booking['status']);
        $this->assertSame($quote['total_fare'], $booking['data']['grand_total']);
    }

    public function test_optional_services_are_added_once_and_coupon_is_server_verified(): void
    {
        DB::table('categories')->where('id', 1)->update(['pet_friendly_charge' => 200, 'roof_carrier_charge' => 300]);
        DB::table('products')->where('id', 88)->update(['gst_percentage' => 5]);
        DB::table('coupons')->insert(['name' => 'TEN', 'value' => 10, 'status' => 'active',
            'from_date' => today()->subDay()->toDateString(), 'to_date' => today()->addDay()->toDateString()]);
        $quote = app(\App\Services\FareService::class)->estimate([
            'route_id' => 88, 'category_id' => 1, 'pat_selected' => true, 'roof_carrier_selected' => true,
        ]);
        $this->assertSame(3019.0, $quote['total_fare']);
        $this->assertSame(500.0, $quote['category_options_total']);
        $this->payload = array_merge($this->payload, ['payment_method' => 'cash',
            'category_options' => ['pet_friendly', 'roof_carrier'], 'category_options_total' => 500,
            'coupon_name' => 'ten', 'coupon_value' => 1, 'grand_total' => 2717.10]);
        $result = $this->service->create($this->payload);
        $this->assertTrue($result['status']);
        $this->assertSame(2717.10, $result['data']['grand_total']);
        $this->assertSame(301.90, $result['data']['coupon_value']);
    }

    public function test_explicit_distance_and_duration_use_same_category_rates(): void
    {
        DB::table('products')->where('id', 88)->update(['km_limit' => 200, 'hr_limit' => 4]);
        $quote = app(\App\Services\FareService::class)->estimate([
            'route_id' => 88, 'category_id' => 1, 'distance_km' => 220, 'duration_hr' => 5,
        ]);
        $this->assertSame(2889.0, $quote['total_fare']);
        $this->assertSame(220.0, $quote['distance_km']);
        $this->payload = array_merge($this->payload, ['payment_method' => 'cash',
            'distance_km' => 220, 'duration_hr' => 5, 'grand_total' => 2889]);
        $result = $this->service->create($this->payload);
        $this->assertTrue($result['status']);
        $this->assertSame($quote['total_fare'], $result['data']['grand_total']);
    }

    public function test_expired_coupon_is_rejected_before_booking(): void
    {
        DB::table('coupons')->insert(['name' => 'OLD', 'value' => 10, 'status' => 'active',
            'from_date' => today()->subDays(3)->toDateString(), 'to_date' => today()->subDay()->toDateString()]);
        $this->payload['payment_method'] = 'cash';
        $this->payload['coupon_name'] = 'OLD';
        $this->assertSame(422, $this->service->create($this->payload)['code']);
        $this->assertSame(0, DB::table('orders')->count());
    }

    public function test_round_trip_category_is_not_looked_up_as_a_product(): void
    {
        Cache::shouldReceive('get')->with(Mockery::pattern('/^round_trip_route_v2_/'))->andReturn([
            'status' => true, 'total_km' => 400.0, 'duration_text' => '6 Hr', 'duration_seconds' => 21600]);
        $this->payload = array_merge($this->payload, ['product_id' => null, 'price_id' => null,
            'ride_type' => 'round_trip', 'service_type' => 'round_trip', 'fare_type' => 'normal',
            'locations' => [['name' => 'Agra'], ['name' => 'Delhi'], ['name' => 'Agra']],
            'payment_method' => 'cash', 'return_time' => '17:00', 'grand_total' => 4900]);
        $result = $this->service->create($this->payload);
        $this->assertTrue($result['status']);
        $this->assertSame(4900.0, $result['data']['grand_total']);
        $this->assertSame('Hatchback', $result['data']['product_name']);
        $this->assertSame(0, DB::table('order_items')->count());
    }

    public function test_local_quantity_does_not_multiply_an_already_multiplied_price(): void
    {
        DB::table('products')->where('id', 88)->update(['ride_type' => 'local', 'plan' => '8 Hour / 80 Km']);
        DB::table('prices')->where('id', 88)->update(['price' => 2000]);
        $this->payload = array_merge($this->payload, ['ride_type' => 'local', 'service_type' => 'local',
            'plan' => '8 Hour / 80 Km', 'quantity' => 2, 'return_date' => '2027-01-02', 'return_time' => '18:00', 'payment_method' => 'cash', 'grand_total' => 4000]);
        $result = $this->service->create($this->payload);
        $this->assertTrue($result['status']);
        $this->assertSame(4000.0, $result['data']['grand_total']);
        $item = DB::table('order_items')->first();
        $this->assertSame(2, (int) $item->quantity);
        $this->assertSame(2000.0, (float) $item->unit_ammount);
        $this->assertSame(4000.0, (float) $item->total_ammount);
    }

    public function test_round_search_quotes_verified_google_distance_with_category_minimum(): void
    {
        config(['services.google.maps_key' => 'test-key']);
        Cache::shouldReceive('get')->with(Mockery::pattern('/^round_trip_route_v2_/'))->andReturn(null);
        Cache::shouldReceive('put')->once()->andReturn(true);
        \Illuminate\Support\Facades\Http::fake(fn () => \Illuminate\Support\Facades\Http::response([
            'status' => 'OK', 'rows' => [['elements' => [['status' => 'OK',
                'distance' => ['value' => 150000], 'duration' => ['value' => 7200]]]]],
        ]));
        $result = \App\Services\RoundTripFareService::estimate([
            'locations' => [['name' => 'Agra'], ['name' => 'Delhi'], ['name' => 'Agra']],
            'start_date' => '2027-01-02', 'start_time' => '10:00', 'end_date' => '2027-01-03', 'end_time' => '17:00',
        ]);
        $this->assertTrue($result['status']);
        $this->assertSame(300.0, $result['data']['total_km']);
        $this->assertSame(5600.0, $result['data']['vehicles'][0]['grand_total']);
        \Illuminate\Support\Facades\Http::assertSentCount(2);
    }

    public function test_local_scheduled_extra_hours_are_recomputed_from_dates(): void
    {
        DB::table('products')->where('id', 88)->update(['ride_type' => 'local', 'plan' => '8 Hour / 80 Km']);
        DB::table('prices')->where('id', 88)->update(['price' => 2000]);
        $this->payload = array_merge($this->payload, ['ride_type' => 'local', 'service_type' => 'local',
            'plan' => '8 Hour / 80 Km', 'quantity' => 2, 'payment_method' => 'cash',
            'duration_hr' => 0, 'grand_total' => 8800]);
        $result = $this->service->create($this->payload);
        $this->assertTrue($result['status']);
        $this->assertSame(8800.0, $result['data']['grand_total']);
        $this->assertSame(4800.0, $result['data']['fare_breakup']['extra_hr_amount']);
    }

}
