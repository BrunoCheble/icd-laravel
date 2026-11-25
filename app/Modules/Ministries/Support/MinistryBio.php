<?php

namespace App\Modules\Ministries\Support;

/**
 * A text organised in sections, as JSON: a pastor's biography and a ministry's page on the site.
 *
 * {"layout": "sections" | "simple",           sections: with an index to jump to each; simple: one after another
 *  "sections": [{"title": "Formação",          (optional: a section without a title opens the text, out of the index)
 *                "blocks": [{"type": "text", "text": "…"},
 *                           {"type": "list", "ordered": false,
 *                            "items": [{"title": "Licenciado em Letras", "text": "pela FURB", "url": "", "sub": ["…", "…"]}]},
 *                           {"type": "table", "head": true, "rows": [["Função", "Responsável"], ["…", "…"]]},
 *                           {"type": "link", "text": "Vídeo sobre o ministério:", "label": "Assista no YouTube", "url": "https://…"}]}]}
 *
 * Texts are plain: **between two pairs of asterisks** is bold (the site shows it so) and a blank line starts a new
 * paragraph. A list can be numbered ("ordered") and its items can have sub-items ("sub"); a table's first row is its
 * header when "head" is true. clean() keeps only that shape (plain texts, http(s) addresses); an empty one is null.
 */
class MinistryBio
{
    public const LAYOUTS = ['sections', 'simple'];

    private const MAX_SECTIONS = 30;
    private const MAX_BLOCKS = 40;
    private const MAX_ITEMS = 60;
    private const MAX_SUB = 20;
    private const MAX_ROWS = 40;
    private const MAX_COLUMNS = 8;

    public static function clean(mixed $bio): ?array
    {
        if (is_string($bio)) {
            $bio = json_decode($bio, true);
        }
        if (! is_array($bio)) {
            return null;
        }

        $sections = [];
        foreach (array_slice((array) ($bio['sections'] ?? []), 0, self::MAX_SECTIONS) as $section) {
            $blocks = [];
            foreach (array_slice((array) ($section['blocks'] ?? []), 0, self::MAX_BLOCKS) as $block) {
                $block = self::block((array) $block);
                if ($block) {
                    $blocks[] = $block;
                }
            }
            $title = self::text($section['title'] ?? null, 120);
            if ($blocks || $title !== '') {
                $sections[] = ['title' => $title, 'blocks' => $blocks];
            }
        }

        return $sections ? [
            'layout' => in_array($bio['layout'] ?? null, self::LAYOUTS, true) ? $bio['layout'] : 'sections',
            'sections' => $sections,
        ] : null;
    }

    private static function block(array $block): ?array
    {
        return match ($block['type'] ?? null) {
            'text' => ($text = self::text($block['text'] ?? null, 6000)) !== '' ? ['type' => 'text', 'text' => $text] : null,
            'list' => ($items = self::items((array) ($block['items'] ?? []))) ? array_filter([
                'type' => 'list',
                'ordered' => ! empty($block['ordered']),
                'items' => $items,
            ], fn ($value) => $value !== false) : null,
            'table' => ($rows = self::rows((array) ($block['rows'] ?? []))) ? [
                'type' => 'table',
                'head' => ! empty($block['head']),
                'rows' => $rows,
            ] : null,
            'link' => ($url = self::url($block['url'] ?? null)) ? [
                'type' => 'link',
                'text' => self::text($block['text'] ?? null, 300),
                'label' => self::text($block['label'] ?? null, 120) ?: $url,
                'url' => $url,
            ] : null,
            default => null,
        };
    }

    private static function items(array $items): array
    {
        $clean = [];
        foreach (array_slice($items, 0, self::MAX_ITEMS) as $item) {
            $item = (array) $item;
            $title = self::text($item['title'] ?? null, 200);
            $text = self::text($item['text'] ?? null, 2000);
            $sub = array_values(array_filter(array_map(fn ($line) => self::text($line, 1000), array_slice((array) ($item['sub'] ?? []), 0, self::MAX_SUB)), fn ($line) => $line !== ''));
            if ($title === '' && $text === '' && ! $sub) {
                continue;
            }
            $clean[] = array_filter(['title' => $title, 'text' => $text, 'url' => self::url($item['url'] ?? null), 'sub' => $sub], fn ($value) => $value !== '' && $value !== null && $value !== []);
        }

        return $clean;
    }

    /** The table's rows, all as wide as the widest (empty cells added); rows with nothing in them are dropped. */
    private static function rows(array $rows): array
    {
        $rows = array_map(fn ($row) => array_map(fn ($cell) => self::text($cell, 500), array_slice(array_values((array) $row), 0, self::MAX_COLUMNS)), array_slice($rows, 0, self::MAX_ROWS));
        $rows = array_values(array_filter($rows, fn ($row) => implode('', $row) !== ''));
        $width = $rows ? max(array_map('count', $rows)) : 0;

        return array_map(fn ($row) => array_pad($row, $width, ''), $rows);
    }

    private static function text(mixed $text, int $max): string
    {
        return mb_substr(trim(strip_tags((string) $text)), 0, $max);
    }

    private static function url(mixed $url): ?string
    {
        $url = trim((string) $url);

        return $url !== '' && preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL) ? mb_substr($url, 0, 500) : null;
    }
}
