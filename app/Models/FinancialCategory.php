<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialCategory extends Model
{

    // Campos que podem ser preenchidos em massa
    protected $fillable = [
        'name',
        'expected_total',
        'active',
    ];

    /**
     * Relacionamento: Uma categoria financeira pode ter vários movimentos financeiros
     */
    public function financials()
    {
        return $this->hasMany(FinancialMovement::class);
    }

    /**
     * Escopo para buscar categorias ativas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Retorna a descrição do total esperado, com formatação adequada
     */
    public function getFormattedExpectedTotalAttribute()
    {
        return number_format($this->expected_total, 2, ',', '.'); // Formata para exibir como dinheiro
    }
}
