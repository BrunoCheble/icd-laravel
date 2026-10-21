<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Members\Models\Member;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'member_id',
        'is_admin',
        'permissions',
        'password',
        'must_change_password',
        'temporary_password_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // The kinds of notification wanted (see SendPushDigestService::KINDS); null: all.
            'push_kinds' => 'array',
            'is_admin' => 'boolean',
            // What the user may do in each module (see App\Support\Permissions); administrators: everything.
            'permissions' => 'array',
            'must_change_password' => 'boolean',
            'temporary_password_expires_at' => 'datetime',
        ];
    }

    /** The member the user is (optional). */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** A mobile number as kept: digits only, without the Portuguese country code (351 / 00351 / +351). */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $digits = str_starts_with($digits, '00') ? substr($digits, 2) : $digits;
        if (strlen($digits) === 12 && str_starts_with($digits, '351')) {
            $digits = substr($digits, 3);
        }

        return $digits === '' ? null : $digits;
    }

    /** Whether the temporary password ran out (it is changed on the first sign-in, see EnsurePasswordChanged). */
    public function temporaryPasswordExpired(): bool
    {
        return $this->must_change_password && $this->temporary_password_expires_at?->isPast();
    }

    /**
     * Devices the user signs in with by fingerprint / Face ID.
     */
    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class);
    }
}
