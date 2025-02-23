<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'initial_balance',
        'total_expense',
        'total_income',
        'total_unidentified',
        'calculated_balance',
        'real_balance',
        'start_date',
        'end_date',
    ];

    /**
     * Relacionamento: Um saldo financeiro pertence a uma carteira (Wallet)
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Calcula o saldo calculado com base nas receitas, despesas e saldo inicial
     */
    public function getCalculatedBalanceAttribute()
    {
        return $this->initial_balance + $this->total_income - $this->total_expense;
    }

    /**
     * Atualiza o campo `calculated_balance` com base nas regras de negócio
     */
    public function updateCalculatedBalance()
    {
        $this->calculated_balance = $this->initial_balance + $this->total_income - $this->total_expense;
        $this->save();
    }

    /**
     * Escopo para buscar saldos financeiros dentro de um intervalo de datas
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }
}
