<?php

namespace App\Modules\Prayers\Services;

use App\Modules\Prayers\Models\Prayer;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListPrayersService
{
    // The periods to look at (request value => from when); "all" has no limit.
    public const PERIODS = ['today', 'week', 'month', 'all'];

    /**
     * The prayer requests of a period (all by default), newest first, filtered by text (name or request).
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = $this->inPeriod(Prayer::query(), $filters['period'] ?? 'all')->latest()->orderBy('id', 'desc');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('request', 'like', $like));
        }

        return $query->paginate()->withQueryString();
    }

    /**
     * How many requests each period has, e.g. ["today" => 3, "week" => 12, "month" => 40, "all" => 310].
     */
    public function countByPeriod(): array
    {
        return collect(self::PERIODS)->mapWithKeys(fn ($period) => [$period => $this->inPeriod(Prayer::query(), $period)->count()])->all();
    }

    private function inPeriod(Builder $query, string $period): Builder
    {
        $from = $this->start($period, now());

        return $from ? $query->where('created_at', '>=', $from) : $query;
    }

    // Today, this week (from Monday), this month; null: since always.
    private function start(string $period, CarbonInterface $now): ?CarbonInterface
    {
        return match ($period) {
            'today' => $now->copy()->startOfDay(),
            'week' => $now->copy()->startOfWeek(),
            'month' => $now->copy()->startOfMonth(),
            default => null,
        };
    }
}
