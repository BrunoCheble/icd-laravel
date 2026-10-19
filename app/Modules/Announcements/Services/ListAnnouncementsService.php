<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListAnnouncementsService
{
    public const STATUSES = [Announcement::PENDING, Announcement::PUBLISHED, Announcement::EXPIRED];

    /**
     * The announcements, newest first, by status (pending, published, expired; all by default), type and text
     * (title, description or the member's name), each with its member.
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = $this->withStatus(Announcement::query()->with('member'), $filters['status'] ?? null)->latest()->orderBy('id', 'desc');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(fn ($q) => $q
                ->where('title', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('member', fn ($member) => $member->whereRaw("CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?", [$like])));
        }

        return $query->paginate(18)->withQueryString();
    }

    /**
     * How many announcements each status has, e.g. ["pending" => 2, "published" => 7, "expired" => 15].
     */
    public function countByStatus(): array
    {
        return collect(self::STATUSES)->mapWithKeys(fn ($status) => [$status => $this->withStatus(Announcement::query(), $status)->count()])->all();
    }

    private function withStatus(Builder $query, ?string $status): Builder
    {
        $today = now()->startOfDay();

        return match ($status) {
            Announcement::PENDING => $query->where('is_approved', false),
            Announcement::PUBLISHED => $query->where('is_approved', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $today)),
            Announcement::EXPIRED => $query->where('is_approved', true)->where('expires_at', '<', $today),
            default => $query,
        };
    }
}
