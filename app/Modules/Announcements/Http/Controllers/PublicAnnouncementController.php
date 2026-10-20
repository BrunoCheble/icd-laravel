<?php

namespace App\Modules\Announcements\Http\Controllers;

use App\Modules\Announcements\Support\AnnouncementCategories;
use App\Http\Controllers\Controller;
use App\Modules\Announcements\Http\Requests\PublicAnnouncementRequest;
use App\Modules\Announcements\Services\RegisterAnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The public page where a member sends an announcement; it waits for approval before the site shows it.
 */
class PublicAnnouncementController extends Controller
{
    public function create(): View
    {
        app()->setLocale('pt');

        return view('announcements::public.create', ['categories' => AnnouncementCategories::forForms()]);
    }

    public function store(PublicAnnouncementRequest $request, RegisterAnnouncementService $service): RedirectResponse
    {
        app()->setLocale('pt');
        $announcement = $service->execute($request->validated(), $request->member());

        return redirect()->route('site.announcement')->with('sent', $announcement->type);
    }
}
