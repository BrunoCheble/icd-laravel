<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Http\UploadedFile;

class SaveSongAudioService
{
    // Folder under public/ with the audio files of the songs.
    public const FOLDER = 'audio/songs';

    /**
     * Stores a new audio file for the song (replacing the old one) or removes it. Each upload gets a new name, so
     * browsers and the offline copies of the app never keep playing an old file.
     */
    public function execute(Song $song, ?UploadedFile $file, bool $remove = false): Song
    {
        if (! $file && ! $remove) {
            return $song;
        }

        $this->deleteFile($song);
        $song->audio_path = null;

        if ($file) {
            $name = $song->id . '-' . bin2hex(random_bytes(6)) . '.' . strtolower($file->getClientOriginalExtension() ?: 'mp3');
            $file->move(public_path(self::FOLDER), $name);
            $song->audio_path = $name;
        }

        $song->save();

        return $song;
    }

    // Largest audio file accepted by the song form (see SongRequest), in MB.
    public const MAX_MEGABYTES = 30;

    /**
     * Largest audio file that can be sent, in MB: the form's limit or the server's (PHP upload limits), if lower.
     */
    public static function maxUploadMegabytes(): int
    {
        $toMegabytes = function (string $value): float {
            $number = (float) $value;

            return match (strtolower(substr(trim($value), -1))) {
                'g' => $number * 1024,
                'k' => $number / 1024,
                'm' => $number,
                default => $number / 1048576,
            };
        };
        $limits = array_filter([
            self::MAX_MEGABYTES,
            $toMegabytes((string) ini_get('upload_max_filesize')),
            $toMegabytes((string) ini_get('post_max_size')),
        ], fn ($limit) => $limit > 0);

        return (int) floor(min($limits));
    }

    public function deleteFile(Song $song): void
    {
        $path = $song->audio_path ? public_path(self::FOLDER . '/' . $song->audio_path) : null;

        if ($path && is_file($path)) {
            unlink($path);
        }
    }
}
