{{-- The member form (create and edit), in sections. $member, $genders, $maritalStatus, $membershipStatus, $people
     (the other adult members: id, name, document, gender), $pendingFields (edit: the details still missing, marked
     "Pending") --}}
@php
    $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '';
    $field = fn (string $name, $default = null) => old($name, $default ?? $member->{$name});
    $pendingFields ??= [];
    // The "Pending" tag of a missing detail.
    $pendingTag = fn (string $name) => in_array($name, $pendingFields, true)
        ? new \Illuminate\Support\HtmlString(' <span class="m-tag is-part"><i class="fa-solid fa-circle-exclamation"></i> ' . e(__('Pending detail')) . '</span>')
        : '';
@endphp

<div class="m-form" x-data="memberForm(@js(['gender' => $field('gender'), 'marital' => $field('marital_status'), 'spouse' => (bool) ($field('spouse_id') || $field('spouse_name'))]))">
    @if ($pendingFields)
        <div class="m-card m-section" style="border-color: #fde68a; background: #fffbeb;">
            <p class="m-help" style="color: #92400e;"><i class="fa-solid fa-circle-exclamation"></i> {{ trans_choice(':count detail is missing; it is marked "Pending" below.|:count details are missing; they are marked "Pending" below.', count($pendingFields), ['count' => count($pendingFields)]) }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="m-card m-section" style="border-color: #fecaca; background: #fef2f2;">
            <p class="m-error"><i class="fa-solid fa-triangle-exclamation"></i> {{ __('Check the fields marked below.') }}</p>
        </div>
    @endif

    {{-- Person --}}
    <section class="m-card m-section">
        <h3 class="m-section-title"><i class="fa-solid fa-user"></i> {{ __('Person') }}</h3>
        <div class="m-grid">
            <div class="m-grid cols-3">
                @foreach (['first_name' => [__('First Name'), true], 'middle_name' => [__('Middle Name'), false], 'last_name' => [__('Last Name'), true]] as $name => [$label, $required])
                    <div class="m-field">
                        <label class="m-label" for="{{ $name }}">{{ $label }}@unless ($required) <span class="m-optional">({{ __('optional') }})</span>@endunless</label>
                        <input id="{{ $name }}" name="{{ $name }}" type="text" class="m-input" value="{{ $field($name) }}" @required($required) autocomplete="off">
                        @error($name)<p class="m-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            <div class="m-grid cols-3">
                <div class="m-field">
                    <label class="m-label" for="document_number">{{ __('Document Number') }} (NIF){{ $pendingTag('document_number') }}</label>
                    <input id="document_number" name="document_number" type="text" inputmode="numeric" class="m-input" value="{{ $field('document_number') }}" required autocomplete="off">
                    @error('document_number')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <label class="m-label" for="date_of_birth">{{ __('Date of Birth') }}{{ $pendingTag('date_of_birth') }}</label>
                    <input id="date_of_birth" name="date_of_birth" type="date" class="m-input" value="{{ old('date_of_birth', $date($member->date_of_birth)) }}">
                    @error('date_of_birth')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <span class="m-label">{{ __('Gender') }}</span>
                    <div class="m-seg" role="radiogroup" aria-label="{{ __('Gender') }}">
                        @foreach ($genders as $value => $label)
                            <label><input type="radio" name="gender" value="{{ $value }}" x-model="gender" required><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                    @error('gender')<p class="m-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </section>

    {{-- Contact and address --}}
    <section class="m-card m-section">
        <h3 class="m-section-title"><i class="fa-solid fa-address-book"></i> {{ __('Contact') }}</h3>
        <div class="m-grid">
            <div class="m-grid cols-2">
                <div class="m-field">
                    <label class="m-label" for="phone_number">{{ __('Phone Number') }}{{ $pendingTag('phone_number') }}</label>
                    <input id="phone_number" name="phone_number" type="tel" class="m-input" value="{{ $field('phone_number') }}" autocomplete="off">
                    @error('phone_number')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <label class="m-label" for="email">{{ __('Email') }}{{ $pendingTag('email') }}</label>
                    <input id="email" name="email" type="email" class="m-input" value="{{ $field('email') }}" autocomplete="off">
                    @error('email')<p class="m-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="m-field">
                <label class="m-label" for="address">{{ __('Address') }}{{ $pendingTag('address') }}</label>
                <input id="address" name="address" type="text" class="m-input" value="{{ $field('address') }}" autocomplete="off">
                @error('address')<p class="m-error">{{ $message }}</p>@enderror
            </div>
            <div class="m-grid cols-2">
                <div class="m-field">
                    <label class="m-label" for="zip_code">{{ __('Zip Code') }}</label>
                    <input id="zip_code" name="zip_code" type="text" class="m-input" value="{{ $field('zip_code') }}" autocomplete="off">
                    @error('zip_code')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <label class="m-label" for="city">{{ __('City') }}</label>
                    <input id="city" name="city" type="text" class="m-input" value="{{ $field('city') }}" autocomplete="off">
                    @error('city')<p class="m-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </section>

    {{-- Family: the spouse, father and mother are members too --}}
    <section class="m-card m-section">
        <h3 class="m-section-title"><i class="fa-solid fa-people-roof"></i> {{ __('Family') }}</h3>
        <div class="m-grid">
            <div class="m-grid cols-2">
                <div class="m-field">
                    <label class="m-label" for="marital_status">{{ __('Marital Status') }}</label>
                    <select id="marital_status" name="marital_status" class="m-input" x-model="marital" required>
                        <option value="" disabled>{{ __('Select') }}</option>
                        @foreach ($maritalStatus as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('marital_status')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field" x-show="marital === 'M'">
                    <label class="m-label" for="date_joined">{{ __('Wedding Date') }}{{ $pendingTag('date_joined') }}</label>
                    <input id="date_joined" name="date_joined" type="date" class="m-input" value="{{ old('date_joined', $date($member->date_joined)) }}">
                    @error('date_joined')<p class="m-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="m-field" x-show="marital === 'M' || spouseSet">
                <span class="m-label">{{ __('Spouse') }}{{ $pendingTag('spouse') }}</span>
                @include('members::members.partials.person-picker', ['name' => 'spouse_id', 'want' => 'spouse', 'freeName' => 'spouse_name'])
                <p class="m-help">{{ __('Pick a member, or type the name when the spouse is not a member.') }}</p>
                @error('spouse_id')<p class="m-error">{{ $message }}</p>@enderror
                @error('spouse_name')<p class="m-error">{{ $message }}</p>@enderror
            </div>
            <div class="m-grid cols-2">
                <div class="m-field">
                    <span class="m-label">{{ __('Father') }} <span class="m-optional">({{ __('optional') }})</span></span>
                    @include('members::members.partials.person-picker', ['name' => 'father_id', 'want' => 'M'])
                    @error('father_id')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <span class="m-label">{{ __('Mother') }} <span class="m-optional">({{ __('optional') }})</span></span>
                    @include('members::members.partials.person-picker', ['name' => 'mother_id', 'want' => 'F'])
                    @error('mother_id')<p class="m-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </section>

    {{-- Church --}}
    <section class="m-card m-section">
        <h3 class="m-section-title"><i class="fa-solid fa-church"></i> {{ __('Church') }}</h3>
        <div class="m-grid">
            <div class="m-grid cols-2">
                <div class="m-field">
                    <label class="m-label" for="membership_status">{{ __('Status') }}</label>
                    <select id="membership_status" name="membership_status" class="m-input">
                        @foreach ($membershipStatus as $value => $label)
                            <option value="{{ $value }}" @selected($field('membership_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('membership_status')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <label class="m-label" for="baptism_date">{{ __('Baptism Date') }}</label>
                    <input id="baptism_date" name="baptism_date" type="date" class="m-input" value="{{ old('baptism_date', $date($member->baptism_date)) }}">
                    @error('baptism_date')<p class="m-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="m-field">
                <label class="m-label" for="notes">{{ __('Notes') }} <span class="m-optional">({{ __('optional') }})</span></label>
                <textarea id="notes" name="notes" rows="3" class="m-input">{{ $field('notes') }}</textarea>
                @error('notes')<p class="m-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <div class="m-form-actions">
        <a href="{{ $member->exists ? route('members.show', $member) : route('members.index') }}" class="m-btn">{{ __('Cancel') }}</a>
        <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
    </div>
</div>

<script>
    // The members that can be picked as spouse, father or mother.
    window.memberPeople = @js($people);

    // The form's gender and marital status: the spouse is offered of the other gender, and only when married (or
    // when one is already set).
    function memberForm({ gender, marital, spouse }) {
        return { gender: gender || '', marital: marital || '', spouseSet: spouse };
    }
</script>
