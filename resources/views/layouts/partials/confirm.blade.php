{{-- The system's confirmation window, in place of the browser's confirm(): centred on computers, a bottom sheet on
     phones. A form with data-confirm="question" (or a submit button with it) asks before going; a form that deletes
     (DELETE) gets the red "Delete" button, others "Confirm" (data-confirm-button and data-confirm-title change them).
     Scripts ask with `await askConfirm('question')` or askConfirm({ message, title, button, danger }): true or false.
     Included once per page (the layout, and the pages with their own). --}}
@once
<div class="m-confirm" id="m-confirm" hidden role="alertdialog" aria-modal="true" aria-labelledby="m-confirm-title" aria-describedby="m-confirm-text">
    <div class="m-confirm-overlay" data-confirm-answer="no"></div>
    <div class="m-confirm-box">
        <span class="m-confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
        <h2 id="m-confirm-title"></h2>
        <p id="m-confirm-text"></p>
        <div class="m-confirm-foot">
            <button type="button" class="m-confirm-btn" data-confirm-answer="no">{{ __('Cancel') }}</button>
            <button type="button" class="m-confirm-btn is-go" data-confirm-answer="yes"></button>
        </div>
    </div>
</div>
<style>
    .m-confirm { position: fixed; inset: 0; z-index: 90; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .m-confirm[hidden] { display: none; }
    .m-confirm-overlay { position: absolute; inset: 0; background: rgba(17, 24, 39, .5); animation: m-confirm-fade .15s ease-out; }
    .m-confirm-box { position: relative; width: min(26rem, 100%); padding: 1.5rem 1.25rem 1.25rem; border-radius: 1rem; background: var(--bg-fff, #fff); box-shadow: 0 25px 50px rgba(0, 0, 0, .25); text-align: center; animation: m-confirm-in .18s ease-out; }
    .m-confirm-icon { display: inline-grid; place-items: center; width: 3rem; height: 3rem; margin-bottom: .75rem; border-radius: 999px; background: var(--bg-fef3c7, #fef3c7); color: var(--tx-92400e, #92400e); font-size: 1.2rem; }
    .m-confirm.is-danger .m-confirm-icon { background: var(--bg-fee2e2, #fee2e2); color: var(--tx-dc2626, #dc2626); }
    .m-confirm h2 { margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--tx-111827, #111827); }
    .m-confirm p { margin: .45rem 0 0; font-size: .9rem; line-height: 1.5; color: var(--tx-4b5563, #4b5563); white-space: pre-line; }
    .m-confirm-foot { display: flex; gap: .5rem; margin-top: 1.25rem; }
    .m-confirm-btn { flex: 1; min-height: 44px; padding: 0 1rem; border: 1px solid var(--bd-d1d5db, #d1d5db); border-radius: .6rem; background: var(--bg-fff, #fff); color: var(--tx-374151, #374151); font: inherit; font-size: .9rem; font-weight: 700; cursor: pointer; }
    .m-confirm-btn:hover { background: var(--bg-f3f4f6, #f3f4f6); }
    .m-confirm-btn:focus-visible { outline: 2px solid #f8a925; outline-offset: 2px; }
    .m-confirm-btn.is-go { border-color: #f8a925; background: #f8a925; color: #fff; }
    .m-confirm-btn.is-go:hover { background: #e0941a; }
    .m-confirm.is-danger .m-confirm-btn.is-go { border-color: #dc2626; background: #dc2626; }
    .m-confirm.is-danger .m-confirm-btn.is-go:hover { background: #b91c1c; }
    @media (max-width: 639px) {
        .m-confirm { align-items: flex-end; padding: 0; }
        .m-confirm-box { width: 100%; border-radius: 1.1rem 1.1rem 0 0; padding-bottom: max(1.25rem, env(safe-area-inset-bottom)); animation-name: m-confirm-up; }
    }
    @keyframes m-confirm-fade { from { opacity: 0; } }
    @keyframes m-confirm-in { from { opacity: 0; transform: scale(.96); } }
    @keyframes m-confirm-up { from { transform: translateY(100%); } }
</style>
<script>
    (() => {
        const box = document.getElementById('m-confirm');
        const title = document.getElementById('m-confirm-title');
        const text = document.getElementById('m-confirm-text');
        const go = box.querySelector('.is-go');
        const words = { title: @js(__('Are you sure?')), confirm: @js(__('Confirm')), delete: @js(__('Delete')) };
        let answer = null;
        let before = null;

        const close = (yes) => {
            if (!answer) return;
            box.hidden = true;
            const done = answer;
            answer = null;
            before?.focus?.();
            done(yes);
        };

        // Asks the question; resolves true (confirmed) or false (cancelled, Esc, a tap outside).
        window.askConfirm = (options) => {
            const { message = '', title: heading = '', button = '', danger = false } = typeof options === 'string' ? { message: options } : options;
            close(false);
            title.textContent = heading || words.title;
            text.textContent = message;
            text.hidden = !message;
            go.textContent = button || (danger ? words.delete : words.confirm);
            box.classList.toggle('is-danger', danger);
            box.querySelector('.m-confirm-icon i').className = 'fa-solid ' + (danger ? 'fa-trash' : 'fa-triangle-exclamation');
            before = document.activeElement;
            box.hidden = false;
            box.querySelector('[data-confirm-answer="no"].m-confirm-btn').focus();
            return new Promise((resolve) => { answer = resolve; });
        };

        box.addEventListener('click', (event) => {
            const choice = event.target.closest('[data-confirm-answer]')?.dataset.confirmAnswer;
            if (choice) close(choice === 'yes');
        });
        document.addEventListener('keydown', (event) => {
            if (!box.hidden && event.key === 'Escape') { event.preventDefault(); close(false); }
        });

        // Forms (and submit buttons) with data-confirm: asked first; confirmed, they go as usual.
        const ask = (form, source) => askConfirm({
            message: source.dataset.confirm,
            title: source.dataset.confirmTitle,
            button: source.dataset.confirmButton,
            danger: source.dataset.confirmDanger !== undefined
                ? source.dataset.confirmDanger !== 'false'
                : (form.querySelector('input[name="_method"]')?.value || '').toUpperCase() === 'DELETE',
        });
        document.addEventListener('submit', async (event) => {
            const form = event.target;
            const submitter = event.submitter;
            const source = submitter?.dataset.confirm !== undefined ? submitter : (form.dataset.confirm !== undefined ? form : null);
            if (!source || form.dataset.confirmed) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            if (!(await ask(form, source))) return;
            form.dataset.confirmed = '1';
            form.requestSubmit(submitter || undefined);
            delete form.dataset.confirmed;
        }, true);
    })();
</script>
@endonce
