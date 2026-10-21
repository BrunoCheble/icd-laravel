<?php

namespace App\Modules\Discipleship\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Discipleship\Http\Requests\SaveDiscipleshipRequest;
use App\Modules\Discipleship\Http\Requests\TransferDiscipleshipRequest;
use App\Modules\Discipleship\Models\Discipleship;
use App\Modules\Discipleship\Services\GetDiscipleHistoryService;
use App\Modules\Discipleship\Services\GetDiscipleshipFormOptionsService;
use App\Modules\Discipleship\Services\ListDiscipleshipsService;
use App\Modules\Discipleship\Services\RemoveDiscipleshipService;
use App\Modules\Discipleship\Services\SaveDiscipleshipService;
use App\Modules\Discipleship\Services\TransferDiscipleshipService;
use App\Modules\Discipleship\Support\DiscipleshipAccess;
use App\Modules\Members\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscipleshipController extends Controller
{
    public function index(Request $request, ListDiscipleshipsService $service): View
    {
        $user = $request->user();
        // Everyone's (open to the module's users), only one's own ("me") or one mentor's.
        $own = (int) $user->member_id;
        $filter = (string) $request->query('mentor', '');
        $mentor = $filter === 'me' ? ($own ?: -1) : ((int) $filter ?: null);
        $mentors = Member::query()->whereIn('id', Discipleship::query()->select('mentor_id'))->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        // The mentors who receive the reminders (they have a user).
        $withUser = User::query()->whereNotNull('member_id')->pluck('member_id')->all();

        return view('discipleship::discipleships.index', $service->execute($mentor) + [
            'mentor' => $mentor,
            'filter' => $filter,
            'mentors' => $mentors,
            'withUser' => $withUser,
            'linked' => DiscipleshipAccess::mentorOf($user) !== 0,
        ]);
    }

    public function create(Request $request, GetDiscipleshipFormOptionsService $options): View
    {
        return view('discipleship::discipleships.form', ['discipleship' => new Discipleship(['started_at' => now()]), 'people' => $options->people()]);
    }

    public function store(SaveDiscipleshipRequest $request, SaveDiscipleshipService $service): RedirectResponse
    {
        $service->execute($request->validated());

        return redirect()->route('discipleships.index')->with('success', __('Disciple added.'));
    }

    public function edit(Request $request, Discipleship $discipleship, GetDiscipleshipFormOptionsService $options): View
    {
        DiscipleshipAccess::authorize($request->user(), $discipleship->mentor_id);

        return view('discipleship::discipleships.form', ['discipleship' => $discipleship, 'people' => $options->people()]);
    }

    public function update(SaveDiscipleshipRequest $request, Discipleship $discipleship, SaveDiscipleshipService $service): RedirectResponse
    {
        DiscipleshipAccess::authorize($request->user(), $discipleship->mentor_id);
        $service->execute($request->validated(), $discipleship);

        return redirect()->route('discipleships.index')->with('success', __('Discipleship saved.'));
    }

    /** The disciple's meetings with every mentor, and who followed them. */
    public function history(Request $request, Discipleship $discipleship, GetDiscipleHistoryService $service): View
    {
        return view('discipleship::discipleships.history', $service->execute($discipleship) + ['discipleship' => $discipleship->load('disciple')]);
    }

    /** Another mentor goes on with the disciple (the administrators, or the mentor now). */
    public function transfer(TransferDiscipleshipRequest $request, Discipleship $discipleship, TransferDiscipleshipService $service): RedirectResponse
    {
        DiscipleshipAccess::authorize($request->user(), $discipleship->mentor_id);
        abort_unless($discipleship->is_active, 403);
        $next = $service->execute($discipleship, (int) $request->validated('mentor_id'));

        return redirect()->route('discipleships.index')->with('success', __(':disciple is now followed by :mentor.', [
            'disciple' => $next->disciple->full_name, 'mentor' => $next->mentor->full_name,
        ]));
    }

    public function destroy(Request $request, Discipleship $discipleship, RemoveDiscipleshipService $service): RedirectResponse
    {
        DiscipleshipAccess::authorize($request->user(), $discipleship->mentor_id);
        $service->execute($discipleship);

        return redirect()->route('discipleships.index')->with('success', __('Discipleship deleted.'));
    }
}
