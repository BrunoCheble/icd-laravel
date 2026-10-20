<?php

namespace App\Modules\Announcements\Services;

use App\Helpers\WhatsAppHelper;
use App\Modules\Announcements\Models\Announcement;
use Illuminate\Support\Facades\Cache;

class ListPublicAnnouncementsService
{
    // How long the list is kept (changes clear it at once, see Announcement::booted()).
    public const MINUTES = 10;

    /**
     * The announcements the site shows: approved and not expired, newest first, with what the site needs to show
     * them (the type's name, the picture's address, the WhatsApp link). Cached, so many visits make one query.
     */
    public function execute(): array
    {
        return Cache::remember(Announcement::PUBLIC_CACHE, now()->addMinutes(self::MINUTES), fn () => Announcement::query()
            ->where('is_approved', true)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()->startOfDay()))
            ->latest()
            ->get()
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'description' => $announcement->description,
                'type' => $announcement->type,
                'type_name' => $announcement->type_name,
                'contact' => $announcement->contact,
                'whatsapp' => WhatsAppHelper::url($announcement->contact),
                'link' => $announcement->link,
                // The picture to show (the default one without a picture), and the PDF when one was sent.
                'image_url' => $announcement->picture_url,
                'has_picture' => $announcement->url_image !== null && ! $announcement->isPdf(),
                'pdf_url' => $announcement->isPdf() ? $announcement->url_image : null,
                'is_pdf' => $announcement->isPdf(),
                'is_approved' => true,
                'expires_at' => $announcement->expires_at,
                'created_at' => $announcement->created_at?->toIso8601String(),
            ])
            ->all());
    }
}
