<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Users') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        /* The users: one row each (who, how they sign in, their access) */
        .users-list { margin-top: .75rem; overflow: hidden; }
        .users-list .m-row { display: flex; }
        .users-list .m-row-main:hover strong { color: var(--brand-text); }
        .users-list .m-row-text small { white-space: normal; }
    </style>

    @php
        $initials = function (string $name) {
            $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
            return mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
        };
        $modules = \App\Support\Permissions::MODULES;
    @endphp

    <div class="m-page">
        @include('layouts.alert')

        <div class="m-head">
            <div>
                <h1>{{ __('Users') }}</h1>
                <p>{{ __('Who signs in to the admin, and what each one may do.') }}</p>
            </div>
            <div class="m-head-actions">
                <a href="{{ route('users.create') }}" class="m-btn is-primary"><i class="fa-solid fa-plus"></i> {{ __('New user') }}</a>
            </div>
        </div>

        <div class="m-card users-list">
            @foreach ($users as $user)
                <div class="m-row">
                    <a href="{{ route('users.edit', $user) }}" class="m-row-main">
                        <span class="m-initials">{{ $initials($user->name) }}</span>
                        <span class="m-row-text">
                            <strong>{{ $user->name }}@if ($user->is(auth()->user())) <span class="m-muted">({{ __('you') }})</span>@endif</strong>
                            <small>{{ collect([$user->email, $user->phone_number])->filter()->implode(' · ') }}</small>
                            <span class="m-tags">
                                @if ($user->is_admin)
                                    <span class="m-tag is-ok"><i class="fa-solid fa-shield-halved"></i> {{ __('Administrator') }}</span>
                                @elseif ($user->permissions)
                                    @foreach ($user->permissions as $module => $actions)
                                        @isset($modules[$module])<span class="m-tag is-media">{{ __($modules[$module][0]) }}</span>@endisset
                                    @endforeach
                                @else
                                    <span class="m-tag is-off">{{ __('No access') }}</span>
                                @endif
                                @if ($user->must_change_password)
                                    @if ($user->temporaryPasswordExpired())
                                        <span class="m-tag is-danger"><i class="fa-solid fa-clock"></i> {{ __('Temporary password expired') }}</span>
                                    @else
                                        <span class="m-tag is-part"><i class="fa-solid fa-key"></i> {{ __('Temporary password until :date', ['date' => $user->temporary_password_expires_at?->timezone('Europe/Lisbon')->format('d/m H:i')]) }}</span>
                                    @endif
                                @endif
                            </span>
                        </span>
                    </a>
                    <i class="fa-solid fa-chevron-right m-muted"></i>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
