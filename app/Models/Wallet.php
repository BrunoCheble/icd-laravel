<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    // Campos que podem ser preenchidos em massa
    protected $fillable = [
        'name',
        'active',
    ];

    /**
     * Relacionamento: Uma carteira pode ter vários movimentos financeiros
     */
    public function financialMovements()
    {
        return $this->hasMany(FinancialMovement::class);
    }

    /**
     * Relacionamento: Uma carteira pode ter vários saldos financeiros
     */
    public function financialBalances()
    {
        return $this->hasMany(FinancialBalance::class);
    }

    /**
     * Escopo para buscar apenas carteiras ativas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Calcula o saldo atual de uma carteira
     * Esse método pode ser utilizado, por exemplo, para calcular o saldo final
     * baseado nas transações e saldos registrados
     */
    public function getCurrentBalance()
    {
        // O saldo atual pode ser calculado de várias formas, mas uma ideia seria somar
        // os saldos das transações e saldos financeiros.
        $balance = $this->financialBalances()->latest()->first(); // Pega o último saldo registrado

        // Se um saldo for encontrado, retorna o saldo real, senão, retorna o saldo inicial
        return $balance ? $balance->real_balance : 0;
    }
}
