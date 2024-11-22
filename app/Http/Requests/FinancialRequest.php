<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinancialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
			'member_id' => 'string',
			'description' => 'required|string',
			'amount' => 'required',
			'date' => 'required',
			'type' => 'required|string',
			'category' => 'required|string',
			'notes' => 'string',
        ];
    }
}
