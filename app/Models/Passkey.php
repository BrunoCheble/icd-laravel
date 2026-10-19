<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A device a user signs in with by fingerprint / Face ID (a WebAuthn credential): its public key and signature count.
 */
class Passkey extends Model
{
    protected $fillable = ['name', 'credential_id', 'public_key', 'sign_count', 'last_used_at'];

    protected $hidden = ['public_key'];

    protected function casts(): array
    {
        return [
            'sign_count' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
