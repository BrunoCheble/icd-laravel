<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $ministry->name }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .mn-grid { display: grid; gap: 1rem; margin-top: .75rem; max-width: 56rem; }
        /* No overflow clipping: the member picker's list opens below the card */
        .mn-card > h3 { display: flex; align-items: center; gap: .5rem; margin: 0; padding: .85rem 1rem; border-bottom: 1px solid var(--bd-f1f2f4); font-size: .95rem; font-weight: 800; color: var(--tx-111827); }
        .mn-card > h3 small { margin-left: auto; font-size: .8rem; font-weight: 600; color: var(--tx-6b7280); }
        .mn-row { display: flex; align-items: center; gap: .75rem; padding: .65rem 1rem; border-bottom: 1px solid var(--bd-f1f2f4); }
        .mn-row:last-child { border-bottom: 0; }
        .mn-row.is-off { opacity: .55; }
        .mn-order { flex: none; width: 1.6rem; text-align: center; font-size: .8rem; font-weight: 700; color: var(--tx-9ca3af); }
        .mn-photo { flex: none; width: 2.6rem; height: 2.6rem; border-radius: 50%; object-fit: cover; background: var(--bg-f3f4f6); }
        .mn-text { flex: 1; min-width: 0; }
        .mn-text strong { display: block; font-weight: 700; color: var(--tx-111827); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .mn-text small { display: block; font-size: .82rem; color: var(--tx-6b7280); }
        .mn-add { display: grid; gap: .6rem; padding: 1rem; border-top: 1px solid var(--bd-f1f2f4); border-radius: 0 0 .9rem .9rem; background: var(--bg-fafafa); }
        @media (min-width: 700px) { .mn-add { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) auto; align-items: start; } }
    </style>

    <div class="m-page">
        <a href="{{ route('ministries.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Ministries') }}</a>
        @include('layouts.alert')
        <div class="m-head">
            <div>
                <h1>{{ $ministry->name }}</h1>
                <p>
                    {{ trans_choice(':count member|:count members', $ministry->memberships->where('active', true)->count(), ['count' => $ministry->memberships->where('active', true)->count()]) }}
                    @if ($ministry->isPastoral()) · <span class="m-tag is-ok"><i class="fa-solid fa-globe"></i> {{ __('On the site: Pastors') }}</span>@endif
                </p>
            </div>
            @can('members.update')
                <div class="m-head-actions">
                    <a href="{{ route('ministries.edit', $ministry) }}" class="m-btn"><i class="fa-solid fa-pen"></i> <span class="m-hide-sm">{{ __('Edit') }}</span></a>
                </div>
            @endcan
        </div>

        <div class="mn-grid">
            {{-- The members, in their order --}}
            <section class="m-card mn-card" id="members">
                <h3><i class="fa-solid fa-users m-muted"></i> {{ __('Members') }} <small>{{ $ministry->isPastoral() ? __('In the site\'s order') : '' }}</small></h3>
                @forelse ($ministry->memberships as $membership)
                    @continue(! $membership->member)
                    <div @class(['mn-row', 'is-off' => ! $membership->active])>
                        <span class="mn-order">{{ $membership->sort_order }}</span>
                        <img class="mn-photo" src="{{ $membership->member->url_photo }}" alt="" loading="lazy">
                        <span class="mn-text">
                            <strong><a href="{{ route('members.show', $membership->member) }}" style="color: inherit; text-decoration: none;">{{ $membership->member->full_name }}</a></strong>
                            <small>
                                {{ $membership->role ?: __('No role') }}
                                @unless ($membership->active) · {{ __('Not active') }}@endunless
                                @if ($membership->bio) · <i class="fa-regular fa-file-lines"></i> {{ __('Biography') }}@endif
                            </small>
                        </span>
                        @can('members.update')
                            <a href="{{ route('ministries-members.edit', $membership) }}" class="m-btn is-ghost is-icon" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}"><i class="fa-solid fa-pen"></i></a>
                        @endcan
                    </div>
                @empty
                    <p class="m-muted" style="padding: 1rem; margin: 0;">{{ __('No members in this ministry yet.') }}</p>
                @endforelse

                @can('members.update')
                    <form method="POST" action="{{ route('ministries-members.store', $ministry) }}" class="mn-add">
                        @csrf
                        <div>
                            @include('members::members.partials.person-picker', ['member' => new \App\Modules\Ministries\Models\MinistryMember(), 'name' => 'member_id', 'want' => null])
                            @error('member_id')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <input name="role" type="text" class="m-input" value="{{ old('role') }}" maxlength="80" placeholder="{{ __('Role (optional)') }}" aria-label="{{ __('Ministry role') }}">
                        <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-user-plus"></i> {{ __('Add') }}</button>
                    </form>
                @endcan
            </section>
        </div>
    </div>

    <script>
        window.memberPeople = @js($people);
    </script>
</x-app-layout>
