<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $event->name }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .gl-drop { display: grid; justify-items: center; gap: .5rem; padding: 1.5rem 1rem; border: 2px dashed var(--bd-d1d5db); border-radius: .9rem; text-align: center; cursor: pointer; transition: border-color .15s, background .15s; }
        .gl-drop:hover, .gl-drop.is-over { border-color: var(--brand); background: var(--brand-soft); }
        .gl-drop i { font-size: 1.6rem; color: var(--brand-text); }
        .gl-drop strong { color: var(--tx-111827); }
        .gl-drop small { color: var(--tx-6b7280); font-size: .82rem; }
        .gl-progress { display: flex; align-items: center; gap: .6rem; margin-top: .75rem; font-size: .9rem; color: var(--tx-374151); }
        .gl-bar { flex: 1; height: 6px; overflow: hidden; border-radius: 6px; background: var(--bg-f3f4f6); }
        .gl-bar b { display: block; height: 100%; background: var(--brand); transition: width .2s; }
        .gl-photos { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(13rem, 100%), 1fr)); gap: .9rem; margin-top: .75rem; }
        .gl-photo { overflow: hidden; }
        .gl-photo.is-hidden .gl-img img { opacity: .35; }
        .gl-img { position: relative; aspect-ratio: 4 / 5; background: var(--bg-f3f4f6); }
        .gl-img img { width: 100%; height: 100%; object-fit: cover; }
        .gl-img .m-tag { position: absolute; top: .5rem; left: .5rem; }
        .gl-img .m-tag + .m-tag { top: 2.1rem; }
        .gl-photo-body { padding: .6rem; }
        .gl-photo-body .m-input { min-height: 36px; font-size: .85rem; }
        .gl-saved { display: block; min-height: 1.1rem; margin-top: .2rem; font-size: .75rem; color: var(--tx-166534); }
        .gl-tools { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .2rem; }
        .gl-tools form { margin: 0; }
        .gl-tools .m-btn { min-height: 34px; width: 34px; padding: 0; font-size: .85rem; }
        .gl-tools .is-on { color: var(--brand-text); border-color: var(--brand); }
    </style>

    <div class="m-page">
        @include('layouts.alert')

        <a href="{{ route('gallery.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Gallery') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ $event->name }}</h1>
                <p>
                    {{ $event->event_date->translatedFormat('d F Y') }} · {{ trans_choice(':count photo|:count photos', $event->photos->count(), ['count' => $event->photos->count()]) }} ·
                    @if ($event->is_published)<span class="m-tag is-ok"><i class="fa-solid fa-globe"></i> {{ __('On the site') }}</span>@else<span class="m-tag is-off"><i class="fa-solid fa-eye-slash"></i> {{ __('Not on the site') }}</span>@endif
                </p>
            </div>
            @can('gallery.update')
                <div class="m-head-actions">
                    <a href="{{ route('gallery.edit', $event) }}" class="m-btn"><i class="fa-solid fa-pen"></i> {{ __('Edit event') }}</a>
                </div>
            @endcan
        </div>

        @can('gallery.write')
            {{-- The photos go one by one, made smaller by the browser first (a phone's photos are large) --}}
            <section class="m-card m-section" x-data="galleryUpload(@js(route('gallery.photos.store', $event)))">
                <label class="gl-drop" :class="{ 'is-over': over }" @dragover.prevent="over = true" @dragleave="over = false" @drop.prevent="over = false; send($event.dataTransfer.files)">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <strong>{{ __('Add photos') }}</strong>
                    <small>{{ __('Choose or drag several at once (JPG, PNG or WebP). They are made lighter before going.') }}</small>
                    <input type="file" accept="image/jpeg,image/png,image/webp" multiple hidden @change="send($event.target.files); $event.target.value = ''" :disabled="busy">
                </label>
                <div class="gl-progress" x-show="busy || failed.length" x-cloak>
                    <span x-text="busy ? @js(__('Sending')) + ' ' + done + ' / ' + total + '…' : ''"></span>
                    <span class="gl-bar" x-show="busy"><b :style="'width:' + (total ? done / total * 100 : 0) + '%'"></b></span>
                </div>
                <p class="m-error" x-show="failed.length" x-cloak x-text="@js(__('Not sent:')) + ' ' + failed.join(', ') + '. ' + @js(__('Reload the page to see the ones that went.'))"></p>
                <p class="m-help mt-2"><i class="fa-solid fa-circle-info"></i> {{ __('Only photos the people in them agree to show; avoid close-ups of children.') }}</p>
            </section>
        @endcan

        @if ($event->photos->isEmpty())
            <div class="m-card m-section mt-3"><p class="m-muted">{{ __('No photos yet.') }}</p></div>
        @else
            <div class="gl-photos">
                @foreach ($event->photos as $photo)
                    <article class="m-card gl-photo {{ $photo->is_hidden ? 'is-hidden' : '' }}">
                        <a href="{{ $photo->url() }}" target="_blank" rel="noopener" class="gl-img">
                            <img src="{{ $photo->thumbUrl() }}" alt="{{ $photo->caption }}" loading="lazy">
                            @if ($cover?->id === $photo->id)<span class="m-tag is-key"><i class="fa-solid fa-star"></i> {{ __('Cover') }}</span>@endif
                            @if ($photo->is_hidden)<span class="m-tag is-off"><i class="fa-solid fa-eye-slash"></i> {{ __('Hidden') }}</span>@endif
                        </a>
                        <div class="gl-photo-body">
                            @can('gallery.update')
                                <div x-data="galleryCaption(@js(route('gallery.photos.update', $photo)), @js($photo->caption ?? ''))">
                                    <input type="text" class="m-input" maxlength="160" placeholder="{{ __('Caption (optional)') }}" x-model="caption" @change="save()" @keydown.enter.prevent="$event.target.blur()">
                                    <span class="gl-saved" x-text="note"></span>
                                </div>
                                <div class="gl-tools">
                                    <form method="POST" action="{{ route('gallery.photos.update', $photo) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_cover" value="1">
                                        <button type="submit" class="m-btn {{ $cover?->id === $photo->id ? 'is-on' : '' }}" title="{{ __('Make it the cover') }}" @disabled($cover?->id === $photo->id)><i class="fa-solid fa-star"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('gallery.photos.update', $photo) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_hidden" value="{{ $photo->is_hidden ? 0 : 1 }}">
                                        <button type="submit" class="m-btn" title="{{ $photo->is_hidden ? __('Show on the site') : __('Hide from the site') }}"><i class="fa-solid {{ $photo->is_hidden ? 'fa-eye' : 'fa-eye-slash' }}"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('gallery.photos.move', $photo) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="step" value="-1">
                                        <button type="submit" class="m-btn" title="{{ __('Move back') }}" @disabled($loop->first)><i class="fa-solid fa-arrow-left"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('gallery.photos.move', $photo) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="step" value="1">
                                        <button type="submit" class="m-btn" title="{{ __('Move on') }}" @disabled($loop->last)><i class="fa-solid fa-arrow-right"></i></button>
                                    </form>
                                    @can('gallery.delete')
                                        <form method="POST" action="{{ route('gallery.photos.destroy', $photo) }}" x-data @submit="if (!confirm(@js(__('Delete this photo?')))) $event.preventDefault()">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="m-btn" title="{{ __('Delete') }}"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            @else
                                <small class="m-muted">{{ $photo->caption }}</small>
                            @endcan
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    <script>
        // Sends the chosen photos one by one (made at most 2400 px and JPEG by the browser when it can), then reloads.
        function galleryUpload(url) {
            const shrink = async (file) => {
                try {
                    const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
                    const scale = Math.min(1, 2400 / Math.max(bitmap.width, bitmap.height));
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.round(bitmap.width * scale);
                    canvas.height = Math.round(bitmap.height * scale);
                    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
                    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.88));
                    return blob ? new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file;
                } catch (e) {
                    return file;
                }
            };
            return {
                over: false, busy: false, done: 0, total: 0, failed: [],
                async send(files) {
                    const list = [...files].filter((file) => file.type.startsWith('image/'));
                    if (!list.length || this.busy) return;
                    this.busy = true; this.done = 0; this.total = list.length; this.failed = [];
                    for (const file of list) {
                        const body = new FormData();
                        body.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                        body.append('photo', await shrink(file));
                        try {
                            const response = await fetch(url, { method: 'POST', body, headers: { Accept: 'application/json' } });
                            if (!response.ok) this.failed.push(file.name);
                        } catch (e) {
                            this.failed.push(file.name);
                        }
                        this.done += 1;
                    }
                    this.busy = false;
                    if (!this.failed.length) location.reload();
                },
            };
        }

        // A photo's caption, saved when it changes.
        function galleryCaption(url, caption) {
            return {
                caption, note: '',
                async save() {
                    this.note = '';
                    const response = await fetch(url, { method: 'PATCH', headers: { 'Content-Type': 'application/json', Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ caption: this.caption }) });
                    this.note = response.ok ? @js(__('Saved')) : @js(__('Not saved'));
                },
            };
        }
    </script>
</x-app-layout>
