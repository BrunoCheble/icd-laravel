<?php

namespace App\Modules\Gallery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gallery\Services\ListPublicGalleryService;
use Illuminate\Http\JsonResponse;

class PublicGalleryController extends Controller
{
    /** The site's gallery (see ListPublicGalleryService). */
    public function index(ListPublicGalleryService $service): JsonResponse
    {
        return response()->json(['data' => $service->execute()])->header('Cache-Control', 'public, max-age=60');
    }
}
