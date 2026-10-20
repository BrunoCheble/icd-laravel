<?php

namespace App\Modules\Announcements\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Announcements\Models\AnnouncementComment;
use App\Modules\Announcements\Services\MarkCommentReadService;
use App\Modules\Announcements\Services\RemoveCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

/**
 * The team reading (or marking as not read) and deleting the members' private comments about announcements.
 */
class AnnouncementCommentController extends Controller
{
    public function read(Request $request, AnnouncementComment $comment, MarkCommentReadService $service): RedirectResponse
    {
        $read = $request->boolean('read');
        $service->execute($comment, $read);

        return Redirect::back()->with('success', $read ? __('Comment marked as read.') : __('Comment marked as not read.'));
    }

    public function destroy(AnnouncementComment $comment, RemoveCommentService $service): RedirectResponse
    {
        $service->execute($comment);

        return Redirect::back()->with('success', __('Comment deleted.'));
    }
}
