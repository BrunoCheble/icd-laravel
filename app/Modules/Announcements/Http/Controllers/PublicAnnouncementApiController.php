<?php

namespace App\Modules\Announcements\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Announcements\Http\Requests\PublicAnnouncementRequest;
use App\Modules\Announcements\Services\RegisterAnnouncementService;
use App\Modules\Announcements\Support\AnnouncementCategories;
use Illuminate\Http\JsonResponse;

/**
 * The church site's own form to announce: the categories with their fields, and the sending (by a member, the NIF;
 * it waits for approval). The same rules as the system's public page.
 */
class PublicAnnouncementApiController extends Controller
{
    public function categories(): JsonResponse
    {
        app()->setLocale('pt');

        return response()->json(['data' => AnnouncementCategories::forForms()])->header('Cache-Control', 'public, max-age=3600');
    }

    public function store(PublicAnnouncementRequest $request, RegisterAnnouncementService $service): JsonResponse
    {
        app()->setLocale('pt');
        $announcement = $service->execute($request->validated(), $request->member());
        [$title, $message, $note] = $this->thanks()[$announcement->type] ?? [__('Thank you!'), '', null];

        return response()->json(['title' => $title, 'message' => $message, 'note' => $note], 201);
    }

    /** What follows a sent announcement, by its category. */
    private function thanks(): array
    {
        return [
            'service' => [__('Your service was received!'), __('The team will review it and it will be on the church\'s site once approved.'), null],
            'lessons' => [__('Your lessons were received!'), __('The team will review them and they will be on the church\'s site once approved.'), null],
            'resume' => [__('Your résumé was received!'), __('The team will review it and it will be on the church\'s site once approved. God bless your search!'), null],
            'job' => [__('Your job offer was received!'), __('The team will review it and it will be on the church\'s site once approved.'), null],
            'donation' => [__('Your donation was received!'), __('Thank you for your generosity! It will be on the church\'s site once approved.'), null],
            'sale' => [__('Your sale was received!'), __('The team will review it and it will be on the church\'s site once approved.'), __('Sales are agreed directly between you and whoever is interested.')],
        ];
    }
}
