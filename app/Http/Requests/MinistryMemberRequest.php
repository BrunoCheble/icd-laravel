<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MinistryMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        $ministryMemberId = $this->route('ministry_member'); // Obtém o ID do registro na rota

        return [
            'ministry_id' => ['required', 'integer', 'exists:ministries,id'],
            'member_id' => [
                'required',
                'integer',
                'exists:members,id',
                Rule::unique('ministry_members')
                    ->where(function ($query) {
                        return $query->where('ministry_id', $this->ministry_id);
                    })
                    ->ignore($ministryMemberId) // Ignora o ID atual na validação
            ],
            'role' => ['string'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'ministry_id.required' => __('The ministry is required.'),
            'member_id.required' => __('The member is required.'),
            'ministry_id.exists' => __('The ministry does not exist.'),
            'member_id.exists' => __('The member does not exist.'),
            'member_id.unique' => __('The member is already a member of this ministry.'),
            'role.string' => __('The role must be a string.')
        ];
    }
}
