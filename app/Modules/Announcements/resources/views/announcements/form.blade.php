{{-- The announcement form (create and edit), in sections. $announcement, $people (the members to pick as author) --}}
@php
    $field = fn (string $name) => old($name, $announcement->{$name});
    $icons = ['resume' => 'fa-file-lines', 'service' => 'fa-screwdriver-wrench', 'donation' => 'fa-hand-holding-heart', 'product' => 'fa-tag'];
@endphp

<div class="m-form">
    @if ($errors->any())
        <div class="m-card m-section" style="border-color: #fecaca; background: #fef2f2;">
            <p class="m-error"><i class="fa-solid fa-triangle-exclamation"></i> {{ __('Check the fields marked below.') }}</p>
        </div>
    @endif

    {{-- The announcement --}}
    <section class="m-card m-section">
        <h3 class="m-section-title"><i class="fa-solid fa-bullhorn"></i> {{ __('Announcement') }}</h3>
        <div class="m-grid">
            <div class="m-field">
                <span class="m-label">{{ __('Type') }}</span>
                <div class="m-seg" role="radiogroup" aria-label="{{ __('Type') }}">
                    @foreach (\App\Enums\AnnouncementType::options() as $value => $label)
                        <label><input type="radio" name="type" value="{{ $value }}" @checked($field('type') === $value) required><span><i class="fa-solid {{ $icons[$value] ?? 'fa-bullhorn' }}"></i> <span class="m-hide-sm">{{ $label }}</span></span></label>
                    @endforeach
                </div>
                @error('type')<p class="m-error">{{ $message }}</p>@enderror
            </div>
            <div class="m-field">
                <label class="m-label" for="title">{{ __('Title') }}</label>
                <input id="title" name="title" type="text" class="m-input" value="{{ $field('title') }}" required maxlength="255">
                @error('title')<p class="m-error">{{ $message }}</p>@enderror
            </div>
            <div class="m-field">
                <label class="m-label" for="description">{{ __('Description') }}</label>
                <textarea id="description" name="description" rows="6" class="m-input" required>{{ $field('description') }}</textarea>
                @error('description')<p class="m-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    {{-- Who and how to reach them --}}
    <section class="m-card m-section">
        <h3 class="m-section-title"><i class="fa-solid fa-address-book"></i> {{ __('Member and contact') }}</h3>
        <div class="m-grid">
            <div class="m-field">
                <span class="m-label">{{ __('Member') }}</span>
                @include('members::members.partials.person-picker', ['member' => $announcement, 'name' => 'member_id', 'want' => null])
                @error('member_id')<p class="m-error">{{ $message }}</p>@enderror
            </div>
            <div class="m-grid cols-2">
                <div class="m-field">
                    <label class="m-label" for="contact">{{ __('Telephone Number') }}</label>
                    <input id="contact" name="contact" type="tel" class="m-input" value="{{ $field('contact') }}" required>
                    @error('contact')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <label class="m-label" for="link">{{ __('Link') }} <span class="m-optional">({{ __('optional') }})</span></label>
                    <input id="link" name="link" type="text" class="m-input" value="{{ $field('link') }}" placeholder="https://">
                    @error('link')<p class="m-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </section>

    {{-- Picture or PDF --}}
    <section class="m-card m-section">
        <h3 class="m-section-title"><i class="fa-solid fa-image"></i> {{ __('Image or PDF') }} <small>{{ __('optional') }}</small></h3>
        <div class="m-grid">
            @if ($announcement->url_image)
                <p class="m-help">
                    @if ($announcement->isPdf())
                        <a href="{{ $announcement->url_image }}" target="_blank" rel="noopener" class="m-link"><i class="fa-solid fa-file-pdf"></i> {{ __('Open the current file') }}</a>
                    @else
                        <img src="{{ $announcement->picture_url }}" alt="" style="width: 16rem; max-width: 100%; aspect-ratio: 16 / 10; object-fit: cover; border-radius: .6rem;">
                    @endif
                </p>
            @endif
            <div class="m-field">
                <input id="image_path" name="image_path" type="file" accept=".pdf,image/jpeg,image/png" class="m-input">
                @include('announcements::partials.image-cropper', ['input' => 'image_path'])
                <p class="m-help">{{ __('JPG, PNG or PDF, up to 5 MB. Pictures are cropped to the announcements\' format (16:10); without one, the default picture is shown.') }}@if ($announcement->url_image) {{ __('A new file replaces the current one.') }}@endif</p>
                @error('image_path')<p class="m-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    {{-- Publishing --}}
    <section class="m-card m-section">
        <h3 class="m-section-title"><i class="fa-solid fa-eye"></i> {{ __('Publishing') }}</h3>
        <div class="m-grid cols-2">
            <div class="m-field">
                <span class="m-label">{{ __('Approved') }}</span>
                <div class="m-seg" role="radiogroup" aria-label="{{ __('Approved') }}">
                    <label><input type="radio" name="is_approved" value="1" @checked((string) (int) $field('is_approved') === '1')><span><i class="fa-solid fa-check"></i> {{ __('Yes') }}</span></label>
                    <label><input type="radio" name="is_approved" value="0" @checked((string) (int) $field('is_approved') !== '1')><span>{{ __('No') }}</span></label>
                </div>
                <p class="m-help">{{ __('Only approved announcements show on the site.') }}</p>
            </div>
            <div class="m-field">
                <label class="m-label" for="expires_at">{{ __('Expires At') }} <span class="m-optional">({{ __('optional') }})</span></label>
                <input id="expires_at" name="expires_at" type="date" class="m-input" value="{{ $field('expires_at') }}">
                @error('expires_at')<p class="m-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <div class="m-form-actions">
        <a href="{{ route('announcements.index') }}" class="m-btn">{{ __('Cancel') }}</a>
        <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
    </div>
</div>

<script>
    // The members to pick as the announcement's author.
    window.memberPeople = @js($people);
</script>
