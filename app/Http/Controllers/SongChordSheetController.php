<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChordSheetToStructureRequest;
use App\Http\Requests\ParseChordSheetRequest;
use App\Services\BuildStructureFromChordSheetService;
use App\Services\AlignSectionStartsService;
use App\Services\ParseChordSheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SongChordSheetController extends Controller
{
    /**
     * Interprets a pasted chord sheet (HTML <pre> or text) and returns the chord_sheet JSON for review in the form.
     */
    public function parse(
        ParseChordSheetRequest $request,
        ParseChordSheetService $parser,
        BuildStructureFromChordSheetService $structureBuilder,
    ): JsonResponse
    {
        $sheet = $parser->execute($request->validated('source'));

        $hasChords = collect($sheet['sections'])->flatMap(fn (array $section) => $section['lines'])
            ->contains(fn (string $line) => preg_match('/\[[^\]]+\]/u', $line) === 1);

        if (! $hasChords) {
            return response()->json(['message' => __('No chords or sections were found in the chord sheet.')], 422);
        }

        $lines = collect($sheet['sections'])->flatMap(fn (array $section) => $section['lines']);

        return response()->json([
            'chord_sheet' => ['sections' => $sheet['sections']],
            // Initial chord map, used by the form only when the song has no structure yet.
            'structure' => $structureBuilder->execute($sheet['sections']),
            'stats' => [
                'sections' => count($sheet['sections']),
                'lines'    => $lines->count(),
                'chords'   => $lines->sum(fn (string $line) => preg_match_all('/\[[^\]]+\]/u', $line)),
            ],
        ]);
    }

    /**
     * Chord map (structure) generated from the chord sheet currently in the form.
     */
    public function structure(
        ChordSheetToStructureRequest $request,
        BuildStructureFromChordSheetService $structureBuilder,
        AlignSectionStartsService $aligner,
    ): JsonResponse {
        // Section times already marked in the current structure are kept for the matching new blocks.
        $structure = $aligner->execute($structureBuilder->execute($request->sections()), $request->validated('structure'));

        return response()->json(['structure' => $structure]);
    }

    /**
     * Page with the "Send to ICD" bookmarklet, which imports a chord sheet page opened in the browser.
     */
    public function bookmarklet(): View
    {
        $source = file_get_contents(resource_path('js/bookmarklets/chord-sheet-import.js'));
        $source = str_replace(['__ICD_URL__', '__ICD_VERSION__'], [rtrim(url('/'), '/'), self::bookmarkletVersion()], $source);
        // Block comments and line breaks removed; the code is percent-encoded into a javascript: URL.
        $code = trim(preg_replace('/\s+/', ' ', preg_replace('#/\*.*?\*/#s', '', $source)));
        $bookmarklet = 'javascript:' . rawurlencode($code);

        return view('songs.bookmarklet', compact('bookmarklet'));
    }

    /**
     * Version of the bookmarklet code, so the song form can tell when an outdated bookmark is used.
     */
    public static function bookmarkletVersion(): string
    {
        return (string) filemtime(resource_path('js/bookmarklets/chord-sheet-import.js'));
    }
}
