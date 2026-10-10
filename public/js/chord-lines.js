/*
 * Display lines of a chord map (structure) section and the kind of each block (for its color).
 * Shared by the public setlist page and the admin song pages. Requires chord-transposer.js.
 *
 * A "|" in a section's chords is a line break marked by hand; a section without one is split automatically.
 * Passing chords (a section's "passing": positions of its chords, line breaks not counted) are shown but do not
 * count when looking for repetitions: "C G/B Am F" with G/B passing repeats "C Am F".
 */

const LINE_BREAK = '|';

// Kind of block by its name, in Portuguese or English ("Refrão", "CHORUS", "PRE_CHORUS", "Primeira Parte"...),
// used to give each kind of block its color: intro, verse, prechorus, chorus, bridge, instrumental, ending or other.
function blockType(name) {
    const text = String(name ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/_/g, ' ');
    if (/pre[\s-]*(chorus|refrao|coro)/.test(text)) return 'prechorus';
    if (/chorus|refrao|\bcoro\b/.test(text)) return 'chorus';
    if (/bridge|ponte/.test(text)) return 'bridge';
    if (/intro/.test(text)) return 'intro';
    if (/final|outro|ending|\btag\b|\bfim\b|encerramento/.test(text)) return 'ending';
    if (/solo|riff|interl|instrument|passagem|transi/.test(text)) return 'instrumental';
    if (/verse|verso|part|estrofe/.test(text)) return 'verse';
    return 'other';
}

// Chords of a structure section, without line breaks.
const chordsOnly = (chords) => chords.filter(chord => chord !== LINE_BREAK);
const hasLineBreaks = (chords) => chords.includes(LINE_BREAK);

// Steps: consecutive variations of the same family (E4 E, Bm7 Bm/A) form one step of at most 2 chords,
// while a repeated identical chord (G G) is two steps. `first` is the position of the first chord.
// A passing chord joins the step before it (or the next one, at the start), so it never makes a step of its own.
function chordSteps(chords, first = 0, passing = new Set()) {
    const steps = [];
    let leading = [];
    chords.forEach((chord, index) => {
        const position = first + index;
        const last = steps[steps.length - 1];
        if (passing.has(position)) {
            if (last) {
                last.chords.push(chord);
                last.positions.push(position);
            } else {
                leading.push({ chord, position });
            }
            return;
        }
        const family = ChordTransposer.chordFamily(chord) ?? chord;
        const variation = last && last.family === family && last.lastMain !== chord && last.mains < 2
            && !passing.has(last.positions[last.positions.length - 1]);
        if (variation) {
            last.chords.push(chord);
            last.positions.push(position);
            last.lastMain = chord;
            last.mains++;
        } else {
            steps.push({
                family,
                chords: [...leading.map(item => item.chord), chord],
                positions: [...leading.map(item => item.position), position],
                lastMain: chord,
                mains: 1,
            });
            leading = [];
        }
    });
    // Only passing chords: they still need a step to be shown.
    if (leading.length) {
        steps.push({ family: ChordTransposer.chordFamily(leading[0].chord) ?? leading[0].chord, chords: leading.map(item => item.chord), positions: leading.map(item => item.position), mains: 0 });
    }
    return steps;
}

// Splits a section's chords into display lines. Each line is a list of steps, each step
// { family, chords, positions } where positions are the chord indexes in the section (line breaks not counted).
// A section with line breaks ("|") is shown exactly as marked; otherwise the lines are found automatically.
// `songCycles`: cycles (lists of families) found anywhere in the song, also recognized when played once here.
// `passing`: positions of the section's passing chords.
function chordSegments(chords, songCycles = [], passing = []) {
    const passingSet = new Set(passing || []);
    if (!hasLineBreaks(chords)) return automaticSegments(chords, songCycles, passingSet);

    const lines = [];
    let line = [];
    let position = 0;
    const close = () => {
        if (line.length) lines.push(chordSteps(line, position - line.length, passingSet));
        line = [];
    };
    chords.forEach(chord => {
        if (chord === LINE_BREAK) return close();
        line.push(chord);
        position++;
    });
    close();

    lines.cycles = [];
    return lines;
}

// Automatic lines:
// - Chords are compared by family (Em = Em7, D = D/F#), in steps (see chordSteps).
// - A repeated sequence (cycle) gets one line per repetition, whatever its size.
// - A repetition comes first; without one, a phrase that starts like the last cycle follows it while
//   the steps match (G D after the cycle G D Em C).
// - Chords without repetition end a stretch where their opening (3+ steps) is played again later;
//   each stretch is split every 4 steps, and a single step left over stays on the line before.
function automaticSegments(chords, songCycles, passing) {
    const STEPS_PER_LINE = 4;
    const steps = chordSteps(chords, 0, passing);

    const families = (from, length) => steps.slice(from, from + length).map(step => step.family).join('\u0000');
    const same = (a, b, length) => families(a, length) === families(b, length);
    const chordsOf = (list) => [...list];
    // Shortest sequence (2+ steps) starting at `start` that is immediately repeated.
    const repeatedLength = (start) => {
        for (let length = 2; start + length * 2 <= steps.length; length++) {
            if (same(start, start + length, length)) return length;
        }
        return 0;
    };
    // How many steps from `start` follow the last cycle.
    const followsCycle = (start) => {
        let count = 0;
        while (cycle && count < cycle.length && start + count < steps.length && steps[start + count].family === cycle[count]) count++;
        return count;
    };

    const lines = [];
    const foundCycles = [];
    let pending = [];
    let cycle = null;
    const opening = (list, size) => list.slice(0, size).map(step => step.family).join('\u0000');
    // Split every 4 steps; a single step left over stays on the line before.
    const pushChunks = (list) => {
        const chunks = [];
        for (let i = 0; i < list.length; i += STEPS_PER_LINE) chunks.push(list.slice(i, i + STEPS_PER_LINE));
        if (chunks.length > 1 && chunks[chunks.length - 1].length === 1) chunks[chunks.length - 2].push(...chunks.pop());
        chunks.forEach(chunk => lines.push(chordsOf(chunk)));
    };
    // Chords without repetition: a stretch ends where its opening (3+ steps) is played again later, and
    // each stretch is split every 4 steps, so the same passage wraps the same way every time.
    const flush = () => {
        if (!pending.length) return;
        if (pending.length === 1 && lines.length) {
            lines[lines.length - 1].push(...chordsOf(pending));
            pending = [];
            return;
        }

        let rest = pending;
        while (rest.length) {
            let boundary = 0;
            for (let size = Math.min(8, Math.floor(rest.length / 2)); size >= 3 && !boundary; size--) {
                for (let at = size; at + size <= rest.length; at++) {
                    if (opening(rest.slice(at), size) === opening(rest, size)) {
                        boundary = at;
                        break;
                    }
                }
            }
            if (boundary) {
                pushChunks(rest.slice(0, boundary));
                rest = rest.slice(boundary);
                continue;
            }

            pushChunks(rest);
            rest = [];
        }
        pending = [];
    };

    // A cycle of the song (3+ steps) played in full at `start`.
    const songCycleAt = (start) => songCycles.find(known => known.length > 2
        && start + known.length <= steps.length
        && known.every((family, i) => steps[start + i].family === family));

    let index = 0;
    while (index < steps.length) {
        const known = songCycleAt(index);
        if (known && repeatedLength(index) === 0) {
            flush();
            cycle = known;
            lines.push(chordsOf(steps.slice(index, index + known.length)));
            index += known.length;
            continue;
        }

        // A real repetition comes first; otherwise a phrase following the last cycle; otherwise pending.
        const length = repeatedLength(index);
        if (!length) {
            const phrase = followsCycle(index);
            if (phrase >= 2) {
                flush();
                lines.push(chordsOf(steps.slice(index, index + phrase)));
                index += phrase;
            } else {
                pending.push(steps[index++]);
            }
            continue;
        }
        flush();
        cycle = steps.slice(index, index + length).map(step => step.family);
        foundCycles.push(cycle);
        const start = index;
        while (index + length <= steps.length && same(start, index, length)) {
            lines.push(chordsOf(steps.slice(index, index + length)));
            index += length;
        }
    }
    flush();

    lines.cycles = foundCycles;
    return lines;
}

// Cycles repeated inside any section of the song; `passingLists`: each section's passing chords.
function songCyclesOf(chordLists, passingLists = []) {
    return chordLists.flatMap((chords, section) => chordSegments(chords, [], passingLists[section]).cycles);
}

// ---- Versions of a song's map for an instrument (same blocks and times, its own chords) ----

// A version's map in the song's key (`toKey`): it is stored in the key it was saved in.
function versionStructure(version, toKey) {
    const from = version?.musical_key || toKey;
    return (Array.isArray(version?.structure) ? version.structure : []).map(block => (block && typeof block === 'object' && Array.isArray(block.chords)
        ? { ...block, chords: from && toKey && from !== toKey ? ChordTransposer.transposeChords(block.chords.map(String), from, toKey) : block.chords.map(String) }
        : block));
}

// The chord sheet with the chords of another map of the same blocks and times (an instrument's version): chord n of
// the sheet is chord n of the base map (when the sheet has the base map's chords). Each chord of the sheet takes the
// version's chord sounding when it starts (by the chords' beats); when that is still the chord before it in the
// version (e.g. G4 G C/G played as one G on the bass), its mark goes away and the lyrics stay. Without beats, a block
// of the version with as many chords as in the base gives its chords in order; the others keep the sheet's.
function sheetWithChords(sections, baseStructure, structure) {
    const listOf = (block) => chordsOnly(Array.isArray(block?.chords) ? block.chords.map(String) : []);
    const beatsOf = (block, count) => {
        const durations = Array.isArray(block?.durations) ? block.durations.map(Number) : [];
        return count && durations.length === count && durations.every(value => value > 0) ? durations : null;
    };
    const base = (baseStructure || []).map(listOf);
    const other = (structure || []).map(listOf);
    const lineChords = (line) => (/^\s*\{/.test(String(line)) ? [] : [...String(line).matchAll(/\[([^\]]+)\]/g)].map(match => match[1]));
    const sheetChords = (sections || []).flatMap(section => (section.lines || []).flatMap(lineChords));
    if (JSON.stringify(sheetChords) !== JSON.stringify(base.flat())) return sections;
    // The chord for each chord of the sheet; null: the version is still on the chord before (no mark).
    const names = [];
    base.forEach((list, b) => {
        const own = other[b] || [];
        const baseBeats = beatsOf(baseStructure[b], list.length);
        const ownBeats = beatsOf(structure[b], own.length);
        if (baseBeats && ownBeats) {
            const starts = [];
            ownBeats.reduce((time, beats) => { starts.push(time); return time + beats; }, 0);
            let time = 0, last = -1;
            list.forEach((chord, k) => {
                let index = 0;
                while (index + 1 < starts.length && starts[index + 1] <= time + 1e-6) index++;
                names.push(index === last ? null : own[index]);
                last = index;
                time += baseBeats[k];
            });
        } else {
            list.forEach((chord, k) => names.push(own.length === list.length ? own[k] : chord));
        }
    });
    let n = 0;
    // A line of chords only that lost some marks is tidied (one space between chords).
    const tidy = (line) => (line.replace(/\[[^\]]+\]/g, '').trim() === '' ? line.trim().replace(/\s+/g, ' ') : line);
    return (sections || []).map(section => ({
        ...section,
        lines: (section.lines || []).map(line => (/^\s*\{/.test(String(line)) ? line : tidy(String(line).replace(/\[([^\]]+)\]/g, () => {
            const name = names[n++];
            return name === null ? '' : `[${name}]`;
        })))),
    }));
}
