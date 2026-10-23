<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\CustomerAppReleaseController;
use Illuminate\Http\Request;
use Tests\TestCase;

class CustomerAppReleaseTest extends TestCase
{
    public function test_release_metadata_is_public_and_contains_no_customer_data(): void
    {
        $release = ['enabled' => true, 'latest_build' => 10, 'minimum_build' => 0,
            'package_name' => 'com.duracabs.app', 'latest_version' => '1.1.0',
            'store_url' => 'https://play.google.com/store/apps/details?id=com.duracabs.app'];
        config(['customer_app_release.android' => $release]);
        $response = (new CustomerAppReleaseController())(Request::create('/api/v1/app-release?platform=android'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($release, $response->getData(true)['data']);
    }

    public function test_bad_minimum_version_cannot_publish_an_impossible_update(): void
    {
        config(['customer_app_release.android' => ['latest_build' => 10, 'minimum_build' => 11]]);
        $response = (new CustomerAppReleaseController())(Request::create('/api/v1/app-release'));
        $this->assertSame(503, $response->getStatusCode());
    }

    public function test_unsupported_platform_is_rejected(): void
    {
        $response = (new CustomerAppReleaseController())(Request::create('/api/v1/app-release?platform=ios'));
        $this->assertSame(422, $response->getStatusCode());
    }
}
