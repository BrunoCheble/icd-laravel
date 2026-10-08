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

    public function deleteFile(Song $song): void
    {
        $path = $song->audio_path ? public_path(self::FOLDER . '/' . $song->audio_path) : null;

        if ($path && is_file($path)) {
            unlink($path);
        }
    }
}
