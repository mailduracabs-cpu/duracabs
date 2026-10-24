<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Services\SelfDriveDamageReview;
use Illuminate\Support\Facades\{DB,Schema,Storage,Http};
use Illuminate\Database\Schema\Blueprint;
class SelfDriveDamageProcessingTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        config(['database.connections.damage_test'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],'database.default'=>'damage_test',
            'self_drive_damage.api_key'=>'test-key','self_drive_damage.model'=>'test-model']);
        DB::purge('damage_test');
        (require database_path('migrations/2026_10_24_180000_create_self_drive_damage_reports.php'))->up();
        Schema::create('self_drive_bookings',function(Blueprint $t){ $t->id(); $t->decimal('damage_amount')->default(0); });
        DB::table('self_drive_bookings')->insert(['id'=>12,'damage_amount'=>0]);
        Storage::fake('local');
    }
    protected function tearDown(): void { DB::disconnect('damage_test'); parent::tearDown(); }
    private function service(): SelfDriveDamageReview {
        return new class extends SelfDriveDamageReview {
            public array $alerts=[];
            public function read(array $source): string { return 'test-snapshot-'.$source['path']; }
            public function notify(object $report): void { $this->alerts[]=$report->status; }
        };
    }
    private function booking(): object { return (object)['id'=>12,'pickup_images'=>'["self-drive/inspections/pickup/a.jpg"]','drop_images'=>'["self-drive/inspections/drop/a.jpg"]']; }
    private function fakeResponse(): void {
        Http::fake(['api.openai.com/*'=>Http::response(['choices'=>[['finish_reason'=>'stop','message'=>['content'=>json_encode([
            'summary'=>'Possible new dent.','full_coverage'=>false,'limitations'=>'Rear unclear.',
            'findings'=>[['before_id'=>'before_1','after_id'=>'after_2','description'=>'Possible new front dent.','confidence'=>0.8]]
        ])]]]])]);
    }
    public function test_same_photos_create_one_report_and_no_damage_charge(): void {
        $this->fakeResponse(); $service=$this->service(); $service->process($this->booking()); $service->process($this->booking());
        $this->assertSame(1,DB::table('self_drive_damage_reports')->count());
        $this->assertSame('possible_damage',DB::table('self_drive_damage_reports')->first()->status);
        $this->assertEquals(0,DB::table('self_drive_bookings')->value('damage_amount'));
        Http::assertSentCount(1);
        Storage::disk('local')->assertExists('self-drive-damage/1/before_1.jpg');
    }
    public function test_changed_return_photos_create_new_evidence_report(): void {
        $this->fakeResponse(); $service=$this->service(); $service->process($this->booking());
        $booking=$this->booking(); $booking->drop_images='["self-drive/inspections/drop/replaced.jpg"]'; $service->process($booking);
        $this->assertSame(2,DB::table('self_drive_damage_reports')->count()); Http::assertSentCount(2);
    }
    public function test_missing_pickup_photos_notify_manual_review_without_provider(): void {
        Http::fake(); $booking=$this->booking(); $booking->pickup_images='[]'; $service=$this->service(); $service->process($booking);
        $this->assertSame('manual_review',DB::table('self_drive_damage_reports')->first()->status);
        $this->assertContains('manual_review',$service->alerts); Http::assertNothingSent();
    }
}
