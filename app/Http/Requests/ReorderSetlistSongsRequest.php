<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Payload: [{"song_id": 10, "position": 1}, {"song_id": 25, "position": 2}, ...]
 */
class ReorderSetlistSongsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*'          => ['required', 'array'],
            '*.song_id'  => ['required', 'integer', 'distinct'],
            '*.position' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                // The new order must contain exactly the songs of this setlist.
                $sent = collect($this->all())->pluck('song_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                $current = $this->route('setlist')->songs()->pluck('songs.id')->sort()->values()->all();

                if ($sent !== $current) {
                    $validator->errors()->add('order', __('The order must contain exactly the songs of this setlist.'));
                }
            },
        ];
    }

    /**
     * Song ids sorted by the requested position.
     */
    public function orderedSongIds(): array
    {
        return collect($this->validated())
            ->sortBy('position')
            ->pluck('song_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
