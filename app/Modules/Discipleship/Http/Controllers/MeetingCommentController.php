<?php

namespace App\Modules\Discipleship\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Discipleship\Http\Requests\StoreMeetingCommentRequest;
use App\Modules\Discipleship\Models\DiscipleshipMeeting;
use App\Modules\Discipleship\Models\DiscipleshipMeetingComment;
use App\Modules\Discipleship\Services\AddMeetingCommentService;
use App\Modules\Discipleship\Support\DiscipleshipAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MeetingCommentController extends Controller
{
    public function store(StoreMeetingCommentRequest $request, DiscipleshipMeeting $meeting, AddMeetingCommentService $service): RedirectResponse
    {
        // Also the mentor who follows one of its disciples now (the history is the member's).
        abort_unless(DiscipleshipAccess::canView($request->user(), $meeting), 403, __('You do not have access to this page.'));
        $service->execute($meeting, $request->user(), $request->validated('body'));

        return redirect()->to(route('discipleships.meetings.show', $meeting) . '#comments')->with('success', __('Comment added.'));
    }

    /** Only its author or an administrator deletes a comment. */
    public function destroy(Request $request, DiscipleshipMeetingComment $comment): RedirectResponse
    {
        abort_unless($request->user()->is_admin || $comment->user_id === $request->user()->id, 403);
        $meeting = $comment->meeting;
        $comment->delete();

        return redirect()->to(route('discipleships.meetings.show', $meeting) . '#comments')->with('success', __('Comment deleted.'));
    }
}
