<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $categoryId = $this->route('financial-categories') ? $this->route('financial-categories')->id : null;
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('financial_categories')->ignore($categoryId),
            ],
            'expected_total' => 'nullable|numeric|min:0',
            'active' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The category name is required.',
            'name.unique' => 'A category with this name already exists.',
            'expected_total.numeric' => 'The expected total value must be numeric.',
            'expected_total.min' => 'The expected total value cannot be negative.',
            'active.boolean' => 'The active field must be true or false.',
        ];
    }
}
