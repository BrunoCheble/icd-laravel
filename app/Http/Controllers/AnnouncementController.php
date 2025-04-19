<?php

namespace App\Http\Controllers;

use App\Enums\AnnouncementType;
use App\Helpers\ArrayHelper;
use App\Helpers\MemberHelper;
use App\Http\Requests\AnnouncementRequest;
use App\Models\Announcement;
use App\Models\Member;
use App\Services\RegisterAnnouncementService;
use App\Services\SaveAnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Redirect;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::with('member')->paginate();
        return view('announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        $announcement = new Announcement();
        $members = ArrayHelper::toKeyValueArray(Member::all(), 'id', 'first_and_last_name_and_document');
        $announcementOptions = AnnouncementType::options();
        return view('announcements.create', compact('announcement', 'members', 'announcementOptions'));
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

    public function show(Announcement $announcement): View
    {
        return view('announcements.show', compact('announcement'));
    }

    public function edit($id): View
    {
        $announcement = Announcement::find($id);
        $members = ArrayHelper::toKeyValueArray(Member::all(), 'id', 'first_and_last_name_and_document');
        $announcementOptions = AnnouncementType::options();
        return view('announcements.edit', compact('announcement', 'members', 'announcementOptions'));
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

    public function destroy(Announcement $announcement): RedirectResponse
    {
        try {
            $announcement->delete();
        } catch (\Exception $e) {
            return Redirect::route('announcements.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('announcements.index')
            ->with('success', __('Announcement deleted successfully.'));
    }
}
