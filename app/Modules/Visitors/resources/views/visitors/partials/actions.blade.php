{{-- The actions sheet of a visitor, opened by visitors::visitors.partials.actions-button ("visitor-actions" event):
     details, edit, WhatsApp messages, activate, and deactivate (active, with the reason) or delete (inactive).
     Included once on the page. --}}
@include('partials.ui')
<div x-data="visitorActions()" @visitor-actions.window="open($event.detail)" @keydown.escape.window="close()">
    <template x-teleport="body">
        <div class="m-sheet" x-show="visitor" x-cloak role="dialog" aria-modal="true" :aria-label="visitor?.name">
            <div class="m-sheet-overlay" @click="close()"></div>
            <div class="m-sheet-box" x-show="visitor" x-transition.opacity>
                <div class="m-sheet-head">
                    <div>
                        <h2 x-text="visitor?.name"></h2>
                        <p x-text="subtitle()"></p>
                    </div>
                    <button type="button" class="m-btn is-ghost is-icon" @click="close()" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                </div>

                {{-- What is known, and everything that can be done --}}
                <div x-show="panel === 'menu'">
                    <dl class="visitor-facts">
                        <template x-for="[label, value] in Object.entries(visitor?.details || {})" :key="label">
                            <div><dt x-text="label"></dt><dd x-text="value"></dd></div>
                        </template>
                    </dl>
                    <div class="m-menu mt-4">
                        <a :href="visitor?.edit"><i class="fa-solid fa-pen"></i> {{ __('Edit') }}</a>
                        <button type="button" x-show="visitor?.whatsapp" @click="panel = 'whatsapp'"><i class="fa-brands fa-whatsapp"></i> {{ __('WhatsApp') }}</button>
                        <a :href="visitor?.activate" x-show="visitor && visitor.status !== 'actived'"><i class="fa-solid fa-check" style="color: #16a34a;"></i> {{ __('Activate') }}</a>
                        <button type="button" class="is-danger" x-show="visitor?.status === 'actived'" @click="panel = 'remove'"><i class="fa-solid fa-user-slash"></i> {{ __('Deactivate') }}</button>
                        <button type="button" class="is-danger" x-show="visitor && visitor.status !== 'actived'" @click="panel = 'remove'"><i class="fa-solid fa-trash"></i> {{ __('Delete') }}</button>
                    </div>
                </div>

                {{-- WhatsApp: ready messages, or one's own --}}
                <div x-show="panel === 'whatsapp'">
                    <div class="m-menu">
                        <template x-for="message in messages()" :key="message.text">
                            <a :href="whatsappUrl(message.text)" target="_blank" rel="noopener"><i :class="message.icon"></i> <span x-text="message.text"></span></a>
                        </template>
                    </div>
                    <div class="m-field mt-4">
                        <label class="m-label" for="visitor_message">{{ __('Or send your own message:') }}</label>
                        <textarea id="visitor_message" class="m-input" rows="3" x-model="text"></textarea>
                    </div>
                    <div class="m-sheet-foot">
                        <button type="button" class="m-btn" @click="panel = 'menu'">{{ __('Back') }}</button>
                        <a :href="whatsappUrl(text)" target="_blank" rel="noopener" class="m-btn is-primary"><i class="fa-brands fa-whatsapp"></i> {{ __('Send Message') }}</a>
                    </div>
                </div>

                {{-- Deactivate (active: the reason goes to the observation) or delete (inactive) --}}
                <form method="POST" :action="visitor?.destroy" x-show="panel === 'remove'">
                    @csrf
                    @method('DELETE')
                    <template x-if="visitor?.status === 'actived'">
                        <div class="m-field">
                            <p class="text-sm text-gray-700 mb-2">{{ __('The visitor becomes inactive; nothing is deleted.') }}</p>
                            <label class="m-label" for="visitor_reason">{{ __('Reason') }}</label>
                            <textarea id="visitor_reason" name="reason" class="m-input" rows="3"></textarea>
                        </div>
                    </template>
                    <template x-if="visitor && visitor.status !== 'actived'">
                        <p class="text-sm text-gray-700">{{ __('Are you sure you want to delete this visitor?') }}</p>
                    </template>
                    <div class="m-sheet-foot">
                        <button type="button" class="m-btn" @click="panel = 'menu'">{{ __('Cancel') }}</button>
                        <button type="submit" class="m-btn is-danger" x-text="visitor?.status === 'actived' ? @js(__('Deactivate')) : @js(__('Delete'))"></button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

@once
    <style>
        .visitor-facts { display: grid; margin: 0; }
        .visitor-facts > div { display: grid; grid-template-columns: 8rem minmax(0, 1fr); gap: .75rem; padding: .45rem 0; border-bottom: 1px solid #f3f4f6; }
        .visitor-facts > div:last-child { border-bottom: 0; }
        .visitor-facts dt { font-size: .82rem; font-weight: 600; color: #6b7280; }
        .visitor-facts dd { margin: 0; font-size: .9rem; color: #111827; overflow-wrap: anywhere; white-space: pre-line; }
    </style>
    <script>
        // The actions of the visitor picked (sent by its "⋯" button), in panels: menu, WhatsApp, remove.
        function visitorActions() {
            return {
                visitor: null,
                panel: 'menu',
                text: '',
                open(visitor) {
                    this.visitor = visitor;
                    this.panel = 'menu';
                    this.text = `A paz, ${visitor.sibling} ${visitor.firstName}! Tudo bem ?`;
                },
                close() { this.visitor = null; },
                subtitle() {
                    if (!this.visitor) return '';
                    return [this.visitor.phone, @js(\App\Enums\VisitorStatus::options())[this.visitor.status]].filter(Boolean).join(' · ');
                },
                messages() {
                    const { firstName } = this.visitor || {};
                    return [
                        { icon: 'fa-solid fa-church', text: `Foi uma alegria ter você conosco, ${firstName}! Volte sempre.` },
                        { icon: 'fa-solid fa-hands-praying', text: `Deus te fortaleça, ${firstName}!` },
                        { icon: 'fa-solid fa-heart-pulse', text: `Estamos orando por você, ${firstName}.` },
                        { icon: 'fa-solid fa-face-smile-beam', text: `Que Deus abençoe sua semana, ${firstName}!` },
                    ];
                },
                whatsappUrl(text) { return this.visitor?.whatsapp ? `${this.visitor.whatsapp}?text=${encodeURIComponent(text || '')}` : '#'; },
            };
        }
    </script>
@endonce
