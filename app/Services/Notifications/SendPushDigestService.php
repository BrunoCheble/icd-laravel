<?php

namespace App\Services\Notifications;

use App\Enums\MembershipStatus;
use App\Models\PushSubscription;
use App\Models\User;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Models\AnnouncementComment;
use App\Modules\Dashboard\Services\GetTodayMessagesService;
use App\Modules\Members\Models\Member;
use App\Modules\Music\Models\SongSuggestion;
use App\Modules\Prayers\Models\Prayer;
use App\Support\Menu;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The notifications of the installed app (from 8:00 to 22:00, Lisbon time): what is new (prayer requests,
 * announcements to approve, private comments, chord suggestions, members who registered) right after it comes in
 * (sendNews(), see NotifyNews), and once an hour (execute()) the day's birthdays and wedding anniversaries in the
 * morning and anything new not sent yet. Both go on from where the last one stopped, so nothing is sent twice; what
 * comes at night waits for the morning. Each user gets only the kinds they chose in their profile (all by default)
 * of the modules they may see (see Permissions), and the number on the icon counts only what waits in those.
 */
class SendPushDigestService
{
    public const LAST_RUN = 'push-digest:last-run';
    public const MORNING_SENT = 'push-digest:morning';
    public const TIMEZONE = 'Europe/Lisbon';
    public const FROM_HOUR = 8;
    public const UNTIL_HOUR = 22;

    // The kinds a user can choose (key => label).
    public const KINDS = [
        'birthdays' => 'Birthdays and wedding anniversaries (in the morning)',
        'prayers' => 'Prayer requests',
        'announcements' => 'Announcements to approve',
        'comments' => 'Private comments on announcements',
        'suggestions' => 'Chord suggestions',
        'members' => 'Members who registered',
    ];

    // The modules a kind needs (seeing any of them).
    public const KIND_MODULES = [
        'birthdays' => ['dashboard', 'anniversaries'],
        'prayers' => ['prayers'],
        'announcements' => ['announcements'],
        'comments' => ['announcements'],
        'suggestions' => ['suggestions'],
        'members' => ['members'],
    ];

    public function __construct(private SendPushService $push, private GetTodayMessagesService $today)
    {
    }

    /** Whether an hour went by since the last time (checked after the requests, see RunPushDigest). */
    public function isDue(): bool
    {
        $last = Cache::get(self::LAST_RUN);

        return ! $last || Carbon::parse($last)->diffInMinutes(now(), true) >= 55;
    }

    /** What just came in, now (not at night); returns the notifications sent. */
    public function sendNews(): array
    {
        if ($this->isNight() || ! PushSubscription::query()->exists()) {
            return [];
        }

        return Cache::lock('push-digest', 30)->block(10, fn () => $this->send(false));
    }

    /** Sends what there is (at any hour with $anyHour); returns the notifications sent. */
    public function execute(bool $anyHour = false): array
    {
        if (! $anyHour && $this->isNight()) {
            return [];
        }
        // Nobody to tell: nothing piles up for later.
        if (! PushSubscription::query()->exists()) {
            Cache::forever(self::LAST_RUN, now()->toIso8601String());

            return [];
        }

        return $this->send(true);
    }

    private function isNight(): bool
    {
        $hour = now(self::TIMEZONE)->hour;

        return $hour < self::FROM_HOUR || $hour >= self::UNTIL_HOUR;
    }

    /** What is new since the last time (and the morning's, once a day), to each user as they chose. */
    private function send(bool $withMorning): array
    {
        $now = now(self::TIMEZONE);
        $since = Carbon::parse(Cache::get(self::LAST_RUN, now()->subHour()->toIso8601String()));
        Cache::forever(self::LAST_RUN, now()->toIso8601String());

        $locale = app()->getLocale();
        app()->setLocale('pt');
        try {
            $morning = null;
            if ($withMorning && Cache::get(self::MORNING_SENT) !== $now->toDateString()) {
                Cache::forever(self::MORNING_SENT, $now->toDateString());
                $morning = $this->morning($now);
            }
            $news = $this->news($since);
            if (! $morning && $news->isEmpty()) {
                return [];
            }

            $sent = [];
            foreach (PushSubscription::query()->with('user')->get()->groupBy('user_id') as $devices) {
                $user = $devices->first()->user;
                $kinds = self::kindsOf($user);
                // The number on the installed app's icon: what waits in the user's menu.
                $waiting = app(Menu::class)->pendingTotal($user);
                $notifications = array_filter([
                    $morning && in_array('birthdays', $kinds, true) ? $morning : null,
                    $this->newsNotification($news->whereIn('kind', $kinds)->values()),
                ]);
                foreach ($notifications as $notification) {
                    $this->push->execute($notification + ['badge' => $waiting], $devices);
                    $sent[$notification['title'] . '|' . $notification['body']] = $notification;
                }
            }

            return array_values($sent);
        } finally {
            app()->setLocale($locale);
        }
    }

    /** The kinds a user gets: the ones they chose (all until they choose) of the modules they may see. */
    public static function kindsOf(?User $user): array
    {
        return array_values(array_intersect($user?->push_kinds ?? array_keys(self::KINDS), self::allowedKinds($user)));
    }

    /** The kinds of the modules the user may see (administrators: all). */
    public static function allowedKinds(?User $user): array
    {
        return array_keys(array_filter(self::KIND_MODULES, fn (array $modules) => collect($modules)->contains(fn ($module) => Permissions::allows($user, $module))));
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

    /** What is new since the last time, by kind: [kind, text, url], only the kinds with something. */
    private function news(Carbon $since): Collection
    {
        return collect([
            ['prayers', Prayer::query()->where('created_at', '>', $since)->count(), ':count new prayer request|:count new prayer requests', route('prayers.index')],
            ['announcements', Announcement::query()->where('is_approved', false)->where('created_at', '>', $since)->count(), ':count announcement to approve|:count announcements to approve', route('announcements.index', ['status' => 'pending'])],
            ['comments', AnnouncementComment::query()->where('created_at', '>', $since)->count(), ':count private comment|:count private comments', route('announcements.index', ['status' => 'comments'])],
            ['suggestions', SongSuggestion::query()->where('status', 'pending')->where('created_at', '>', $since)->count(), ':count chord suggestion|:count chord suggestions', route('song-suggestions.index')],
            ['members', Member::query()->where('membership_status', MembershipStatus::PENDING)->where('created_at', '>', $since)->count(), ':count member registered|:count members registered', route('members.index', ['membership_status' => MembershipStatus::PENDING])],
        ])->filter(fn ($kind) => $kind[1] > 0)
            ->map(fn ($kind) => ['kind' => $kind[0], 'text' => trans_choice($kind[2], $kind[1], ['count' => $kind[1]]), 'url' => $kind[3]])
            ->values();
    }

    /** One notification with the news given (it opens the page of the first one), or null without any. */
    private function newsNotification(Collection $news): ?array
    {
        return $news->isEmpty() ? null : [
            'title' => __('News in the system'),
            'body' => $news->pluck('text')->implode(' · '),
            'url' => $news[0]['url'],
            'tag' => 'news-' . now()->timestamp,
        ];
    }
}
