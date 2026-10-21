<?php

namespace App\Modules\Prayers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Prayers\Http\Requests\PublicPrayerRequest;
use App\Modules\Prayers\Services\RegisterPrayerService;
use Illuminate\Http\JsonResponse;

/**
 * The church site's own form for a prayer request: anyone (no need to be a member), with the same rules as the
 * system's public page.
 */
class PublicPrayerApiController extends Controller
{
    public function store(PublicPrayerRequest $request, RegisterPrayerService $service): JsonResponse
    {
        $service->execute($request->validated());
        \App\Modules\Prayers\Support\PrayerDailyLimit::hit($request->input('device'), $request->ip());

        return response()->json([
            'title' => __('We received your request.'),
            'message' => __('We will pray for you. God bless you!'),
        ], 201);
    }
}
