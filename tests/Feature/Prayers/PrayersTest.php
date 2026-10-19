<?php

namespace Tests\Feature\Prayers;

use App\Models\User;
use App\Modules\Prayers\Models\Prayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The prayer requests page, on the test database (icd_test, emptied on every run).
 */
class PrayersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-21 12:00:00'); // a Wednesday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_page_needs_a_login(): void
    {
        $this->get('/prayers')->assertRedirect('/login');
        $prayer = $this->prayer('ZZ Teste', 'Pela família', now());
        $this->delete("/prayers/{$prayer->id}")->assertRedirect('/login');
        $this->assertModelExists($prayer);
    }

    public function test_the_requests_are_listed_by_period_and_searched(): void
    {
        $this->prayer('Ana Hoje', 'Pela saúde da mãe', now()->subHour());
        $this->prayer('Bruno Segunda', 'Pelo emprego', Carbon::parse('2026-10-19 09:00'));
        $this->prayer('Carla Mês', null, Carbon::parse('2026-10-02 09:00'));
        $this->prayer('Duarte Antigo', 'Pela viagem', Carbon::parse('2026-08-01 09:00'));
        $this->actingAs(User::factory()->create());

        $this->get('/prayers')->assertOk()->assertSeeInOrder(['Ana Hoje', 'Bruno Segunda', 'Carla Mês', 'Duarte Antigo']);
        $this->get('/prayers?period=today')->assertSee('Ana Hoje')->assertDontSee('Bruno Segunda');
        $this->get('/prayers?period=week')->assertSee('Bruno Segunda')->assertDontSee('Carla Mês');
        $this->get('/prayers?period=month')->assertSee('Carla Mês')->assertDontSee('Duarte Antigo');
        $this->get('/prayers?search=emprego')->assertSee('Bruno Segunda')->assertDontSee('Ana Hoje');
        $this->get('/prayers?search=duarte')->assertSee('Duarte Antigo')->assertDontSee('Carla Mês');
        $this->get('/prayers?period=nope')->assertSessionHasErrors('period');
    }

    public function test_a_request_is_deleted(): void
    {
        $prayer = $this->prayer('ZZ Teste', 'Pela família', now());

        $this->actingAs(User::factory()->create())->from('/prayers?period=today')
            ->delete("/prayers/{$prayer->id}")
            ->assertRedirect('/prayers?period=today');
        $this->assertModelMissing($prayer);
    }

    public function test_anyone_leaves_a_request_on_the_public_page(): void
    {
        $this->get('/prayer-request')->assertOk()->assertSee('name="phone_number"', false);

        $this->post('/new-prayer-request', ['name' => 'zz teste maria', 'request' => 'Pela minha família', 'phone_number' => '+351 912 345 678'])
            ->assertRedirect('/prayer-request');
        $this->get('/prayer-request')->assertSee('We received your request.');
        $prayer = Prayer::where('name', 'Zz Teste Maria')->firstOrFail();
        $this->assertSame('912345678', $prayer->phone_number);
        $this->assertSame('Pela minha família', $prayer->request);

        // A foreign number keeps its code; the mobile number is optional
        $this->post('/new-prayer-request', ['name' => 'ZZ Teste Ana', 'phone_number' => '+55 11 98765-4321']);
        $this->assertSame('5511987654321', Prayer::where('name', 'ZZ Teste Ana')->value('phone_number'));
        $this->post('/new-prayer-request', ['name' => 'ZZ Teste Sem Telemóvel'])->assertSessionHasNoErrors();

        $this->post('/new-prayer-request', ['name' => '', 'phone_number' => '123'])->assertSessionHasErrors(['name', 'phone_number']);
        // A bot filling the hidden field
        $this->post('/new-prayer-request', ['name' => 'Spam', 'website' => 'http://spam'])->assertSessionHasErrors('website');
        $this->assertFalse(Prayer::where('name', 'Spam')->exists());
    }

    public function test_the_list_offers_to_answer_on_whatsapp(): void
    {
        $this->prayer('ZZ Teste Maria', 'Pela família', now())->update(['phone_number' => '912345678']);

        $this->actingAs(User::factory()->create())->get('/prayers')->assertSee('912345678')->assertSee('351912345678');
    }

    private function prayer(string $name, ?string $request, Carbon $at): Prayer
    {
        $prayer = Prayer::create(['name' => $name, 'request' => $request]);
        $prayer->forceFill(['created_at' => $at])->save();
        return $prayer;
    }
}
