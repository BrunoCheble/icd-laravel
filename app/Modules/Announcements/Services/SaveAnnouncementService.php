<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\Announcement;
use App\Services\FileUploadService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SaveAnnouncementService
{
    public function __construct(private FileUploadService $fileUploadService)
    {
    }

    /**
     * Creates the announcement, or updates the one given: a new file replaces the old one; the approval date is set
     * when it becomes approved and cleared when it stops being so; it expires at the end of its day.
     */
    public function execute(array $data, ?Announcement $announcement = null): Announcement
    {
        return DB::transaction(function () use ($data, $announcement) {
            $approved = (bool) ($data['is_approved'] ?? false);
            $values = [
                'title'       => $data['title'],
                'description' => $data['description'],
                'type'        => $data['type'],
                'contact'     => $data['contact'],
                'link'        => $data['link'] ?? null,
                'member_id'   => $data['member_id'],
                'is_approved' => $approved,
                'expires_at'  => ! empty($data['expires_at']) ? Carbon::parse($data['expires_at'])->endOfDay()->format('Y-m-d H:i:s') : null,
            ];

            if (($data['image_path'] ?? null) instanceof UploadedFile) {
                $values['image_path'] = $this->fileUploadService->upload($data['image_path'], 'announcements');
                // The file it replaces goes (only one sent to the public folder).
                $old = $announcement?->image_path;
                if ($old && str_starts_with($old, 'images/announcements/') && is_file(public_path($old))) {
                    @unlink(public_path($old));
                }
            }

            if ($approved && (! $announcement || ! $announcement->is_approved)) {
                $values['approved_at'] = now();
            } elseif (! $approved) {
                $values['approved_at'] = null;
            }

            return $announcement ? tap($announcement)->update($values) : Announcement::create($values);
        });
    }
}
