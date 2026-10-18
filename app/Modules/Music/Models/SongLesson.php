<?php

namespace App\Modules\Music\Models;

use App\Modules\Music\Services\SaveSongLessonService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A lesson of a song for an instrument, of one type (see TYPES): a YouTube video, an audio file, a PDF or an image.
 * Optionally with notes; a video or audio has stretches saved to be repeated (`loops`). `contributed`: a YouTube link
 * sent by a musician on the public page (only the team edits or removes it).
 */
class SongLesson extends Model
{
    public const TYPES = ['youtube', 'audio', 'pdf', 'image'];

    // File extensions accepted for each type that is a file.
    public const FILE_EXTENSIONS = [
        'audio' => ['mp3', 'm4a', 'aac', 'wav', 'ogg', 'oga', 'opus'],
        'pdf'   => ['pdf'],
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    ];

    protected $fillable = ['instrument', 'title', 'type', 'notes', 'video_url', 'loops', 'position', 'contributed'];

    protected function casts(): array
    {
        return ['loops' => 'array', 'position' => 'integer', 'contributed' => 'boolean'];
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public static function isFileType(?string $type): bool
    {
        return array_key_exists((string) $type, self::FILE_EXTENSIONS);
    }

    public function fileUrl(): ?string
    {
        return $this->file_path && is_file(public_path(SaveSongLessonService::FOLDER . '/' . $this->file_path))
            ? asset(SaveSongLessonService::FOLDER . '/' . $this->file_path)
            : null;
    }

    /**
     * The lesson as the pages use it (the lessons tab and the public setlist page).
     */
    public function payload(): array
    {
        return [
            'id'         => $this->id,
            'instrument' => $this->instrument,
            'title'      => $this->title,
            'type'       => $this->type,
            'notes'      => $this->notes,
            'video_url'  => $this->type === 'youtube' ? $this->video_url : null,
            'file_url'   => self::isFileType($this->type) ? $this->fileUrl() : null,
            'file_name'  => $this->file_name,
            'loops'      => in_array($this->type, ['youtube', 'audio'], true) ? ($this->loops ?? []) : [],
            // Sent by a musician on the public page.
            'contributed' => (bool) $this->contributed,
        ];
    }
}
