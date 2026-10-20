{{--
    A lesson (Alpine variable `lesson`, see SongLesson::payload), of one type: a YouTube video or an audio (played with a
    stretch to repeat, public/js/lesson-player.js), a PDF or an image; with its notes.
    $saveUrl: JavaScript expression with the URL that saves the lesson's stretches (lessons tab, the team), or null.
    $contributeUrl: JavaScript expression with the URL where a musician adds a stretch (public page), or null.
    The stretches are the lesson's, for everyone. The colors follow the page's theme when it has one.
--}}
<div class="lesson-card" :class="{ 'is-open': $store.lessons.open === lesson.id }">
    {{-- Closed at first; opening one closes (and pauses) the one that was open --}}
    <button type="button" class="lesson-head" @click="$store.lessons.toggle(lesson.id)" :aria-expanded="$store.lessons.open === lesson.id">
        <i class="lesson-type" :class="{ youtube: 'fa-brands fa-youtube', audio: 'fa-solid fa-music', pdf: 'fa-solid fa-file-pdf', image: 'fa-solid fa-image' }[lesson.type]"></i>
        <strong class="lesson-title" x-text="lesson.title"></strong>
        <span class="lesson-badge" x-show="lesson.contributed" :title="@js(__('Sent by a musician on the setlist page'))"><i class="fa-solid fa-user"></i> {{ __('Musician') }}</span>
        <i class="fa-solid fa-chevron-down lesson-chevron"></i>
    </button>
    <div class="lesson-body" x-show="$store.lessons.open === lesson.id">
    <p class="lesson-notes" x-show="lesson.notes" x-text="lesson.notes"></p>

    <template x-if="lesson.type === 'image' && lesson.file_url">
        <a :href="lesson.file_url" target="_blank" rel="noopener"><img class="lesson-image" :src="lesson.file_url" :alt="lesson.file_name || lesson.title" loading="lazy"></a>
    </template>
    <template x-if="lesson.type === 'pdf' && lesson.file_url">
        <a class="lesson-file" :href="lesson.file_url" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i> <span x-text="lesson.file_name || @js(__('Open file'))"></span></a>
    </template>

    <template x-if="(lesson.type === 'youtube' && lesson.video_url) || (lesson.type === 'audio' && lesson.file_url)">
        <div class="lp" x-data="lessonPlayer({ lessonId: lesson.id, videoUrl: lesson.type === 'youtube' ? lesson.video_url : null, audioUrl: lesson.type === 'audio' ? lesson.file_url : null, loops: lesson.loops || [], saveUrl: {{ $saveUrl }}, contributeUrl: {{ $contributeUrl ?? 'null' }}, csrf: document.querySelector('meta[name=csrf-token]')?.content })">
            <div class="lp-video" x-show="kind === 'video'" x-ref="video">
                <button type="button" class="lp-poster" x-show="!started" @click="play()" :aria-label="@js(__('Play'))">
                    <img :src="`https://img.youtube.com/vi/${videoId}/hqdefault.jpg`" alt="" loading="lazy">
                    <i class="fa-solid fa-play"></i>
                </button>
            </div>
            <audio x-ref="audio" preload="metadata" :src="lesson.type === 'audio' ? lesson.file_url : null" x-show="false"></audio>
            <div class="lp-audio-name" x-show="kind === 'audio'"><i class="fa-solid fa-music"></i> <span x-text="lesson.file_name || @js(__('Audio'))"></span></div>

            {{-- Where it is: a tap jumps there; the A–B stretch is lit --}}
            <input type="range" class="lp-seek" min="0" step="0.1" x-effect="$el.max = duration || 0; $el.value = time" @input="seek(Number($event.target.value))"
                :disabled="!duration" :aria-label="@js(__('Position'))"
                :style="duration && a !== null ? `--lp-a: ${a / duration * 100}%; --lp-b: ${(b ?? a) / duration * 100}%;` : '--lp-a: 0%; --lp-b: 0%;'">

            <div class="lp-controls">
                <button type="button" class="lp-btn" @click="back(5)" :title="@js(__('Back 5 s'))" :aria-label="@js(__('Back 5 s'))"><i class="fa-solid fa-rotate-left"></i><span class="lp-small">5</span></button>
                <button type="button" class="lp-btn is-main" @click="toggle()" :aria-label="playing ? @js(__('Pause')) : @js(__('Play'))"><i class="fa-solid" :class="playing ? 'fa-pause' : 'fa-play'"></i></button>
                <button type="button" class="lp-btn" :class="{ 'is-on': looping }" @click="toggleLoop()" :disabled="a === null || b === null" :title="@js(__('Repeat the stretch'))" :aria-label="@js(__('Repeat the stretch'))"><i class="fa-solid fa-repeat"></i></button>
                <span class="lp-time"><span x-text="format(time)"></span><small x-text="`/ ${format(duration || null)}`"></small></span>
                <select class="lp-speed" :value="speed" @change="setSpeed(Number($event.target.value))" :title="@js(__('Speed'))" :aria-label="@js(__('Speed'))">
                    <template x-for="value in speeds" :key="value"><option :value="value" x-text="`${Math.round(value * 100)}%`" :selected="value === speed"></option></template>
                </select>
            </div>

            {{-- The stretch to repeat: A and B marked while it plays, nudged by a tenth of a second --}}
            <div class="lp-loop">
                <span class="lp-mark" :class="{ 'is-set': a !== null }">
                    <button type="button" class="lp-mark-key" @click="markA()" :title="@js(__('Mark the start of the stretch here'))">A</button>
                    <button type="button" class="lp-nudge" @click="nudge('a', -0.1)" :disabled="a === null" aria-label="−0.1 s">−</button>
                    <span class="lp-value" x-text="format(a)"></span>
                    <button type="button" class="lp-nudge" @click="nudge('a', 0.1)" :disabled="a === null" aria-label="+0.1 s">+</button>
                </span>
                <span class="lp-mark" :class="{ 'is-set': b !== null }">
                    <button type="button" class="lp-mark-key" @click="markB()" :disabled="a === null" :title="@js(__('Mark the end of the stretch here: it repeats from A'))">B</button>
                    <button type="button" class="lp-nudge" @click="nudge('b', -0.1)" :disabled="b === null" aria-label="−0.1 s">−</button>
                    <span class="lp-value" x-text="format(b)"></span>
                    <button type="button" class="lp-nudge" @click="nudge('b', 0.1)" :disabled="b === null" aria-label="+0.1 s">+</button>
                </span>
            </div>

            {{-- Saved stretches, for everyone: a tap repeats one. Removed only by the team (lessons tab); the ones saved by
                 a musician on the public page show it --}}
            <div class="lp-loops" x-show="loops.length">
                <template x-for="(loop, i) in loops" :key="`${loop.start}-${loop.end}-${i}`">
                    <span class="lp-chip" :class="{ 'is-on': isCurrentLoop(loop), 'is-mine': loop.contributed }" :title="loop.contributed ? @js(__('Saved by a musician')) : `${format(loop.start)} – ${format(loop.end)}`">
                        <button type="button" @click="playLoop(loop)"><i class="fa-solid" :class="loop.contributed ? 'fa-user' : 'fa-repeat'"></i> <span x-text="loop.label"></span></button>
                        <button type="button" class="lp-chip-x" x-show="savesLesson" @click="removeLoop(i)" :disabled="saving" :aria-label="@js(__('Remove'))">×</button>
                    </span>
                </template>
            </div>
            <div class="lp-save" x-show="a !== null && b !== null">
                <input type="text" x-model="loopLabel" maxlength="60" :placeholder="@js(__('Name (e.g. intro riff)'))" @keydown.enter.prevent="addLoop()" x-show="canSave">
                <button type="button" class="lp-btn is-save" @click="addLoop()" :disabled="saving" x-show="canSave"
                    :title="savesLesson ? @js(__('Saved in the lesson, for everyone')) : @js(__('Saved in the lesson for everyone, as yours (only the team can change or remove it)'))"><i class="fa-solid fa-bookmark"></i><span class="lp-label"> {{ __('Save stretch') }}</span></button>
                <button type="button" class="lp-btn" @click="clearLoop()" :title="@js(__('Clear the stretch'))" :aria-label="@js(__('Clear the stretch'))"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <span class="lp-error" x-show="message" x-text="message"></span>
        </div>
        </div>
    </template>
    </div>
</div>

@once
    <style>
        .lesson-card { --lc-surface: var(--surface, #fff); --lc-surface-2: var(--surface-2, #f3f4f6); --lc-border: var(--border, #e5e7eb); --lc-text: var(--text, #111827); --lc-muted: var(--muted, #6b7280); --lc-accent: var(--accent, var(--brand));
            padding: .75rem; border: 1px solid var(--lc-border); border-radius: .6rem; background: var(--lc-surface); color: var(--lc-text); }
        .lesson-head { display: flex; align-items: center; gap: .5rem; width: 100%; padding: 0; border: 0; background: none; color: inherit; font: inherit; text-align: left; cursor: pointer; }
        .lesson-badge { display: inline-flex; align-items: center; gap: .25rem; padding: .05rem .5rem; border-radius: 999px; background: var(--lc-surface-2); color: var(--lc-muted); font-size: .72rem; font-weight: 700; white-space: nowrap; }
        .lesson-chevron { margin-left: auto; color: var(--lc-muted); transition: transform .15s; }
        .lesson-card.is-open .lesson-chevron { transform: rotate(180deg); }
        .lesson-body { margin-top: .35rem; }
        .lesson-title { font-size: 1rem; }
        .lesson-type { color: var(--lc-muted); }
        .lesson-type.fa-youtube { color: var(--tx-dc2626); }
        .lp-chip.is-mine { border-style: dashed; }
        .lesson-notes { margin: .45rem 0 0; white-space: pre-wrap; font-size: .9rem; color: var(--lc-text); }
        .lesson-image { display: block; max-width: 100%; max-height: 24rem; margin-top: .6rem; border-radius: .4rem; border: 1px solid var(--lc-border); }
        .lesson-file { display: inline-flex; align-items: center; gap: .4rem; margin-top: .6rem; font-weight: 600; color: var(--lc-accent); }
        .lp { margin-top: .7rem; padding-top: .6rem; border-top: 1px dashed var(--lc-border); }
        .lp-video { position: relative; width: 100%; aspect-ratio: 16 / 9; background: #000; border-radius: .45rem; overflow: hidden; }
        .lp-video iframe { position: absolute; inset: 0; width: 100%; height: 100%; }
        .lp-poster { position: absolute; inset: 0; width: 100%; height: 100%; padding: 0; border: 0; background: #000; cursor: pointer; }
        .lp-poster img { width: 100%; height: 100%; object-fit: cover; opacity: .85; }
        .lp-poster i { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); padding: .9rem 1.1rem; border-radius: 999px; background: rgba(0, 0, 0, .65); color: #fff; font-size: 1.4rem; }
        .lp-audio-name { font-size: .85rem; color: var(--lc-muted); }
        .lp-seek { display: block; width: 100%; height: 1.6rem; margin: .45rem 0 0; accent-color: var(--lc-accent); background: transparent; cursor: pointer;
            --lp-track: linear-gradient(to right, var(--lc-surface-2) 0 var(--lp-a), color-mix(in srgb, var(--lc-accent) 45%, transparent) var(--lp-a) var(--lp-b), var(--lc-surface-2) var(--lp-b) 100%); }
        .lp-seek::-webkit-slider-runnable-track { height: .45rem; border-radius: 999px; background: var(--lp-track); border: 1px solid var(--lc-border); }
        .lp-seek::-moz-range-track { height: .45rem; border-radius: 999px; background: var(--lp-track); border: 1px solid var(--lc-border); }
        .lp-seek::-webkit-slider-thumb { margin-top: -.4rem; }
        .lp-controls { display: grid; grid-template-columns: auto auto auto 1fr auto; align-items: center; gap: .4rem; margin-top: .35rem; }
        .lp-loop { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem; margin-top: .45rem; }
        .lp-loops { display: flex; gap: .35rem; margin-top: .45rem; overflow-x: auto; scrollbar-width: none; padding-bottom: .1rem; }
        .lp-loops::-webkit-scrollbar { display: none; }
        .lp-save { display: flex; gap: .35rem; margin-top: .45rem; }
        .lp-save input { flex: 1; min-width: 0; min-height: 40px; padding: 0 .6rem; border: 1px solid var(--lc-border); border-radius: .5rem; background: var(--lc-surface); color: var(--lc-text); font-size: .9rem; }
        .lp-btn { display: inline-flex; align-items: center; justify-content: center; gap: .25rem; min-width: 40px; min-height: 40px; padding: 0 .6rem; border: 1px solid var(--lc-border); border-radius: .5rem; background: var(--lc-surface-2); color: var(--lc-text); font-weight: 700; font-size: .9rem; cursor: pointer; }
        .lp-btn:disabled { opacity: .45; cursor: default; }
        .lp-btn.is-main { min-width: 52px; background: var(--lc-accent); border-color: var(--lc-accent); color: #fff; font-size: 1rem; }
        .lp-btn.is-on { background: var(--lc-accent); border-color: var(--lc-accent); color: #fff; }
        .lp-small { font-size: .75rem; }
        .lp-time { display: flex; flex-direction: column; align-items: center; line-height: 1.1; font-variant-numeric: tabular-nums; font-size: .9rem; font-weight: 700; color: var(--lc-text); }
        .lp-time small { font-size: .7rem; font-weight: 500; color: var(--lc-muted); }
        .lp-speed { min-height: 40px; padding: 0 1.5rem 0 .5rem; border: 1px solid var(--lc-border); border-radius: .5rem; background-color: var(--lc-surface-2); color: var(--lc-text); font-size: .85rem; }
        .lp-mark { display: grid; grid-template-columns: 2.4rem 1.9rem 1fr 1.9rem; align-items: center; min-height: 40px; border: 1px solid var(--lc-border); border-radius: .5rem; background: var(--lc-surface-2); overflow: hidden; }
        .lp-mark.is-set { border-color: var(--lc-accent); }
        .lp-mark-key { align-self: stretch; border: 0; border-right: 1px solid var(--lc-border); background: var(--lc-surface); color: var(--lc-text); font-weight: 800; cursor: pointer; }
        .lp-mark.is-set .lp-mark-key { background: var(--lc-accent); color: #fff; }
        .lp-mark-key:disabled { opacity: .45; cursor: default; }
        .lp-nudge { align-self: stretch; border: 0; background: none; color: var(--lc-text); font-weight: 800; font-size: 1rem; cursor: pointer; }
        .lp-nudge:disabled { opacity: .3; cursor: default; }
        .lp-value { text-align: center; font-variant-numeric: tabular-nums; font-size: .82rem; color: var(--lc-muted); }
        .lp-chip { flex: none; display: inline-flex; align-items: center; border: 1px solid var(--lc-border); border-radius: 999px; background: var(--lc-surface-2); overflow: hidden; }
        .lp-chip button { min-height: 36px; padding: 0 .75rem; border: 0; background: none; color: var(--lc-text); font-size: .85rem; font-weight: 600; white-space: nowrap; cursor: pointer; }
        .lp-chip.is-on { border-color: var(--lc-accent); background: var(--lc-accent); }
        .lp-chip.is-on button { color: #fff; }
        .lp-chip .lp-chip-x { padding: 0 .6rem 0 .2rem; color: var(--lc-muted); }
        @media (max-width: 480px) {
            .lesson-card { padding: .65rem; }
            .lp-label { display: none; }
        }
        .lp-error { color: var(--tx-dc2626); font-size: .8rem; }
    </style>
@endonce
