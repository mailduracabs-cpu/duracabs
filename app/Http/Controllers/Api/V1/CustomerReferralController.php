<?php
namespace App\Http\Controllers\Api\V1;
use App\Services\CustomerReferralService;
use Illuminate\Http\Request;
use RuntimeException;
class CustomerReferralController extends BaseApiController {
    public function show(Request $request, CustomerReferralService $service) {
        try { return $this->success($service->summary($request->user()), 'Refer & Earn'); }
        catch (RuntimeException $e) { return $this->error($e->getMessage(), 422); }
    }
    public function apply(Request $request, CustomerReferralService $service) {
        $data = $request->validate(['code' => ['required', 'string', 'max:20', 'regex:/^[a-zA-Z0-9]+$/']]);
        try { $service->apply($request->user(), $data['code']); return $this->success($service->summary($request->user()), 'Referral code applied.'); }
        catch (RuntimeException $e) { return $this->error($e->getMessage(), 422); }
    }
}
