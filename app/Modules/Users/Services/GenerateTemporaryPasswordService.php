<?php

namespace App\Modules\Users\Services;

use App\Models\User;

class GenerateTemporaryPasswordService
{
    // How long it is valid.
    public const HOURS = 48;

    // Easy to read and type (no 0 / O, 1 / l / I).
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * A new temporary password for the user (the old one stops working), valid for HOURS and changed on the first
     * sign-in; returns it in clear, to be sent once (only its hash is kept).
     */
    public function execute(User $user): string
    {
        $password = $this->random(4) . '-' . $this->random(4);
        $user->forceFill([
            'password' => $password,
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addHours(self::HOURS),
            'remember_token' => null,
        ])->save();

        return $password;
    }

    private function random(int $length): string
    {
        $text = '';
        for ($i = 0; $i < $length; $i++) {
            $text .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $text;
    }
}
