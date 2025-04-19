<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use App\Services\FileUploadService;
use DateTime;

class SaveAnnouncementService
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function execute(array $data, ?Announcement $announcement = null): Announcement
    {
        return DB::transaction(function () use ($data, $announcement) {
            $values = [
                'title'       => $data['title'],
                'description' => $data['description'],
                'type'        => $data['type'],
                'contact'     => $data['contact'],
                'link'        => $data['link'],
                'member_id'   => $data['member_id'],
                'is_approved' => $data['is_approved'],
                'expires_at' => \Carbon\Carbon::createFromFormat('Y-m-d', $data['expires_at'])->endOfDay()->format('Y-m-d H:i:s'),
            ];

            if (!empty($data['image_path']) && $data['image_path'] instanceof \Illuminate\Http\UploadedFile) {
                $values['image_path'] = $this->fileUploadService->upload($data['image_path'], 'announcements');
            }

            if ($announcement && $announcement->is_approved && $data['is_approved'] == 0) {
                $values['approved_at'] = null;
            } else if ((!$announcement || !$announcement->is_approved) && $data['is_approved'] == 1) {
                $values['approved_at'] = now();
            }
            return $announcement ? tap($announcement)->update($values) : Announcement::create($values);
        });
    }
}
