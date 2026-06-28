<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\HomeService;

class HomeController extends BaseApiController
{
    public function appConfig(HomeService $homeService)
    {
        return $this->success($homeService->appConfig(), 'App config loaded successfully');
    }

    public function home(HomeService $homeService)
    {
        return $this->success($homeService->home(), 'Home data loaded successfully');
    }

    public function contact(HomeService $homeService)
    {
        $config = $homeService->appConfig();

        return $this->success([
            'support_mobile' => $config['support_mobile'],
            'whatsapp_mobile' => $config['whatsapp_mobile'],
            'email' => $config['email'],
            'website' => $config['website'],
        ], 'Contact details loaded successfully');
    }

    public function settings(HomeService $homeService)
    {
        return $this->success($homeService->appConfig(), 'Settings loaded successfully');
    }
}
