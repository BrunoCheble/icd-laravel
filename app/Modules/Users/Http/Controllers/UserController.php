<?php

namespace App\Modules\Users\Http\Controllers;

use App\Helpers\WhatsAppHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Users\Http\Requests\SaveUserRequest;
use App\Modules\Users\Services\GenerateTemporaryPasswordService;
use App\Modules\Users\Services\GetUserFormOptionsService;
use App\Modules\Users\Services\ListUsersService;
use App\Modules\Users\Services\RemoveUserService;
use App\Modules\Users\Services\SaveUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class UserController extends Controller
{
    public function index(ListUsersService $service): View
    {
        return view('users::users.index', ['users' => $service->execute()]);
    }

    public function create(GetUserFormOptionsService $options): View
    {
        return view('users::users.form', ['user' => new User(), 'people' => $options->execute()]);
    }

    /** A new user gets a temporary password right away, to send by WhatsApp. */
    public function store(SaveUserRequest $request, SaveUserService $service, GenerateTemporaryPasswordService $password): RedirectResponse
    {
        $user = $service->execute($request->validated());

        return $this->showPassword($user, $password->execute($user));
    }

    public function edit(User $user, GetUserFormOptionsService $options): View
    {
        return view('users::users.form', ['user' => $user, 'people' => $options->execute($user)]);
    }

    public function update(SaveUserRequest $request, User $user, SaveUserService $service): RedirectResponse
    {
        try {
            $service->execute($request->validated(), $user);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('users.index')->with('success', __('User saved.'));
    }

    public function destroy(Request $request, User $user, RemoveUserService $service): RedirectResponse
    {
        try {
            $service->execute($user, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('users.index')->with('success', __('User deleted.'));
    }

    public function temporaryPassword(User $user, GenerateTemporaryPasswordService $service): RedirectResponse
    {
        return $this->showPassword($user, $service->execute($user));
    }

    /** The temporary password just made, shown once (it is not kept in clear), with the WhatsApp message. */
    public function showTemporaryPassword(Request $request, User $user): View|RedirectResponse
    {
        $password = $request->session()->get('temporary_password');
        if (! $password) {
            return redirect()->route('users.edit', $user);
        }

        $login = $user->phone_number ?: $user->email;
        $message = implode("\n", [
            'A paz, ' . explode(' ', trim($user->name))[0] . '! 🙏',
            '',
            'Foi criado o seu acesso ao painel da Igreja Cidade de David.',
            '',
            'Endereço: ' . route('login'),
            'Utilizador: ' . $login,
            'Palavra-passe temporária: ' . $password,
            '',
            'É válida durante ' . GenerateTemporaryPasswordService::HOURS . ' horas. Ao entrar, vai escolher a sua própria palavra-passe.',
        ]);
        $phone = $user->phone_number ?: $user->member?->phone_number;

        return view('users::users.temporary-password', [
            'user' => $user,
            'password' => $password,
            'login' => $login,
            'message' => $message,
            'whatsapp' => WhatsAppHelper::url($phone, $message),
            'expires' => $user->temporary_password_expires_at,
        ]);
    }

    private function showPassword(User $user, string $password): RedirectResponse
    {
        // Flashed to the next page only: a refresh of it does not show it again.
        return redirect()->route('users.temporary-password.show', $user)->with('temporary_password', $password);
    }
}
