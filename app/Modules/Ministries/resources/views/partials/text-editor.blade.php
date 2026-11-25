{{--
    The editor of a text in sections (see MinistryBio): a pastor's biography, a ministry's page on the site.
    Sections of blocks (paragraph, list, table, link); the JSON goes in the hidden field $name, kept up to date.
    Variables: $name, $value (the text, or null), $layoutChoice (whether to choose "with an index" or "simple").
--}}
@once
    <style>
        .bio-section { border: 1px solid var(--bd-e5e7eb); border-radius: .8rem; padding: .85rem; display: grid; gap: .7rem; }
        .bio-section-head { display: flex; gap: .4rem; align-items: center; }
        .bio-section-head .m-input { flex: 1; font-weight: 700; }
        .bio-block { border-left: 3px solid var(--brand-200); padding: .2rem 0 .2rem .75rem; display: grid; gap: .45rem; }
        .bio-block-head { display: flex; align-items: center; gap: .4rem; font-size: .75rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--tx-6b7280); }
        .bio-block-head .tools { margin-left: auto; display: flex; gap: .15rem; }
        .bio-block-head label { display: inline-flex; align-items: center; gap: .3rem; margin-left: .5rem; font-weight: 600; letter-spacing: 0; text-transform: none; cursor: pointer; }
        .bio-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .35rem; align-items: start; }
        .bio-item-fields { display: grid; gap: .35rem; }
        @media (min-width: 800px) { .bio-item-fields { grid-template-columns: minmax(0, 1fr) minmax(0, 2fr) minmax(0, 1fr); } }
        .bio-sub { display: grid; gap: .3rem; margin: .1rem 0 .2rem 1.25rem; }
        .bio-sub-row { display: flex; gap: .3rem; align-items: center; }
        .bio-sub-row .m-input { flex: 1; }
        .bio-table { overflow-x: auto; }
        .bio-table table { border-collapse: separate; border-spacing: .25rem; }
        .bio-table td { padding: 0; vertical-align: top; }
        .bio-table .m-input { min-width: 9rem; }
        .bio-table tr.is-head .m-input { font-weight: 700; background: var(--bg-f3f4f6); }
        .bio-add { display: flex; flex-wrap: wrap; gap: .35rem; }
        .bio .m-btn.is-icon { width: 32px; height: 32px; min-height: 32px; }
        .bio .m-input { min-height: 38px; }
    </style>
    <script>
        // A text in sections of blocks (paragraph, list, table, link), kept as JSON in the hidden field.
        function textEditor({ value, labels }) {
            let next = 0;
            const key = () => ++next;
            const item = (data = {}) => ({ key: key(), title: data.title || '', text: data.text || '', url: data.url || '', sub: (data.sub || []).map((line) => ({ key: key(), text: line })) });
            const row = (cells) => ({ key: key(), cells: cells.map((text) => ({ key: key(), text })) });
            const block = (type, data = {}) => {
                if (type === 'list') return { key: key(), type, ordered: !! data.ordered, items: (data.items || [{}]).map(item) };
                if (type === 'table') return { key: key(), type, head: data.head ?? true, rows: (data.rows || [['', ''], ['', '']]).map(row) };
                return { key: key(), type, text: data.text || '', label: data.label || '', url: data.url || '' };
            };
            const section = (data = {}) => ({ key: key(), title: data.title || '', blocks: (data.blocks || []).map((b) => block(b.type, b)) });
            return {
                layout: value?.layout || 'sections',
                sections: (value?.sections || []).map(section),
                labels,
                item, block, section,
                move(list, index, step) {
                    const to = index + step;
                    if (to < 0 || to >= list.length) return;
                    list.splice(to, 0, list.splice(index, 1)[0]);
                },
                addColumn(table) { table.rows.forEach((r) => r.cells.push({ key: key(), text: '' })); },
                removeColumn(table, c) { if (table.rows[0].cells.length > 1) table.rows.forEach((r) => r.cells.splice(c, 1)); },
                addRow(table) { table.rows.push(row(Array(table.rows[0]?.cells.length || 2).fill(''))); },
                subLine() { return { key: key(), text: '' }; },
                get json() {
                    const sections = this.sections.map((s) => ({
                        title: s.title,
                        blocks: s.blocks.map((b) => {
                            if (b.type === 'list') return { type: 'list', ordered: b.ordered, items: b.items.map((i) => ({ title: i.title, text: i.text, url: i.url, sub: i.sub.map((line) => line.text) })) };
                            if (b.type === 'table') return { type: 'table', head: b.head, rows: b.rows.map((r) => r.cells.map((c) => c.text)) };
                            if (b.type === 'link') return { type: 'link', text: b.text, label: b.label, url: b.url };
                            return { type: 'text', text: b.text };
                        }),
                    }));
                    return sections.length ? JSON.stringify({ layout: this.layout, sections }) : '';
                },
            };
        }
    </script>
@endonce

<div class="bio" x-data="textEditor(@js(['value' => $value, 'labels' => ['text' => __('Paragraph'), 'list' => __('List'), 'table' => __('Table'), 'link' => __('Link')]]))">
    <input type="hidden" name="{{ $name }}" :value="json">

    @if ($layoutChoice ?? true)
        <div class="m-field" x-show="sections.length" style="margin-bottom: .8rem;">
            <span class="m-label">{{ __('Layout') }}</span>
            <div class="m-seg" role="radiogroup" aria-label="{{ __('Layout') }}">
                <label><input type="radio" value="sections" x-model="layout"><span><i class="fa-solid fa-list-ol"></i> {{ __('With an index') }}</span></label>
                <label><input type="radio" value="simple" x-model="layout"><span>{{ __('Simple text') }}</span></label>
            </div>
            <p class="m-help" x-show="layout === 'sections'">{{ __('An index with the sections\' titles, to jump to each one (good for long texts).') }}</p>
            <p class="m-help" x-show="layout === 'simple'">{{ __('One section after another, without an index (titles optional).') }}</p>
        </div>
    @endif
    <p class="m-help" style="margin: 0 0 .8rem;">{{ __('In the texts, **between two pairs of asterisks** shows in bold; a blank line starts a new paragraph.') }}</p>

    <div style="display: grid; gap: .8rem;">
        <template x-for="(section, s) in sections" :key="section.key">
            <div class="bio-section">
                <div class="bio-section-head">
                    <input type="text" class="m-input" x-model="section.title" maxlength="120" :placeholder="@js(__('Section title (without one, it opens the text)'))">
                    <button type="button" class="m-btn is-ghost is-icon" @click="move(sections, s, -1)" :disabled="s === 0" aria-label="{{ __('Up') }}"><i class="fa-solid fa-arrow-up"></i></button>
                    <button type="button" class="m-btn is-ghost is-icon" @click="move(sections, s, 1)" :disabled="s === sections.length - 1" aria-label="{{ __('Down') }}"><i class="fa-solid fa-arrow-down"></i></button>
                    <button type="button" class="m-btn is-ghost is-icon" @click="sections.splice(s, 1)" aria-label="{{ __('Delete') }}"><i class="fa-solid fa-trash"></i></button>
                </div>

                <template x-for="(block, b) in section.blocks" :key="block.key">
                    <div class="bio-block">
                        <div class="bio-block-head">
                            <span x-text="labels[block.type]"></span>
                            <template x-if="block.type === 'list'"><label><input type="checkbox" x-model="block.ordered"> {{ __('Numbered') }}</label></template>
                            <template x-if="block.type === 'table'"><label><input type="checkbox" x-model="block.head"> {{ __('First row is the header') }}</label></template>
                            <span class="tools">
                                <button type="button" class="m-btn is-ghost is-icon" @click="move(section.blocks, b, -1)" :disabled="b === 0" aria-label="{{ __('Up') }}"><i class="fa-solid fa-arrow-up"></i></button>
                                <button type="button" class="m-btn is-ghost is-icon" @click="move(section.blocks, b, 1)" :disabled="b === section.blocks.length - 1" aria-label="{{ __('Down') }}"><i class="fa-solid fa-arrow-down"></i></button>
                                <button type="button" class="m-btn is-ghost is-icon" @click="section.blocks.splice(b, 1)" aria-label="{{ __('Delete') }}"><i class="fa-solid fa-xmark"></i></button>
                            </span>
                        </div>

                        <template x-if="block.type === 'text'">
                            <textarea class="m-input" rows="3" x-model="block.text" maxlength="6000" :placeholder="@js(__('A paragraph…'))"></textarea>
                        </template>

                        <template x-if="block.type === 'list'">
                            <div style="display: grid; gap: .45rem;">
                                <template x-for="(item, i) in block.items" :key="item.key">
                                    <div>
                                        <div class="bio-item">
                                            <div class="bio-item-fields">
                                                <input type="text" class="m-input" x-model="item.title" maxlength="200" :placeholder="@js(__('In bold (e.g. Definição:)'))">
                                                <input type="text" class="m-input" x-model="item.text" maxlength="2000" :placeholder="@js(__('Text'))">
                                                <input type="url" class="m-input" x-model="item.url" maxlength="500" :placeholder="@js(__('Link (optional)'))">
                                            </div>
                                            <button type="button" class="m-btn is-ghost is-icon" @click="block.items.splice(i, 1)" aria-label="{{ __('Delete') }}"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                        <div class="bio-sub">
                                            <template x-for="(line, l) in item.sub" :key="line.key">
                                                <div class="bio-sub-row">
                                                    <i class="fa-solid fa-turn-up fa-rotate-90" style="color: var(--tx-9ca3af);"></i>
                                                    <input type="text" class="m-input" x-model="line.text" maxlength="1000" :placeholder="@js(__('Sub-item'))">
                                                    <button type="button" class="m-btn is-ghost is-icon" @click="item.sub.splice(l, 1)" aria-label="{{ __('Delete') }}"><i class="fa-solid fa-xmark"></i></button>
                                                </div>
                                            </template>
                                            <div><button type="button" class="m-btn is-ghost" style="min-height: 30px;" @click="item.sub.push(subLine())"><i class="fa-solid fa-plus"></i> {{ __('Sub-item') }}</button></div>
                                        </div>
                                    </div>
                                </template>
                                <div><button type="button" class="m-btn" @click="block.items.push(item())"><i class="fa-solid fa-plus"></i> {{ __('Item') }}</button></div>
                            </div>
                        </template>

                        <template x-if="block.type === 'table'">
                            <div style="display: grid; gap: .45rem;">
                                <div class="bio-table">
                                    <table>
                                        <tbody>
                                            <template x-for="(row, r) in block.rows" :key="row.key">
                                                <tr :class="{ 'is-head': block.head && r === 0 }">
                                                    <template x-for="(cell, c) in row.cells" :key="cell.key">
                                                        <td><textarea class="m-input" rows="1" x-model="cell.text" maxlength="500"></textarea></td>
                                                    </template>
                                                    <td><button type="button" class="m-btn is-ghost is-icon" @click="block.rows.length > 1 && block.rows.splice(r, 1)" :disabled="block.rows.length === 1" aria-label="{{ __('Delete the row') }}" title="{{ __('Delete the row') }}"><i class="fa-solid fa-xmark"></i></button></td>
                                                </tr>
                                            </template>
                                            <tr>
                                                <template x-for="(cell, c) in block.rows[0].cells" :key="cell.key">
                                                    <td style="text-align: center;"><button type="button" class="m-btn is-ghost is-icon" @click="removeColumn(block, c)" :disabled="block.rows[0].cells.length === 1" aria-label="{{ __('Delete the column') }}" title="{{ __('Delete the column') }}"><i class="fa-solid fa-xmark"></i></button></td>
                                                </template>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="bio-add">
                                    <button type="button" class="m-btn" @click="addRow(block)"><i class="fa-solid fa-plus"></i> {{ __('Row') }}</button>
                                    <button type="button" class="m-btn" @click="addColumn(block)"><i class="fa-solid fa-plus"></i> {{ __('Column') }}</button>
                                </div>
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
                    <button type="button" class="m-btn" @click="section.blocks.push(block('table'))"><i class="fa-solid fa-table"></i> {{ __('Table') }}</button>
                    <button type="button" class="m-btn" @click="section.blocks.push(block('link'))"><i class="fa-solid fa-link"></i> {{ __('Link') }}</button>
                </div>
            </div>
        </template>
    </div>
    <div class="mt-3"><button type="button" class="m-btn" @click="sections.push(section())"><i class="fa-solid fa-plus"></i> {{ __('Section') }}</button></div>
</div>
