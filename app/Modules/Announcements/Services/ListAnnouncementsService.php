<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListAnnouncementsService
{
    public const STATUSES = [Announcement::PENDING, Announcement::PUBLISHED, Announcement::EXPIRED];
    // Not a status: the announcements with members' comments not read yet.
    public const COMMENTS = 'comments';

    /**
     * The announcements, newest first, by status (pending, published, expired, or with comments not read; all by
     * default), category and text (title, description or the member's name), each with its member, comments and stars.
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = $this->withStatus(Announcement::query()->with(['member', 'comments.member', 'ratings.member']), $filters['status'] ?? null)->latest()->orderBy('id', 'desc');

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
     * How many announcements each status has, and how many have comments not read, e.g. ["pending" => 2,
     * "published" => 7, "expired" => 15, "comments" => 1].
     */
    public function countByStatus(): array
    {
        return collect([...self::STATUSES, self::COMMENTS])->mapWithKeys(fn ($status) => [$status => $this->withStatus(Announcement::query(), $status)->count()])->all();
    }

    private function withStatus(Builder $query, ?string $status): Builder
    {
        $today = now()->startOfDay();

        return match ($status) {
            Announcement::PENDING => $query->where('is_approved', false),
            Announcement::PUBLISHED => $query->where('is_approved', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $today)),
            Announcement::EXPIRED => $query->where('is_approved', true)->where('expires_at', '<', $today),
            self::COMMENTS => $query->whereHas('comments', fn ($comments) => $comments->whereNull('read_at')),
            default => $query,
        };
    }
}
