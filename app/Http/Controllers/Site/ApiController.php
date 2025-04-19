<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
   /**
     * Get a listing of approved announcements.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $announcements = Announcement::where('is_approved', false)
            ->latest()
            ->get();

        return response()->json(['data' => $announcements], 200);
    }

}
