<?php

namespace Tests\Feature\Visitors;

use App\Enums\VisitorStatus;
use App\Models\User;
use App\Modules\Visitors\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The visitors pages, on the test database (icd_test, emptied on every run; see tests/TestCase.php).
 */
class VisitorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_visitors_pages_need_a_login(): void
    {
        $visitor = $this->visitor();

        foreach (['/visitors', '/visitors/create', "/visitors/{$visitor->id}/edit"] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_every_visitors_page_opens(): void
    {
        $visitor = $this->visitor(['invited_by' => 'instagram', 'group' => 'youth']);
        $this->actingAs(User::factory()->create());

        $this->get('/visitors')->assertOk()->assertSee('ZZ Teste Visitante')->assertSee('Instagram');
        $this->get('/visitors/create')->assertOk();
        $this->get("/visitors/{$visitor->id}/edit")->assertOk()->assertSee('ZZ Teste Visitante');
    }

    public function test_the_list_searches_filters_and_sorts(): void
    {
        $this->visitor(['name' => 'Ana Costa', 'city' => 'Porto', 'gender' => 'F', 'group' => 'youth']);
        $this->visitor(['name' => 'Bruno Silva', 'phone_number' => '912345678', 'invited_by' => 'Carlos']);
        $this->visitor(['name' => 'Carla Dias', 'status' => VisitorStatus::INACTIVED]);
        $this->actingAs(User::factory()->create());

        $this->get('/visitors?search=porto')->assertSee('Ana Costa')->assertDontSee('Bruno Silva');
        $this->get('/visitors?search=912345')->assertSee('Bruno Silva')->assertDontSee('Ana Costa');
        $this->get('/visitors?search=carlos')->assertSee('Bruno Silva')->assertDontSee('Ana Costa');
        $this->get('/visitors?status=inactived')->assertSee('Carla Dias')->assertDontSee('Ana Costa');
        $this->get('/visitors?gender=F&group=youth')->assertSee('Ana Costa')->assertDontSee('Bruno Silva');
        $this->get('/visitors?sort=name&direction=desc')->assertSeeInOrder(['Carla Dias', 'Bruno Silva', 'Ana Costa']);
        $this->get('/visitors?sort=nope')->assertSessionHasErrors('sort');
    }

    public function test_the_list_by_period_of_the_visit(): void
    {
        Carbon::setTestNow('2026-10-21 12:00:00'); // a Wednesday
        $at = function (string $name, string $date) {
            $visitor = $this->visitor(['name' => $name]);
            $visitor->forceFill(['created_at' => Carbon::parse($date)])->save();
        };
        $at('Semana Ana', '2026-10-19 10:00');
        $at('Mes Bruno', '2026-10-05 10:00');
        $at('Ano Carla', '2026-03-01 10:00');
        $at('Antigo Duarte', '2025-06-01 10:00');
        $this->actingAs(User::factory()->create());

        $this->get('/visitors?period=week')->assertSee('Semana Ana')->assertDontSee('Mes Bruno');
        $this->get('/visitors?period=month')->assertSee('Mes Bruno')->assertDontSee('Ano Carla');
        $this->get('/visitors?period=year')->assertSee('Ano Carla')->assertDontSee('Antigo Duarte');
        // All: split by period, newest first
        $this->get('/visitors')->assertSeeInOrder(['This week', 'Semana Ana', 'Earlier this month', 'Mes Bruno', 'Earlier this year', 'Ano Carla', 'Before this year', 'Antigo Duarte']);
        $this->get('/visitors?period=nope')->assertSessionHasErrors('period');
        Carbon::setTestNow();
    }

    public function test_a_visitor_is_created_and_updated(): void
    {
        $user = User::factory()->create();
        $data = ['name' => 'zz teste novo', 'gender' => 'M', 'phone_number' => '912 345 678', 'group' => 'adult', 'created_by' => 'Recepção'];

        $this->actingAs($user)->post('/visitors', $data)->assertRedirect('/visitors');
        $visitor = Visitor::where('phone_number', '912345678')->firstOrFail();
        $this->assertSame('Zz Teste Novo', $visitor->name);
        $this->assertSame(VisitorStatus::ACTIVED, $visitor->status);

        $this->actingAs($user)->patch("/visitors/{$visitor->id}", ['city' => 'Braga'] + $data)->assertRedirect('/visitors');
        $this->assertSame('Braga', $visitor->fresh()->city);

        $this->actingAs($user)->post('/visitors', ['gender' => 'X', 'group' => 'nope'] + $data)->assertSessionHasErrors(['gender', 'group']);
    }

    public function test_an_active_visitor_is_deactivated_with_the_reason_and_an_inactive_one_deleted(): void
    {
        $visitor = $this->visitor(['observation' => 'Veio com a família']);
        $user = User::factory()->create();

        $this->actingAs($user)->delete("/visitors/{$visitor->id}", ['reason' => 'Mudou de cidade'])->assertRedirect('/visitors');
        $this->assertSame(VisitorStatus::INACTIVED, $visitor->fresh()->status);
        $this->assertSame('Veio com a família - Motivo da desativação: Mudou de cidade', $visitor->fresh()->observation);

        $this->actingAs($user)->get("/visitors/{$visitor->id}/activate")->assertRedirect();
        $this->assertSame(VisitorStatus::ACTIVED, $visitor->fresh()->status);

        $visitor->update(['status' => VisitorStatus::INACTIVED]);
        $this->actingAs($user)->delete("/visitors/{$visitor->id}")->assertRedirect('/visitors');
        $this->assertModelMissing($visitor);
    }

    private function visitor(array $data = []): Visitor
    {
        return Visitor::create($data + [
            'name' => 'ZZ Teste Visitante',
            'gender' => 'M',
            'created_by' => 'ZZ Teste',
            'status' => VisitorStatus::ACTIVED,
        ]);
    }
}
