<?php

namespace App\Modules\Members\Services;

use App\Modules\Members\Models\Member;
use Illuminate\Http\UploadedFile;
use App\Support\ImageResizer;
use Illuminate\Support\Str;

class UploadMemberPhotoService
{
    /**
     * Saves the photo in public/images (named by the time, the member and a few random letters) and removes the one it replaces.
     */
    public function execute(Member $member, UploadedFile $file): void
    {
        $name = time() . '-' . $member->id . '-' . Str::lower(Str::random(6));
        // Made light (upright, at most 1200 px, JPEG); what the server cannot do keeps the picture as it came.
        $filename = ImageResizer::resize($file->getRealPath(), public_path('images/' . $name));
        if (! $filename) {
            $filename = $name . '.' . strtolower($file->getClientOriginalExtension());
            $file->move(public_path('images'), $filename);
        }

        $oldImage = $member->photo;
        $member->photo = $filename;
        $member->save();

        if ($oldImage && file_exists(public_path('images/' . $oldImage))) {
            unlink(public_path('images/' . $oldImage));
        }
    }
}
