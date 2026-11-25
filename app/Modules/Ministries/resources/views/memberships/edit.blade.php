<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $membership->member->full_name }}
        </h2>
    </x-slot>

    @include('partials.ui')

    @php
        $ministry = $membership->ministry;
        $bio = old('bio') ? json_decode(old('bio'), true) : $membership->bio;
    @endphp

    <div class="m-page">
        <a href="{{ route('ministries.show', $ministry) }}#members" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ $ministry->name }}</a>
        <div class="m-head">
            <div>
                <h1>{{ $membership->member->full_name }}</h1>
                <p>{{ $ministry->name }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('ministries-members.update', $membership) }}">
            @csrf
            @method('PUT')

            <div class="m-form">
                <section class="m-card m-section">
                    <h3 class="m-section-title"><i class="fa-solid fa-id-badge"></i> {{ __('In the ministry') }}</h3>
                    <div class="m-grid cols-2">
                        <div class="m-field">
                            <label class="m-label" for="role">{{ __('Ministry role') }} <span class="m-optional">({{ __('optional') }})</span></label>
                            <input id="role" name="role" type="text" class="m-input" value="{{ old('role', $membership->role) }}" maxlength="80" placeholder="{{ __('E.g. Pastor Presidente, Vocal') }}">
                            @error('role')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="m-field">
                            <label class="m-label" for="sort_order">{{ __('Order') }}</label>
                            <input id="sort_order" name="sort_order" type="number" min="0" max="999" class="m-input" value="{{ old('sort_order', $membership->sort_order) }}">
                            <p class="m-help">{{ $ministry->isPastoral() ? __('Smaller first, also on the site.') : __('Smaller first.') }}</p>
                            @error('sort_order')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="m-field">
                            <span class="m-label">{{ __('State') }}</span>
                            <div class="m-seg" role="radiogroup" aria-label="{{ __('State') }}">
                                <label><input type="radio" name="active" value="1" @checked(old('active', $membership->active))><span>{{ __('Is active') }}</span></label>
                                <label><input type="radio" name="active" value="0" @checked(! old('active', $membership->active))><span>{{ __('Not active') }}</span></label>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- The biography: sections of texts, lists, tables and links, organised as each person wants (see MinistryBio) --}}
                <section class="m-card m-section">
                    <h3 class="m-section-title"><i class="fa-regular fa-file-lines"></i> {{ __('Biography') }} <span class="m-optional">({{ __('optional') }})</span></h3>
                    <p class="m-help" style="margin-top: -.4rem;">{{ $ministry->isPastoral() ? __('Shows on the site, in the "Biography" button of the pastor. Without sections, there is no button.') : __('Only shows on the site for the ministry marked as "Pastores".') }}</p>
                    @include('ministries::partials.text-editor', ['name' => 'bio', 'value' => $bio])
                    @error('bio')<p class="m-error">{{ $message }}</p>@enderror
                </section>

                <div class="m-form-actions">
                    <a href="{{ route('ministries.show', $ministry) }}#members" class="m-btn">{{ __('Cancel') }}</a>
                    <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
                </div>
            </div>
        </form>

        @can('members.update')
            <form method="POST" action="{{ route('ministries-members.destroy', $membership) }}" class="mt-4" data-confirm="{{ __('Remove this member from the ministry?') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="m-btn is-danger"><i class="fa-solid fa-user-minus"></i> {{ __('Remove from the ministry') }}</button>
            </form>
        @endcan
    </div>

</x-app-layout>
