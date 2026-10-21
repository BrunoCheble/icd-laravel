<?php

namespace App\Services\Notifications;

use App\Enums\MembershipStatus;
use App\Models\PushSubscription;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Models\AnnouncementComment;
use App\Modules\Dashboard\Services\GetTodayMessagesService;
use App\Modules\Members\Models\Member;
use App\Modules\Music\Models\SongSuggestion;
use App\Modules\Prayers\Models\Prayer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The notifications of the installed app, once an hour (from 8:00 to 22:00, Lisbon time): in the first hour of the day
 * the day's birthdays and wedding anniversaries, and every hour only what is new since the last time (prayer
 * requests, announcements to approve, private comments, chord suggestions, members who registered). Nothing is sent twice; what comes
 * at night waits for the morning.
 */
class SendPushDigestService
{
    public const LAST_RUN = 'push-digest:last-run';
    public const MORNING_SENT = 'push-digest:morning';
    public const TIMEZONE = 'Europe/Lisbon';
    public const FROM_HOUR = 8;
    public const UNTIL_HOUR = 22;

    public function __construct(private SendPushService $push, private GetTodayMessagesService $today)
    {
    }

    /** Whether an hour went by since the last time (checked after the requests, see RunPushDigest). */
    public function isDue(): bool
    {
        $last = Cache::get(self::LAST_RUN);

        return ! $last || Carbon::parse($last)->diffInMinutes(now(), true) >= 55;
    }

    /** Sends what there is (at any hour with $anyHour); returns the notifications sent. */
    public function execute(bool $anyHour = false): array
    {
        $now = now(self::TIMEZONE);
        if (! $anyHour && ($now->hour < self::FROM_HOUR || $now->hour >= self::UNTIL_HOUR)) {
            return [];
        }
        $since = Carbon::parse(Cache::get(self::LAST_RUN, now()->subHour()->toIso8601String()));
        Cache::forever(self::LAST_RUN, now()->toIso8601String());
        // Nobody to tell: nothing piles up for later.
        if (! PushSubscription::query()->exists()) {
            return [];
        }

        $locale = app()->getLocale();
        app()->setLocale('pt');
        try {
            $notifications = [];
            if (Cache::get(self::MORNING_SENT) !== $now->toDateString()) {
                Cache::forever(self::MORNING_SENT, $now->toDateString());
                $notifications[] = $this->morning($now);
            }
            $notifications[] = $this->news($since);
            $notifications = array_values(array_filter($notifications));
        } finally {
            app()->setLocale($locale);
        }
        // The number on the installed app's icon: what waits in the menu.
        $waiting = app(\App\Support\Menu::class)->pendingTotal();
        foreach ($notifications as $notification) {
            $this->push->execute($notification + ['badge' => $waiting]);
        }

        return $notifications;
    }

    /** The day's birthdays and wedding anniversaries, or null when there are none. */
    private function morning(Carbon $now): ?array
    {
        $today = $this->today->execute($now);
        $lines = array_filter([
            collect($today['birthdays'])->isNotEmpty() ? '🎂 ' . collect($today['birthdays'])->pluck('title')->implode(', ') : null,
            collect($today['weddings'])->isNotEmpty() ? '💍 ' . collect($today['weddings'])->pluck('title')->implode(', ') : null,
        ]);

        return $lines ? ['title' => __('Today at the church'), 'body' => implode("\n", $lines), 'url' => route('dashboard'), 'tag' => 'morning-' . $now->toDateString()] : null;
    }

    /** What is new since the last time, or null when nothing is; it opens the page of the first kind of news. */
    private function news(Carbon $since): ?array
    {
        $kinds = collect([
            [Prayer::query()->where('created_at', '>', $since)->count(), ':count new prayer request|:count new prayer requests', route('prayers.index')],
            [Announcement::query()->where('is_approved', false)->where('created_at', '>', $since)->count(), ':count announcement to approve|:count announcements to approve', route('announcements.index', ['status' => 'pending'])],
            [AnnouncementComment::query()->where('created_at', '>', $since)->count(), ':count private comment|:count private comments', route('announcements.index', ['status' => 'comments'])],
            [SongSuggestion::query()->where('status', 'pending')->where('created_at', '>', $since)->count(), ':count chord suggestion|:count chord suggestions', route('song-suggestions.index')],
            [Member::query()->where('membership_status', MembershipStatus::PENDING)->where('created_at', '>', $since)->count(), ':count member registered|:count members registered', route('members.index', ['membership_status' => MembershipStatus::PENDING])],
        ])->filter(fn ($kind) => $kind[0] > 0)->values();

        return $kinds->isEmpty() ? null : [
            'title' => __('News in the system'),
            'body' => $kinds->map(fn ($kind) => trans_choice($kind[1], $kind[0], ['count' => $kind[0]]))->implode(' · '),
            'url' => $kinds[0][2],
            'tag' => 'news',
        ];
    }
}
