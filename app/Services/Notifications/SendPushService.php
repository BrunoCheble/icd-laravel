<?php

namespace App\Services\Notifications;

use App\Models\PushSubscription;
use App\Support\WebPush;
use Illuminate\Support\Collection;
use Throwable;

class SendPushService
{
    public function __construct(private WebPush $push)
    {
    }

    /**
     * Sends a notification (title, body, the page it opens) to the devices given, every user's by default; the
     * devices no longer subscribed are forgotten. Returns how many received it.
     */
    public function execute(array $notification, ?Collection $devices = null): int
    {
        $sent = 0;
        foreach ($devices ?? PushSubscription::all() as $device) {
            try {
                $status = $this->push->send($device->endpoint, $device->public_key, $device->auth_token, $notification);
            } catch (Throwable $error) {
                report($error);
                continue;
            }
            if (in_array($status, [404, 410], true)) {
                $device->delete();
            } elseif ($status >= 200 && $status < 300) {
                $sent++;
            }
        }

        return $sent;
    }
}
