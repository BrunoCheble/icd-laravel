<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $member->first_and_last_name }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .member-hero { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem 1.25rem; padding: 1.25rem; }
        .member-photo { position: relative; flex: none; display: block; width: 6rem; height: 6rem; padding: 0; border: 0; border-radius: 999px; background: none; cursor: pointer; }
        .member-photo img { width: 100%; height: 100%; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 3px #fff, 0 0 0 4px #e5e7eb; }
        .member-photo span { position: absolute; right: 0; bottom: 0; width: 2rem; height: 2rem; display: grid; place-items: center; border: 2px solid #fff; border-radius: 999px; background: var(--brand); color: #fff; font-size: .8rem; }
        .member-hero-main { flex: 1 1 14rem; min-width: 0; }
        .member-hero-main h1 { margin: 0; font-size: 1.4rem; font-weight: 800; color: #111827; letter-spacing: -.01em; }
        .member-hero-main p { margin: .15rem 0 0; font-size: .9rem; color: #6b7280; }
        .member-hero-tags { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
        .member-hero-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
        @media (max-width: 639px) {
            .member-hero { padding: 1rem; justify-content: center; text-align: center; }
            .member-hero-main { flex-basis: 100%; }
            .member-hero-tags { justify-content: center; }
            .member-hero-actions { flex-basis: 100%; }
            .member-hero-actions .m-btn:not(.is-icon) { flex: 1; }
        }

        .member-cards-grid { display: grid; gap: 1rem; margin-top: 1rem; }
        @media (min-width: 768px) { .member-cards-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .member-cards-grid .is-wide { grid-column: span 2; } }
        .member-facts { display: grid; gap: .1rem; margin: 0; }
        .member-facts > div { display: grid; grid-template-columns: 9rem minmax(0, 1fr); gap: .75rem; padding: .5rem 0; border-bottom: 1px solid #f3f4f6; }
        .member-facts > div:last-child { border-bottom: 0; }
        .member-facts dt { font-size: .82rem; font-weight: 600; color: #6b7280; }
        .member-facts dd { margin: 0; font-size: .9rem; color: #111827; overflow-wrap: anywhere; }
        .member-facts dd a { color: var(--brand-text); text-decoration: none; }
        .member-facts dd a:hover { text-decoration: underline; }
        @media (max-width: 639px) { .member-facts > div { grid-template-columns: 7.5rem minmax(0, 1fr); } }
        .member-person { display: flex; align-items: center; gap: .65rem; padding: .5rem .25rem; border-radius: .6rem; color: #111827; text-decoration: none; }
        .member-person:hover { background: #f9fafb; }
        .member-person strong { display: block; font-size: .9rem; font-weight: 700; }
        .member-person small { display: block; font-size: .78rem; color: #6b7280; }
        .member-notes { margin: 0; font-size: .9rem; color: #374151; white-space: pre-line; }
    </style>

    @php
        $statuses = \App\Enums\MembershipStatus::options();
        $tagOf = ['actived' => 'is-ok', 'pending' => 'is-part', 'inactived' => 'is-danger'];
        $day = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : null;
        $years = $member->years();
        $family = collect([
            __('Spouse') => $member->spouse,
            __('Father') => $member->father,
            __('Mother') => $member->mother,
        ])->filter();
    @endphp

    <div class="m-page">
        @include('layouts.alert')
        <a href="{{ route('members.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Members') }}</a>

        <div class="m-card member-hero">
            {{-- Click the photo to change it --}}
            <form method="POST" action="{{ route('members.uploadPhoto', $member->id) }}" enctype="multipart/form-data" x-data>
                @csrf
                @method('PATCH')
                <input type="file" name="photo" accept="image/*" class="hidden" x-ref="photo" @change="$el.form.submit()">
                <button type="button" class="member-photo" @click="$refs.photo.click()" title="{{ __('Change photo') }}">
                    <img src="{{ $member->url_photo }}" alt="{{ $member->first_and_last_name }}">
                    <span><i class="fa-solid fa-camera"></i></span>
                </button>
            </form>
            <div class="member-hero-main">
                <h1>{{ $member->full_name }}</h1>
                <p>{{ collect([$member->phone_number, $member->email])->filter()->implode(' · ') ?: __('No contact') }}</p>
                <div class="member-hero-tags">
                    <span class="m-tag {{ $tagOf[$member->membership_status] ?? 'is-off' }}">{{ $statuses[$member->membership_status] ?? $member->membership_status }}</span>
                    @if ($years !== null)<span class="m-tag is-media">{{ trans_choice(':count year|:count years', $years, ['count' => $years]) }}</span>@endif
                    @if ($pending)<span class="m-tag is-part"><i class="fa-solid fa-circle-exclamation"></i> {{ trans_choice(':count pending item|:count pending items', count($pending), ['count' => count($pending)]) }}</span>@endif
                </div>
                @error('photo')<p class="m-error mt-2">{{ $message }}</p>@enderror
            </div>
            <div class="member-hero-actions">
                <a href="{{ route('members.edit', $member) }}" class="m-btn is-primary"><i class="fa-solid fa-pen"></i> {{ __('Edit') }}</a>
                @include('members::members.partials.actions-button')
            </div>
        </div>

        <div class="member-cards-grid">
            @if ($pending)
                <section class="m-card m-section is-wide" style="border-color: #fde68a;">
                    <h3 class="m-section-title"><i class="fa-solid fa-circle-exclamation"></i> {{ __('Pending Informations') }}</h3>
                    <ul class="member-pending">
                        @foreach ($pending as $item)
                            <li><i class="fa-solid fa-circle-exclamation"></i> <span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="m-card m-section">
                <h3 class="m-section-title"><i class="fa-solid fa-user"></i> {{ __('Person') }}</h3>
                <dl class="member-facts">
                    <div><dt>{{ __('Document Number') }}</dt><dd>{{ $member->document_number ?: '—' }}</dd></div>
                    <div><dt>{{ __('Date of Birth') }}</dt><dd>{{ $day($member->date_of_birth) ?? '—' }}</dd></div>
                    <div><dt>{{ __('Gender') }}</dt><dd>{{ $member->gender_name }}</dd></div>
                    <div><dt>{{ __('Marital Status') }}</dt><dd>{{ $member->marital_status_name }}</dd></div>
                </dl>
            </section>

            <section class="m-card m-section">
                <h3 class="m-section-title"><i class="fa-solid fa-address-book"></i> {{ __('Contact') }}</h3>
                <dl class="member-facts">
                    <div><dt>{{ __('Phone Number') }}</dt><dd>@if ($member->phone_number)<a href="tel:{{ $member->phone_number }}">{{ $member->phone_number }}</a>@else — @endif</dd></div>
                    <div><dt>{{ __('Email') }}</dt><dd>@if ($member->email)<a href="mailto:{{ $member->email }}">{{ $member->email }}</a>@else — @endif</dd></div>
                    <div><dt>{{ __('Address') }}</dt><dd>{{ $member->address ?: '—' }}</dd></div>
                    <div><dt>{{ __('City') }}</dt><dd>{{ collect([$member->zip_code, $member->city])->filter()->implode(' ') ?: '—' }}</dd></div>
                </dl>
            </section>

            <section class="m-card m-section">
                <h3 class="m-section-title"><i class="fa-solid fa-people-roof"></i> {{ __('Family') }}</h3>
                @foreach ($family as $relation => $person)
                    <a href="{{ route('members.show', $person) }}" class="member-person">
                        @include('members::members.partials.avatar', ['member' => $person, 'class' => 'w-9 h-9'])
                        <span><strong>{{ $person->first_and_last_name }}</strong><small>{{ $relation }}@if ($relation === __('Spouse') && $member->date_joined) · {{ __('Wedding Date') }} {{ $day($member->date_joined) }}@endif</small></span>
                    </a>
                @endforeach
                @foreach ($children as $child)
                    <a href="{{ route('members.show', $child) }}" class="member-person">
                        @include('members::members.partials.avatar', ['member' => $child, 'class' => 'w-9 h-9'])
                        <span><strong>{{ $child->first_and_last_name }}</strong><small>{{ $child->gender === 'M' ? __('Son') : __('Daughter') }}@if ($child->years() !== null) · {{ trans_choice(':count year|:count years', $child->years(), ['count' => $child->years()]) }}@endif</small></span>
                    </a>
                @endforeach
                @if (! $member->spouse && $member->spouse_name)
                    {{-- A spouse who is not a member: only the name --}}
                    <div class="member-person">
                        @php($words = preg_split('/\s+/', trim($member->spouse_name)))
                        <span class="m-initials">{{ mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : '')) }}</span>
                        <span><strong>{{ $member->spouse_name }}</strong><small>{{ __('Spouse') }} · {{ __('not a member') }}@if ($member->date_joined) · {{ __('Wedding Date') }} {{ $day($member->date_joined) }}@endif</small></span>
                    </div>
                @endif
                @if ($family->isEmpty() && $children->isEmpty() && ! $member->spouse_name)
                    <p class="m-muted">{{ __('No family members in the church.') }}</p>
                @endif
            </section>

            <section class="m-card m-section">
                <h3 class="m-section-title"><i class="fa-solid fa-church"></i> {{ __('Church') }}</h3>
                <dl class="member-facts">
                    <div><dt>{{ __('Status') }}</dt><dd>{{ $statuses[$member->membership_status] ?? $member->membership_status }}</dd></div>
                    <div><dt>{{ __('Baptism Date') }}</dt><dd>{{ $day($member->baptism_date) ?? '—' }}</dd></div>
                    <div><dt>{{ __('Created At') }}</dt><dd>{{ $member->created_at_formatted }}</dd></div>
                    <div><dt>{{ __('Updated At') }}</dt><dd>{{ $member->updated_at_formatted }}</dd></div>
                </dl>
            </section>

            @if ($member->notes)
                <section class="m-card m-section is-wide">
                    <h3 class="m-section-title"><i class="fa-solid fa-note-sticky"></i> {{ __('Notes') }}</h3>
                    <p class="member-notes">{{ $member->notes }}</p>
                </section>
            @endif
        </div>

        @include('members::members.partials.actions')
    </div>
</x-app-layout>
