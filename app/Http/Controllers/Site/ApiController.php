<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Modules\Announcements\Models\Announcement;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    /**
     * The announcements the site shows: approved and not expired, newest first.
     */
    public function index(): JsonResponse
    {
        $announcements = Announcement::where('is_approved', true)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()->startOfDay()))
            ->latest()
            ->get();

        return response()->json(['data' => $announcements], 200);
    }

}
