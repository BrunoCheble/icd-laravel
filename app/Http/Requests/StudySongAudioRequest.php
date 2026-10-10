<?php

namespace App\Http\Requests;

use App\Services\SaveSongAudioService;
use Illuminate\Foundation\Http\FormRequest;

class StudySongAudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The song's audio file sent by the study app (same formats and size as the song form).
     */
    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'mimes:mp3,mpga,m4a,mp4,aac,ogg,oga,wav', 'max:' . SaveSongAudioService::MAX_MEGABYTES * 1024],
        ];
    }

    public function messages(): array
    {
        return [
            'audio.uploaded' => __('The audio is larger than the server accepts (:size MB). Send a smaller file or raise the upload limit of the server.', ['size' => SaveSongAudioService::maxUploadMegabytes()]),
        ];
    }
}
