<?php

namespace Maatify\Realm\Tests\Support;

use Maatify\Realm\FCMDeprecated;

/** A concrete FCMDeprecated for tests: the endpoint is a local HTTPS stand-in. */
final class TestFcm extends FCMDeprecated
{
    public function __construct(private readonly string $url)
    {
        $this->api_key = 'CANARY-FCM-KEY';
    }

    protected function endpoint(): string
    {
        return $this->url;
    }

    /** @return mixed */
    public function sendTestNotification()
    {
        return $this->SendDeviceIDNotification('device-token', 'title', 'message');
    }
}
