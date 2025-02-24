<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinistryMember extends Model
{
    use HasFactory;

    // Definindo o nome da tabela associativa
    protected $table = 'ministry_members';

    // Definir os campos que podem ser atribuídos em massa (mass-assignment)
    protected $fillable = ['ministry_id', 'member_id', 'role', 'active'];

    /**
     * Definir a relação com o modelo Ministry.
     * Um registro em 'ministry_member' pertence a um 'Ministry'.
     */
    public function ministry()
    {
        return $this->belongsTo(Ministry::class);
    }

    /**
     * Definir a relação com o modelo Member.
     * Um registro em 'ministry_member' pertence a um 'Member'.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
