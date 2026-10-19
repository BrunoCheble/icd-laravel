<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Http\Requests\SentMessageRequest;
use App\Modules\Dashboard\Services\GetTodayMessagesService;
use App\Modules\Dashboard\Services\MarkMessageSentService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(GetTodayMessagesService $service): View
    {
        return view('dashboard::index', $service->execute(now()));
    }

    public function markSent(SentMessageRequest $request, MarkMessageSentService $service): JsonResponse
    {
        $sent = $service->execute($request->validated('kind'), (int) $request->validated('recipient_id'), $request->user());
        return response()->json(['sent' => GetTodayMessagesService::sentInfo($sent)]);
    }

    public function unmarkSent(SentMessageRequest $request, MarkMessageSentService $service): JsonResponse
    {
        $service->execute($request->validated('kind'), (int) $request->validated('recipient_id'), $request->user(), false);
        return response()->json(['sent' => null]);
    }
}
