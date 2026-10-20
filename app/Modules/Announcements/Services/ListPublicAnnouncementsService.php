<?php

namespace App\Modules\Announcements\Services;

use App\Helpers\WhatsAppHelper;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Support\AnnouncementCategories;
use Illuminate\Support\Facades\Cache;

class ListPublicAnnouncementsService
{
    // How long the list is kept (changes clear it at once, see Announcement::booted() and AnnouncementRating::booted()).
    public const MINUTES = 10;

    /**
     * The announcements the site shows: approved, not expired and of a category (the old products are left out),
     * newest first, with what the site needs to show them: the picture (the first photo or the category's banner),
     * the photos, the category's fields, the WhatsApp link, the résumé's PDF, the members' average stars and where to
     * send stars or a private comment (the comments themselves are only for the team). In Portuguese, as the site.
     * Cached, so many visits make one query.
     */
    public function execute(): array
    {
        return Cache::remember(Announcement::PUBLIC_CACHE, now()->addMinutes(self::MINUTES), function () {
            $locale = app()->getLocale();
            app()->setLocale('pt');
            try {
                return Announcement::query()
                    ->where('is_approved', true)
                    ->whereIn('type', AnnouncementCategories::keys())
                    ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()->startOfDay()))
                    ->with('ratings')
                    ->latest()
                    ->get()
                    ->map(fn (Announcement $announcement) => [
                        'id' => $announcement->id,
                        'title' => $announcement->title,
                        'description' => $announcement->description,
                        'type' => $announcement->type,
                        'type_name' => $announcement->type_name,
                        'details' => $announcement->detail_lines,
                        'contact' => $announcement->contact,
                        'whatsapp' => WhatsAppHelper::url($announcement->contact),
                        'link' => $announcement->link,
                        'image_url' => $announcement->picture_url,
                        'photos' => $announcement->photo_urls,
                        'pdf_url' => $announcement->isPdf() ? $announcement->url_image : null,
                        'is_approved' => true,
                        'expires_at' => $announcement->expires_at,
                        'created_at' => $announcement->created_at?->toIso8601String(),
                        'rating' => $announcement->ratingSummary(),
                        'ratings_url' => route('api.announcements.ratings', $announcement),
                        'comments_url' => route('api.announcements.comments', $announcement),
                    ])
                    ->all();
            } finally {
                app()->setLocale($locale);
            }
        });
    }
}
