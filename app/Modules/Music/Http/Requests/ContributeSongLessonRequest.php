<?php

namespace App\Modules\Music\Http\Requests;

use App\Modules\Music\Services\ContributeSongLessonService;
use Illuminate\Foundation\Http\FormRequest;

class ContributeSongLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A YouTube video for an instrument, sent by a musician on the public page.
     */
    public function rules(): array
    {
        return [
            'instrument' => ['required', 'string', 'max:50'],
            'video_url'  => ['required', 'url', 'max:255', 'regex:/(youtube(-nocookie)?\.com|youtu\.be)\//i', function (string $attribute, mixed $value, \Closure $fail) {
                if (is_string($value) && ContributeSongLessonService::youtubeId($value) === null) {
                    $fail(__('Use the link of a YouTube video.'));
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'video_url.regex'    => __('Use a YouTube link.'),
            'video_url.required' => __('Paste the YouTube link.'),
        ];
    }
}
