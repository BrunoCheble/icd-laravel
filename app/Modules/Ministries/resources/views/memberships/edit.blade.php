<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $membership->member->full_name }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .bio-section { border: 1px solid var(--bd-e5e7eb); border-radius: .8rem; padding: .85rem; display: grid; gap: .7rem; }
        .bio-section-head { display: flex; gap: .4rem; align-items: center; }
        .bio-section-head .m-input { flex: 1; font-weight: 700; }
        .bio-block { border-left: 3px solid var(--brand-200); padding: .2rem 0 .2rem .75rem; display: grid; gap: .45rem; }
        .bio-block-head { display: flex; align-items: center; gap: .4rem; font-size: .75rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--tx-6b7280); }
        .bio-block-head .tools { margin-left: auto; display: flex; gap: .15rem; }
        .bio-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .35rem; align-items: start; }
        .bio-item-fields { display: grid; gap: .35rem; }
        @media (min-width: 800px) { .bio-item-fields { grid-template-columns: minmax(0, 1fr) minmax(0, 2fr) minmax(0, 1fr); } }
        .bio-add { display: flex; flex-wrap: wrap; gap: .35rem; }
        .bio .m-btn.is-icon { width: 32px; height: 32px; min-height: 32px; }
        .bio .m-input { min-height: 38px; }
    </style>

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

        <form method="POST" action="{{ route('ministries-members.update', $membership) }}" x-data="bioEditor(@js(['bio' => $bio]))" @submit="serialize()">
            @csrf
            @method('PUT')
            <input type="hidden" name="bio" x-ref="bio">

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

                {{-- The biography: sections of texts, lists and links, organised as each person wants (see MinistryBio) --}}
                <section class="m-card m-section bio">
                    <h3 class="m-section-title"><i class="fa-regular fa-file-lines"></i> {{ __('Biography') }} <span class="m-optional">({{ __('optional') }})</span></h3>
                    <p class="m-help" style="margin-top: -.4rem;">{{ $ministry->isPastoral() ? __('Shows on the site, in the "Biography" button of the pastor. Without sections, there is no button.') : __('Only shows on the site for the ministry marked as "Pastores".') }}</p>

                    <div class="m-field" x-show="sections.length">
                        <span class="m-label">{{ __('Biography layout') }}</span>
                        <div class="m-seg" role="radiogroup" aria-label="{{ __('Biography layout') }}">
                            <label><input type="radio" value="sections" x-model="layout"><span><i class="fa-solid fa-list-ol"></i> {{ __('With an index') }}</span></label>
                            <label><input type="radio" value="simple" x-model="layout"><span>{{ __('Simple text') }}</span></label>
                        </div>
                        <p class="m-help" x-show="layout === 'sections'">{{ __('An index with the sections\' titles, to jump to each one (good for long biographies).') }}</p>
                        <p class="m-help" x-show="layout === 'simple'">{{ __('One section after another, without an index (titles optional).') }}</p>
                    </div>

                    <div style="display: grid; gap: .8rem;">
                        <template x-for="(section, s) in sections" :key="section.key">
                            <div class="bio-section">
                                <div class="bio-section-head">
                                    <input type="text" class="m-input" x-model="section.title" maxlength="120" :placeholder="@js(__('Section title (e.g. Academic background)'))">
                                    <button type="button" class="m-btn is-ghost is-icon" @click="move(sections, s, -1)" :disabled="s === 0" aria-label="{{ __('Up') }}"><i class="fa-solid fa-arrow-up"></i></button>
                                    <button type="button" class="m-btn is-ghost is-icon" @click="move(sections, s, 1)" :disabled="s === sections.length - 1" aria-label="{{ __('Down') }}"><i class="fa-solid fa-arrow-down"></i></button>
                                    <button type="button" class="m-btn is-ghost is-icon" @click="sections.splice(s, 1)" aria-label="{{ __('Delete') }}"><i class="fa-solid fa-trash"></i></button>
                                </div>

                                <template x-for="(block, b) in section.blocks" :key="block.key">
                                    <div class="bio-block">
                                        <div class="bio-block-head">
                                            <span x-text="labels[block.type]"></span>
                                            <span class="tools">
                                                <button type="button" class="m-btn is-ghost is-icon" @click="move(section.blocks, b, -1)" :disabled="b === 0" aria-label="{{ __('Up') }}"><i class="fa-solid fa-arrow-up"></i></button>
                                                <button type="button" class="m-btn is-ghost is-icon" @click="move(section.blocks, b, 1)" :disabled="b === section.blocks.length - 1" aria-label="{{ __('Down') }}"><i class="fa-solid fa-arrow-down"></i></button>
                                                <button type="button" class="m-btn is-ghost is-icon" @click="section.blocks.splice(b, 1)" aria-label="{{ __('Delete') }}"><i class="fa-solid fa-xmark"></i></button>
                                            </span>
                                        </div>
                                        <template x-if="block.type === 'text'">
                                            <textarea class="m-input" rows="3" x-model="block.text" maxlength="4000" :placeholder="@js(__('A paragraph…'))"></textarea>
                                        </template>
                                        <template x-if="block.type === 'list'">
                                            <div style="display: grid; gap: .45rem;">
                                                <template x-for="(item, i) in block.items" :key="item.key">
                                                    <div class="bio-item">
                                                        <div class="bio-item-fields">
                                                            <input type="text" class="m-input" x-model="item.title" maxlength="200" :placeholder="@js(__('In bold (e.g. Licenciado em Letras)'))">
                                                            <input type="text" class="m-input" x-model="item.text" maxlength="1000" :placeholder="@js(__('Text (e.g. pela FURB)'))">
                                                            <input type="url" class="m-input" x-model="item.url" maxlength="500" :placeholder="@js(__('Link (optional)'))">
                                                        </div>
                                                        <button type="button" class="m-btn is-ghost is-icon" @click="block.items.splice(i, 1)" aria-label="{{ __('Delete') }}"><i class="fa-solid fa-xmark"></i></button>
                                                    </div>
                                                </template>
                                                <div><button type="button" class="m-btn" @click="block.items.push(item())"><i class="fa-solid fa-plus"></i> {{ __('Item') }}</button></div>
                                            </div>
                                        </template>
                                        <template x-if="block.type === 'link'">
                                            <div class="bio-item-fields">
                                                <input type="text" class="m-input" x-model="block.text" maxlength="300" :placeholder="@js(__('Text before (e.g. Video about the ministry:)'))">
                                                <input type="text" class="m-input" x-model="block.label" maxlength="120" :placeholder="@js(__('Link text (e.g. Watch on YouTube)'))">
                                                <input type="url" class="m-input" x-model="block.url" maxlength="500" placeholder="https://">
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <div class="bio-add">
                                    <button type="button" class="m-btn" @click="section.blocks.push(block('text'))"><i class="fa-solid fa-paragraph"></i> {{ __('Paragraph') }}</button>
                                    <button type="button" class="m-btn" @click="section.blocks.push(block('list'))"><i class="fa-solid fa-list-ul"></i> {{ __('List') }}</button>
                                    <button type="button" class="m-btn" @click="section.blocks.push(block('link'))"><i class="fa-solid fa-link"></i> {{ __('Link') }}</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="mt-3"><button type="button" class="m-btn" @click="sections.push(section())"><i class="fa-solid fa-plus"></i> {{ __('Section') }}</button></div>
                    @error('bio')<p class="m-error">{{ $message }}</p>@enderror
                </section>

                <div class="m-form-actions">
                    <a href="{{ route('ministries.show', $ministry) }}#members" class="m-btn">{{ __('Cancel') }}</a>
                    <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
                </div>
            </div>
        </form>

        @can('members.update')
            <form method="POST" action="{{ route('ministries-members.destroy', $membership) }}" class="mt-4" x-data @submit="if (!confirm(@js(__('Remove this member from the ministry?')))) $event.preventDefault()">
                @csrf
                @method('DELETE')
                <button type="submit" class="m-btn is-danger"><i class="fa-solid fa-user-minus"></i> {{ __('Remove from the ministry') }}</button>
            </form>
        @endcan
    </div>

    <script>
        // The biography as sections of blocks (paragraph, list, link), kept as JSON in "bio" when saving.
        function bioEditor({ bio }) {
            let next = 0;
            const key = () => ++next;
            const item = (data = {}) => ({ key: key(), title: data.title || '', text: data.text || '', url: data.url || '' });
            const block = (type, data = {}) => type === 'list'
                ? { key: key(), type, items: (data.items || [{}]).map(item) }
                : { key: key(), type, text: data.text || '', label: data.label || '', url: data.url || '' };
            const section = (data = {}) => ({ key: key(), title: data.title || '', blocks: (data.blocks || []).map(b => block(b.type, b)) });
            return {
                layout: bio?.layout || 'sections',
                sections: (bio?.sections || []).map(section),
                labels: { text: @js(__('Paragraph')), list: @js(__('List')), link: @js(__('Link')) },
                item, block, section,
                move(list, index, step) {
                    const to = index + step;
                    if (to < 0 || to >= list.length) return;
                    list.splice(to, 0, list.splice(index, 1)[0]);
                },
                serialize() {
                    const strip = ({ key, ...rest }) => rest;
                    const sections = this.sections.map(s => ({
                        title: s.title,
                        blocks: s.blocks.map(b => b.type === 'list' ? { type: 'list', items: b.items.map(strip) } : strip(b)),
                    }));
                    this.$refs.bio.value = sections.length ? JSON.stringify({ layout: this.layout, sections }) : '';
                },
            };
        }
    </script>
</x-app-layout>
