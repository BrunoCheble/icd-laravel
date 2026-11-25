<?php

namespace App\Modules\Ministries\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ministries\Services\ListPublicMinistriesService;
use Illuminate\Http\JsonResponse;

class PublicMinistryController extends Controller
{
    /** The site's ministries with their pages (see ListPublicMinistriesService). */
    public function index(ListPublicMinistriesService $service): JsonResponse
    {
        return response()->json(['data' => $service->execute()])->header('Cache-Control', 'public, max-age=60');
    }
}
