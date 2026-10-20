<?php

namespace App\Modules\Announcements\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Announcements\Http\Requests\PublicRatingRequest;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Services\RateAnnouncementService;
use App\Modules\Announcements\Support\AnnouncementCategories;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * The stars members give a published announcement from the site: one per member (by the NIF), which they can change;
 * not to their own announcement.
 */
class PublicRatingController extends Controller
{
    public function store(PublicRatingRequest $request, Announcement $announcement, RateAnnouncementService $service): JsonResponse
    {
        abort_unless($announcement->status() === Announcement::PUBLISHED && AnnouncementCategories::has($announcement->type), 404);

        $member = $request->member();
        if ($member->id === $announcement->member_id) {
            throw ValidationException::withMessages(['member_document' => __('You cannot rate your own announcement.')]);
        }
        $rating = $service->execute($announcement, $member, (int) $request->validated('stars'));

        return response()->json([
            'message' => $rating->wasRecentlyCreated ? __('Thank you for your rating!') : __('Your rating was changed.'),
            'rating' => $announcement->ratingSummary(),
        ], $rating->wasRecentlyCreated ? 201 : 200);
    }
}
