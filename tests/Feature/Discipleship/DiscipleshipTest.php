<?php

namespace Tests\Feature\Discipleship;

use App\Enums\MembershipStatus;
use App\Models\PushSubscription;
use App\Models\User;
use App\Modules\Discipleship\Models\Discipleship;
use App\Modules\Discipleship\Models\DiscipleshipMeeting;
use App\Modules\Members\Models\Member;
use App\Services\Notifications\SendPushDigestService;
use App\Services\Notifications\SendPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Discipleship: mentors and their disciples, meetings (one or more disciples), whether they took place, comments,
 * and the mentor's reminders (test database icd_test, emptied on every run).
 */
class DiscipleshipTest extends TestCase
{
    use RefreshDatabase;

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

    /** Lisbon time, kept in UTC (a "now" in another zone would make Carbon read the database's dates in it). */
    private function at(string $lisbon): void
    {
        $this->travelTo(Carbon::parse($lisbon, 'Europe/Lisbon')->utc());
    }

    private function member(string $first): Member
    {
        static $document = 940000000;

        return Member::create(['first_name' => $first, 'last_name' => 'ZZ Teste', 'document_number' => (string) $document++,
            'gender' => 'M', 'marital_status' => 'S', 'membership_status' => MembershipStatus::ACTIVED]);
    }

    /** A mentor: a member with a user of the admin (only the discipleship module). */
    private function mentor(string $first): User
    {
        return User::factory()->withPermissions(['discipleships' => ['read', 'write', 'update', 'delete']])
            ->create(['name' => "{$first} ZZ Teste", 'member_id' => $this->member($first)->id]);
    }

    private function meeting(User $mentor, array $disciples, string $lisbon): DiscipleshipMeeting
    {
        $this->actingAs($mentor)->post('/discipleships/meetings', [
            'disciples' => collect($disciples)->pluck('id')->all(),
            'date' => substr($lisbon, 0, 10), 'time' => substr($lisbon, 11, 5), 'location' => 'Igreja',
        ])->assertSessionHasNoErrors();

        return DiscipleshipMeeting::query()->latest('id')->firstOrFail();
    }

    public function test_a_mentor_adds_their_disciples_and_a_member_has_one_mentor_at_a_time(): void
    {
        $ana = $this->mentor('Ana');
        $bruno = $this->mentor('Bruno');
        $joao = $this->member('João');

        // The mentor is always the user's own member, whatever is sent.
        $this->actingAs($ana)->post('/discipleships', ['disciple_id' => $joao->id, 'mentor_id' => $bruno->member_id])
            ->assertRedirect(route('discipleships.index'));
        $this->assertDatabaseHas('discipleships', ['mentor_id' => $ana->member_id, 'disciple_id' => $joao->id, 'is_active' => true]);

        $this->actingAs($bruno)->post('/discipleships', ['disciple_id' => $joao->id])->assertSessionHasErrors('disciple_id');
        $this->actingAs($ana)->post('/discipleships', ['disciple_id' => $ana->member_id])->assertSessionHasErrors('disciple_id');
    }

    public function test_everyone_with_the_module_sees_all_and_changes_only_their_own(): void
    {
        $ana = $this->mentor('Ana');
        $bruno = $this->mentor('Bruno');
        $joao = $this->member('João');
        $pedro = $this->member('Pedro');
        Discipleship::create(['mentor_id' => $ana->member_id, 'disciple_id' => $joao->id]);
        $brunos = Discipleship::create(['mentor_id' => $bruno->member_id, 'disciple_id' => $pedro->id]);
        $this->at('2026-10-21 10:00');
        $meeting = $this->meeting($bruno, [$pedro], '2026-10-22 20:00');

        $this->actingAs($ana)->get('/discipleships')->assertOk()->assertSee('João ZZ Teste')->assertSee('Pedro ZZ Teste');
        // "Only mine".
        $this->actingAs($ana)->get('/discipleships?mentor=me')->assertOk()->assertSee('João ZZ Teste')->assertDontSee('Pedro ZZ Teste');
        $this->actingAs($ana)->get(route('discipleships.history', $brunos))->assertOk();
        $this->actingAs($ana)->get(route('discipleships.meetings.show', $meeting))->assertOk()->assertDontSee(__('Add comment'));
        // Seeing, not changing.
        $this->actingAs($ana)->patch(route('discipleships.meetings.outcome', $meeting), ['status' => 'held'])->assertForbidden();
        $this->actingAs($ana)->get(route('discipleships.edit', $brunos))->assertForbidden();
        $this->actingAs($ana)->post(route('discipleships.meetings.comments.store', $meeting), ['body' => 'x'])->assertForbidden();
        // Not one's disciple: not in a meeting.
        $this->actingAs($ana)->post('/discipleships/meetings', ['disciples' => [$pedro->id], 'date' => '2026-10-23', 'time' => '20:00'])
            ->assertSessionHasErrors('disciples');
        // Without the module, nothing.
        $this->actingAs(User::factory()->withPermissions(['prayers' => ['read']])->create())->get('/discipleships')->assertForbidden();
    }

    public function test_a_user_who_is_no_member_has_no_discipleships(): void
    {
        $user = User::factory()->withPermissions(['discipleships' => ['read', 'write']])->create();
        $this->actingAs($user)->get('/discipleships')->assertOk()->assertSee(__('Your user is not linked to a member: ask an administrator.'));
        $this->actingAs($user)->post('/discipleships', ['disciple_id' => $this->member('João')->id])->assertSessionHasErrors('mentor_id');
        $this->assertSame(0, Discipleship::query()->count());
    }

    public function test_a_group_meeting_in_lisbon_time_is_marked_with_who_was_there_or_why_not(): void
    {
        $ana = $this->mentor('Ana');
        $joao = $this->member('João');
        $pedro = $this->member('Pedro');
        foreach ([$joao, $pedro] as $disciple) {
            Discipleship::create(['mentor_id' => $ana->member_id, 'disciple_id' => $disciple->id]);
        }
        $this->at('2026-10-21 10:00');
        $meeting = $this->meeting($ana, [$joao, $pedro], '2026-10-22 20:00');

        // Summer time in Lisbon (UTC+1).
        $this->assertSame('2026-10-22 19:00:00', $meeting->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame(DiscipleshipMeeting::SCHEDULED, $meeting->status);

        $this->actingAs($ana)->patch(route('discipleships.meetings.outcome', $meeting), ['status' => 'not_held'])->assertSessionHasErrors('reason');
        $this->actingAs($ana)->patch(route('discipleships.meetings.outcome', $meeting), ['status' => 'not_held', 'reason' => 'postponed', 'reason_note' => 'Chuva'])
            ->assertRedirect(route('discipleships.meetings.show', $meeting));
        $this->assertSame(['not_held', 'postponed', 'Chuva'], [$meeting->fresh()->status, $meeting->fresh()->reason, $meeting->fresh()->reason_note]);

        $this->actingAs($ana)->patch(route('discipleships.meetings.outcome', $meeting), ['status' => 'held', 'attended' => [$joao->id]]);
        $meeting->refresh();
        $this->assertSame('held', $meeting->status);
        $this->assertNull($meeting->reason);
        $this->assertSame([$joao->id => 1, $pedro->id => 0], $meeting->disciples->mapWithKeys(fn ($d) => [$d->id => (int) $d->pivot->attended])->all());

        $this->actingAs($ana)->get('/discipleships')->assertOk()->assertSee('22/10/2026');
    }

    public function test_comments_are_added_and_deleted_by_their_author(): void
    {
        $ana = $this->mentor('Ana');
        $joao = $this->member('João');
        Discipleship::create(['mentor_id' => $ana->member_id, 'disciple_id' => $joao->id]);
        $meeting = $this->meeting($ana, [$joao], now()->addDay()->format('Y-m-d') . ' 20:00');

        $this->actingAs($ana)->post(route('discipleships.meetings.comments.store', $meeting), ['body' => 'Falámos sobre oração.'])->assertRedirect();
        $comment = $meeting->comments()->firstOrFail();
        $this->actingAs($ana)->get(route('discipleships.meetings.show', $meeting))->assertSee('Falámos sobre oração.');

        $other = User::factory()->withPermissions(['discipleships' => ['read', 'update', 'delete']])->create(['member_id' => $ana->member_id]);
        $this->actingAs($other)->delete(route('discipleships.meetings.comments.destroy', $comment))->assertForbidden();
        $this->actingAs($ana)->delete(route('discipleships.meetings.comments.destroy', $comment))->assertRedirect();
        $this->assertModelMissing($comment);
    }

    public function test_the_mentor_is_reminded_in_the_morning_and_two_hours_before(): void
    {
        $ana = $this->mentor('Ana');
        $bruno = $this->mentor('Bruno');
        $joao = $this->member('João');
        $pedro = $this->member('Pedro');
        Discipleship::create(['mentor_id' => $ana->member_id, 'disciple_id' => $joao->id]);
        Discipleship::create(['mentor_id' => $bruno->member_id, 'disciple_id' => $pedro->id]);
        $this->at('2026-10-20 18:00');
        $this->meeting($ana, [$joao], '2026-10-21 20:00');
        $this->meeting($bruno, [$pedro], '2026-10-21 09:30');
        $device = fn (User $user) => PushSubscription::create(['user_id' => $user->id, 'endpoint' => 'https://push.example/' . uniqid(),
            'endpoint_hash' => hash('sha256', uniqid()), 'public_key' => 'k', 'auth_token' => 'a']);
        $device($ana);
        $device($bruno);

        // In the morning: Ana, the day's meeting; Bruno's is near, so only the "before" one (with the time).
        $this->at('2026-10-21 08:05');
        app(SendPushDigestService::class)->execute();
        $to = fn (User $user) => collect($this->sent)->filter(fn ($n, $i) => in_array($user->id, $this->to[$i], true))->map(fn ($n) => $n['title'] . ' | ' . $n['body'])->values()->all();
        $this->assertSame(['Encontro de discipulado hoje | 20:00 · João ZZ Teste · Igreja'], $to($ana));
        $this->assertSame(['Encontro de discipulado às 09:30 | Pedro ZZ Teste · Igreja'], $to($bruno));

        // Nothing again an hour later; Ana's two hours before.
        $this->sent = $this->to = [];
        $this->at('2026-10-21 09:05');
        app(SendPushDigestService::class)->execute();
        $this->assertSame([], $this->sent);
        $this->at('2026-10-21 18:05');
        app(SendPushDigestService::class)->execute();
        $this->assertSame(['Encontro de discipulado às 20:00 | João ZZ Teste · Igreja'], $to($ana));
        $this->assertSame([], $to($bruno));
    }

    public function test_a_disciple_passed_to_another_mentor_keeps_their_history_with_the_new_one(): void
    {
        $ana = $this->mentor('Ana');
        $bruno = $this->mentor('Bruno');
        $joao = $this->member('João');
        $first = Discipleship::create(['mentor_id' => $ana->member_id, 'disciple_id' => $joao->id, 'started_at' => '2026-01-10']);
        $this->at('2026-10-10 10:00');
        $old = $this->meeting($ana, [$joao], '2026-10-12 20:00');
        $this->actingAs($ana)->patch(route('discipleships.meetings.outcome', $old), ['status' => 'held', 'attended' => [$joao->id]]);
        $this->actingAs($ana)->post(route('discipleships.meetings.comments.store', $old), ['body' => 'Começou a ler a Bíblia.']);

        $this->actingAs($ana)->get(route('discipleships.edit', $first))->assertOk()->assertSee(__('Pass to another mentor'));
        // Bruno cannot take him while Ana follows him; Ana passes him on.
        $this->actingAs($bruno)->post('/discipleships', ['disciple_id' => $joao->id])->assertSessionHasErrors('disciple_id');
        $this->actingAs($bruno)->post(route('discipleships.transfer', $first), ['mentor_id' => $bruno->member_id])->assertForbidden();
        $this->actingAs($ana)->post(route('discipleships.transfer', $first), ['mentor_id' => $bruno->member_id])->assertRedirect(route('discipleships.index'));
        $this->assertFalse($first->fresh()->is_active);
        $now = Discipleship::query()->where('mentor_id', $bruno->member_id)->where('disciple_id', $joao->id)->firstOrFail();
        $this->assertTrue($now->is_active);

        // Bruno sees the whole history (Ana's meeting and its comments) and comments on it; its outcome stays Ana's.
        $this->actingAs($bruno)->get('/discipleships')->assertSee('12/10/2026');
        $this->actingAs($bruno)->get(route('discipleships.history', $now))->assertOk()->assertSee('Ana ZZ Teste')->assertSee('Bruno ZZ Teste');
        $this->actingAs($bruno)->get(route('discipleships.meetings.show', $old))->assertOk()->assertSee('Começou a ler a Bíblia.');
        $this->actingAs($bruno)->post(route('discipleships.meetings.comments.store', $old), ['body' => 'Continuamos.'])->assertRedirect();
        $this->assertSame(2, $old->comments()->count());
        $this->actingAs($bruno)->patch(route('discipleships.meetings.outcome', $old), ['status' => 'scheduled'])->assertForbidden();
        // Ana no longer follows him: Bruno's new meetings she sees, not comments.
        $this->actingAs($bruno)->post('/discipleships/meetings', ['disciples' => [$joao->id], 'date' => '2026-10-15', 'time' => '20:00'])->assertSessionHasNoErrors();
        $this->actingAs($ana)->post(route('discipleships.meetings.comments.store', DiscipleshipMeeting::query()->latest('id')->first()), ['body' => 'x'])->assertForbidden();
    }
}
