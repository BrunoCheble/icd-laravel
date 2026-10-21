<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Support\Permissions;
use RuntimeException;

class SaveUserService
{
    /**
     * Creates or updates a user: who they are, how they sign in and their access (administrators: everything; the
     * others: the permissions by module). The last administrator stays one.
     */
    public function execute(array $data, ?User $user = null): User
    {
        $user ??= new User();
        $isAdmin = (bool) ($data['is_admin'] ?? false);
        if ($user->exists && $user->is_admin && ! $isAdmin && User::query()->where('is_admin', true)->count() <= 1) {
            throw new RuntimeException(__('There must be at least one administrator.'));
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'member_id' => $data['member_id'] ?? null,
            'is_admin' => $isAdmin,
            'permissions' => $isAdmin ? null : Permissions::clean($data['permissions'] ?? []),
        ]);
        // A new user signs in first with a temporary password (see GenerateTemporaryPasswordService).
        if (! $user->exists) {
            $user->password = bin2hex(random_bytes(32));
        }
        $user->save();

        return $user;
    }
}
