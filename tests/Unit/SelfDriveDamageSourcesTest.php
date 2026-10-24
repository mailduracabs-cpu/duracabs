<?php
namespace Tests\Unit;
use Tests\TestCase;
use App\Services\SelfDriveDamageReview;
use RuntimeException;
class SelfDriveDamageSourcesTest extends TestCase {
    public function test_partner_return_slots_take_precedence_over_customer_drop_photos(): void {
        $booking=(object)['id'=>12,'pickup_images'=>json_encode(['self-drive/inspections/pickup/a.jpg']),
            'drop_images'=>json_encode(['self-drive/inspections/drop/old.jpg']),
            'return_draft'=>json_encode(['photos'=>['front'=>'partner-return/12/front.jpg']])];
        $sources=(new SelfDriveDamageReview)->sources($booking);
        $this->assertCount(2,$sources); $this->assertSame('local',$sources[1]['disk']);
        $this->assertSame('front',$sources[1]['label']); $this->assertSame('BEFORE',$sources[0]['phase']);
    }
    public function test_customer_return_photos_use_existing_public_disk(): void {
        $sources=(new SelfDriveDamageReview)->sources((object)['id'=>12,'drop_images'=>'["self-drive/inspections/drop/a.jpg"]']);
        $this->assertSame('public',$sources[0]['disk']);
    }
    public function test_arbitrary_url_cannot_be_fetched(): void {
        $this->expectException(RuntimeException::class);
        (new SelfDriveDamageReview)->sources((object)['id'=>12,'pickup_images'=>'["https://example.com/private.jpg"]']);
    }
    public function test_parent_traversal_is_rejected(): void {
        $this->expectException(RuntimeException::class);
        (new SelfDriveDamageReview)->sources((object)['id'=>12,'pickup_images'=>'["self-drive/inspections/pickup/../../secret.jpg"]']);
    }
    public function test_other_booking_partner_photos_are_rejected(): void {
        $this->expectException(RuntimeException::class);
        (new SelfDriveDamageReview)->sources((object)['id'=>12,'return_draft'=>'{"photos":{"front":"partner-return/99/a.jpg"}}']);
    }
    public function test_customer_cannot_open_admin_reports(): void {
        $user=\Mockery::mock(\App\Models\User::class)->makePartial();
        $user->shouldReceive('canUseAdminLogin')->once()->andReturn(false);
        $request=\Illuminate\Http\Request::create('/admin/self-drive-damage'); $request->setUserResolver(fn()=>$user);
        try { (new \App\Http\Controllers\SelfDriveDamageReportController)->index($request); $this->fail('Must deny access'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403,$e->getStatusCode()); }
    }
}
