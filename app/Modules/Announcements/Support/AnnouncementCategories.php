<?php

namespace App\Modules\Announcements\Support;

/**
 * The announcement categories. Each one has its banner (the site shows it, not pictures sent by people, except the
 * photos of donations and sales), how long it lasts by default and its own fields, kept in the announcement's
 * "details" (JSON). The forms, the validation and the site's labels all come from here.
 *
 * Category: label, icon, months, fields, and optionally pdf (takes a PDF: a résumé) and photos (how many: donations,
 * sales). A member may have up to LIMIT announcements of each category at a time (waiting or published).
 * Field: label, type (select | text | yesno), options (select: key => label), required, max (text length).
 * The keys are stored; the labels are translated when shown.
 */
class AnnouncementCategories
{
    public const SERVICE = 'service';
    public const LESSONS = 'lessons';
    public const RESUME = 'resume';
    public const JOB = 'job';
    public const DONATION = 'donation';
    public const SALE = 'sale';

    // How many announcements of the same category a member may have at a time (waiting or published).
    public const LIMIT = 3;

    /** The categories, in their order on the forms and the site. */
    public static function all(): array
    {
        $jobAreas = [
            'office' => 'Administration and office',
            'sales' => 'Retail and sales',
            'restaurant' => 'Restaurants and kitchen',
            'cleaning' => 'Cleaning',
            'construction' => 'Construction and maintenance',
            'health' => 'Health and care',
            'education' => 'Education',
            'tech' => 'Computers and technology',
            'beauty' => 'Beauty',
            'logistics' => 'Transport and logistics',
            'other' => 'Other',
        ];
        $conditions = [
            'new' => 'New',
            'like_new' => 'Like new',
            'used' => 'Used, in good condition',
        ];

        return [
            self::SERVICE => [
                'label' => 'Services',
                'icon' => 'fa-screwdriver-wrench',
                'months' => 6,
                'fields' => [
                    'area' => ['label' => 'Area', 'type' => 'select', 'required' => true, 'options' => [
                        'construction' => 'Construction and repairs',
                        'electricity' => 'Electricity and plumbing',
                        'cleaning' => 'Cleaning',
                        'beauty' => 'Beauty and well-being',
                        'health' => 'Health and care',
                        'office' => 'Accounting and paperwork',
                        'tech' => 'Computers and technology',
                        'transport' => 'Transport and removals',
                        'food' => 'Cooking and events',
                        'other' => 'Other',
                    ]],
                    'company' => ['label' => 'Company name', 'type' => 'text', 'required' => false, 'max' => 80, 'placeholder' => 'E.g. Silva Pinturas'],
                    'location' => ['label' => 'Location', 'type' => 'text', 'required' => true, 'max' => 80, 'placeholder' => 'E.g. Lisbon and Amadora'],
                    'availability' => ['label' => 'Availability', 'type' => 'text', 'required' => false, 'max' => 80, 'placeholder' => 'E.g. Weekdays and Saturdays'],
                    'other_contact' => ['label' => 'Other contact (email or website)', 'type' => 'text', 'required' => false, 'max' => 120, 'placeholder' => 'E.g. geral@empresa.pt'],
                ],
            ],
            self::LESSONS => [
                'label' => 'Lessons and training',
                'icon' => 'fa-graduation-cap',
                'months' => 6,
                'fields' => [
                    'subject' => ['label' => 'Subject', 'type' => 'text', 'required' => true, 'max' => 80, 'placeholder' => 'E.g. Maths, guitar, English'],
                    'level' => ['label' => 'Level', 'type' => 'select', 'required' => true, 'options' => [
                        'basic' => 'Primary school',
                        'secondary' => 'Secondary school',
                        'university' => 'University',
                        'adults' => 'Adults',
                        'all' => 'All levels',
                    ]],
                    'mode' => ['label' => 'Where', 'type' => 'select', 'required' => true, 'options' => [
                        'in_person' => 'In person',
                        'online' => 'Online',
                        'both' => 'In person or online',
                    ]],
                ],
            ],
            self::RESUME => [
                'label' => 'Looking for work',
                'icon' => 'fa-file-lines',
                'months' => 3,
                'pdf' => true,
                'fields' => [
                    'area' => ['label' => 'Area of interest', 'type' => 'select', 'required' => true, 'options' => $jobAreas],
                    'experience' => ['label' => 'Experience', 'type' => 'select', 'required' => true, 'options' => [
                        'none' => 'No experience',
                        'under_1' => 'Less than a year',
                        '1_3' => '1 to 3 years',
                        'over_3' => 'More than 3 years',
                    ]],
                    'availability' => ['label' => 'Availability', 'type' => 'select', 'required' => true, 'options' => [
                        'full' => 'Full time',
                        'part' => 'Part time',
                        'weekends' => 'Weekends',
                        'flexible' => 'Flexible',
                    ]],
                    'driving' => ['label' => 'Driving licence', 'type' => 'yesno', 'required' => false],
                ],
            ],
            self::JOB => [
                'label' => 'Job offer',
                'icon' => 'fa-briefcase',
                'months' => 2,
                'fields' => [
                    'role' => ['label' => 'Role', 'type' => 'text', 'required' => true, 'max' => 80, 'placeholder' => 'E.g. Kitchen assistant'],
                    'area' => ['label' => 'Area', 'type' => 'select', 'required' => true, 'options' => $jobAreas],
                    'contract' => ['label' => 'Contract', 'type' => 'select', 'required' => true, 'options' => [
                        'contract' => 'Employment contract',
                        'temporary' => 'Temporary',
                        'part' => 'Part time',
                        'internship' => 'Internship',
                    ]],
                    'location' => ['label' => 'Place of work', 'type' => 'text', 'required' => true, 'max' => 80, 'placeholder' => 'E.g. Sintra'],
                ],
            ],
            self::DONATION => [
                'label' => 'Donations',
                'icon' => 'fa-hand-holding-heart',
                'months' => 1,
                'photos' => 3,
                'fields' => [
                    'condition' => ['label' => 'Condition', 'type' => 'select', 'required' => true, 'options' => $conditions],
                    'location' => ['label' => 'Pick-up place', 'type' => 'text', 'required' => false, 'max' => 80, 'placeholder' => 'E.g. Amadora'],
                ],
            ],
            self::SALE => [
                'label' => 'Sales',
                'icon' => 'fa-tag',
                'months' => 2,
                'photos' => 3,
                'fields' => [
                    'price' => ['label' => 'Price', 'type' => 'text', 'required' => true, 'max' => 30, 'placeholder' => 'E.g. 25 €'],
                    'condition' => ['label' => 'Condition', 'type' => 'select', 'required' => true, 'options' => $conditions],
                    'location' => ['label' => 'Location', 'type' => 'text', 'required' => false, 'max' => 80, 'placeholder' => 'E.g. Amadora'],
                ],
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function has(?string $category): bool
    {
        return $category !== null && array_key_exists($category, self::all());
    }

    public static function label(string $category): ?string
    {
        $label = self::all()[$category]['label'] ?? null;
        return $label ? __($label) : null;
    }

    /** The banner shown for the category (public/images/announcements/categories). */
    public static function banner(string $category): string
    {
        return "images/announcements/categories/{$category}.svg";
    }

    /** Whether the category takes a PDF (a résumé). */
    public static function takesPdf(?string $category): bool
    {
        return (bool) (self::all()[$category]['pdf'] ?? false);
    }

    /** How many photos the category takes (donations and sales), 0 for the others. */
    public static function photos(?string $category): int
    {
        return (int) (self::all()[$category]['photos'] ?? 0);
    }

    /** How many announcements of the category a member may have at a time (waiting or published), or null. */
    public static function limit(?string $category): ?int
    {
        return self::has($category) ? self::LIMIT : null;
    }

    /** The end of an announcement of the category sent today. */
    public static function defaultExpiry(string $category): string
    {
        return now()->addMonths(self::all()[$category]['months'] ?? 3)->toDateString();
    }

    /** Validation rules of the category's fields, as "details.<field>". */
    public static function rules(?string $category): array
    {
        $rules = ['details' => ['nullable', 'array']];
        foreach (self::all()[$category]['fields'] ?? [] as $name => $field) {
            $rule = [$field['required'] ? 'required' : 'nullable'];
            $rule = array_merge($rule, match ($field['type']) {
                'select' => ['string', 'in:' . implode(',', array_keys($field['options']))],
                'yesno' => ['boolean'],
                default => ['string', 'max:' . ($field['max'] ?? 120)],
            });
            $rules["details.{$name}"] = $rule;
        }

        return $rules;
    }

    /** Validation rules of a donation's or a sale's photos (pictures only, up to its number). */
    public static function photoRules(?string $category): array
    {
        $max = self::photos($category);

        return $max
            ? ['photos' => ['nullable', 'array', "max:{$max}"], 'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]
            : ['photos' => ['prohibited']];
    }

    /** Readable names of the fields (for the validation messages). */
    public static function attributes(?string $category): array
    {
        $names = [];
        foreach (self::all()[$category]['fields'] ?? [] as $name => $field) {
            $names["details.{$name}"] = mb_strtolower(__($field['label']));
        }

        return $names;
    }

    /** Only the category's fields, trimmed, the empty ones left out ("yesno" as true / false). */
    public static function clean(?string $category, ?array $details): array
    {
        $clean = [];
        foreach (self::all()[$category]['fields'] ?? [] as $name => $field) {
            $value = $details[$name] ?? null;
            if ($field['type'] === 'yesno') {
                if ($value !== null && $value !== '') {
                    $clean[$name] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '') {
                $clean[$name] = $value;
            }
        }

        return $clean;
    }

    /**
     * The details as they read, in the category's order: [["label" => "Area", "value" => "Cleaning", "short" =>
     * "Cleaning"], …]; "short" is what a tag shows (a yes / no says what it is about: "Driving licence: No").
     */
    public static function describe(?string $category, ?array $details): array
    {
        $lines = [];
        foreach (self::all()[$category]['fields'] ?? [] as $name => $field) {
            if (! array_key_exists($name, $details ?? [])) {
                continue;
            }
            $value = $details[$name];
            $value = match ($field['type']) {
                'select' => __($field['options'][$value] ?? (string) $value),
                'yesno' => $value ? __('Yes') : __('No'),
                default => (string) $value,
            };
            $short = $field['type'] === 'yesno' ? __($field['label']) . ': ' . $value : $value;
            $lines[] = ['label' => __($field['label']), 'value' => $value, 'short' => $short];
        }

        return $lines;
    }

    /** The categories for the forms' scripts, translated: {key: {label, icon, months, pdf, fields: {name: {…}}}}. */
    public static function forForms(): array
    {
        return collect(self::all())->map(fn ($category) => [
            'label' => __($category['label']),
            'icon' => $category['icon'],
            'months' => $category['months'],
            'pdf' => (bool) ($category['pdf'] ?? false),
            'photos' => (int) ($category['photos'] ?? 0),
            'limit' => self::LIMIT,
            'fields' => collect($category['fields'])->map(fn ($field) => [
                'label' => __($field['label']),
                'type' => $field['type'],
                'required' => $field['required'],
                'max' => $field['max'] ?? null,
                'placeholder' => isset($field['placeholder']) ? __($field['placeholder']) : '',
                'options' => collect($field['options'] ?? [])->map(fn ($label) => __($label))->all(),
            ])->all(),
        ])->all();
    }
}
