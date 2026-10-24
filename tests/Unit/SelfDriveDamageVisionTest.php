<?php
namespace Tests\Unit;
use App\Services\SelfDriveDamageVision;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
class SelfDriveDamageVisionTest extends TestCase {
    private function images(): array { return ['before_1'=>['phase'=>'BEFORE','label'=>'0','mime'=>'image/jpeg','bytes'=>'fake-before'], 'after_2'=>['phase'=>'AFTER','label'=>'front','mime'=>'image/jpeg','bytes'=>'fake-after']]; }
    private function sampleResult(): array { return ['summary'=>'No clear new damage.','full_coverage'=>false,'limitations'=>'Rear not comparable.','findings'=>[]]; }
    public function test_incomplete_coverage_always_requires_review(): void {
        $this->assertSame('manual_review',(new SelfDriveDamageVision)->validate($this->sampleResult(),$this->images())['status']);
    }
    public function test_full_coverage_only_means_no_visible_change(): void {
        $data=$this->sampleResult(); $data['full_coverage']=true;
        $this->assertSame('no_visible_change',(new SelfDriveDamageVision)->validate($data,$this->images())['status']);
    }
    public function test_new_damage_is_flagged_with_evidence(): void {
        $data=$this->sampleResult(); $data['findings'][]=['before_id'=>'before_1','after_id'=>'after_2','description'=>'Possible dent on front door.','confidence'=>0.7];
        $this->assertSame('possible_damage',(new SelfDriveDamageVision)->validate($data,$this->images())['status']);
    }
    public function test_made_up_evidence_is_rejected(): void {
        $data=$this->sampleResult(); $data['findings'][]=['before_id'=>'nonexistent','after_id'=>'after_2','description'=>'Dent','confidence'=>0.8];
        $this->expectException(RuntimeException::class); (new SelfDriveDamageVision)->validate($data,$this->images());
    }
    public function test_missing_configuration_never_calls_provider(): void {
        Http::fake(); config(['self_drive_damage.api_key'=>null,'self_drive_damage.model'=>null]);
        try { (new SelfDriveDamageVision)->compare($this->images()); $this->fail('Must require manual review'); }
        catch (RuntimeException $e) { Http::assertNothingSent(); $this->assertStringContainsString('configuration',$e->getMessage()); }
    }
    public function test_provider_receives_private_data_images_and_structured_schema(): void {
        config(['self_drive_damage.api_key'=>'test-key','self_drive_damage.model'=>'test-model']);
        Http::fake(['api.openai.com/*'=>Http::response(['choices'=>[['finish_reason'=>'stop','message'=>['content'=>json_encode($this->sampleResult())]]]])]);
        $this->assertSame('manual_review',(new SelfDriveDamageVision)->compare($this->images())['status']);
        Http::assertSent(fn($request)=>$request['store']===false && $request['response_format']['json_schema']['strict']===true
            && str_starts_with($request['messages'][0]['content'][2]['image_url']['url'],'data:image/jpeg;base64,'));
    }
    public function test_refusal_is_manual_review_not_no_damage(): void {
        config(['self_drive_damage.api_key'=>'test-key','self_drive_damage.model'=>'test-model']);
        Http::fake(['api.openai.com/*'=>Http::response(['choices'=>[['finish_reason'=>'stop','message'=>['refusal'=>'Cannot compare']]]])]);
        $this->expectException(RuntimeException::class); (new SelfDriveDamageVision)->compare($this->images());
    }
}
