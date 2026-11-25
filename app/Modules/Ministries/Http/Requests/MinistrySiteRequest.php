<?php

namespace App\Modules\Ministries\Http\Requests;

use App\Modules\Ministries\Models\Ministry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A ministry's page on the site (see Ministry): the card, the page's title and text, whether and where it shows. */
class MinistrySiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** The icon as its bare name ("church"), also when given as a class ("fas fa-church", "fa-solid fa-church"). */
    protected function prepareForValidation(): void
    {
        $icon = trim((string) $this->input('site_icon'));
        $icon = preg_replace('/^(fa[srb]?|fa-solid|fa-regular|fa-brands)\s+/', '', $icon);
        $this->merge([
            'site_visible' => $this->boolean('site_visible'),
            'site_icon' => preg_replace('/^fa-/', '', $icon) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'site_visible' => ['boolean'],
            'site_order' => ['integer', 'min:0', 'max:999'],
            'site_group' => ['required', Rule::in(array_keys(Ministry::SITE_GROUPS))],
            'site_icon' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/'],
            'site_tag' => ['nullable', 'string', 'max:60'],
            'site_title' => ['nullable', 'string', 'max:120'],
            'site_page_title' => ['nullable', 'string', 'max:160'],
            'site_summary' => ['nullable', 'string', 'max:2000'],
            // The page's JSON (see MinistryBio), from the editor.
            'site_page' => ['nullable', 'string', 'max:200000', 'json'],
        ];
    }

    public function attributes(): array
    {
        return [
            'site_order' => __('order'), 'site_group' => __('group'), 'site_icon' => __('icon'), 'site_tag' => __('tag'),
            'site_title' => __('card title'), 'site_page_title' => __('page title'), 'site_summary' => __('summary'), 'site_page' => __('page'),
        ];
    }
}
