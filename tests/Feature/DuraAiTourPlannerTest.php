<?php
namespace Tests\Feature;

use App\Services\DuraAiTourPlanner;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class DuraAiTourPlannerTest extends TestCase
{
    private function details(): array
    {
        return ['pickup_city' => 'Agra', 'destinations' => ['Jaipur'], 'service_type' => 'round_trip',
            'start_date' => now()->addDays(2)->toDateString(), 'start_time' => '08:00',
            'end_date' => now()->addDays(3)->toDateString(), 'end_time' => '20:00',
            'travellers' => 4, 'bags' => 2, 'budget' => 10000, 'interests' => ['Heritage']];
    }
    private function plan(): array
    {
        return ['title' => 'Heritage journey', 'summary' => 'Travel at a relaxed pace with time for rest.', 'days' => [
            ['day' => 1, 'title' => 'Travel and explore', 'stops' => [
                ['city' => 'Agra', 'period' => 'Travel', 'activity' => 'Depart after pickup.'],
                ['city' => 'Jaipur', 'period' => 'Afternoon', 'activity' => 'Explore local heritage sights.'],
            ]],
            ['day' => 2, 'title' => 'Return journey', 'stops' => [
                ['city' => 'Agra', 'period' => 'Travel', 'activity' => 'Return with a rest break.'],
            ]],
        ]];
    }
    protected function setUp(): void
    {
        parent::setUp();
        config(['dura_ai_tour.enabled' => true, 'dura_ai_tour.api_key' => 'test-only-key', 'dura_ai_tour.model' => 'gpt-4.1-mini']);
        Http::preventStrayRequests();
    }
    public function test_endpoint_generates_itinerary_without_a_fare(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode($this->plan())]]]])]);
        $this->postJson('/api/v1/ai/tour-itinerary', $this->details() + ['fare' => 1, 'grand_total' => 1])
            ->assertOk()->assertJsonPath('status', true)->assertJsonPath('data.days.0.day', 1)
            ->assertJsonMissingPath('data.fare')->assertJsonMissingPath('data.grand_total');
        Http::assertSent(fn ($request) => $request['store'] === false
            && $request['response_format']['json_schema']['strict'] === true
            && ! str_contains($request['messages'][1]['content'], 'grand_total'));
    }
    public function test_return_time_is_required_and_no_provider_call_occurs(): void
    {
        $data = $this->details(); unset($data['end_time']);
        $this->postJson('/api/v1/ai/tour-itinerary', $data)->assertUnprocessable();
        Http::assertNothingSent();
    }
    public function test_disabled_ai_is_honest_and_does_not_call_provider(): void
    {
        config(['dura_ai_tour.enabled' => false]);
        $this->postJson('/api/v1/ai/tour-itinerary', $this->details())->assertStatus(503);
        Http::assertNothingSent();
    }
    public function test_provider_error_does_not_expose_its_body_or_key(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'secret-provider-body test-only-key'], 401)]);
        $response = $this->postJson('/api/v1/ai/tour-itinerary', $this->details())->assertStatus(503);
        $this->assertStringNotContainsString('secret-provider-body', $response->getContent());
        $this->assertStringNotContainsString('test-only-key', $response->getContent());
    }
    public function test_one_way_has_one_day_without_a_required_return_date(): void
    {
        $data = $this->details(); $data['service_type'] = 'one_way'; unset($data['end_date'], $data['end_time']);
        $input = (new DuraAiTourPlanner)->input($data);
        $this->assertSame(1, $input['days']); $this->assertSame(['Agra', 'Jaipur'], $input['route']);
        $this->assertNull($input['end_time']);
    }
    public function test_one_way_cannot_price_one_destination_but_plan_several(): void
    {
        $data = $this->details(); $data['service_type'] = 'one_way'; $data['destinations'][] = 'Delhi';
        $this->expectException(ValidationException::class); (new DuraAiTourPlanner)->input($data);
    }
    public function test_self_drive_itinerary_retains_the_selected_rental_period(): void
    {
        $data = $this->details(); $data['service_type'] = 'self_drive';
        $input = (new DuraAiTourPlanner)->input($data);
        $this->assertSame(2, $input['days']);
        $this->assertSame(['Agra', 'Jaipur', 'Agra'], $input['route']);
        $this->assertSame('20:00', $input['end_time']);
    }
    public function test_generated_price_fields_are_rejected(): void
    {
        $plan = $this->plan(); $plan['price'] = 1;
        $this->expectException(RuntimeException::class);
        $planner = new DuraAiTourPlanner; $planner->validatePlan($plan, $planner->input($this->details()));
    }
    public function test_generated_price_claims_in_activity_text_are_rejected(): void
    {
        $plan = $this->plan(); $plan['days'][0]['stops'][1]['activity'] = 'Special fare INR 100.';
        $this->expectException(RuntimeException::class);
        $planner = new DuraAiTourPlanner; $planner->validatePlan($plan, $planner->input($this->details()));
    }
    public function test_unselected_cities_are_rejected(): void
    {
        $plan = $this->plan(); $plan['days'][0]['stops'][1]['city'] = 'Mumbai';
        $this->expectException(RuntimeException::class);
        $planner = new DuraAiTourPlanner; $planner->validatePlan($plan, $planner->input($this->details()));
    }
    public function test_missing_days_are_rejected(): void
    {
        $plan = $this->plan(); array_pop($plan['days']);
        $this->expectException(RuntimeException::class);
        $planner = new DuraAiTourPlanner; $planner->validatePlan($plan, $planner->input($this->details()));
    }
    public function test_changed_route_order_is_rejected(): void
    {
        $data = $this->details(); $data['destinations'] = ['Delhi', 'Jaipur'];
        $plan = $this->plan(); $plan['days'][0]['stops'][] = ['city' => 'Delhi', 'period' => 'Evening', 'activity' => 'Explore heritage sights.'];
        $this->expectException(RuntimeException::class);
        $planner = new DuraAiTourPlanner; $planner->validatePlan($plan, $planner->input($data));
    }
    public function test_output_dates_are_derived_from_customer_dates(): void
    {
        $planner = new DuraAiTourPlanner; $data = $this->details();
        $result = $planner->validatePlan($this->plan(), $planner->input($data));
        $this->assertSame($data['start_date'], $result['days'][0]['date']);
        $this->assertSame($data['end_date'], $result['days'][1]['date']);
    }
}
