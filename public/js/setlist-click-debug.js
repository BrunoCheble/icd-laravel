// Metronome diagnostics of the public setlist page, loaded only with ?debug=click in its address. It watches the page's
// component from outside and changes nothing in how the count works.
(() => {
    const MAX_ROWS = 300;
    // A click farther than this from its place (ms) is shown in amber / red.
    const WARN_MS = 10;
    const BAD_MS = 25;
    const rows = [];
    const pending = new Map();
    let d = null;
    let anchor = null;
    let previousWhen = null;
    let number = 0;
    let dirty = false;
    let lastTick = null;

    const ms = (value, plus = false) => (value === null || value === undefined ? '—' : `${plus && value > 0 ? '+' : ''}${value.toFixed(1)}`);
    const pulseMs = () => (60000 / d.liveBpm) * (d.liveGrid?.clickDouble ? 0.5 : 1);
    const blockAt = (tick) => (d.liveGrid?.blocks || []).filter(block => !block.marker && block.tick <= tick).at(-1) ?? null;
    const place = (tick) => {
        const size = (d.liveGrid?.perBar ?? 4) * 2;
        return `${Math.floor(tick / size) + 1}.${Math.floor((tick % size) / 2) + 1}${tick % 2 ? '½' : ''}`;
    };
    const restartGrid = () => { anchor = null; previousWhen = null; };

    function addRow(row) {
        row.number = ++number;
        rows.push(row);
        if (rows.length > MAX_ROWS) rows.shift();
        dirty = true;
        return row;
    }

    function recordClick(tick, delay) {
        const context = d.clickContext;
        if (!context) return;
        const when = (context.currentTime + Math.max(0, delay ?? 0)) * 1000;
        const pulse = pulseMs();
        // The pulse is measured from the second click: the first one may wait for the audio to wake up (its interval,
        // shown in the next row, tells how much).
        if (anchor === null && previousWhen !== null) anchor = when;
        const deviation = anchor === null ? null : when - (anchor + Math.round((when - anchor) / pulse) * pulse);
        const interval = previousWhen === null ? null : when - previousWhen;
        const block = blockAt(tick);
        const row = addRow({
            kind: 'click', tick, when, place: place(tick), block: block?.name ?? '—',
            newBlock: !!block && block.tick === tick,
            ideal: pulse, deviation, interval,
            // Clicks missing since the one before it.
            missed: interval === null ? 0 : Math.max(0, Math.round(interval / pulse) - 1),
            // Scheduled after its moment had passed (the page was too busy to do it ahead).
            late: delay !== undefined && delay < 0 ? -delay * 1000 : 0,
            cursor: null,
        });
        previousWhen = when;
        pending.set(tick, row);
    }

    function watchComponent() {
        const clickBeat = d.clickBeat;
        d.clickBeat = function (accent, delay) {
            if (this.live.click) {
                // Called with no delay: the click of the place being played (start, or a restart seen late).
                const tick = delay === undefined ? this.live.tick : this.live.clickNext;
                // The context may not exist yet on the very first click.
                const result = clickBeat.call(this, accent, delay);
                recordClick(tick, delay);
                return result;
            }
            return clickBeat.call(this, accent, delay);
        };

        const cancel = d.cancelScheduledClicks;
        d.cancelScheduledClicks = function () {
            const now = (this.clickContext?.currentTime ?? 0) * 1000;
            // Scheduled but taken back: they did not sound, so they do not count.
            for (let i = rows.length - 1; i >= 0 && rows[i].kind === 'click' && rows[i].when > now; i--) {
                pending.delete(rows[i].tick);
                rows.splice(i, 1);
                dirty = true;
            }
            const last = rows.filter(row => row.kind === 'click').at(-1);
            previousWhen = last && previousWhen !== null ? last.when : null;
            return cancel.call(this);
        };

        const startLive = d.startLive;
        d.startLive = function () {
            restartGrid();
            lastTick = null;
            addRow({ kind: 'note', text: `Início no compasso ${place(this.live.tick)}, ${Math.round(this.liveBpm)} BPM` });
            return startLive.call(this);
        };
        const setLiveBpm = d.setLiveBpm;
        d.setLiveBpm = function (bpm, save) {
            const running = this.live.running;
            const result = setLiveBpm.call(this, bpm, save);
            if (running) {
                restartGrid();
                addRow({ kind: 'note', text: `BPM mudou para ${Math.round(this.liveBpm)}: a pulsação recomeça aqui` });
            }
            return result;
        };
        const jump = d.jumpOnPulse;
        d.jumpOnPulse = function (tick) {
            const result = jump.call(this, tick);
            const block = blockAt(tick);
            addRow({ kind: 'note', text: `Salto para ${block?.name ?? '—'} (${place(tick)}): espera ${ms(Math.max(0, this.live.startedAt - performance.now()))} ms até ao próximo clique` });
            lastTick = tick;
            return result;
        };
        const stopLive = d.stopLive;
        d.stopLive = function () {
            const wasRunning = this.live.running;
            const result = stopLive.call(this);
            if (wasRunning) addRow({ kind: 'note', text: 'Parado' });
            return result;
        };

        // The cursor: how long after its beat's moment the page moved it.
        const follow = () => {
            if (d.live.running && d.live.tick !== lastTick) {
                lastTick = d.live.tick;
                const row = pending.get(lastTick);
                if (row && d.live.heldTick === null) {
                    const half = 30000 / d.liveBpm;
                    row.cursor = performance.now() - (d.live.startedAt + (lastTick - d.live.startTick) * half);
                    pending.delete(lastTick);
                    dirty = true;
                }
            }
            requestAnimationFrame(follow);
        };
        requestAnimationFrame(follow);
    }

    function tone(value) {
        const size = Math.abs(value ?? 0);
        return size >= BAD_MS ? 'bad' : size >= WARN_MS ? 'warn' : '';
    }

    function buildPanel() {
        const style = document.createElement('style');
        style.textContent = `
            .click-debug { position: fixed; left: .5rem; right: .5rem; bottom: 5rem; z-index: 2000; max-width: 46rem; max-height: 46vh; display: flex; flex-direction: column;
                background: rgba(17, 19, 24, .96); color: #f3f4f6; border: 1px solid #f5a524; border-radius: .6rem; font: 12px/1.35 ui-monospace, Menlo, monospace; }
            .click-debug.is-small .cd-body { display: none; }
            .cd-head { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; padding: .4rem .5rem; border-bottom: 1px solid #313743; }
            .cd-head strong { color: #f5a524; }
            .cd-head button { font: inherit; color: inherit; background: #252a33; border: 1px solid #313743; border-radius: .35rem; padding: .15rem .45rem; cursor: pointer; }
            .cd-spacer { flex: 1; }
            .cd-summary { padding: .35rem .5rem; color: #9aa3b2; border-bottom: 1px solid #313743; }
            .cd-summary b { color: #f3f4f6; font-weight: 700; }
            .cd-body { display: flex; flex-direction: column; min-height: 0; }
            .cd-rows { overflow: auto; min-height: 0; }
            .click-debug table { width: 100%; border-collapse: collapse; }
            .click-debug th { position: sticky; top: 0; background: #1b1e25; color: #9aa3b2; font-weight: 400; text-align: right; padding: .15rem .4rem; white-space: nowrap; }
            .click-debug td { text-align: right; padding: .1rem .4rem; white-space: nowrap; }
            .click-debug th:nth-child(2), .click-debug td:nth-child(2) { text-align: left; }
            .click-debug tr.is-block td { background: rgba(245, 165, 36, .14); }
            .click-debug td.note { text-align: left; color: #93c5fd; white-space: normal; }
            .click-debug .warn { color: #fbbf24; }
            .click-debug .bad { color: #f87171; font-weight: 700; }
        `;
        document.head.append(style);

        const panel = document.createElement('div');
        panel.className = 'click-debug';
        panel.innerHTML = `
            <div class="cd-head">
                <strong>Diagnóstico do clique</strong><span class="cd-spacer"></span>
                <button type="button" data-do="copy">Copiar</button>
                <button type="button" data-do="clear">Limpar</button>
                <button type="button" data-do="size">Minimizar</button>
            </div>
            <div class="cd-body">
                <div class="cd-summary"></div>
                <div class="cd-rows"><table><thead><tr>
                    <th title="Número da linha">#</th><th>Bloco</th><th title="Compasso.tempo">Lugar</th>
                    <th title="Distância do clique ao lugar certo da pulsação">Desvio ms</th>
                    <th title="Tempo desde o clique anterior">Intervalo ms</th>
                    <th title="Intervalo certo neste BPM">Ideal ms</th>
                    <th title="Quanto tempo depois do seu tempo o cursor andou">Cursor ms</th>
                </tr></thead><tbody></tbody></table></div>
            </div>`;
        document.body.append(panel);

        panel.addEventListener('click', event => {
            const action = event.target.dataset?.do;
            if (action === 'size') {
                event.target.textContent = panel.classList.toggle('is-small') ? 'Abrir' : 'Minimizar';
            } else if (action === 'clear') {
                rows.length = 0;
                pending.clear();
                restartGrid();
                dirty = true;
            } else if (action === 'copy') {
                copy(event.target);
            }
        });
        return panel;
    }

    function clickRows() { return rows.filter(row => row.kind === 'click'); }

    function summaryText() {
        const clicks = clickRows();
        const measured = clicks.filter(row => row.interval !== null);
        const context = d.clickContext;
        const latency = context
            ? `Atraso de saída de som: ${ms((context.outputLatency || context.baseLatency || 0) * 1000)} ms (fixo, depende do aparelho; não entra no desvio)`
            : 'Ligue o metrónomo e dê play para medir';
        if (!measured.length) return { clicks: clicks.length, html: latency };
        const size = values => (values.length ? Math.max(...values.map(Math.abs)) : null);
        const average = values => (values.length ? values.reduce((a, b) => a + Math.abs(b), 0) / values.length : null);
        const blocks = measured.filter(row => row.newBlock).map(row => row.deviation);
        const others = measured.filter(row => !row.newBlock).map(row => row.deviation);
        const cursor = clicks.filter(row => row.cursor !== null).map(row => row.cursor);
        const missed = clicks.reduce((total, row) => total + row.missed, 0);
        return {
            clicks: clicks.length,
            html: `Cliques <b>${clicks.length}</b> · Desvio máx.: troca de bloco <b>${ms(size(blocks))}</b> ms, resto <b>${ms(size(others))}</b> ms · médio <b>${ms(average(measured.map(row => row.deviation)))}</b> ms · Cursor máx. <b>${ms(size(cursor))}</b> ms · Perdidos <b>${missed}</b><br>${latency}`,
        };
    }

    function rowHtml(row) {
        if (row.kind === 'note') return `<tr><td>${row.number}</td><td class="note" colspan="6">${escapeHtml(row.text)}</td></tr>`;
        const missed = row.missed ? ` <span class="bad">(${row.missed} perdido${row.missed > 1 ? 's' : ''})</span>` : '';
        return `<tr class="${row.newBlock ? 'is-block' : ''}"><td>${row.number}</td><td>${escapeHtml(row.block)}</td><td>${row.place}</td>
            <td class="${tone(row.deviation)}">${ms(row.deviation, true)}</td>
            <td class="${row.interval === null ? '' : tone(row.interval - row.ideal * (row.missed + 1))}">${ms(row.interval)}${missed}</td>
            <td>${ms(row.ideal)}</td><td>${ms(row.cursor)}</td></tr>`;
    }

    function escapeHtml(text) {
        const node = document.createElement('span');
        node.textContent = text;
        return node.innerHTML;
    }

    function asText() {
        const lines = [['#', 'Bloco', 'Lugar', 'Desvio ms', 'Intervalo ms', 'Ideal ms', 'Cursor ms', 'Perdidos', 'Troca de bloco'].join('\t')];
        rows.forEach(row => {
            lines.push(row.kind === 'note' ? `${row.number}\t${row.text}`
                : [row.number, row.block, row.place, ms(row.deviation, true), ms(row.interval), ms(row.ideal), ms(row.cursor), row.missed, row.newBlock ? 'sim' : ''].join('\t'));
        });
        return lines.join('\n');
    }

    async function copy(button) {
        const text = asText();
        const label = button.textContent;
        try {
            await navigator.clipboard.writeText(text);
            button.textContent = 'Copiado';
        } catch (error) {
            // Without clipboard permission (an address that is not secure): shown to be copied by hand.
            window.prompt('Copie o texto:', text);
        }
        setTimeout(() => { button.textContent = label; }, 1500);
    }

    function start(component) {
        d = component;
        watchComponent();
        const panel = buildPanel();
        const body = panel.querySelector('tbody');
        const box = panel.querySelector('.cd-rows');
        const summary = panel.querySelector('.cd-summary');
        // Drawn a few times a second, away from the beats' work, so that measuring does not disturb what is measured.
        setInterval(() => {
            if (!dirty && !pending.size) return;
            dirty = false;
            const atEnd = box.scrollTop + box.clientHeight >= box.scrollHeight - 30;
            summary.innerHTML = summaryText().html;
            body.innerHTML = rows.map(rowHtml).join('');
            if (atEnd) box.scrollTop = box.scrollHeight;
        }, 400);
        summary.innerHTML = summaryText().html;
        window.clickDebug = { rows, asText };
    }

    const wait = () => {
        const component = document.querySelector('[x-data^="setlistPage"]')?._x_dataStack?.[0];
        if (component) start(component);
        else setTimeout(wait, 100);
    };
    wait();
})();
