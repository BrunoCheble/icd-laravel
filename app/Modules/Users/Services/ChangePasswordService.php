<?php

namespace App\Modules\Users\Services;

use App\Models\User;

class ChangePasswordService
{
    /** The user's own password, in place of the temporary one. */
    public function execute(User $user, string $password): void
    {
        $user->forceFill([
            'password' => $password,
            'must_change_password' => false,
            'temporary_password_expires_at' => null,
        ])->save();
    }
}
