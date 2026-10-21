<?php

namespace App\Modules\Ministries\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ministries\Http\Requests\MinistryRoleRequest;
use App\Modules\Ministries\Models\Ministry;
use App\Modules\Ministries\Models\MinistryRole;
use App\Modules\Ministries\Services\RemoveMinistryRoleService;
use App\Modules\Ministries\Services\SaveMinistryRoleService;
use Illuminate\Http\RedirectResponse;

class MinistryRoleController extends Controller
{
    public function store(MinistryRoleRequest $request, Ministry $ministry, SaveMinistryRoleService $service): RedirectResponse
    {
        $service->execute($request->validated(), $ministry);

        return redirect()->to(route('ministries.show', $ministry) . '#roles')->with('success', __('Role added.'));
    }

    public function update(MinistryRoleRequest $request, MinistryRole $role, SaveMinistryRoleService $service): RedirectResponse
    {
        $service->execute($request->validated(), role: $role);

        return redirect()->to(route('ministries.show', $role->ministry_id) . '#roles')->with('success', __('Role saved.'));
    }

    public function destroy(MinistryRole $role, RemoveMinistryRoleService $service): RedirectResponse
    {
        $service->execute($role);

        return redirect()->to(route('ministries.show', $role->ministry_id) . '#roles')->with('success', __('Role deleted.'));
    }
}
