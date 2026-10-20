<?php

namespace App\Modules\Announcements\Services;

use App\Helpers\WhatsAppHelper;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Models\AnnouncementComment;
use App\Modules\Announcements\Support\AnnouncementCategories;
use Illuminate\Support\Facades\Cache;

class ListPublicAnnouncementsService
{
    // How long the list is kept (changes clear it at once, see Announcement::booted() and AnnouncementComment::booted()).
    public const MINUTES = 10;

    /**
     * The announcements the site shows: approved, not expired and of a category (the old donations and products are
     * left out), newest first, with what the site needs to show them: the category's banner and fields, the WhatsApp
     * link, the résumé's PDF and the approved comments. In Portuguese, as the site. Cached, so many visits make one
     * query.
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
                    ->with(['comments' => fn ($query) => $query->where('is_approved', true)->with('member')])
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
                        'pdf_url' => $announcement->isPdf() ? $announcement->url_image : null,
                        'is_approved' => true,
                        'expires_at' => $announcement->expires_at,
                        'created_at' => $announcement->created_at?->toIso8601String(),
                        'comments' => $announcement->comments->map(fn (AnnouncementComment $comment) => [
                            'name' => $comment->member?->first_and_last_name ?? __('Member'),
                            'body' => $comment->body,
                            'date' => $comment->created_at?->format('d/m/Y'),
                        ])->values()->all(),
                        'comments_url' => route('api.announcements.comments', $announcement),
                    ])
                    ->all();
            } finally {
                app()->setLocale($locale);
            }
        });
    }
}
