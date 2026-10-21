<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A device where a user turned the notifications on (see App\Support\WebPush).
 */
class PushSubscription extends Model
{
    protected $fillable = ['user_id', 'endpoint', 'endpoint_hash', 'public_key', 'auth_token', 'device'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
