<?php

namespace App\Modules\Ministries\Support;

/**
 * A biography as JSON, organised by each person their own way:
 *
 * {"layout": "sections" | "simple",           sections: with an index to jump to each; simple: one after another
 *  "sections": [{"title": "Formação",          (optional with "simple")
 *                "blocks": [{"type": "text", "text": "…"},
 *                           {"type": "list", "items": [{"title": "Licenciado em Letras", "text": "pela FURB", "url": ""}]},
 *                           {"type": "link", "text": "Vídeo sobre o ministério:", "label": "Assista no YouTube", "url": "https://…"}]}]}
 *
 * clean() keeps only that shape (plain texts, http(s) addresses); an empty biography is null.
 */
class MinistryBio
{
    public const LAYOUTS = ['sections', 'simple'];

    private const MAX_SECTIONS = 20;
    private const MAX_BLOCKS = 30;
    private const MAX_ITEMS = 50;

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
            'text' => ($text = self::text($block['text'] ?? null, 4000)) !== '' ? ['type' => 'text', 'text' => $text] : null,
            'list' => ($items = self::items((array) ($block['items'] ?? []))) ? ['type' => 'list', 'items' => $items] : null,
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
            $text = self::text($item['text'] ?? null, 1000);
            if ($title === '' && $text === '') {
                continue;
            }
            $clean[] = array_filter(['title' => $title, 'text' => $text, 'url' => self::url($item['url'] ?? null)], fn ($value) => $value !== '' && $value !== null);
        }

        return $clean;
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
