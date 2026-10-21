<?php

namespace Tests\Feature\Notifications;

use App\Enums\MembershipStatus;
use App\Models\PushSubscription;
use App\Models\User;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Members\Models\Member;
use App\Modules\Prayers\Models\Prayer;
use App\Services\Notifications\SendPushDigestService;
use App\Services\Notifications\SendPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The app's notifications: the day's birthdays once in the morning, then only what is new, never at night.
 */
class PushDigestTest extends TestCase
{
    use RefreshDatabase;

    /** The notifications "sent" (nothing goes out in the tests), and to which users. */
    private array $sent = [];
    private array $to = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(SendPushDigestService::LAST_RUN);
        Cache::forget(SendPushDigestService::MORNING_SENT);
        $this->app->instance(SendPushService::class, new class($this->sent, $this->to) extends SendPushService {
            public function __construct(private array &$sent, private array &$to) {}
            public function execute(array $notification, ?Collection $devices = null): int
            {
                $this->sent[] = $notification;
                $this->to[] = $devices?->pluck('user_id')->unique()->values()->all();
                return 1;
            }
        });
    }

    private function at(string $lisbon): void
    {
        $this->travelTo(Carbon::parse($lisbon, SendPushDigestService::TIMEZONE));
    }

    private function device(?array $kinds = null): PushSubscription
    {
        return PushSubscription::create(['user_id' => User::factory()->create(['push_kinds' => $kinds])->id, 'endpoint' => 'https://push.example/' . uniqid(),
            'endpoint_hash' => hash('sha256', uniqid()), 'public_key' => 'k', 'auth_token' => 'a']);
    }

    private function member(array $data = []): Member
    {
        static $document = 930000000;

        return Member::create($data + ['first_name' => 'ZZ Teste', 'last_name' => 'Aviso', 'document_number' => (string) $document++,
            'gender' => 'F', 'marital_status' => 'S', 'membership_status' => MembershipStatus::ACTIVED]);
    }

    public function test_the_birthdays_come_once_in_the_morning_and_then_only_what_is_new(): void
    {
        $this->device();
        $this->at('2026-10-21 08:05');
        $this->member(['first_name' => 'Maria', 'last_name' => 'ZZ Teste', 'date_of_birth' => '1990-10-21']);
        $digest = app(SendPushDigestService::class);

        $digest->execute();
        $this->assertCount(1, $this->sent);
        $this->assertSame('Hoje na igreja', $this->sent[0]['title']);
        $this->assertStringContainsString('🎂 Maria ZZ Teste', $this->sent[0]['body']);

        // An hour later: nothing new, nothing sent (the birthdays are not sent again)
        $this->at('2026-10-21 09:05');
        $this->assertTrue($digest->isDue());
        $digest->execute();
        $this->assertCount(1, $this->sent);

        // New things since then, each counted once
        $this->at('2026-10-21 09:30');
        Prayer::create(['name' => 'ZZ Teste', 'request' => 'Saúde']);
        Prayer::create(['name' => 'ZZ Teste Dois', 'request' => 'Paz']);
        Announcement::create(['member_id' => $this->member()->id, 'type' => 'service', 'title' => 'ZZ Teste', 'description' => 'X', 'contact' => '9', 'is_approved' => false]);
        $this->member(['membership_status' => MembershipStatus::PENDING]);
        $song = app(\App\Modules\Music\Services\SaveSongService::class)->execute(['title' => 'ZZ Teste', 'artist' => 'A', 'bpm' => 60, 'musical_key' => 'G',
            'structure' => json_encode([['section' => 'INTRO', 'chords' => ['G'], 'durations' => [4], 'beats_per_bar' => 4]])]);
        \Illuminate\Support\Facades\DB::table('song_suggestions')->insert(['song_id' => $song->id, 'changes' => '[]', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        $this->at('2026-10-21 10:05');
        $digest->execute();
        $this->assertCount(2, $this->sent);
        $this->assertSame('Novidades no sistema', $this->sent[1]['title']);
        $this->assertSame('2 novos pedidos de oração · 1 anúncio por aprovar · 1 sugestão de acordes · 1 novo registo de membro', $this->sent[1]['body']);
        // The number on the app's icon: what waits in the menu (the announcement to approve, the chord suggestion)
        $this->assertSame(2, $this->sent[1]['badge']);
        $this->assertSame(route('prayers.index'), $this->sent[1]['url']);

        $this->at('2026-10-21 11:05');
        $digest->execute();
        $this->assertCount(2, $this->sent);
    }

    public function test_what_comes_in_is_sent_right_away_but_not_at_night(): void
    {
        $this->device();
        $this->at('2026-10-21 10:00');
        Cache::put(SendPushDigestService::LAST_RUN, now()->subMinutes(10)->toIso8601String());

        $this->postJson('/api/public/prayers', ['name' => 'ZZ Teste Agora', 'request' => 'Paz'])->assertCreated();
        $this->assertCount(1, $this->sent);
        $this->assertSame('1 novo pedido de oração', $this->sent[0]['body']);

        // The hourly one does not send it again
        $this->at('2026-10-21 11:05');
        app(SendPushDigestService::class)->execute();
        $this->assertNotContains('Novidades no sistema', array_column(array_slice($this->sent, 1), 'title'));

        // At night: kept for the morning
        $this->at('2026-10-21 23:30');
        $this->postJson('/api/public/prayers', ['name' => 'ZZ Teste Noite', 'request' => 'Paz'])->assertCreated();
        $count = count($this->sent);
        $this->at('2026-10-22 08:05');
        app(SendPushDigestService::class)->execute();
        $this->assertSame('1 novo pedido de oração', collect(array_slice($this->sent, $count))->firstWhere('title', 'Novidades no sistema')['body']);
    }

    public function test_nothing_at_night_and_what_came_waits_for_the_morning(): void
    {
        $this->device();
        $digest = app(SendPushDigestService::class);
        $this->at('2026-10-21 21:30');
        $digest->execute();
        $before = count($this->sent);

        $this->at('2026-10-21 23:10');
        Prayer::create(['name' => 'ZZ Teste Noite', 'request' => 'Paz']);
        $this->assertSame([], $digest->execute());

        $this->at('2026-10-22 08:05');
        $digest->execute();
        $this->assertSame('1 novo pedido de oração', collect($this->sent)->slice($before)->firstWhere('title', 'Novidades no sistema')['body']);
    }

    public function test_without_devices_nothing_is_sent_or_kept_for_later(): void
    {
        $this->at('2026-10-21 10:00');
        Prayer::create(['name' => 'ZZ Teste', 'request' => 'Paz']);
        $this->assertSame([], app(SendPushDigestService::class)->execute());
        $this->assertFalse(app(SendPushDigestService::class)->isDue());
    }

    public function test_a_device_is_turned_on_off_and_tested(): void
    {
        $user = User::factory()->create();
        $subscription = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc', 'keys' => ['p256dh' => 'BPublic', 'auth' => 'secret']];

        $this->actingAs($user)->postJson('/push/subscriptions', $subscription)->assertOk();
        $this->actingAs($user)->postJson('/push/subscriptions', $subscription)->assertOk();
        $this->assertSame(1, PushSubscription::where('user_id', $user->id)->count());
        $this->actingAs($user)->postJson('/push/subscriptions', ['endpoint' => 'http://insecure'] + $subscription)->assertJsonValidationErrors('endpoint');

        $this->actingAs($user)->postJson('/push/test', ['endpoint' => $subscription['endpoint']])->assertOk()->assertJsonPath('sent', 1);
        $this->assertSame('As notificações estão ligadas', __($this->sent[0]['title'], [], 'pt'));

        $this->actingAs($user)->deleteJson('/push/subscriptions', ['endpoint' => $subscription['endpoint']])->assertOk();
        $this->assertSame(0, PushSubscription::count());
        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('pushNotifications(', false);
    }

    public function test_each_user_gets_only_the_kinds_they_chose(): void
    {
        $onlyPrayers = $this->device(['prayers']);
        $nothing = $this->device([]);
        $everything = $this->device();
        $this->at('2026-10-21 08:05');
        $this->member(['first_name' => 'Maria', 'last_name' => 'ZZ Teste', 'date_of_birth' => '1990-10-21']);
        Prayer::create(['name' => 'ZZ Teste', 'request' => 'Paz']);
        $this->member(['membership_status' => MembershipStatus::PENDING]);

        app(SendPushDigestService::class)->execute();
        $by = fn ($device) => collect($this->sent)->filter(fn ($n, $i) => in_array($device->user_id, $this->to[$i], true))->pluck('body')->values()->all();
        $this->assertSame(['1 novo pedido de oração'], $by($onlyPrayers));
        $this->assertSame([], $by($nothing));
        $this->assertSame(['🎂 Maria ZZ Teste', '1 novo pedido de oração · 1 novo registo de membro'], $by($everything));
    }

    public function test_the_kinds_are_chosen_in_the_profile(): void
    {
        $user = User::factory()->create();
        $this->assertSame(array_keys(SendPushDigestService::KINDS), SendPushDigestService::kindsOf($user));

        $this->actingAs($user)->patchJson('/push/preferences', ['kinds' => ['prayers', 'suggestions']])->assertOk();
        $this->assertSame(['prayers', 'suggestions'], $user->fresh()->push_kinds);
        $this->actingAs($user)->patchJson('/push/preferences', ['kinds' => []])->assertOk();
        $this->assertSame([], $user->fresh()->push_kinds);
        $this->actingAs($user)->patchJson('/push/preferences', ['kinds' => ['nope']])->assertJsonValidationErrors('kinds.0');
    }
}
