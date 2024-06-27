<?php

namespace App\Services;

use App\Models\Member;

class UploadMemberPhoto
{
    public static function uploadAndSave($file, Member $member)
    {
        $filename = time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images'), $filename);

        $oldImage = $member->photo;
        $member->photo = $filename;
        $member->save();

        if ($oldImage && file_exists(public_path('images/' . $oldImage))) {
            unlink(public_path('images/' . $oldImage));
        }
    }
}
