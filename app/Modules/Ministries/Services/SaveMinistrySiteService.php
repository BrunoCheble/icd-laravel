<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Ministries\Models\Ministry;
use App\Modules\Ministries\Support\MinistryBio;

class SaveMinistrySiteService
{
    /** Saves a ministry's page on the site: the card, the page's title and its text (kept in MinistryBio's shape). */
    public function execute(Ministry $ministry, array $data): Ministry
    {
        $text = fn (string $field) => trim((string) ($data[$field] ?? '')) ?: null;

        $ministry->fill([
            'site_visible' => (bool) ($data['site_visible'] ?? false),
            'site_order' => (int) ($data['site_order'] ?? 0),
            'site_group' => $data['site_group'] ?? 'ministries',
            'site_icon' => $text('site_icon'),
            'site_tag' => $text('site_tag'),
            'site_title' => $text('site_title'),
            'site_page_title' => $text('site_page_title'),
            'site_summary' => $text('site_summary'),
            'site_page' => MinistryBio::clean($data['site_page'] ?? null),
        ])->save();

        return $ministry;
    }
}
