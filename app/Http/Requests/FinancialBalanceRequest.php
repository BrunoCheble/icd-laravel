<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinancialBalanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Permite que todos os usuários possam fazer a requisição
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'wallet_id' => 'required|exists:wallets,id', // O ID da carteira precisa existir
            'initial_balance' => 'required|numeric', // O saldo inicial precisa ser numérico
            'total_expense' => 'nullable|numeric', // A despesa total é opcional, mas deve ser numérica
            'total_income' => 'nullable|numeric', // A receita total é opcional, mas deve ser numérica
            'total_unidentified' => 'nullable|numeric', // Despesas ou receitas não identificadas, opcional
            'start_date' => 'required|date', // A data de início é obrigatória e deve ser uma data válida
            'end_date' => 'required|date|after_or_equal:start_date', // A data de término deve ser válida e posterior ou igual à data de início
        ];
    }

    /**
     * Get the custom attributes for the validation errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'wallet_id' => 'Wallet',
            'initial_balance' => 'Initial Balance',
            'total_expense' => 'Total Expense',
            'total_income' => 'Total Income',
            'total_unidentified' => 'Total Unidentified',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
        ];
    }
}
