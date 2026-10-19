<?php

namespace Tests\Feature\Announcements;

use App\Enums\MembershipStatus;
use App\Models\User;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Members\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The announcements pages, on the test database (icd_test, emptied on every run).
 */
class AnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_pages_need_a_login(): void
    {
        $announcement = $this->announcement();
        foreach (['/announcements', '/announcements/create', "/announcements/{$announcement->id}/edit"] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_every_page_opens(): void
    {
        $announcement = $this->announcement(['title' => 'ZZ Teste Bolos caseiros']);
        $this->actingAs(User::factory()->create());

        $this->get('/announcements')->assertOk()->assertSee('ZZ Teste Bolos caseiros');
        $this->get('/announcements/create')->assertOk();
        $this->get("/announcements/{$announcement->id}/edit")->assertOk()->assertSee('ZZ Teste Bolos caseiros');
    }

    public function test_the_list_by_status_type_and_search(): void
    {
        $this->announcement(['title' => 'Pendente Um', 'is_approved' => false]);
        $this->announcement(['title' => 'Publicado Dois', 'is_approved' => true, 'expires_at' => now()->addWeek(), 'type' => 'service']);
        $this->announcement(['title' => 'Expirado Tres', 'is_approved' => true, 'expires_at' => now()->subDay()]);
        $this->actingAs(User::factory()->create());

        $this->get('/announcements?status=pending')->assertSee('Pendente Um')->assertDontSee('Publicado Dois');
        $this->get('/announcements?status=published')->assertSee('Publicado Dois')->assertDontSee('Expirado Tres');
        $this->get('/announcements?status=expired')->assertSee('Expirado Tres')->assertDontSee('Pendente Um');
        $this->get('/announcements?type=service')->assertSee('Publicado Dois')->assertDontSee('Pendente Um');
        $this->get('/announcements?search=dois')->assertSee('Publicado Dois')->assertDontSee('Expirado Tres');
        $this->get('/announcements?status=nope')->assertSessionHasErrors('status');
    }

    public function test_an_announcement_is_created_without_an_expiry_and_updated(): void
    {
        $member = $this->member();
        $user = User::factory()->create();
        $data = ['member_id' => $member->id, 'type' => 'product', 'title' => 'ZZ Teste Novo', 'description' => 'Texto', 'contact' => '912345678', 'is_approved' => '1'];

        $this->actingAs($user)->post('/announcements', $data)->assertRedirect('/announcements');
        $announcement = Announcement::where('title', 'ZZ Teste Novo')->firstOrFail();
        $this->assertTrue($announcement->is_approved);
        $this->assertNotNull($announcement->approved_at);
        $this->assertNull($announcement->expires_at);

        $this->actingAs($user)->patch("/announcements/{$announcement->id}", ['is_approved' => '0', 'expires_at' => '2026-12-31'] + $data)->assertRedirect('/announcements');
        $announcement->refresh();
        $this->assertFalse($announcement->is_approved);
        $this->assertNull($announcement->approved_at);
        $this->assertSame('2026-12-31', $announcement->expires_at);

        $this->actingAs($user)->post('/announcements', ['type' => 'nope', 'image_path' => UploadedFile::fake()->create('x.exe', 1)] + $data)->assertSessionHasErrors(['type', 'image_path']);
    }

    public function test_approve_take_back_and_delete(): void
    {
        $announcement = $this->announcement(['is_approved' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->from('/announcements?status=pending')->patch("/announcements/{$announcement->id}/approval", ['approved' => '1'])->assertRedirect('/announcements?status=pending');
        $this->assertTrue($announcement->fresh()->is_approved);
        $this->assertNotNull($announcement->fresh()->approved_at);

        $this->actingAs($user)->patch("/announcements/{$announcement->id}/approval", ['approved' => '0']);
        $this->assertFalse($announcement->fresh()->is_approved);

        $this->actingAs($user)->delete("/announcements/{$announcement->id}")->assertRedirect();
        $this->assertModelMissing($announcement);
    }

    public function test_the_site_api_lists_only_approved_announcements_not_expired(): void
    {
        $this->announcement(['title' => 'Pendente Um', 'is_approved' => false]);
        $this->announcement(['title' => 'Publicado Dois', 'is_approved' => true, 'expires_at' => now()->addWeek()]);
        $this->announcement(['title' => 'Sem Prazo', 'is_approved' => true]);
        $this->announcement(['title' => 'Expirado Tres', 'is_approved' => true, 'expires_at' => now()->subDay()]);

        $titles = collect($this->getJson('/api/public/announcements')->assertOk()->json('data'))->pluck('title')->sort()->values()->all();
        $this->assertSame(['Publicado Dois', 'Sem Prazo'], $titles);
    }

    private function announcement(array $data = []): Announcement
    {
        return Announcement::create($data + [
            'member_id' => $this->member()->id,
            'type' => 'product',
            'title' => 'ZZ Teste Aviso',
            'description' => 'Texto do aviso',
            'contact' => '912345678',
            'is_approved' => false,
        ]);
    }

    private function member(): Member
    {
        static $document = 950000000;

        return Member::create([
            'first_name' => 'ZZ Teste', 'last_name' => 'Membro', 'document_number' => (string) $document++,
            'gender' => 'M', 'marital_status' => 'S', 'membership_status' => MembershipStatus::ACTIVED,
        ]);
    }
}
