<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;

class CustomerAppReleaseController extends BaseApiController
{
    public function __invoke(Request $request)
    {
        if ($request->query('platform', 'android') !== 'android') {
            return $this->error('Unsupported app platform.', 422);
        }
        $data = config('customer_app_release.android');
        if (!is_array($data) || (int) ($data['latest_build'] ?? 0) < 1
            || (int) ($data['minimum_build'] ?? 0) < 0
            || (int) ($data['minimum_build'] ?? 0) > (int) ($data['latest_build'] ?? 0)) {
            return $this->error('Release configuration is unavailable.', 503);
        }
        // Public read-only metadata. It never returns customer data or credentials.
        return $this->success($data, 'Customer app release')->header('Cache-Control', 'no-store');
    }
}
