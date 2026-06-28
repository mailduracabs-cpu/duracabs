<?php

namespace App\Http\Controllers\Api\V1;

class NotificationController extends BaseApiController
{
    public function index()
    {
        return $this->success([], 'Notifications API coming in Package 6');
    }

    public function read()
    {
        return $this->success([], 'Notification read API coming in Package 6');
    }
}
