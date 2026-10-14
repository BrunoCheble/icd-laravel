<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ImportSongChordSheetService
{
    public function __construct(
        private ParseChordSheetService $parser,
        private BuildStructureFromChordSheetService $structure,
        private CreateSongBarsService $bars,
    ) {
    }

    /**
     * Fills a song that has no chord sheet yet (e.g. pre-registered from a schedule) with the one sent by "Send to
     * ICD": the chord sheet, the chord map made from it and its bars (4/4, one bar per chord), plus the key and video
     * when the song has none. A song that already has a chord sheet is refused (it is replaced on its form instead).
     */
    public function execute(Song $song, string $sheet, ?string $key = null, ?string $youtube = null): Song
    {
        if (! empty($song->chord_sheet['sections'] ?? null)) {
            throw new ConflictHttpException(__('This song already has a chord sheet.'));
        }

        $sections = $this->parser->execute($sheet)['sections'];
        $hasChords = collect($sections)->flatMap(fn (array $section) => $section['lines'])
            ->contains(fn (string $line) => preg_match('/\[[^\]]+\]/u', $line) === 1);
        if (! $hasChords) {
            throw ValidationException::withMessages(['sheet' => __('No chords or sections were found in the chord sheet.')]);
        }

        $song->chord_sheet = ['sections' => $sections];
        $song->structure = json_decode(json_encode($this->structure->execute($sections)));
        if (! $song->musical_key && $key) {
            $song->musical_key = $key;
        }
        if (! $song->youtube_url && $youtube) {
            $song->youtube_url = $youtube;
        }
        $song->save();

        return $this->bars->execute($song);
    }
}
