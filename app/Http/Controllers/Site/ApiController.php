<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Modules\Announcements\Services\ListPublicAnnouncementsService;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    /**
     * The announcements the site shows (see ListPublicAnnouncementsService), cached here and in the browser.
     */
    public function index(ListPublicAnnouncementsService $service): JsonResponse
    {
        return response()->json(['data' => $service->execute()], 200)
            ->header('Cache-Control', 'public, max-age=300');
    }
}
