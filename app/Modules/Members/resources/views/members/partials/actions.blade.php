{{-- The actions sheet of a member, opened by members::members.partials.actions-button ("member-actions" event):
     profile, edit, WhatsApp messages, what a pending member still has to send, new photo, activate, and deactivate
     (active, with the reason) or delete (pending). Included once on the page. --}}
@include('partials.ui')
<div x-data="memberActions()" @member-actions.window="open($event.detail)" @keydown.escape.window="close()">
    <template x-teleport="body">
        <div class="m-sheet" x-show="member" x-cloak role="dialog" aria-modal="true" :aria-label="member?.name">
            <div class="m-sheet-overlay" @click="close()"></div>
            <div class="m-sheet-box" x-show="member" x-transition.opacity>
                <div class="m-sheet-head">
                    <div>
                        <h2 x-text="member?.name"></h2>
                        <p x-text="subtitle()"></p>
                    </div>
                    <button type="button" class="m-btn is-ghost is-icon" @click="close()" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                </div>

                {{-- Everything that can be done --}}
                <div class="m-menu" x-show="panel === 'menu'">
                    <a :href="member?.show"><i class="fa-solid fa-id-card"></i> {{ __('View profile') }}</a>
                    <a :href="member?.edit"><i class="fa-solid fa-pen"></i> {{ __('Edit') }}</a>
                    <button type="button" x-show="member?.whatsapp" @click="panel = 'whatsapp'"><i class="fa-brands fa-whatsapp"></i> {{ __('WhatsApp') }}</button>
                    <button type="button" x-show="member?.pending.length" @click="panel = 'pending'">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ __('Pending Informations') }}
                        <span class="m-tag is-part" style="margin-left: auto;" x-text="member?.pending.length"></span>
                    </button>
                    <button type="button" @click="$refs.photo.click()"><i class="fa-solid fa-camera"></i> {{ __('Change photo') }}</button>
                    <a :href="member?.activate" x-show="member && member.status !== 'actived'"><i class="fa-solid fa-check" style="color: var(--tx-16a34a);"></i> {{ __('Activate') }}</a>
                    <button type="button" class="is-danger" x-show="member?.status === 'actived'" @click="panel = 'remove'"><i class="fa-solid fa-user-slash"></i> {{ __('Deactivate') }}</button>
                    <button type="button" class="is-danger" x-show="member?.status === 'pending'" @click="panel = 'remove'"><i class="fa-solid fa-trash"></i> {{ __('Delete') }}</button>
                </div>
                <form method="POST" :action="member?.photo" enctype="multipart/form-data" x-ref="photoForm" class="hidden">
                    @csrf
                    @method('PATCH')
                    <input type="file" name="photo" accept="image/*" x-ref="photo" @change="$refs.photoForm.submit()">
                </form>

                {{-- WhatsApp: ready messages, or one's own --}}
                <div x-show="panel === 'whatsapp'">
                    <div class="m-menu">
                        <template x-for="message in messages()" :key="message.text">
                            <a :href="whatsappUrl(message.text)" target="_blank" rel="noopener"><i :class="message.icon"></i> <span x-text="message.text"></span></a>
                        </template>
                    </div>
                    <div class="m-field mt-4">
                        <label class="m-label" for="member_message">{{ __('Or send your own message:') }}</label>
                        <textarea id="member_message" class="m-input" rows="3" x-model="text"></textarea>
                    </div>
                    <div class="m-sheet-foot">
                        <button type="button" class="m-btn" @click="panel = 'menu'">{{ __('Back') }}</button>
                        <a :href="whatsappUrl(text)" target="_blank" rel="noopener" class="m-btn is-primary"><i class="fa-brands fa-whatsapp"></i> {{ __('Send Message') }}</a>
                    </div>
                </div>

                {{-- What a member registered on the site still has to send --}}
                <div x-show="panel === 'pending'">
                    <ul class="member-pending">
                        <template x-for="item in member?.pending || []" :key="item">
                            <li><i class="fa-solid fa-circle-exclamation"></i> <span x-text="item"></span></li>
                        </template>
                    </ul>
                    <div class="m-field mt-4" x-show="member?.complete">
                        <span class="m-label">{{ __('Link for the member to fill it in') }}</span>
                        <div class="flex gap-2">
                            <input type="text" class="m-input" readonly :value="member?.complete" @focus="$el.select()">
                            <button type="button" class="m-btn" @click="navigator.clipboard?.writeText(member.complete); copied = true; setTimeout(() => copied = false, 2000)">
                                <i class="fa-solid" :class="copied ? 'fa-check' : 'fa-copy'"></i> <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))"></span>
                            </button>
                        </div>
                        <p class="m-help">{{ __('Personal link, valid for :count days.', ['count' => \App\Modules\Members\Services\GetRegistrationLinkService::DAYS]) }}</p>
                    </div>
                    <div class="m-field mt-4" x-show="member?.whatsapp">
                        <label class="m-label" for="member_pending_message">{{ __('Message') }}</label>
                        <textarea id="member_pending_message" class="m-input" rows="7" x-model="pendingText"></textarea>
                    </div>
                    <div class="m-sheet-foot">
                        <button type="button" class="m-btn" @click="panel = 'menu'">{{ __('Back') }}</button>
                        <a x-show="member?.whatsapp" :href="whatsappUrl(pendingText)" target="_blank" rel="noopener" class="m-btn is-primary"><i class="fa-brands fa-whatsapp"></i> {{ __('Ask by WhatsApp') }}</a>
                    </div>
                </div>

                {{-- Deactivate (active: the reason goes to the notes) or delete (pending) --}}
                <form method="POST" :action="member?.destroy" x-show="panel === 'remove'">
                    @csrf
                    @method('DELETE')
                    <template x-if="member?.status === 'actived'">
                        <div class="m-field">
                            <p class="text-sm text-gray-700 mb-2">{{ __('The member becomes inactive; nothing is deleted.') }}</p>
                            <label class="m-label" for="member_reason">{{ __('Reason') }}</label>
                            <textarea id="member_reason" name="reason" class="m-input" rows="3" placeholder="{{ __('Why is the member leaving?') }}"></textarea>
                        </div>
                    </template>
                    <template x-if="member?.status === 'pending'">
                        <p class="text-sm text-gray-700">{{ __('Are you sure you want to delete this member?') }} {{ __('The registration was never accepted; it is deleted for good.') }}</p>
                    </template>
                    <div class="m-sheet-foot">
                        <button type="button" class="m-btn" @click="panel = 'menu'">{{ __('Cancel') }}</button>
                        <button type="submit" class="m-btn is-danger" x-text="member?.status === 'pending' ? @js(__('Delete')) : @js(__('Deactivate'))"></button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

@once
    <style>
        .member-pending { display: grid; gap: .4rem; margin: 0; padding: 0; list-style: none; }
        .member-pending li { display: flex; align-items: flex-start; gap: .6rem; padding: .55rem .75rem; border-radius: .6rem; background: var(--bg-fffbeb); color: var(--tx-78350f); font-size: .9rem; }
        .member-pending i { margin-top: .2rem; color: var(--tx-d97706); }
    </style>
    <script>
        // The actions of the member picked (sent by its "⋯" button), in panels: menu, WhatsApp, pending, remove.
        function memberActions() {
            return {
                member: null,
                panel: 'menu',
                text: '',
                pendingText: '',
                copied: false,
                open(member) {
                    this.member = member;
                    this.panel = 'menu';
                    this.text = `A paz, ${member.sibling} ${member.firstName}! Tudo bem ?`;
                    const why = member.status === 'pending' ? 'Para concluir o seu registo como membro da igreja' : 'Para mantermos o seu registo na igreja atualizado';
                    this.pendingText = `A paz, ${member.sibling} ${member.firstName}! Tudo bem ? ${why}, faltam-nos alguns dados:\n`
                        + member.pending.map(item => `- ${item};`).join('\n')
                        + (member.complete ? `\n\nPode preenchê-los aqui, é rápido: ${member.complete}` : '');
                },
                close() { this.member = null; },
                subtitle() {
                    if (!this.member) return '';
                    return [this.member.phone, @js(\App\Enums\MembershipStatus::options())[this.member.status]].filter(Boolean).join(' · ');
                },
                messages() {
                    const { sibling, firstName } = this.member || {};
                    return [
                        { icon: 'fa-solid fa-gift', text: `Feliz aniversário, ${sibling} ${firstName}!` },
                        { icon: 'fa-solid fa-hands-praying', text: `Deus te fortaleça, ${firstName}!` },
                        { icon: 'fa-solid fa-heart-pulse', text: `Estamos orando por você, ${firstName}.` },
                        { icon: 'fa-solid fa-face-smile-beam', text: `Que Deus abençoe sua semana, ${firstName}!` },
                    ];
                },
                whatsappUrl(text) { return this.member?.whatsapp ? `${this.member.whatsapp}?text=${encodeURIComponent(text || '')}` : '#'; },
            };
        }
    </script>
@endonce
