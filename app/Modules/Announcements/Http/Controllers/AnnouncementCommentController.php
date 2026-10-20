<?php

namespace App\Modules\Announcements\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Announcements\Models\AnnouncementComment;
use App\Modules\Announcements\Services\ApproveCommentService;
use App\Modules\Announcements\Services\RemoveCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

/**
 * The team approving (or taking back) and deleting the members' comments about announcements.
 */
class AnnouncementCommentController extends Controller
{
    public function approval(Request $request, AnnouncementComment $comment, ApproveCommentService $service): RedirectResponse
    {
        $approved = $request->boolean('approved');
        $service->execute($comment, $approved);

        return Redirect::back()->with('success', $approved ? __('Comment approved.') : __('Comment back to pending.'));
    }

    public function destroy(AnnouncementComment $comment, RemoveCommentService $service): RedirectResponse
    {
        $service->execute($comment);

        return Redirect::back()->with('success', __('Comment deleted.'));
    }
}
