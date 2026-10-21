<?php

namespace App\Services\Notifications;

use App\Models\PushSubscription;
use App\Models\User;

class SavePushSubscriptionService
{
    /** A device where the user turned the notifications on (the same device again only updates it). */
    public function execute(User $user, array $data, ?string $device): PushSubscription
    {
        return PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            ['user_id' => $user->id, 'endpoint' => $data['endpoint'], 'public_key' => $data['keys']['p256dh'], 'auth_token' => $data['keys']['auth'], 'device' => $device ? mb_substr($device, 0, 160) : null],
        );
    }
}
