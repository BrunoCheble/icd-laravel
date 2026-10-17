<?php

namespace App\Http\Requests;

use App\Models\SongLesson;
use App\Services\ContributeSongLessonService;
use App\Services\SaveSongAudioService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SongLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A lesson of a song: its instrument, title and type, with the YouTube link (type youtube) or the file (audio, PDF
     * or image) of that type; notes are optional.
     */
    public function rules(): array
    {
        return [
            'instrument' => ['required', 'string', 'max:50'],
            'title'      => ['required', 'string', 'max:150'],
            'type'       => ['required', Rule::in(SongLesson::TYPES)],
            'notes'      => ['nullable', 'string', 'max:20000'],
            'video_url'  => ['nullable', 'required_if:type,youtube', 'url', 'max:255', 'regex:/(youtube(-nocookie)?\.com|youtu\.be)\//i', function (string $attribute, mixed $value, \Closure $fail) {
                if (is_string($value) && ContributeSongLessonService::youtubeId($value) === null) {
                    $fail(__('Use the link of a YouTube video.'));
                }
            }],
            'file'       => ['nullable', 'file', 'max:' . SaveSongAudioService::MAX_MEGABYTES * 1024],
        ];
    }

    /**
     * A file type needs a file of that type: sent now, or the one the lesson already has when its type is unchanged.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $type = $this->input('type');
                if ($validator->errors()->isNotEmpty() || ! SongLesson::isFileType($type)) {
                    return;
                }
                $file = $this->file('file');
                if ($file) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    if (! in_array($extension, SongLesson::FILE_EXTENSIONS[$type], true)) {
                        $validator->errors()->add('file', __('Send a file of the lesson\'s type (:types).', ['types' => implode(', ', SongLesson::FILE_EXTENSIONS[$type])]));
                    }

                    return;
                }
                $lesson = $this->route('lesson');
                if (! ($lesson instanceof SongLesson && $lesson->type === $type && $lesson->file_path)) {
                    $validator->errors()->add('file', __('Send the file of the lesson.'));
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'video_url.regex'       => __('Use a YouTube link.'),
            'video_url.required_if' => __('Paste the YouTube link.'),
            'file.uploaded'         => __('The file is larger than the server accepts (:size MB).', ['size' => SaveSongAudioService::maxUploadMegabytes()]),
        ];
    }
}
