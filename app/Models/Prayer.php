<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prayer extends Model
{
    protected $perPage = 20;

    protected $fillable = [
        'name',
        'request',
    ];
}
