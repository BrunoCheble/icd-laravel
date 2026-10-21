<?php

namespace App\Modules\Discipleship\Http\Requests;

use App\Modules\Discipleship\Models\Discipleship;
use App\Modules\Discipleship\Support\DiscipleshipAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mentor = DiscipleshipAccess::mentorOf($this->user());
        $this->merge(['mentor_id' => $mentor === null ? $this->input('mentor_id') : ($mentor ?: null)]);
    }

    public function rules(): array
    {
        return [
            'mentor_id' => ['required', 'integer', 'exists:members,id'],
            'disciples' => ['required', 'array', 'min:1'],
            'disciples.*' => ['integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** Only the mentor's disciples (the ones already at the meeting may stay, even if no longer active). */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $kept = $this->route('meeting')?->disciples()->pluck('members.id')->all() ?? [];
                $allowed = Discipleship::query()->where('mentor_id', $this->input('mentor_id'))
                    ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('disciple_id', $kept))
                    ->pluck('disciple_id')->all();
                if (array_diff(array_map('intval', $this->input('disciples')), $allowed)) {
                    $validator->errors()->add('disciples', __('Choose only disciples of this mentor.'));
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'mentor_id.required' => __('Your user is not linked to a member: ask an administrator.'),
            'disciples.required' => __('Choose at least one disciple.'),
        ];
    }

    public function attributes(): array
    {
        return ['date' => __('date'), 'time' => __('time'), 'location' => __('place')];
    }
}
