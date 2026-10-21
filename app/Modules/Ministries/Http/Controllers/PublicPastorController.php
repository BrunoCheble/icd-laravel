<?php

namespace App\Modules\Ministries\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ministries\Services\ListPastorsService;
use Illuminate\Http\JsonResponse;

class PublicPastorController extends Controller
{
    /** The site's "Pastores" (see ListPastorsService), cached in the browser for a few minutes. */
    public function index(ListPastorsService $service): JsonResponse
    {
        return response()->json(['data' => $service->execute()])->header('Cache-Control', 'public, max-age=300');
    }
}
