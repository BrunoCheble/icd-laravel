<?php

namespace Tests\Feature\Members;

use App\Enums\MembershipStatus;
use App\Models\User;
use App\Modules\Members\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The month's birthdays and wedding anniversaries, on the test database (icd_test, emptied on every run).
 */
class AnniversariesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_month_shows_its_birthdays_in_day_order_and_each_couple_once(): void
    {
        $this->member(['first_name' => 'Rui', 'date_of_birth' => '1990-03-20']);
        $this->member(['first_name' => 'Ana', 'gender' => 'F', 'date_of_birth' => '1985-03-02']);
        $this->member(['first_name' => 'Zé', 'date_of_birth' => '1985-04-02']);
        $wife = $this->member(['first_name' => 'Maria', 'gender' => 'F', 'marital_status' => 'M']);
        $this->member(['first_name' => 'João', 'marital_status' => 'M', 'spouse_id' => $wife->id, 'date_joined' => '2000-03-15']);
        $this->actingAs(User::factory()->create());

        $response = $this->get('/report/anniversaries?month=3')->assertOk();
        $response->assertSeeInOrder(['Ana', 'Rui'])->assertDontSee('Zé');
        $response->assertSee('João')->assertSee('Maria');
        $this->assertSame(1, substr_count($response->getContent(), '>João</a>'));

        $this->get('/report/anniversaries?month=13')->assertSessionHasErrors('month');
        $this->get('/report/anniversaries')->assertOk();
    }

    private function member(array $data = []): Member
    {
        static $document = 600000000;

        return Member::create($data + [
            'first_name' => 'ZZ Teste',
            'last_name' => 'Membro',
            'document_number' => (string) $document++,
            'gender' => 'M',
            'marital_status' => 'S',
            'membership_status' => MembershipStatus::ACTIVED,
        ]);
    }
}
