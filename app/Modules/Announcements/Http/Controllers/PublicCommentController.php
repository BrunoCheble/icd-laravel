<?php

namespace App\Modules\Announcements\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Announcements\Http\Requests\PublicCommentRequest;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Services\SubmitCommentService;
use App\Modules\Announcements\Support\AnnouncementCategories;
use Illuminate\Http\JsonResponse;

/**
 * Comments sent from the site about a published announcement, by members (their NIF); they wait for approval.
 */
class PublicCommentController extends Controller
{
    public function store(PublicCommentRequest $request, Announcement $announcement, SubmitCommentService $service): JsonResponse
    {
        app()->setLocale('pt');
        // Only the announcements the site shows take comments.
        abort_unless($announcement->status() === Announcement::PUBLISHED && AnnouncementCategories::has($announcement->type), 404);

        $service->execute($announcement, $request->member(), $request->validated('body'));

        return response()->json(['message' => __('Thank you! Your comment shows on the site once approved.')], 201);
    }
}
