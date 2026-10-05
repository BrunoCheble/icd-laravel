<?php

namespace App\Services;

/**
 * Gives blocks the "start" of the matching block of the chord map (structure), the single source of section
 * times. Blocks match in order by section type, allowing a few blocks that exist only in the chord map.
 */
class AlignSectionStartsService
{
    // How many chord map blocks may be skipped when looking for the next matching one.
    private const LOOKAHEAD = 2;

    public function execute(array $sections, mixed $structure): array
    {
        $map = array_values(array_map(fn ($block) => $this->toArray($block), $this->toArray($structure)));
        $pointer = 0;

        foreach ($sections as $index => $section) {
            $sections[$index]['start'] = null;

            for ($candidate = $pointer; $candidate < min(count($map), $pointer + self::LOOKAHEAD + 1); $candidate++) {
                if (($map[$candidate]['section'] ?? null) === ($section['section'] ?? null)) {
                    $sections[$index]['start'] = ($map[$candidate]['start'] ?? '') !== '' ? $map[$candidate]['start'] : null;
                    $pointer = $candidate + 1;
                    break;
                }
            }
        }

        return $sections;
    }

    private function toArray(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return json_decode(json_encode($value ?? []), true) ?? [];
    }
}
