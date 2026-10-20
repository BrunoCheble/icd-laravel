{{-- The announcement form (create and edit), in sections. $announcement, $people (the members to pick as author) --}}
@php
    $field = fn (string $name) => old($name, $announcement->{$name});
    $categories = \App\Modules\Announcements\Support\AnnouncementCategories::forForms();
    // The errors of the category's fields, for the script ("details.area" => message).
    $detailErrors = collect($errors->getMessages())->filter(fn ($messages, $key) => str_starts_with($key, 'details.'))->map(fn ($messages) => $messages[0]);
@endphp

<div class="m-form" x-data="announcementCategory(@js([
        'type' => (string) $field('type'),
        'details' => (object) old('details', $announcement->details ?? []),
        'errors' => (object) $detailErrors->all(),
        'categories' => $categories,
        'creating' => ! $announcement->exists,
        'kept' => count(array_filter($announcement->photos ?? [], fn ($path) => is_file(public_path($path)))),
    ]))">
    @if ($errors->any())
        <div class="m-card m-section" style="border-color: var(--bd-fecaca); background: var(--bg-fef2f2);">
            <p class="m-error"><i class="fa-solid fa-triangle-exclamation"></i> {{ __('Check the fields marked below.') }}</p>
        </div>
    @endif

    {{-- The announcement --}}
    <section class="m-card m-section">
        <h3 class="m-section-title"><i class="fa-solid fa-bullhorn"></i> {{ __('Announcement') }}</h3>
        <div class="m-grid">
            <div class="m-field">
                <span class="m-label">{{ __('Category') }}</span>
                <div class="m-seg" role="radiogroup" aria-label="{{ __('Category') }}">
                    @foreach ($categories as $value => $category)
                        <label title="{{ $category['label'] }}"><input type="radio" name="type" value="{{ $value }}" x-model="type" @change="chosen()" required><span><i class="fa-solid {{ $category['icon'] }}"></i> <span class="m-hide-sm">{{ $category['label'] }}</span></span></label>
                    @endforeach
                </div>
                @if ($announcement->exists && ! \App\Modules\Announcements\Support\AnnouncementCategories::has($announcement->type))
                    <p class="m-help">{{ __('This announcement is of an old type (:type), no longer shown on the site: choose its category.', ['type' => $announcement->type_name]) }}</p>
                @endif
                @error('type')<p class="m-error">{{ $message }}</p>@enderror
            </div>
            {{-- The chosen category's own fields --}}
            <div class="m-grid cols-2" x-show="Object.keys(fields).length">
                <template x-for="(field, name) in fields" :key="type + name">
                    <div class="m-field">
                        <label class="m-label" :for="'details-' + name"><span x-text="field.label"></span> <span class="m-optional" x-show="!field.required">({{ __('optional') }})</span></label>
                        <template x-if="field.type === 'select' || field.type === 'yesno'">
                            <select :id="'details-' + name" :name="'details[' + name + ']'" class="m-input" x-model="details[name]" :required="field.required">
                                <option value="">{{ __('Choose…') }}</option>
                                <template x-for="(label, value) in (field.type === 'yesno' ? { 1: @js(__('Yes')), 0: @js(__('No')) } : field.options)" :key="value">
                                    <option :value="value" x-text="label" :selected="String(details[name] ?? '') === String(value)"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="field.type === 'text'">
                            <input :id="'details-' + name" :name="'details[' + name + ']'" class="m-input" x-model="details[name]" :required="field.required" :maxlength="field.max" :placeholder="field.placeholder">
                        </template>
                        <p class="m-error" x-show="errors['details.' + name]" x-text="errors['details.' + name]"></p>
                    </div>
                </template>
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

    {{-- A résumé's PDF (the picture shown is the category's banner) --}}
    <section class="m-card m-section" x-show="categories[type]?.pdf" x-cloak>
        <h3 class="m-section-title"><i class="fa-solid fa-file-pdf"></i> {{ __('Résumé in PDF') }} <small>{{ __('optional') }}</small></h3>
        <div class="m-grid">
            @if ($announcement->isPdf() && $announcement->url_image)
                <p class="m-help"><a href="{{ $announcement->url_image }}" target="_blank" rel="noopener" class="m-link"><i class="fa-solid fa-file-pdf"></i> {{ __('Open the current file') }}</a></p>
            @endif
            <div class="m-field">
                <input id="image_path" name="image_path" type="file" accept=".pdf,application/pdf" class="m-input" :disabled="!categories[type]?.pdf">
                <p class="m-help">{{ __('PDF, up to 5 MB. Whoever is interested can open it from the site.') }}@if ($announcement->isPdf()) {{ __('A new file replaces the current one.') }}@endif</p>
                @error('image_path')<p class="m-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    {{-- A donation's or a sale's photos (shown in a gallery; the card keeps the category's banner) --}}
    <section class="m-card m-section" x-show="categories[type]?.photos" x-cloak>
        <h3 class="m-section-title"><i class="fa-solid fa-images"></i> {{ __('Photos') }} <small>{{ __('optional') }}</small></h3>
        <div class="m-grid">
            @if ($announcement->photos)
                <div style="display: flex; flex-wrap: wrap; gap: .75rem;">
                    @foreach ($announcement->photos as $path)
                        @if (is_file(public_path($path)))
                            <label style="display: grid; gap: .3rem; font-size: .8rem; color: var(--tx-4b5563);">
                                <img src="{{ asset($path) }}" alt="" style="width: 9rem; aspect-ratio: 16 / 10; object-fit: cover; border-radius: .5rem;">
                                <span><input type="checkbox" name="remove_photos[]" value="{{ $path }}" @change="kept += $event.target.checked ? -1 : 1"> {{ __('Remove') }}</span>
                            </label>
                        @endif
                    @endforeach
                </div>
            @endif
            <div class="m-field">
                <input id="photos" name="photos[]" type="file" multiple accept="image/jpeg,image/png,image/webp" class="m-input" :disabled="!categories[type]?.photos" @change="checkPhotos($event)">
                <p class="m-help">{{ __('Up to 3 photos (JPG, PNG or WebP, up to 5 MB each). They show in a gallery on the announcement; the card keeps the category\'s picture.') }}</p>
                <p class="m-error" x-show="photoError" x-text="photoError"></p>
                @error('photos')<p class="m-error">{{ $message }}</p>@enderror
                @error('photos.*')<p class="m-error">{{ $message }}</p>@enderror
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
                <input id="expires_at" name="expires_at" type="date" class="m-input" value="{{ $field('expires_at') }}" x-ref="expires">
                <p class="m-help">{{ __('A new announcement lasts as long as its category usually does; change it if you need.') }}</p>
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

    // The category chosen and its own fields; a new announcement takes the category's usual length.
    function announcementCategory({ type, details, errors, categories, creating, kept }) {
        return {
            type, details, errors, categories, kept, photoError: '',
            // No more photos than the category takes, with the ones kept.
            checkPhotos(event) {
                const room = (this.categories[this.type]?.photos || 0) - this.kept;
                this.photoError = event.target.files.length > room ? @js(__('Up to 3 photos in all: remove some first.')) : '';
                if (this.photoError) event.target.value = '';
            },
            // Yes / no fields kept as true / false: the form's 1 / 0.
            init() { for (const key in this.details) if (typeof this.details[key] === 'boolean') this.details[key] = this.details[key] ? '1' : '0'; },
            get fields() { return this.categories[this.type]?.fields || {}; },
            chosen() {
                const months = this.categories[this.type]?.months;
                if (!creating || !months) return;
                const end = new Date();
                end.setMonth(end.getMonth() + months);
                this.$refs.expires.value = end.toISOString().slice(0, 10);
            },
        };
    }
</script>
