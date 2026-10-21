<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $user->exists ? $user->name : __('New user') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        /* The permissions: a row per module, a column per action */
        .perm-table { width: 100%; border-collapse: collapse; }
        .perm-table th { padding: .5rem .4rem; font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--tx-6b7280); text-align: center; }
        .perm-table th:first-child { text-align: left; }
        .perm-table td { padding: .55rem .4rem; border-top: 1px solid var(--bd-f1f2f4); text-align: center; }
        .perm-table td:first-child { text-align: left; font-weight: 600; color: var(--tx-111827); font-size: .92rem; }
        .perm-table input[type=checkbox] { width: 1.2rem; height: 1.2rem; border-radius: .3rem; border-color: var(--bd-d1d5db); color: #f8a925; cursor: pointer; }
        .perm-table .none { color: var(--tx-d1d5db); }
        .perm-all { border: 0; background: none; padding: 0; font-size: .78rem; font-weight: 600; color: var(--brand-text); cursor: pointer; }
        @media (max-width: 520px) { .perm-table th { font-size: .62rem; letter-spacing: 0; } .perm-table .perm-all { display: none; } }
        .pw-status { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; }
        .pw-status p { margin: 0; color: var(--tx-4b5563); font-size: .92rem; }
    </style>

    @php
        $field = fn (string $name) => old($name, $user->{$name});
        $modules = \App\Support\Permissions::MODULES;
        $actions = \App\Support\Permissions::ACTIONS;
        $isSelf = $user->exists && $user->is(auth()->user());
    @endphp

    <div class="m-page">
        <a href="{{ route('users.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Users') }}</a>
        @include('layouts.alert')
        <div class="m-head">
            <div>
                <h1>{{ $user->exists ? $user->name : __('New user') }}</h1>
                <p>{{ $user->exists ? __('Who they are, how they sign in and what they may do.') : __('Saving it makes a temporary password, to send by WhatsApp.') }}</p>
            </div>
        </div>

        <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
            @csrf
            @if ($user->exists) @method('PUT') @endif

            <div class="m-form" x-data="userForm(@js([
                    'admin' => (bool) old('is_admin', $user->is_admin),
                    'permissions' => (object) old('permissions', $user->permissions ?? []),
                    'modules' => collect($modules)->map(fn ($module) => $module[2])->all(),
                ]))" @person-picked="fill($event.detail)">
                @if ($errors->any())
                    <div class="m-card m-section" style="border-color: var(--bd-fecaca); background: var(--bg-fef2f2);">
                        <p class="m-error"><i class="fa-solid fa-triangle-exclamation"></i> {{ __('Check the fields marked below.') }}</p>
                    </div>
                @endif

                {{-- Who --}}
                <section class="m-card m-section">
                    <h3 class="m-section-title"><i class="fa-solid fa-user"></i> {{ __('Person') }}</h3>
                    <div class="m-grid cols-2">
                        <div class="m-field m-span-2">
                            <span class="m-label">{{ __('Member') }} <span class="m-optional">({{ __('optional') }})</span></span>
                            @include('members::members.partials.person-picker', ['member' => $user, 'name' => 'member_id', 'want' => null])
                            <p class="m-help">{{ __('Choosing the member fills in the name, the mobile number and the email.') }}</p>
                            @error('member_id')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="m-field m-span-2">
                            <label class="m-label" for="name">{{ __('Name') }}</label>
                            <input id="name" name="name" type="text" class="m-input" x-ref="name" value="{{ $field('name') }}" required maxlength="255">
                            @error('name')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="m-field">
                            <label class="m-label" for="phone_number">{{ __('Mobile number') }}</label>
                            <input id="phone_number" name="phone_number" type="tel" class="m-input" x-ref="phone" value="{{ $field('phone_number') }}" inputmode="tel" placeholder="912 345 678">
                            @error('phone_number')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="m-field">
                            <label class="m-label" for="email">{{ __('Email') }}</label>
                            <input id="email" name="email" type="email" class="m-input" x-ref="email" value="{{ $field('email') }}" placeholder="nome@exemplo.com">
                            @error('email')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <p class="m-help m-span-2" style="margin-top: -.4rem;">{{ __('They sign in with the mobile number or the email (one of them is enough); the temporary password goes to the mobile number by WhatsApp.') }}</p>
                    </div>
                </section>

                {{-- What they may do --}}
                <section class="m-card m-section">
                    <h3 class="m-section-title"><i class="fa-solid fa-shield-halved"></i> {{ __('Access') }}</h3>
                    <div class="m-field">
                        <span class="m-label">{{ __('Administrator') }}</span>
                        <div class="m-seg" role="radiogroup" aria-label="{{ __('Administrator') }}">
                            <label><input type="radio" name="is_admin" value="1" x-model="adminValue" @disabled($isSelf)><span><i class="fa-solid fa-shield-halved"></i> {{ __('Yes') }}</span></label>
                            <label><input type="radio" name="is_admin" value="0" x-model="adminValue" @disabled($isSelf)><span>{{ __('No, only what is chosen') }}</span></label>
                        </div>
                        @if ($isSelf)<input type="hidden" name="is_admin" value="1">@endif
                        <p class="m-help" x-show="admin">{{ __('Administrators may do everything, and manage the users.') }}</p>
                    </div>

                    <div x-show="!admin" x-cloak style="overflow-x: auto;">
                        <table class="perm-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Module') }}</th>
                                    @foreach ($actions as $action => $label)<th>{{ __($label) }}</th>@endforeach
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($modules as $module => [$label, , $allowed])
                                    <tr>
                                        <td>{{ __($label) }}</td>
                                        @foreach ($actions as $action => $actionLabel)
                                            <td>
                                                @if (in_array($action, $allowed, true))
                                                    <input type="checkbox" name="permissions[{{ $module }}][]" value="{{ $action }}" :checked="has('{{ $module }}', '{{ $action }}')" @change="toggle('{{ $module }}', '{{ $action }}', $event.target.checked)" aria-label="{{ __($label) }}: {{ __($actionLabel) }}">
                                                @else
                                                    <span class="none">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td><button type="button" class="perm-all" @click="all('{{ $module }}')" x-text="full('{{ $module }}') ? @js(__('Nothing')) : @js(__('Everything'))"></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="m-help">{{ __('Creating, editing or deleting also needs seeing. Without seeing a module, it does not show in the menu.') }}</p>
                    </div>
                </section>

                <div class="m-form-actions">
                    <a href="{{ route('users.index') }}" class="m-btn">{{ __('Cancel') }}</a>
                    <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ $user->exists ? __('Save') : __('Save and make password') }}</button>
                </div>
            </div>
        </form>

        @if ($user->exists)
            {{-- The password: a new temporary one, to send by WhatsApp --}}
            <section class="m-card m-section mt-4">
                <h3 class="m-section-title"><i class="fa-solid fa-key"></i> {{ __('Password') }}</h3>
                <div class="pw-status">
                    <p>
                        @if ($user->must_change_password)
                            {{ $user->temporaryPasswordExpired() ? __('The temporary password expired without being used.') : __('Temporary password valid until :date; not changed yet.', ['date' => $user->temporary_password_expires_at?->timezone('Europe/Lisbon')->format('d/m/Y H:i')]) }}
                        @else
                            {{ __('The user chose their own password.') }}
                        @endif
                    </p>
                    <form method="POST" action="{{ route('users.temporary-password', $user) }}" x-data @submit="if (!confirm(@js(__('Make a new temporary password? The current password stops working.')))) $event.preventDefault()">
                        @csrf
                        <button type="submit" class="m-btn"><i class="fa-solid fa-key"></i> {{ __('Make temporary password') }}</button>
                    </form>
                </div>
            </section>

            @unless ($isSelf)
                <form method="POST" action="{{ route('users.destroy', $user) }}" class="mt-4" x-data @submit="if (!confirm(@js(__('Delete this user? They will no longer be able to sign in.')))) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="m-btn is-danger"><i class="fa-solid fa-trash"></i> {{ __('Delete user') }}</button>
                </form>
            @endunless
        @endif
    </div>

    <script>
        // The members to pick (with their mobile number and email, to fill in the fields).
        window.memberPeople = @js($people);

        // Administrator or the permissions by module; creating / editing / deleting brings seeing along, and
        // without seeing the rest goes too.
        function userForm({ admin, permissions, modules }) {
            const chosen = {};
            Object.keys(modules).forEach(module => chosen[module] = [...(permissions[module] || [])]);
            return {
                adminValue: admin ? '1' : '0',
                chosen,
                get admin() { return this.adminValue === '1'; },
                has(module, action) { return this.chosen[module].includes(action); },
                toggle(module, action, on) {
                    let list = this.chosen[module].filter(item => item !== action);
                    if (on) list.push(action);
                    if (on && action !== 'read' && !list.includes('read')) list.push('read');
                    if (!on && action === 'read') list = [];
                    this.chosen[module] = list;
                },
                full(module) { return modules[module].every(action => this.has(module, action)); },
                all(module) { this.chosen[module] = this.full(module) ? [] : [...modules[module]]; },
                // The member chosen fills in what is still empty.
                fill(person) {
                    if (!person) return;
                    if (!this.$refs.name.value.trim()) this.$refs.name.value = person.name || '';
                    if (!this.$refs.phone.value.trim() && person.phone) this.$refs.phone.value = person.phone;
                    if (!this.$refs.email.value.trim() && person.email) this.$refs.email.value = person.email;
                },
            };
        }
    </script>
</x-app-layout>
