<?php

namespace App\Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Http\Requests\ChangePasswordRequest;
use App\Modules\Users\Services\ChangePasswordService;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('profile.edit');
        }

        return view('users::password.change', ['user' => $request->user()]);
    }

    public function update(ChangePasswordRequest $request, ChangePasswordService $service): RedirectResponse
    {
        $service->execute($request->user(), $request->validated('password'));

        return redirect()->to(Permissions::homeFor($request->user()))->with('success', __('Password changed. Welcome!'));
    }
}
