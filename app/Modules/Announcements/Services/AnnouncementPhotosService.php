<?php

namespace App\Modules\Announcements\Services;

use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;

/**
 * The photos of donations and sales: sent to the public folder (images/announcements) and deleted with it.
 */
class AnnouncementPhotosService
{
    public const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private FileUploadService $uploads)
    {
    }

    /**
     * The photos kept (without the removed ones, whose files go) and the new ones, up to $max; returns their paths.
     */
    public function execute(array $current, array $remove, array $new, int $max): array
    {
        $kept = [];
        foreach ($current as $path) {
            if (in_array($path, $remove, true) || count($kept) >= $max) {
                $this->delete([$path]);
            } else {
                $kept[] = $path;
            }
        }
        foreach ($new as $file) {
            if ($file instanceof UploadedFile && count($kept) < $max) {
                $kept[] = $this->uploads->upload($file, 'announcements', self::TYPES);
            }
        }

        return $kept;
    }

    /** Deletes the files (only ones of the announcements' folder). */
    public function delete(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && str_starts_with($path, 'images/announcements/') && ! str_contains($path, '..') && is_file(public_path($path))) {
                @unlink(public_path($path));
            }
        }
    }
}
