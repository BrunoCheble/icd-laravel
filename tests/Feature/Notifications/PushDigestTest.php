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

    /** The notifications "sent" (nothing goes out in the tests). */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(SendPushDigestService::LAST_RUN);
        Cache::forget(SendPushDigestService::MORNING_SENT);
        $this->app->instance(SendPushService::class, new class($this->sent) extends SendPushService {
            public function __construct(private array &$sent) {}
            public function execute(array $notification, ?Collection $devices = null): int { $this->sent[] = $notification; return 1; }
        });
    }

    private function at(string $lisbon): void
    {
        $this->travelTo(Carbon::parse($lisbon, SendPushDigestService::TIMEZONE));
    }

    private function device(): PushSubscription
    {
        return PushSubscription::create(['user_id' => User::factory()->create()->id, 'endpoint' => 'https://push.example/' . uniqid(),
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
        $this->at('2026-10-21 10:05');
        $digest->execute();
        $this->assertCount(2, $this->sent);
        $this->assertSame('Novidades no sistema', $this->sent[1]['title']);
        $this->assertSame('2 novos pedidos de oração · 1 anúncio por aprovar · 1 novo registo de membro', $this->sent[1]['body']);
        // The number on the app's icon: what waits in the menu (the announcement to approve)
        $this->assertSame(1, $this->sent[1]['badge']);
        $this->assertSame(route('prayers.index'), $this->sent[1]['url']);

        $this->at('2026-10-21 11:05');
        $digest->execute();
        $this->assertCount(2, $this->sent);
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
}
