<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\Notifications\SavePushSubscriptionService;
use App\Services\Notifications\SendPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The app's notifications on this device: turned on (the device's subscription kept), off, and a test.
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request, SavePushSubscriptionService $service): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'starts_with:https://', 'max:1000'],
            'keys.p256dh' => ['required', 'string', 'max:120'],
            'keys.auth' => ['required', 'string', 'max:60'],
        ]);
        $service->execute($request->user(), $data, $request->userAgent());

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        PushSubscription::query()->where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', (string) $request->input('endpoint')))->delete();

        return response()->json(['ok' => true]);
    }

    public function test(Request $request, SendPushService $push): JsonResponse
    {
        $devices = PushSubscription::query()->where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', (string) $request->input('endpoint')))->get();
        $sent = $push->execute(['title' => __('Notifications are on'), 'body' => __('This is how the church\'s notices will come to this device.'), 'url' => route('profile.edit'), 'tag' => 'test'], $devices);

        return response()->json(['sent' => $sent], $sent ? 200 : 422);
    }
}
