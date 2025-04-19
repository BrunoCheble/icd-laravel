<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FileUploadService
{
    /**
     * Handle file upload and return the stored path.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $allowedMimeTypes
     * @param int $maxSize
     * @return string
     * @throws ValidationException
     */
    public function upload(
        UploadedFile $file,
        string $folder,
        array $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'],
        int $maxSize = 5012
    ): string
    {
        // Validate file
        $this->validateFile($file, $allowedMimeTypes, $maxSize);

        // Store the file in the specified folder
        $path = $file->store($folder, 'public');

        // Return the publicly accessible path
        return 'storage/' . $path;
    }

    /**
     * Validate the uploaded file.
     *
     * @param UploadedFile $file
     * @param array $allowedMimeTypes
     * @param int $maxSize
     * @return void
     * @throws ValidationException
     */
    private function validateFile(UploadedFile $file, array $allowedMimeTypes, int $maxSize)
    {
        // Check MIME type
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw ValidationException::withMessages([
                'file' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedMimeTypes)
            ]);
        }

        // Check file size
        if ($file->getSize() > $maxSize * 1024) {
            throw ValidationException::withMessages([
                'file' => 'The file must not exceed ' . $maxSize . 'KB in size.'
            ]);
        }
    }
}
