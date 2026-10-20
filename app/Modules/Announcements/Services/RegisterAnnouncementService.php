<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Support\AnnouncementCategories;
use App\Modules\Members\Models\Member;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RegisterAnnouncementService
{
    public function __construct(private FileUploadService $fileUploadService, private AnnouncementPhotosService $photos)
    {
    }

    /**
     * An announcement sent from the public page by a member: waiting for approval, with its category's fields and
     * lasting as long as its category usually does (the team can change it).
     */
    public function execute(array $data, Member $member): Announcement
    {
        return DB::transaction(function () use ($data, $member) {
            $filePath = null;
            // Only a résumé takes a file (its PDF).
            if (AnnouncementCategories::takesPdf($data['type']) && ($data['image_path'] ?? null) instanceof UploadedFile) {
                $filePath = $this->fileUploadService->upload($data['image_path'], 'announcements');
            }

            return Announcement::create([
                'title'       => $data['title'],
                'description' => $data['description'],
                'type'        => $data['type'],
                'details'     => AnnouncementCategories::clean($data['type'], $data['details'] ?? []),
                'contact'     => $data['contact'],
                'link'        => $data['link'] ?? null,
                'member_id'   => $member->id,
                'image_path'  => $filePath,
                // A donation's or a sale's photos (up to its category's number).
                'photos'      => $this->photos->execute([], [], $data['photos'] ?? [], AnnouncementCategories::photos($data['type'])),
                'is_approved' => false,
                'expires_at'  => AnnouncementCategories::defaultExpiry($data['type']) . ' 23:59:59',
            ]);
        });
    }
}
