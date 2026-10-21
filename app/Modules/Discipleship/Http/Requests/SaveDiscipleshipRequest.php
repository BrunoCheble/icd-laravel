<?php

namespace App\Modules\Discipleship\Http\Requests;

use App\Modules\Discipleship\Models\Discipleship;
use App\Modules\Discipleship\Support\DiscipleshipAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveDiscipleshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** A mentor who is not an administrator handles only their own disciples. */
    protected function prepareForValidation(): void
    {
        $mentor = DiscipleshipAccess::mentorOf($this->user());
        $this->merge([
            'mentor_id' => $mentor === null ? $this->input('mentor_id') : ($mentor ?: null),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : null,
        ]);
    }

    public function rules(): array
    {
        $discipleship = $this->route('discipleship');

        return [
            'mentor_id' => ['required', 'integer', 'exists:members,id'],
            'disciple_id' => ['required', 'integer', 'exists:members,id', 'different:mentor_id',
                Rule::unique('discipleships')->where('mentor_id', $this->input('mentor_id'))->ignore($discipleship)],
            'started_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** A member is followed by one mentor at a time. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || $this->input('is_active') === false) {
                    return;
                }
                $other = Discipleship::query()->with('mentor')->where('disciple_id', $this->input('disciple_id'))->where('is_active', true)
                    ->where('mentor_id', '!=', $this->input('mentor_id'))->first();
                if ($other) {
                    $validator->errors()->add('disciple_id', __('This member is already followed by :name.', ['name' => $other->mentor->full_name]));
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'mentor_id.required' => __('Your user is not linked to a member: ask an administrator.'),
            'disciple_id.different' => __('The mentor cannot be their own disciple.'),
            'disciple_id.unique' => __('This member is already a disciple of this mentor.'),
        ];
    }

    public function attributes(): array
    {
        return ['disciple_id' => __('disciple'), 'mentor_id' => __('mentor')];
    }
}
