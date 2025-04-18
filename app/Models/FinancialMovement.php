<?php

namespace App\Models;

use App\Enums\FinancialMovementType;
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

    public function getTypeNameAttribute() {
        return FinancialMovementType::options()[$this->type];
    }

    public function isProcessedAttribute() {
        return $this->processed_date != null;
    }

    public function isDebitAttribute() {
        return $this->type === FinancialMovementType::EXPENSE || $this->type === FinancialMovementType::DISCOUNT;
    }

    public function getAmountFormattedAttribute() {
        return '€ '.number_format($this->amount, 2, ',', '.');
    }

    public function getDateFormattedAttribute() {
        return date('d/m/Y', strtotime($this->date));
    }

    public function getProcessedDateFormattedAttribute() {
        return $this->processed_date != null ? date('d/m/Y', strtotime($this->processed_date)) : null;
    }
}
