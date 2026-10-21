<?php

namespace App\Modules\Discipleship\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Discipleship\Http\Requests\MarkMeetingRequest;
use App\Modules\Discipleship\Http\Requests\SaveMeetingRequest;
use App\Modules\Discipleship\Models\DiscipleshipMeeting;
use App\Modules\Discipleship\Services\GetDiscipleshipFormOptionsService;
use App\Modules\Discipleship\Services\MarkMeetingService;
use App\Modules\Discipleship\Services\RemoveMeetingService;
use App\Modules\Discipleship\Services\SaveMeetingService;
use App\Modules\Discipleship\Support\DiscipleshipAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingController extends Controller
{
    /** A new meeting; "disciples[]" and "mentor" may come chosen (from a disciple's row). */
    public function create(Request $request, GetDiscipleshipFormOptionsService $options): View
    {
        $meeting = new DiscipleshipMeeting(['mentor_id' => DiscipleshipAccess::mentorOf($request->user()) ?: $request->integer('mentor') ?: null]);

        return view('discipleship::meetings.form', [
            'meeting' => $meeting,
            'chosen' => array_map('strval', (array) $request->input('disciples', [])),
            'mentors' => $options->mentors($request->user()),
        ]);
    }

    public function store(SaveMeetingRequest $request, SaveMeetingService $service): RedirectResponse
    {
        $meeting = $service->execute($request->validated());

        return redirect()->route('discipleships.meetings.show', $meeting)->with('success', __('Meeting scheduled.'));
    }

    /**
     * Any meeting (open to the module's users); its date and whether it took place are changed by its mentor, and
     * comments come from them and from whoever follows one of its disciples now.
     */
    public function show(Request $request, DiscipleshipMeeting $meeting): View
    {
        $meeting->load(['mentor', 'disciples', 'comments.user']);

        return view('discipleship::meetings.show', [
            'meeting' => $meeting,
            'canChange' => DiscipleshipAccess::allows($request->user(), $meeting->mentor_id),
            'canComment' => DiscipleshipAccess::canComment($request->user(), $meeting),
        ]);
    }

    public function edit(Request $request, DiscipleshipMeeting $meeting, GetDiscipleshipFormOptionsService $options): View
    {
        DiscipleshipAccess::authorize($request->user(), $meeting->mentor_id);
        $chosen = $meeting->disciples()->pluck('members.id')->map(fn ($id) => (string) $id)->all();

        return view('discipleship::meetings.form', [
            'meeting' => $meeting,
            'chosen' => $chosen,
            'mentors' => $options->mentors($request->user(), $chosen),
        ]);
    }

    public function update(SaveMeetingRequest $request, DiscipleshipMeeting $meeting, SaveMeetingService $service): RedirectResponse
    {
        DiscipleshipAccess::authorize($request->user(), $meeting->mentor_id);
        $service->execute($request->validated(), $meeting);

        return redirect()->route('discipleships.meetings.show', $meeting)->with('success', __('Meeting saved.'));
    }

    /** Whether it took place (and who was there) or not (and why). */
    public function outcome(MarkMeetingRequest $request, DiscipleshipMeeting $meeting, MarkMeetingService $service): RedirectResponse
    {
        DiscipleshipAccess::authorize($request->user(), $meeting->mentor_id);
        $service->execute($meeting, $request->validated());

        return redirect()->route('discipleships.meetings.show', $meeting)->with('success', __('Meeting saved.'));
    }

    public function destroy(Request $request, DiscipleshipMeeting $meeting, RemoveMeetingService $service): RedirectResponse
    {
        DiscipleshipAccess::authorize($request->user(), $meeting->mentor_id);
        $service->execute($meeting);

        return redirect()->route('discipleships.index')->with('success', __('Meeting deleted.'));
    }
}
