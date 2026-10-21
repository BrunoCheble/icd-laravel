<?php

namespace Tests\Feature\Ministries;

use App\Enums\MembershipStatus;
use App\Models\User;
use App\Modules\Members\Models\Member;
use App\Modules\Ministries\Models\Ministry;
use App\Modules\Ministries\Models\MinistryMember;
use App\Modules\Ministries\Support\MinistryBio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ministries and their members (role, order, biography), the member's page, and the site's "Pastores"
 * (test database icd_test, emptied on every run).
 */
class MinistriesTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $first): Member
    {
        static $document = 960000000;

        return Member::create(['first_name' => $first, 'last_name' => 'ZZ Teste', 'document_number' => (string) $document++,
            'gender' => 'M', 'marital_status' => 'S', 'membership_status' => MembershipStatus::ACTIVED]);
    }

    public function test_a_ministry_gets_members_with_a_role_in_order_and_the_member_page_lists_them(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/ministries', ['name' => 'ZZ Teste Pastoral', 'is_pastoral' => '1'])->assertRedirect();
        $ministry = Ministry::query()->where('name', 'ZZ Teste Pastoral')->firstOrFail();
        $this->assertTrue($ministry->isPastoral());

        $ana = $this->member('Ana');
        $joao = $this->member('João');
        $this->post(route('ministries-members.store', $ministry), ['member_id' => $joao->id, 'role' => ' Pastor Presidente '])->assertRedirect();
        $this->post(route('ministries-members.store', $ministry), ['member_id' => $ana->id])->assertRedirect();
        $this->post(route('ministries-members.store', $ministry), ['member_id' => $ana->id])->assertSessionHasErrors('member_id');
        $this->assertSame([$joao->id, $ana->id], $ministry->memberships()->pluck('member_id')->all());
        $this->assertSame(['Pastor Presidente', null], $ministry->memberships()->pluck('role')->all());

        $this->get(route('ministries.show', $ministry))->assertOk()->assertSee('Pastor Presidente')->assertSee('Ana ZZ Teste');
        $this->get(route('members.show', $joao))->assertOk()->assertSee('ZZ Teste Pastoral')->assertSee('Pastor Presidente');

        // Only one ministry is the site's "Pastores".
        $this->post('/ministries', ['name' => 'ZZ Teste Outro', 'is_pastoral' => '1']);
        $this->assertFalse($ministry->fresh()->isPastoral());
    }

    public function test_the_biography_keeps_only_its_shape(): void
    {
        $bio = MinistryBio::clean([
            'layout' => 'nope',
            'sections' => [
                ['title' => ' Formação <b>Académica</b> ', 'blocks' => [
                    ['type' => 'list', 'items' => [['title' => 'Licenciado em Letras', 'text' => 'pela FURB', 'url' => 'javascript:alert(1)'], ['title' => '', 'text' => '']]],
                    ['type' => 'link', 'text' => 'Vídeo:', 'label' => '', 'url' => 'https://youtube.com/x'],
                    ['type' => 'script', 'text' => 'x'],
                ]],
                ['title' => '', 'blocks' => []],
            ],
        ]);

        $this->assertEquals(['layout' => 'sections', 'sections' => [['title' => 'Formação Académica', 'blocks' => [
            ['type' => 'list', 'items' => [['title' => 'Licenciado em Letras', 'text' => 'pela FURB']]],
            ['type' => 'link', 'text' => 'Vídeo:', 'label' => 'https://youtube.com/x', 'url' => 'https://youtube.com/x'],
        ]]]], $bio);
        $this->assertNull(MinistryBio::clean(['sections' => []]));
    }

    public function test_the_site_gets_the_active_pastors_in_order_with_role_and_biography(): void
    {
        $pastoral = Ministry::create(['name' => 'ZZ Teste Pastoral', 'key' => Ministry::PASTORAL]);
        $music = Ministry::create(['name' => 'ZZ Teste Music']);
        $emerson = $this->member('Emerson');
        $ana = $this->member('Ana');
        $rui = $this->member('Rui');
        $pastoral->memberships()->create(['member_id' => $ana->id, 'sort_order' => 2, 'active' => true]);
        $first = $pastoral->memberships()->create(['member_id' => $emerson->id, 'role' => 'Pastor', 'sort_order' => 1, 'active' => true]);
        $pastoral->memberships()->create(['member_id' => $rui->id, 'sort_order' => 3, 'active' => false]);
        $music->memberships()->create(['member_id' => $rui->id, 'active' => true]);

        $this->actingAs(User::factory()->create())->put(route('ministries-members.update', $first), [
            'role' => 'Pastor Presidente', 'active' => '1', 'sort_order' => 1,
            'bio' => json_encode(['layout' => 'simple', 'sections' => [['title' => '', 'blocks' => [['type' => 'text', 'text' => 'Pastor desde 2007.']]]]]),
        ])->assertRedirect();

        $response = $this->getJson('/api/public/pastors')->assertOk();
        $this->assertSame(['Emerson ZZ Teste', 'Ana ZZ Teste'], array_column($response->json('data'), 'name'));
        $this->assertSame(['Pastor Presidente', null], array_column($response->json('data'), 'role'));
        $this->assertSame('simple', $response->json('data.0.bio.layout'));
        $this->assertSame('Pastor desde 2007.', $response->json('data.0.bio.sections.0.blocks.0.text'));
        $this->assertNull($response->json('data.1.bio'));
        $this->assertNull($response->json('data.0.photo'));
    }

    public function test_the_ministries_follow_the_members_permissions(): void
    {
        $ministry = Ministry::create(['name' => 'ZZ Teste Music']);
        $reader = User::factory()->withPermissions(['members' => ['read']])->create();
        $this->actingAs($reader)->get(route('ministries.show', $ministry))->assertOk();
        $this->actingAs($reader)->post(route('ministries-members.store', $ministry), ['member_id' => $this->member('Ana')->id])->assertForbidden();
        $this->actingAs(User::factory()->withPermissions(['prayers' => ['read']])->create())->get('/ministries')->assertForbidden();
        $this->assertSame(0, MinistryMember::query()->count());
    }
}
