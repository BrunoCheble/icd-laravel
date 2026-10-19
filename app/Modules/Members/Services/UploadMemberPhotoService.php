<?php

namespace App\Modules\Members\Services;

use App\Modules\Members\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadMemberPhotoService
{
    /**
     * Saves the photo in public/images (named by the time, the member and a few random letters) and removes the one it replaces.
     */
    public function execute(Member $member, UploadedFile $file): void
    {
        $filename = time() . '-' . $member->id . '-' . Str::lower(Str::random(6)) . '.' . strtolower($file->getClientOriginalExtension());
        $file->move(public_path('images'), $filename);

        $oldImage = $member->photo;
        $member->photo = $filename;
        $member->save();

        if ($oldImage && file_exists(public_path('images/' . $oldImage))) {
            unlink(public_path('images/' . $oldImage));
        }
    }
}
