<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListUsersService
{
    /** The users, administrators first, by name. */
    public function execute(): Collection
    {
        return User::query()->with('member:id,first_name,last_name')->orderByDesc('is_admin')->orderBy('name')->get();
    }
}
