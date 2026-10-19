<?php

namespace App\Modules\Announcements\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Announcements\Http\Requests\AnnouncementRequest;
use App\Modules\Announcements\Http\Requests\ListAnnouncementsRequest;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Services\ApproveAnnouncementService;
use App\Modules\Announcements\Services\ListAnnouncementsService;
use App\Modules\Announcements\Services\RemoveAnnouncementService;
use App\Modules\Announcements\Services\SaveAnnouncementService;
use App\Modules\Members\Services\GetMemberFormOptionsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(ListAnnouncementsRequest $request, ListAnnouncementsService $service): View
    {
        $filters = $request->validated();
        $announcements = $service->execute($filters);
        $statusCounts = $service->countByStatus();
        return view('announcements::announcements.index', compact('announcements', 'filters', 'statusCounts'));
    }

    public function create(GetMemberFormOptionsService $options): View
    {
        $announcement = new Announcement(['expires_at' => now()->addMonth()->toDateString()]);
        return view('announcements::announcements.create', ['announcement' => $announcement, 'people' => $this->people($options)]);
    }

    public function store(AnnouncementRequest $request, SaveAnnouncementService $service): RedirectResponse
    {
        try {
            $service->execute($request->validated());
        } catch (\Exception $e) {
            return Redirect::route('announcements.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('announcements.index')
            ->with('success', __('Announcement created successfully.'));
    }

    public function edit(Announcement $announcement, GetMemberFormOptionsService $options): View
    {
        return view('announcements::announcements.edit', ['announcement' => $announcement, 'people' => $this->people($options)]);
    }

    public function update(AnnouncementRequest $request, Announcement $announcement, SaveAnnouncementService $service): RedirectResponse
    {
        try {
            $service->execute($request->validated(), $announcement);
        } catch (\Exception $e) {
            return Redirect::route('announcements.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('announcements.index')
            ->with('success', __('Announcement updated successfully.'));
    }

    /**
     * Approves an announcement, or takes the approval back ("approved" 1 / 0).
     */
    public function approval(Request $request, Announcement $announcement, ApproveAnnouncementService $service): RedirectResponse
    {
        $approved = $request->boolean('approved');
        $service->execute($announcement, $approved);

        return Redirect::back()
            ->with('success', $approved ? __('Announcement approved.') : __('Announcement back to pending.'));
    }

    public function destroy(Announcement $announcement, RemoveAnnouncementService $service): RedirectResponse
    {
        try {
            $service->execute($announcement);
        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', __('Something went wrong'));
        }

        return Redirect::back()
            ->with('success', __('Announcement deleted successfully.'));
    }

    // The members to pick as the announcement's author (adults: id, name, document, gender), as the member form does.
    private function people(GetMemberFormOptionsService $options): array
    {
        return $options->execute()['people'];
    }
}
