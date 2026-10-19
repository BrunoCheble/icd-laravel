<?php

namespace App\Modules\Members\Services;

use App\Enums\MembershipStatus;
use App\Modules\Members\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMembersService
{
    // Sort options (request value => columns, all in the chosen direction; fixed values, never user input).
    public const SORTS = [
        'name'     => ['first_name', 'last_name'],
        'created'  => ['created_at'],
        'birth'    => ['date_of_birth'],
        'city'     => ['city'],
        'status'   => ['membership_status'],
    ];

    public function __construct(private GetMemberPendingInformationService $pending)
    {
    }

    /**
     * Members filtered by text (name, NIF, phone or email), status, gender and marital status, sorted by one of SORTS
     * (newest first by default), each with its pending information ("pendingInformations").
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $sort = array_key_exists($filters['sort'] ?? '', self::SORTS) ? $filters['sort'] : 'created';
        $direction = ($filters['direction'] ?? ($sort === 'created' ? 'desc' : 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Member::query();
        foreach (self::SORTS[$sort] as $column) {
            $query->orderBy($column, $direction);
        }
        $query->orderBy('id', 'desc');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(fn ($q) => $q
                ->whereRaw("CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?", [$like])
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])
                ->orWhere('document_number', 'like', $like)
                ->orWhere('phone_number', 'like', $like)
                ->orWhere('email', 'like', $like));
        }

        foreach (['membership_status', 'gender', 'marital_status'] as $column) {
            if (! empty($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        $members = $query->paginate()->withQueryString();
        foreach ($members as $member) {
            $member->pendingInformations = $this->pending->execute($member);
        }

        return $members;
    }

    /**
     * How many members there are of each status, e.g. ["actived" => 120, "pending" => 4, "inactived" => 9].
     */
    public function countByStatus(): array
    {
        $counts = Member::query()->selectRaw('membership_status, COUNT(*) AS total')->groupBy('membership_status')->pluck('total', 'membership_status');

        return collect(MembershipStatus::options())->map(fn ($label, $status) => (int) ($counts[$status] ?? 0))->all();
    }
}
