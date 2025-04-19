<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use App\Services\FileUploadService;

class RegisterAnnouncementService
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function execute(array $data, Member $member): Announcement
    {
        return DB::transaction(function () use ($data, $member) {
            $filePath = null;

            if (!empty($data['image_path']) && $data['image_path'] instanceof \Illuminate\Http\UploadedFile) {
                $filePath = $this->fileUploadService->upload($data['image_path'], 'announcements');
            }

            return Announcement::create([
                'title'       => $data['title'],
                'description' => $data['description'],
                'type'        => $data['type'],
                'contact'     => $data['contact'],
                'link'        => $data['link'],
                'member_id'   => $member->id,
                'image_path'  => $filePath,
                'is_approved' => false,
            ]);
        });
    }

}
