/**
 * Chord transposition by interval (letter steps + semitones), so note spelling follows the target key.
 * e.g. G -> A: D/F# -> E/G#   |   C -> Eb: Bb -> Db   |   E -> F#: B/D# -> C#/F (E# written as F)
 *
 * Tokens that are not chord symbols (N.C., x2, %, ...) are returned unchanged.
 */
(function (global) {
    const LETTERS = ['C', 'D', 'E', 'F', 'G', 'A', 'B'];
    const NATURAL_PC = { C: 0, D: 2, E: 4, F: 5, G: 7, A: 9, B: 11 };
    const ACCIDENTALS = { '': 0, '#': 1, '♯': 1, '##': 2, 'x': 2, 'b': -1, '♭': -1, 'bb': -2 };
    const SHARP_NAMES = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    // Preferred names for enharmonic notes that fall on a natural note.
    const PREFERRED_NAMES = { 'Cb': 'B', 'B#': 'C', 'Fb': 'E', 'E#': 'F' };
    const FLAT_NAMES = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'Gb', 'G', 'Ab', 'A', 'Bb', 'B'];

    const NOTE = '([A-G])(##|bb|#|b|x|♯|♭)?';
    const KEY_PATTERN = new RegExp(`^${NOTE}(m)?$`);
    // Optional wrapping bracket, root, quality/extensions, optional slash bass note.
    const CHORD_PATTERN = new RegExp(`^([(\\[]?)${NOTE}(.*?)(?:/${NOTE})?([)\\]]?)$`);

    const mod = (value, n) => ((value % n) + n) % n;

    function note(letter, accidental = '') {
        return { letter, accidental, pc: mod(NATURAL_PC[letter] + ACCIDENTALS[accidental], 12) };
    }

    /**
     * "Bm" -> { letter: 'B', accidental: '', pc: 11, minor: true }; null when not a key.
     */
    function parseKey(key) {
        const match = KEY_PATTERN.exec(String(key ?? '').trim());
        if (!match) return null;
        return { ...note(match[1], match[2] ?? ''), minor: match[3] === 'm' };
    }

    /**
     * Whether a key is written with flats, based on its tonic spelling or key signature.
     */
    function usesFlats(key) {
        if (key.accidental.includes('b') || key.accidental === '♭') return true;
        if (key.accidental.includes('#') || key.accidental === '♯' || key.accidental === 'x') return false;
        const majorPc = key.minor ? mod(key.pc + 3, 12) : key.pc;
        const sharps = mod(majorPc * 7, 12); // position on the circle of fifths
        return sharps > 6;
    }

    function interval(fromKey, toKey) {
        return {
            letters: mod(LETTERS.indexOf(toKey.letter) - LETTERS.indexOf(fromKey.letter), 7),
            semitones: mod(toKey.pc - fromKey.pc, 12),
        };
    }

    const preferredName = (name) => PREFERRED_NAMES[name] ?? name;

    function transposeNote(letter, accidental, step, flats) {
        const source = note(letter, accidental);
        const targetLetter = LETTERS[mod(LETTERS.indexOf(letter) + step.letters, 7)];
        const targetPc = mod(source.pc + step.semitones, 12);
        const offset = mod(targetPc - NATURAL_PC[targetLetter] + 6, 12) - 6;

        if (offset === 0) return targetLetter;
        if (offset === 1) return preferredName(targetLetter + '#');
        if (offset === -1) return preferredName(targetLetter + 'b');
        // Double accidentals (only from chromatic notes): use the simpler enharmonic name.
        return (flats ? FLAT_NAMES : SHARP_NAMES)[targetPc];
    }

    /**
     * Transposes one chord symbol from one key to another. Unknown tokens are returned as-is.
     */
    function transposeChord(chord, fromKey, toKey) {
        const from = typeof fromKey === 'string' ? parseKey(fromKey) : fromKey;
        const to = typeof toKey === 'string' ? parseKey(toKey) : toKey;
        const text = String(chord);

        const match = CHORD_PATTERN.exec(text);
        if (!match) return text;

        // Without a valid pair of keys the chord is not moved, only Cb/B#/Fb/E# get their preferred names.
        const step = from && to ? interval(from, to) : { letters: 0, semitones: 0 };

        const [, open, rootLetter, rootAccidental = '', quality, bassLetter, bassAccidental = '', close] = match;
        const flats = to ? usesFlats(to) : false;
        const root = transposeNote(rootLetter, rootAccidental, step, flats);
        const bass = bassLetter ? '/' + transposeNote(bassLetter, bassAccidental, step, flats) : '';

        return open + root + quality + bass + close;
    }

    /**
     * Chord "family" used to spot repeated progressions: root pitch plus basic quality, ignoring
     * extensions and bass notes. "Em7" and "Em" -> "4m"; "D/F#" and "D" -> "2"; "C7M" and "C9" -> "0".
     * Returns null for tokens that are not chords.
     */
    function chordFamily(chord) {
        const match = CHORD_PATTERN.exec(String(chord).trim());
        if (!match) return null;

        const [, , rootLetter, rootAccidental = '', quality] = match;
        const root = note(rootLetter, rootAccidental).pc;
        let type = '';

        if (/^(dim|°|º)/.test(quality)) type = 'dim';
        else if (/^(aug|\+)/.test(quality) || /5\+|\+5|\(#5\)/.test(quality)) type = 'aug';
        else if (/^(maj|M)/.test(quality)) type = '';
        else if (/^(m|min)/.test(quality)) type = 'm';

        return root + type;
    }

    function transposeChords(chords, fromKey, toKey) {
        return (chords ?? []).map(chord => transposeChord(chord, fromKey, toKey));
    }

    const api = { parseKey, transposeChord, transposeChords, chordFamily };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = api;
    } else {
        global.ChordTransposer = api;
    }
})(typeof window !== 'undefined' ? window : globalThis);
