<?php

namespace Tests\Feature\Members;

use App\Enums\MembershipStatus;
use App\Models\User;
use App\Modules\Members\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The members pages, on the test database (icd_test, emptied on every run; see tests/TestCase.php). Photos go to a
 * temporary public folder, removed after each test.
 */
class MembersTest extends TestCase
{
    use RefreshDatabase;

    protected string $publicPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicPath = sys_get_temp_dir() . '/icd-public-' . uniqid();
        File::ensureDirectoryExists("{$this->publicPath}/images");
        foreach (File::glob(public_path() . '/*') as $entry) {
            if (basename($entry) !== 'images') {
                symlink($entry, "{$this->publicPath}/" . basename($entry));
            }
        }
        $this->app->usePublicPath($this->publicPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicPath);
        parent::tearDown();
    }

    public function test_the_members_pages_need_a_login(): void
    {
        $member = $this->member();

        foreach (['/members', '/members/create', "/members/{$member->id}", "/members/{$member->id}/edit"] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_every_members_page_opens(): void
    {
        $spouse = $this->member(['first_name' => 'Maria', 'gender' => 'F', 'marital_status' => 'M']);
        $member = $this->member(['marital_status' => 'M', 'spouse_id' => $spouse->id, 'date_joined' => '2010-05-01']);
        $this->member(['first_name' => 'Pedro', 'father_id' => $member->id, 'date_of_birth' => now()->subYears(8)->toDateString()]);
        $this->actingAs(User::factory()->create());

        foreach (['/members', '/members/create', "/members/{$member->id}", "/members/{$member->id}/edit", '/report/anniversaries'] as $uri) {
            $this->get($uri)->assertOk();
        }
        $this->get("/members/{$member->id}")->assertSee('Maria')->assertSee('Pedro');
    }

    public function test_the_list_searches_filters_and_sorts(): void
    {
        $this->member(['first_name' => 'Ana', 'last_name' => 'Costa', 'document_number' => '111', 'gender' => 'F']);
        $this->member(['first_name' => 'Bruno', 'last_name' => 'Silva', 'document_number' => '222', 'phone_number' => '912345678']);
        $this->member(['first_name' => 'Carla', 'last_name' => 'Dias', 'document_number' => '333', 'gender' => 'F', 'membership_status' => MembershipStatus::PENDING]);
        $this->actingAs(User::factory()->create());

        $this->get('/members?search=ana+costa')->assertSee('Ana Costa')->assertDontSee('Bruno Silva');
        $this->get('/members?search=912345')->assertSee('Bruno Silva')->assertDontSee('Ana Costa');
        $this->get('/members?search=333')->assertSee('Carla Dias')->assertDontSee('Ana Costa');
        $this->get('/members?gender=F&membership_status=pending')->assertSee('Carla Dias')->assertDontSee('Ana Costa');
        $this->get('/members?sort=name&direction=desc')->assertSeeInOrder(['Carla Dias', 'Bruno Silva', 'Ana Costa']);
        $this->get('/members?sort=nope')->assertSessionHasErrors('sort');
    }

    public function test_a_member_is_created_and_the_spouse_linked_back(): void
    {
        $spouse = $this->member(['first_name' => 'Maria', 'gender' => 'F', 'marital_status' => 'M']);

        $this->actingAs(User::factory()->create())
            ->post('/members', [
                'first_name' => 'ZZ Teste', 'last_name' => 'Novo', 'document_number' => '999888777', 'gender' => 'M',
                'marital_status' => 'M', 'membership_status' => MembershipStatus::ACTIVED, 'spouse_id' => (string) $spouse->id,
                'date_joined' => '2015-06-20',
            ])
            ->assertRedirect('/members');

        $member = Member::where('document_number', '999888777')->firstOrFail();
        $this->assertEquals($spouse->id, $member->spouse_id);
        $this->assertEquals($member->id, $spouse->fresh()->spouse_id);
        $this->assertSame('2015-06-20', (string) $spouse->fresh()->date_joined);
    }

    public function test_an_active_member_s_missing_details_are_pending_too_and_marked_in_the_form(): void
    {
        $member = $this->member(['date_of_birth' => null, 'phone_number' => '912345678', 'address' => 'Rua A', 'zip_code' => '1000-001', 'city' => 'Lisboa']);
        $inactive = $this->member(['membership_status' => MembershipStatus::INACTIVED]);
        $this->actingAs(User::factory()->create());

        $this->get("/members/{$member->id}")->assertSee('Data de nascimento');
        $this->get("/members/{$member->id}/edit")->assertSee('Pending detail')->assertSeeInOrder(['Date of Birth', 'Pending detail']);
        $this->get("/members/{$inactive->id}/edit")->assertDontSee('Pending detail');
    }

    public function test_the_spouse_is_a_member_or_a_name(): void
    {
        $member = $this->member(['marital_status' => 'M', 'membership_status' => MembershipStatus::PENDING]);
        $wife = $this->member(['first_name' => 'Maria', 'gender' => 'F', 'marital_status' => 'M']);
        $user = User::factory()->create();
        $data = ['first_name' => 'ZZ Teste', 'last_name' => 'Membro', 'document_number' => $member->document_number, 'gender' => 'M', 'marital_status' => 'M'];

        // Not a member: the name is kept, and the spouse is no longer pending
        $this->actingAs($user)->patch("/members/{$member->id}", $data + ['spouse_name' => '  Ana Lima '])->assertSessionHasNoErrors();
        $this->assertSame('Ana Lima', $member->fresh()->spouse_name);
        $this->actingAs($user)->get("/members/{$member->id}")->assertSee('Ana Lima')->assertDontSee('Nome completo da esposa');
        $this->actingAs($user)->get("/members/{$member->id}/edit")->assertSee('Ana Lima');

        // A member picked: the name goes
        $this->actingAs($user)->patch("/members/{$member->id}", $data + ['spouse_id' => (string) $wife->id, 'spouse_name' => 'Ana Lima']);
        $this->assertNull($member->fresh()->spouse_name);
        $this->assertSame((string) $wife->id, (string) $member->fresh()->spouse_id);

        // Linked back: the wife's name-only spouse goes too
        $other = $this->member(['first_name' => 'Rita', 'gender' => 'F', 'marital_status' => 'M', 'spouse_name' => 'Fulano']);
        $this->actingAs($user)->patch("/members/{$member->id}", $data + ['spouse_id' => (string) $other->id]);
        $this->assertSame((string) $member->id, (string) $other->fresh()->spouse_id);
        $this->assertNull($other->fresh()->spouse_name);
    }

    public function test_the_nif_is_unique_and_the_choices_are_checked(): void
    {
        $other = $this->member(['document_number' => '123456789']);
        $member = $this->member(['document_number' => '987654321']);
        $user = User::factory()->create();
        $data = ['first_name' => 'ZZ Teste', 'last_name' => 'Membro', 'gender' => 'M', 'marital_status' => 'S'];

        $this->actingAs($user)->post('/members', $data + ['document_number' => '123456789'])->assertSessionHasErrors('document_number');
        $this->actingAs($user)->post('/members', ['document_number' => '1', 'gender' => 'X'] + $data)->assertSessionHasErrors('gender');
        $this->actingAs($user)->post('/members', $data + ['document_number' => '1', 'father_id' => '999999'])->assertSessionHasErrors('father_id');
        // Its own NIF is fine on update; it cannot be its own father.
        $this->actingAs($user)->patch("/members/{$member->id}", $data + ['document_number' => '987654321'])->assertSessionHasNoErrors();
        $this->actingAs($user)->patch("/members/{$member->id}", $data + ['document_number' => '987654321', 'father_id' => (string) $member->id])->assertSessionHasErrors('father_id');
        $this->assertSame('ZZ Teste', $member->fresh()->first_name);
        $this->assertSame('123456789', $other->fresh()->document_number);
    }

    public function test_an_active_member_is_deactivated_with_the_reason_and_a_pending_one_deleted(): void
    {
        $active = $this->member(['notes' => 'Veio de Lisboa']);
        $pending = $this->member(['membership_status' => MembershipStatus::PENDING]);
        $user = User::factory()->create();

        $this->actingAs($user)->delete("/members/{$active->id}", ['reason' => 'Mudou de cidade'])->assertRedirect('/members');
        $this->assertSame(MembershipStatus::INACTIVED, $active->fresh()->membership_status);
        $this->assertSame('Veio de Lisboa - Motivo da desativação: Mudou de cidade', $active->fresh()->notes);

        $this->actingAs($user)->delete("/members/{$pending->id}")->assertRedirect('/members');
        $this->assertModelMissing($pending);

        $this->actingAs($user)->get("/members/{$active->id}/activate")->assertRedirect();
        $this->assertSame(MembershipStatus::ACTIVED, $active->fresh()->membership_status);
    }

    public function test_a_pending_member_shows_what_is_missing(): void
    {
        $member = $this->member(['membership_status' => MembershipStatus::PENDING, 'marital_status' => 'M', 'email' => null]);

        $this->actingAs(User::factory()->create())
            ->get("/members/{$member->id}")
            ->assertOk()
            ->assertSee('Nome completo da esposa')
            ->assertSee('E-mail');
    }

    public function test_the_photo_is_replaced(): void
    {
        $member = $this->member();
        $user = User::factory()->create();

        $this->actingAs($user)->from("/members/{$member->id}")
            ->patch("/members/{$member->id}/photo", ['photo' => $this->png('a.png')])
            ->assertRedirect("/members/{$member->id}");
        $first = $member->fresh()->photo;
        $this->assertFileExists(public_path("images/{$first}"));

        $this->actingAs($user)->patch("/members/{$member->id}/photo", ['photo' => UploadedFile::fake()->create('a.svg', 1, 'image/svg+xml')])
            ->assertSessionHasErrors('photo');
        $this->actingAs($user)->patch("/members/{$member->id}/photo", ['photo' => $this->png('b.png')]);
        $this->assertFileDoesNotExist(public_path("images/{$first}"));
        $this->assertFileExists(public_path('images/' . $member->fresh()->photo));
        unlink(public_path('images/' . $member->fresh()->photo));
    }

    /** A 1×1 PNG (the test container has no GD to draw one). */
    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
    }

    private function member(array $data = []): Member
    {
        static $document = 500000000;

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
