<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialMovement extends Model
{
    use HasFactory;
    protected $perPage = 20;

    protected $fillable = [
        'description',
        'date',
        'wallet_id',
        'category_id',
        'amount',
        'type',
        'member_id',
        'ministry_id',
        'processed_date',
        'notes',
    ];

    /**
     * Relacionamento: Um movimento financeiro pertence a uma carteira (Wallet)
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function ministry()
    {
        return $this->belongsTo(Ministry::class);
    }
    /**
     * Relacionamento: Um movimento financeiro pode ter uma categoria (FinancialCategory)
     */
    public function category()
    {
        return $this->belongsTo(FinancialCategory::class);
    }

    /**
     * Escopo para buscar movimentos de um tipo específico
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Escopo para buscar movimentos dentro de um intervalo de datas
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
