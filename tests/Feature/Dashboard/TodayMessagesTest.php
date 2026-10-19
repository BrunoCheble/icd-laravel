<?php

namespace Tests\Feature\Dashboard;

use App\Enums\MembershipStatus;
use App\Enums\VisitorStatus;
use App\Models\User;
use App\Modules\Dashboard\Models\SentMessage;
use App\Modules\Dashboard\Services\GetTodayMessagesService;
use App\Modules\Members\Models\Member;
use App\Modules\Visitors\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The home page: the messages of the day, on the test database (icd_test, emptied on every run).
 */
class TodayMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-19 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_page_lists_the_messages_of_the_day(): void
    {
        $this->member(['first_name' => 'Helena', 'gender' => 'F', 'date_of_birth' => '1988-10-19', 'phone_number' => '912345678']);
        $this->member(['first_name' => 'Rui', 'date_of_birth' => '1975-10-25']);
        $this->member(['first_name' => 'Velho', 'date_of_birth' => '1950-10-19', 'membership_status' => MembershipStatus::INACTIVED]);
        $wife = $this->member(['first_name' => 'Ana', 'gender' => 'F', 'marital_status' => 'M']);
        $this->member(['first_name' => 'João', 'marital_status' => 'M', 'spouse_id' => $wife->id, 'date_joined' => '2010-10-19', 'phone_number' => '911111111']);
        Visitor::create(['name' => 'Lucas Andrade', 'gender' => 'M', 'created_by' => 'ZZ Teste', 'status' => VisitorStatus::ACTIVED, 'phone_number' => '933333333']);
        $old = Visitor::create(['name' => 'Antigo Visitante', 'gender' => 'M', 'created_by' => 'ZZ Teste', 'status' => VisitorStatus::ACTIVED]);
        $old->forceFill(['created_at' => now()->subDays(20)])->save();
        $this->member(['first_name' => 'Carla', 'gender' => 'F', 'membership_status' => MembershipStatus::PENDING, 'email' => null]);

        $response = $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();

        $response->assertSee('Helena')->assertSee('Feliz aniversário, irmã Helena!')->assertSee('https://wa.me/351912345678');
        $response->assertSee('João &amp; Ana', false)->assertSee('São 16 anos de bênçãos');
        $response->assertSee('Lucas Andrade')->assertDontSee('Antigo Visitante');
        $response->assertSee('Carla')->assertSee('Para concluir o seu registo');
        $response->assertSee('Rui')->assertDontSee('Velho');
    }

    public function test_a_message_is_marked_as_sent_once_and_can_be_undone(): void
    {
        $member = $this->member(['first_name' => 'Helena', 'gender' => 'F', 'date_of_birth' => '1988-10-19']);
        $user = User::factory()->create(['name' => 'Bruno']);

        $this->actingAs($user)->postJson('/dashboard/sent-messages', ['kind' => 'birthday', 'recipient_id' => $member->id])
            ->assertOk()->assertJsonPath('sent.by', 'Bruno')->assertJsonPath('sent.at', '09:00');
        $this->actingAs($user)->postJson('/dashboard/sent-messages', ['kind' => 'birthday', 'recipient_id' => $member->id])->assertOk();
        $this->assertSame(1, SentMessage::where(['kind' => 'birthday', 'recipient_id' => $member->id, 'period' => '2026'])->count());
        $this->assertSame(['by' => 'Bruno', 'at' => '09:00'], app(GetTodayMessagesService::class)->execute(now())['birthdays'][0]['sent']);

        $this->actingAs($user)->deleteJson('/dashboard/sent-messages', ['kind' => 'birthday', 'recipient_id' => $member->id])->assertOk();
        $this->assertSame(0, SentMessage::count());

        $this->actingAs($user)->postJson('/dashboard/sent-messages', ['kind' => 'nope', 'recipient_id' => 1])->assertUnprocessable();
    }

    public function test_a_welcomed_visitor_leaves_the_list_the_next_day(): void
    {
        $visitor = Visitor::create(['name' => 'Lucas Andrade', 'gender' => 'M', 'created_by' => 'ZZ Teste', 'status' => VisitorStatus::ACTIVED]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/dashboard/sent-messages', ['kind' => 'welcome', 'recipient_id' => $visitor->id])->assertOk();
        $this->actingAs($user)->get('/dashboard')->assertSee('Lucas Andrade');

        Carbon::setTestNow('2026-10-20 09:00:00');
        $this->actingAs($user)->get('/dashboard')->assertDontSee('Lucas Andrade');
    }

    public function test_a_reminded_pending_member_waits_a_week(): void
    {
        $member = $this->member(['first_name' => 'Carla', 'gender' => 'F', 'membership_status' => MembershipStatus::PENDING, 'email' => null]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/dashboard/sent-messages', ['kind' => 'pending', 'recipient_id' => $member->id])->assertOk();

        Carbon::setTestNow('2026-10-22 09:00:00');
        $this->actingAs($user)->get('/dashboard')->assertDontSee('Carla');
        Carbon::setTestNow('2026-10-26 09:00:00');
        $this->actingAs($user)->get('/dashboard')->assertSee('Carla');
    }

    public function test_the_page_needs_a_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->postJson('/dashboard/sent-messages', ['kind' => 'birthday', 'recipient_id' => 1])->assertUnauthorized();
    }

    private function member(array $data = []): Member
    {
        static $document = 800000000;

        return Member::create($data + [
            'first_name' => 'ZZ Teste',
            'last_name' => 'Membro',
            'document_number' => (string) $document++,
            'email' => 'zz@example.com',
            'gender' => 'M',
            'marital_status' => 'S',
            'membership_status' => MembershipStatus::ACTIVED,
        ]);
    }
}
