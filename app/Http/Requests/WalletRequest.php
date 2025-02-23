<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wallets')->ignore($this->route('wallet')),
            ],
            'active' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The wallet name is required.',
            'name.unique' => 'A wallet with this name already exists.',
            'active.boolean' => 'The active field must be true or false.',
        ];
    }
}
