<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $ministry->name }}
        </h2>
    </x-slot>

    @include('partials.ui')

    @php
        $page = old('site_page') ? json_decode(old('site_page'), true) : $ministry->site_page;
        // Icons of Font Awesome (the site's are version 5: these names exist there too).
        $icons = ['church', 'pray', 'praying-hands', 'bible', 'book', 'book-open', 'child', 'baby', 'music', 'guitar', 'microphone', 'theater-masks',
            'video', 'camera', 'handshake', 'hands-helping', 'hand-holding-heart', 'heart', 'users', 'user-friends', 'star', 'fire', 'mug-hot',
            'coffee', 'ring', 'home', 'graduation-cap', 'bullhorn', 'globe', 'dove', 'cross', 'seedling'];
    @endphp

    <div class="m-page">
        <a href="{{ route('ministries.show', $ministry) }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ $ministry->name }}</a>
        <div class="m-head">
            <div>
                <h1>{{ __('Page on the site') }}</h1>
                <p>{{ __('The card and the page of :name in the site\'s "Conheça Nossos Ministérios".', ['name' => $ministry->name]) }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('ministries.site.update', $ministry) }}">
            @csrf
            @method('PUT')
            <div class="m-form">
                <section class="m-card m-section" x-data="{ icon: @js(old('site_icon', $ministry->site_icon) ?? '') }">
                    <h3 class="m-section-title"><i class="fa-regular fa-id-card"></i> {{ __('Card') }}</h3>
                    <label style="display: flex; gap: .6rem; align-items: flex-start; cursor: pointer;">
                        <input type="checkbox" name="site_visible" value="1" @checked(old('site_visible', $ministry->site_visible)) style="margin-top: .2rem; width: 1.1rem; height: 1.1rem; border-radius: .3rem; color: #f8a925;">
                        <span><strong>{{ __('Show on the site') }}</strong>
                            <span class="m-help" style="display: block;">{{ __('Its card and its page, in the order below (smaller first).') }}</span></span>
                    </label>
                    <div class="m-grid cols-2 mt-3">
                        <div class="m-field">
                            <label class="m-label" for="site_order">{{ __('Order') }}</label>
                            <input id="site_order" name="site_order" type="number" min="0" max="999" class="m-input" value="{{ old('site_order', $ministry->site_order) }}">
                            @error('site_order')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="m-field">
                            <span class="m-label">{{ __('Group') }}</span>
                            <div class="m-seg" role="radiogroup" aria-label="{{ __('Group') }}">
                                @foreach (\App\Modules\Ministries\Models\Ministry::SITE_GROUPS as $group => $label)
                                    <label><input type="radio" name="site_group" value="{{ $group }}" @checked(old('site_group', $ministry->site_group ?? 'ministries') === $group)><span>{{ $label }}</span></label>
                                @endforeach
                            </div>
                            <p class="m-help">{{ __('Shows above the page\'s title; "previous" and "next" go through its group.') }}</p>
                        </div>
                        <div class="m-field">
                            <label class="m-label" for="site_icon">{{ __('Icon') }}</label>
                            <div style="display: flex; gap: .5rem; align-items: center;">
                                <span style="flex: none; width: 2.6rem; height: 2.6rem; display: grid; place-items: center; border-radius: .8rem; background: linear-gradient(135deg, #f8a925, #fbbf24); color: #fff;"><i :class="'fa-solid fa-' + (icon || 'circle-question')"></i></span>
                                <input id="site_icon" name="site_icon" type="text" class="m-input" x-model="icon" list="site-icons" maxlength="60" placeholder="church">
                            </div>
                            <datalist id="site-icons">@foreach ($icons as $name)<option value="{{ $name }}">@endforeach</datalist>
                            <p class="m-help">{!! __('A Font Awesome icon\'s name (see :link).', ['link' => '<a href="https://fontawesome.com/v5/search?o=r&m=free&s=solid" target="_blank" rel="noopener">fontawesome.com</a>']) !!}</p>
                            @error('site_icon')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="m-field">
                            <label class="m-label" for="site_tag">{{ __('Tag') }}</label>
                            <input id="site_tag" name="site_tag" type="text" class="m-input" value="{{ old('site_tag', $ministry->site_tag) }}" maxlength="60" placeholder="{{ __('E.g. ICD Music') }}">
                            <p class="m-help">{{ __('Small, above the card\'s title; also in the "previous" and "next" buttons.') }}</p>
                            @error('site_tag')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="m-field mt-3">
                        <label class="m-label" for="site_title">{{ __('Card title') }}</label>
                        <input id="site_title" name="site_title" type="text" class="m-input" value="{{ old('site_title', $ministry->site_title) }}" maxlength="120" placeholder="{{ $ministry->name }}">
                        <p class="m-help">{{ __('Empty: the ministry\'s name.') }}</p>
                        @error('site_title')<p class="m-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="m-field mt-3">
                        <label class="m-label" for="site_summary">{{ __('Summary') }}</label>
                        <textarea id="site_summary" name="site_summary" class="m-input" rows="4" maxlength="2000">{{ old('site_summary', $ministry->site_summary) }}</textarea>
                        <p class="m-help">{{ __('The card\'s text (it shows up to five lines).') }}</p>
                        @error('site_summary')<p class="m-error">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="m-card m-section">
                    <h3 class="m-section-title"><i class="fa-regular fa-file-lines"></i> {{ __('Page') }}</h3>
                    <p class="m-help" style="margin-top: -.4rem;">{{ __('Opens with "Saiba Mais" on the card. A first section without a title opens the text; the others make the index.') }}</p>
                    <div class="m-field" style="margin-bottom: .8rem;">
                        <label class="m-label" for="site_page_title">{{ __('Page title') }}</label>
                        <input id="site_page_title" name="site_page_title" type="text" class="m-input" value="{{ old('site_page_title', $ministry->site_page_title) }}" maxlength="160" placeholder="{{ $ministry->name }}">
                        <p class="m-help">{{ __('Empty: the card\'s title.') }}</p>
                        @error('site_page_title')<p class="m-error">{{ $message }}</p>@enderror
                    </div>
                    @include('ministries::partials.text-editor', ['name' => 'site_page', 'value' => $page])
                    @error('site_page')<p class="m-error">{{ $message }}</p>@enderror
                </section>

                <div class="m-form-actions">
                    <a href="{{ route('ministries.show', $ministry) }}" class="m-btn">{{ __('Cancel') }}</a>
                    <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
