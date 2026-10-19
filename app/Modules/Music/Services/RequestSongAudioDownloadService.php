<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\Song;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RequestSongAudioDownloadService
{
    /**
     * Asks icd-chords on this computer to download the audio of the song's link and send it back to the song
     * (POST /api/links, a job there). Only on a local computer (the local environment, with the helper's address
     * set): never in production. True when asked; a helper that is not running only leaves a note in the log.
     */
    public function execute(Song $song): bool
    {
        $url = config('services.chords_helper.internal_url');
        if (! app()->environment('local') || ! $url || ! $song->youtube_url) {
            return false;
        }

        try {
            return Http::timeout(3)->post(rtrim($url, '/') . '/api/links', ['id' => $song->id, 'link' => $song->youtube_url])->successful();
        } catch (\Throwable $e) {
            Log::info('icd-chords did not take the download of song ' . $song->id . ': ' . $e->getMessage());
            return false;
        }
    }
}
