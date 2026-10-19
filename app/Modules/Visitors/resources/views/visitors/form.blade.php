{{-- The visitor form (create and edit), in sections. $visitor --}}
@php
    $field = fn (string $name) => old($name, $visitor->{$name});
    $invitedBy = \App\Enums\InvitedBy::options();
    unset($invitedBy[\App\Enums\InvitedBy::WHO]);
@endphp

<div class="m-form">
    @if ($errors->any())
        <div class="m-card m-section" style="border-color: #fecaca; background: #fef2f2;">
            <p class="m-error"><i class="fa-solid fa-triangle-exclamation"></i> {{ __('Check the fields marked below.') }}</p>
        </div>
    @endif

    {{-- Person --}}
    <section class="m-card m-section">
        <h3 class="m-section-title"><i class="fa-solid fa-user"></i> {{ __('Person') }}</h3>
        <div class="m-grid">
            <div class="m-grid cols-2">
                <div class="m-field">
                    <label class="m-label" for="name">{{ __('Name') }}</label>
                    <input id="name" name="name" type="text" class="m-input" value="{{ $field('name') }}" required autocomplete="off">
                    @error('name')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <span class="m-label">{{ __('Gender') }}</span>
                    <div class="m-seg" role="radiogroup" aria-label="{{ __('Gender') }}">
                        @foreach (\App\Enums\Gender::options() as $value => $label)
                            <label><input type="radio" name="gender" value="{{ $value }}" @checked($field('gender') === $value) required><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                    @error('gender')<p class="m-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="m-grid cols-3">
                <div class="m-field">
                    <label class="m-label" for="phone_number">{{ __('Phone Number') }} <span class="m-optional">({{ __('optional') }})</span></label>
                    <input id="phone_number" name="phone_number" type="tel" class="m-input" value="{{ $field('phone_number') }}" autocomplete="off">
                    @error('phone_number')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <label class="m-label" for="city">{{ __('City') }} <span class="m-optional">({{ __('optional') }})</span></label>
                    <input id="city" name="city" type="text" class="m-input" value="{{ $field('city') }}" autocomplete="off">
                    @error('city')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <label class="m-label" for="group">{{ __('Group') }} <span class="m-optional">({{ __('optional') }})</span></label>
                    <select id="group" name="group" class="m-input">
                        <option value="">—</option>
                        @foreach (\App\Enums\VisitorGroup::options() as $value => $label)
                            <option value="{{ $value }}" @selected($field('group') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('group')<p class="m-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </section>

    {{-- The visit --}}
    <section class="m-card m-section">
        <h3 class="m-section-title"><i class="fa-solid fa-church"></i> {{ __('Visit') }}</h3>
        <div class="m-grid">
            <div class="m-grid cols-2">
                <div class="m-field">
                    <label class="m-label" for="invited_by">{{ __('Invited by') }} <span class="m-optional">({{ __('optional') }})</span></label>
                    <input id="invited_by" name="invited_by" type="text" class="m-input" value="{{ $field('invited_by') }}" list="invited_by_options" autocomplete="off">
                    <datalist id="invited_by_options">
                        @foreach ($invitedBy as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </datalist>
                    <p class="m-help">{{ __('A name, or how the visitor heard of the church.') }}</p>
                    @error('invited_by')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <label class="m-label" for="created_by">{{ __('Registered by') }}</label>
                    <input id="created_by" name="created_by" type="text" class="m-input" value="{{ $field('created_by') }}" required autocomplete="off">
                    @error('created_by')<p class="m-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="m-field">
                <label class="m-label" for="observation">{{ __('Observation') }} <span class="m-optional">({{ __('optional') }})</span></label>
                <textarea id="observation" name="observation" rows="3" class="m-input">{{ $field('observation') }}</textarea>
                @error('observation')<p class="m-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <div class="m-form-actions">
        <a href="{{ route('visitors.index') }}" class="m-btn">{{ __('Cancel') }}</a>
        <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
    </div>
</div>
