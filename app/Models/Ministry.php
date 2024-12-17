<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ministry extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Definir a relação many-to-many com o model Member
    public function members()
    {
        return $this->belongsToMany(Member::class, 'ministry_member');
    }
}
