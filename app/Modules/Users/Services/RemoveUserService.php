<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use RuntimeException;

class RemoveUserService
{
    /** Deletes a user (its devices and notifications with it); not oneself, nor the last administrator. */
    public function execute(User $user, User $by): void
    {
        if ($user->is($by)) {
            throw new RuntimeException(__('You cannot delete your own user.'));
        }
        if ($user->is_admin && User::query()->where('is_admin', true)->count() <= 1) {
            throw new RuntimeException(__('There must be at least one administrator.'));
        }

        $user->delete();
    }
}
