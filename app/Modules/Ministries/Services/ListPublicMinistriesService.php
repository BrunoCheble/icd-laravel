<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Ministries\Models\Ministry;

class ListPublicMinistriesService
{
    /**
     * The site's "Conheça Nossos Ministérios": the ministries shown on the site, in their order, each with its group
     * (key and label), icon (a Font Awesome name), the card's tag, title and summary, and its page's title and text
     * (null without one). Without a title, the card takes the name and the page the card's title.
     */
    public function execute(): array
    {
        return Ministry::query()->where('site_visible', true)->orderBy('site_order')->orderBy('id')->get()
            ->map(function (Ministry $ministry) {
                $title = $ministry->site_title ?: $ministry->name;

                return [
                    'id' => $ministry->id,
                    'group' => $ministry->site_group,
                    'group_label' => Ministry::SITE_GROUPS[$ministry->site_group] ?? Ministry::SITE_GROUPS['ministries'],
                    'icon' => $ministry->site_icon ?: 'church',
                    'tag' => $ministry->site_tag ?: $title,
                    'title' => $title,
                    'page_title' => $ministry->site_page_title ?: $title,
                    'summary' => $ministry->site_summary,
                    'page' => $ministry->site_page ?: null,
                ];
            })->all();
    }
}
