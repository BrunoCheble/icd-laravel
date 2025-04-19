<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'title',
        'description',
        'type',
        'contact',
        'link',
        'image_path',
        'is_approved',
        'approved_at',
        'expires_at',
    ];

    /**
     * Relacionamento com o membro que criou o anúncio
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Verifica se o anúncio está aprovado
     */
    public function isApproved(): bool
    {
        return $this->is_approved === true;
    }

    /**
     * Retorna o caminho da imagem do anúncio
     */
    public function getUrlImageAttribute()
    {
        return $this->image_path && file_exists(public_path('images/announcements/' . $this->image_path)) ? asset('images/announcements/' . $this->image_path) : asset('images/announcements/default.jpg');
    }

    public function getExpiresAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $value)->format('Y-m-d') : null;
    }
}
