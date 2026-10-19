<?php

namespace App\Modules\Visitors\Services;

use App\Enums\VisitorStatus;
use App\Modules\Visitors\Models\Visitor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListVisitorsService
{
    // Sort options (request value => columns, all in the chosen direction; fixed values, never user input).
    public const SORTS = [
        'created' => ['created_at'],
        'name'    => ['name'],
        'city'    => ['city'],
        'group'   => ['group'],
    ];

    /**
     * Visitors filtered by text (name, phone, city, who invited or registered, observation), status, gender and group,
     * sorted by one of SORTS (newest first by default).
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $sort = array_key_exists($filters['sort'] ?? '', self::SORTS) ? $filters['sort'] : 'created';
        $direction = ($filters['direction'] ?? ($sort === 'created' ? 'desc' : 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Visitor::query();
        foreach (self::SORTS[$sort] as $column) {
            $query->orderBy($column, $direction);
        }
        $query->orderBy('id', 'desc');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(fn ($q) => $q
                ->where('name', 'like', $like)
                ->orWhere('phone_number', 'like', $like)
                ->orWhere('city', 'like', $like)
                ->orWhere('invited_by', 'like', $like)
                ->orWhere('created_by', 'like', $like)
                ->orWhere('observation', 'like', $like));
        }

        foreach (['status', 'gender', 'group'] as $column) {
            if (! empty($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        return $query->paginate()->withQueryString();
    }

    /**
     * How many visitors there are of each status, e.g. ["actived" => 80, "inactived" => 12].
     */
    public function countByStatus(): array
    {
        $counts = Visitor::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        return collect(VisitorStatus::options())->map(fn ($label, $status) => (int) ($counts[$status] ?? 0))->all();
    }
}
