<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * The side menu of the system, made of one section per module: each module adds its own section (with its pages)
 * from its service provider, so the menu follows the modules. Sections show in `order`; an item is
 * ['label', 'icon' (Font Awesome classes), 'route', 'active' (route name patterns, default "<route prefix>.*"),
 * 'badge' (a closure giving a number, shown when above 0)].
 */
class Menu
{
    private array $sections = [];

    public function section(string $key, ?string $label, int $order, array $items): self
    {
        $this->sections[$key] = ['key' => $key, 'label' => $label, 'order' => $order, 'items' => $items];

        return $this;
    }

    /** Everything waiting in the menu's badges (the number on the installed app's icon). */
    public function pendingTotal(): int
    {
        return collect($this->sections)->flatMap(fn (array $section) => $section['items'])
            ->sum(fn (array $item) => ($item['badge'] ?? null) instanceof Closure && Route::has($item['route']) ? (int) $item['badge']() : 0);
    }

    /**
     * The sections in order, each item with its address, whether it is the current page and its badge; items whose
     * route does not exist are left out.
     */
    public function resolve(Request $request): array
    {
        $sections = collect($this->sections)->sortBy('order')->map(function (array $section) use ($request) {
            $section['items'] = collect($section['items'])
                ->filter(fn (array $item) => Route::has($item['route']))
                ->map(function (array $item) use ($request) {
                    $patterns = (array) ($item['active'] ?? preg_replace('/\.[^.]+$/', '.*', $item['route']));
                    $badge = ($item['badge'] ?? null) instanceof Closure ? (int) $item['badge']() : 0;

                    return [
                        'label'  => $item['label'],
                        'icon'   => $item['icon'],
                        'url'    => route($item['route']),
                        'active' => $request->routeIs(...$patterns),
                        'badge'  => $badge,
                    ];
                })->values()->all();

            return $section;
        });

        return $sections->filter(fn (array $section) => $section['items'] !== [])->values()->all();
    }
}
