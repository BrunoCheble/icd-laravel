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

    private string $publicPath;

    // Files go to a temporary public folder, removed after each test.
    protected function setUp(): void
    {
        parent::setUp();
        $this->publicPath = sys_get_temp_dir() . '/icd-public-' . uniqid();
        \Illuminate\Support\Facades\File::ensureDirectoryExists("{$this->publicPath}/images/announcements");
        // The rest of the public folder (the built assets…) as it is.
        foreach (\Illuminate\Support\Facades\File::glob(public_path() . '/*') as $entry) {
            if (basename($entry) !== 'images') {
                symlink($entry, "{$this->publicPath}/" . basename($entry));
            }
        }
        copy(public_path('images/announcements/default.jpg'), "{$this->publicPath}/images/announcements/default.jpg");
        $this->app->usePublicPath($this->publicPath);
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\File::deleteDirectory($this->publicPath);
        parent::tearDown();
    }

    /** The fields of a service (see AnnouncementCategories). */
    private const SERVICE = ['type' => 'service', 'details' => ['area' => 'construction', 'location' => 'Amadora']];

    /** A 1×1 PNG (the test container has no GD to draw one). */
    private function png(string $name = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
    }

    public function test_a_resume_takes_its_pdf_and_a_new_one_replaces_it(): void
    {
        $member = $this->member();
        $user = User::factory()->create();
        $data = ['member_id' => $member->id, 'type' => 'resume', 'title' => 'ZZ Teste Currículo', 'description' => 'Texto', 'contact' => '912345678', 'is_approved' => '1',
            'details' => ['area' => 'restaurant', 'experience' => 'none', 'availability' => 'full', 'driving' => '1']];

        $this->actingAs($user)->post('/announcements', $data + ['image_path' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf')])->assertRedirect('/announcements');
        $announcement = Announcement::where('title', 'ZZ Teste Currículo')->firstOrFail();
        $first = $announcement->image_path;
        $this->assertStringStartsWith('images/announcements/', $first);
        $this->assertFileExists(public_path($first));
        $this->assertTrue($announcement->isPdf());
        $lines = $announcement->detail_lines;
        $this->assertSame('Driving licence: Yes', end($lines)['short']);
        // The database keeps the keys in its own order
        $this->assertEquals(['area' => 'restaurant', 'experience' => 'none', 'availability' => 'full', 'driving' => true], $announcement->details);
        // The picture is always the category's banner
        $this->assertSame(asset('images/announcements/categories/resume.svg'), $announcement->picture_url);

        $this->actingAs($user)->patch("/announcements/{$announcement->id}", $data + ['image_path' => UploadedFile::fake()->create('novo.pdf', 20, 'application/pdf')]);
        $this->assertFileDoesNotExist(public_path($first));
        $this->assertFileExists(public_path($announcement->fresh()->image_path));

        // No pictures, and no file for the other categories
        $this->actingAs($user)->post('/announcements', ['image_path' => UploadedFile::fake()->create('foto.png', 5, 'image/png')] + $data)->assertSessionHasErrors('image_path');
        $this->actingAs($user)->post('/announcements', ['title' => 'ZZ Teste Serviço', 'image_path' => UploadedFile::fake()->create('x.pdf', 5, 'application/pdf')] + self::SERVICE + $data)->assertRedirect('/announcements');
        $this->assertNull(Announcement::where('title', 'ZZ Teste Serviço')->value('image_path'));
    }

    public function test_each_category_asks_for_its_own_fields(): void
    {
        $member = $this->member();
        $this->actingAs(User::factory()->create());
        $data = ['member_id' => $member->id, 'title' => 'ZZ Teste Campos', 'description' => 'Texto', 'contact' => '912345678'];

        $this->post('/announcements', $data + ['type' => 'service'])->assertSessionHasErrors(['details.area', 'details.location']);
        $this->post('/announcements', $data + ['type' => 'lessons', 'details' => ['subject' => 'Matemática', 'level' => 'nope', 'mode' => 'online']])->assertSessionHasErrors('details.level');
        $this->post('/announcements', $data + ['type' => 'product'] + self::SERVICE)->assertSessionHasErrors('type');

        // Only the category's fields are kept, without the empty ones
        $this->post('/announcements', $data + ['type' => 'job', 'details' => ['role' => ' Cozinheiro ', 'area' => 'restaurant', 'contract' => 'part', 'location' => 'Sintra', 'company' => 'outra', 'extra' => 'x']])->assertRedirect('/announcements');
        $announcement = Announcement::where('title', 'ZZ Teste Campos')->firstOrFail();
        // The database keeps the keys in its own order
        $this->assertEquals(['role' => 'Cozinheiro', 'area' => 'restaurant', 'contract' => 'part', 'location' => 'Sintra'], $announcement->details);
        $this->assertSame(['Cozinheiro', 'Restaurants and kitchen', 'Part time', 'Sintra'], array_column($announcement->detail_lines, 'value'));
        $this->assertSame(['Role', 'Area', 'Contract', 'Place of work'], array_column($announcement->detail_lines, 'label'));
    }

    public function test_a_picture_sent_before_is_served_from_storage(): void
    {
        $folder = storage_path('app/public/announcements');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($folder);
        $name = 'zzteste' . uniqid() . '.png';
        file_put_contents("{$folder}/{$name}", base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
        try {
            $announcement = $this->announcement(['image_path' => "storage/announcements/{$name}", 'is_approved' => true]);
            $this->assertSame(route('announcements.file', $name), $announcement->url_image);
            $this->get("/announcements/files/{$name}")->assertOk()->assertHeader('Content-Type', 'image/png');
            $this->get('/announcements/files/nao-existe.png')->assertNotFound();
            $this->get('/announcements/files/..%2F..%2F.env')->assertNotFound();
        } finally {
            @unlink("{$folder}/{$name}");
        }
    }

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
        $this->announcement(['title' => 'Publicado Dois', 'is_approved' => true, 'expires_at' => now()->addWeek(), 'type' => 'lessons', 'details' => ['subject' => 'Inglês']]);
        $this->announcement(['title' => 'Expirado Tres', 'is_approved' => true, 'expires_at' => now()->subDay()]);
        $this->actingAs(User::factory()->create());

        $this->get('/announcements?status=pending')->assertSee('Pendente Um')->assertDontSee('Publicado Dois');
        $this->get('/announcements?status=published')->assertSee('Publicado Dois')->assertDontSee('Expirado Tres');
        $this->get('/announcements?status=expired')->assertSee('Expirado Tres')->assertDontSee('Pendente Um');
        $this->get('/announcements?type=lessons')->assertSee('Publicado Dois')->assertSee('Inglês')->assertDontSee('Pendente Um');
        $this->get('/announcements?search=dois')->assertSee('Publicado Dois')->assertDontSee('Expirado Tres');
        $this->get('/announcements?status=nope')->assertSessionHasErrors('status');
    }

    public function test_an_announcement_is_created_without_an_expiry_and_updated(): void
    {
        $member = $this->member();
        $user = User::factory()->create();
        $data = ['member_id' => $member->id, 'title' => 'ZZ Teste Novo', 'description' => 'Texto', 'contact' => '912345678', 'is_approved' => '1'] + self::SERVICE;

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

        $this->actingAs($user)->post('/announcements', ['type' => 'nope', 'image_path' => UploadedFile::fake()->create('x.exe', 1)] + $data)->assertSessionHasErrors('type');
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

    public function test_the_site_api_lists_only_approved_announcements_not_expired_of_a_category(): void
    {
        $this->announcement(['title' => 'Pendente Um', 'is_approved' => false]);
        $this->announcement(['title' => 'Publicado Dois', 'is_approved' => true, 'expires_at' => now()->addWeek()]);
        $this->announcement(['title' => 'Sem Prazo', 'is_approved' => true]);
        $this->announcement(['title' => 'Expirado Tres', 'is_approved' => true, 'expires_at' => now()->subDay()]);
        // The old types (products, donations) are no longer on the site
        $this->announcement(['title' => 'Produto Antigo', 'is_approved' => true, 'type' => 'product', 'details' => null]);

        $titles = collect($this->getJson('/api/public/announcements')->assertOk()->json('data'))->pluck('title')->sort()->values()->all();
        $this->assertSame(['Publicado Dois', 'Sem Prazo'], $titles);
    }

    public function test_a_member_announces_on_the_public_page_and_it_waits_for_approval(): void
    {
        $member = $this->member();
        $this->get('/announcement')->assertOk()->assertSee('name="member_document"', false);

        $this->post('/new-announcement', ['member_document' => $member->document_number, 'title' => 'ZZ Teste Pintor',
            'description' => 'Pinturas', 'contact' => '912 345 678'] + self::SERVICE)->assertRedirect('/announcement');
        $announcement = Announcement::where('title', 'ZZ Teste Pintor')->firstOrFail();
        $this->assertFalse($announcement->is_approved);
        $this->assertSame($member->id, $announcement->member_id);
        $this->assertNull($announcement->link);
        // The database keeps the keys in its own order
        $this->assertEquals(['area' => 'construction', 'location' => 'Amadora'], $announcement->details);
        // As long as a service usually lasts
        $this->assertSame(now()->addMonths(6)->toDateString(), $announcement->expires_at);
        $this->get('/announcement')->assertSee('O seu serviço foi recebido!');

        // Not a member, or a bot
        $this->post('/new-announcement', ['member_document' => '000000000', 'title' => 'X', 'description' => 'X', 'contact' => '9'] + self::SERVICE)
            ->assertSessionHasErrors('member_document');
        $this->post('/new-announcement', ['member_document' => $member->document_number, 'title' => 'Spam', 'description' => 'X', 'contact' => '9', 'website' => 'http://spam'] + self::SERVICE)
            ->assertSessionHasErrors('website');
        $this->assertFalse(Announcement::where('title', 'Spam')->exists());
    }

    public function test_repeated_requests_are_limited(): void
    {
        $member = $this->member();
        $form = ['member_document' => $member->document_number, 'description' => 'X', 'contact' => '912345678'] + self::SERVICE;
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/new-announcement', $form + ['title' => "ZZ Teste {$i}"])->assertRedirect();
        }
        $this->post('/new-announcement', $form + ['title' => 'ZZ Teste 6'])->assertStatus(429);

        for ($i = 1; $i <= 10; $i++) {
            $this->get("/check-document-number/{$member->document_number}")->assertOk();
        }
        $this->get("/check-document-number/{$member->document_number}")->assertStatus(429);
    }

    public function test_the_site_api_is_cached_until_an_announcement_changes(): void
    {
        $announcement = $this->announcement(['title' => 'Primeiro', 'is_approved' => true]);
        $response = $this->getJson('/api/public/announcements')->assertOk()->assertJsonPath('data.0.title', 'Primeiro')
            ->assertJsonPath('data.0.type_name', 'Serviços')->assertJsonPath('data.0.whatsapp', 'https://wa.me/351912345678')
            ->assertJsonMissingPath('data.0.member_id')
            // The category's banner and its fields, in Portuguese
            ->assertJsonPath('data.0.image_url', asset('images/announcements/categories/service.svg'))
            ->assertJsonPath('data.0.details', [['label' => 'Área', 'value' => 'Construção e reparações', 'short' => 'Construção e reparações'], ['label' => 'Localização', 'value' => 'Amadora', 'short' => 'Amadora']])
            ->assertJsonPath('data.0.rating', ['average' => null, 'count' => 0])->assertJsonMissingPath('data.0.comments');
        $this->assertStringContainsString('max-age=60', $response->headers->get('Cache-Control'));

        // Cached: a change made without the model (as by hand in the database) is not seen…
        \Illuminate\Support\Facades\DB::table('announcements')->where('id', $announcement->id)->update(['title' => 'Escondido']);
        $this->getJson('/api/public/announcements')->assertJsonPath('data.0.title', 'Primeiro');
        // …while any change through the system clears the cache at once
        $announcement->fresh()->update(['title' => 'Segundo']);
        $this->getJson('/api/public/announcements')->assertJsonPath('data.0.title', 'Segundo');
        $this->actingAs(\App\Models\User::factory()->create())->patch("/announcements/{$announcement->id}/approval", ['approved' => '0']);
        $this->getJson('/api/public/announcements')->assertJsonCount(0, 'data');
    }

    public function test_members_send_private_comments_only_the_team_sees(): void
    {
        $member = $this->member();
        $announcement = $this->announcement(['title' => 'ZZ Teste Comentado', 'is_approved' => true]);
        $url = $this->getJson('/api/public/announcements')->json('data.0.comments_url');
        $this->assertSame(route('api.announcements.comments', $announcement), $url);

        // From the site: a member (the NIF), not a bot
        $this->postJson($url, ['member_document' => $member->document_number, 'body' => 'Não atende o telefone, ainda está disponível?'])->assertCreated()
            ->assertJsonPath('message', 'Obrigado! O seu comentário foi enviado à equipa da igreja.');
        $this->postJson($url, ['member_document' => '000000000', 'body' => 'Olá'])->assertUnprocessable()
            ->assertJsonValidationErrors(['member_document' => 'Só os membros podem comentar. Se é membro, confirme o NIF ou fale connosco.']);
        $this->postJson($url, ['member_document' => $member->document_number, 'body' => 'Spam', 'website' => 'http://spam'])->assertJsonValidationErrors('website');
        $comment = $announcement->comments()->firstOrFail();
        $this->assertNull($comment->read_at);
        $this->assertSame($member->id, $comment->member_id);

        // Never on the site; in the admin (the tab of the ones not read, the card)
        $this->assertStringNotContainsString('ainda está disponível', $this->getJson('/api/public/announcements')->getContent());
        $user = User::factory()->create();
        $this->actingAs($user)->get('/announcements?status=comments')->assertSee('ZZ Teste Comentado');

        $this->actingAs($user)->patch("/announcements/comments/{$comment->id}/read", ['read' => '1'])->assertRedirect();
        $this->assertNotNull($comment->fresh()->read_at);
        $this->actingAs($user)->get('/announcements?status=comments')->assertDontSee('ZZ Teste Comentado');

        $this->actingAs($user)->delete("/announcements/comments/{$comment->id}")->assertRedirect();
        $this->assertModelMissing($comment);
    }

    public function test_only_announcements_on_the_site_take_comments_a_few_at_a_time(): void
    {
        $member = $this->member();
        $pending = $this->announcement(['is_approved' => false]);
        $old = $this->announcement(['is_approved' => true, 'type' => 'product', 'details' => null]);
        foreach ([$pending, $old] as $announcement) {
            $this->postJson(route('api.announcements.comments', $announcement), ['member_document' => $member->document_number, 'body' => 'Olá a todos'])->assertNotFound();
            $this->postJson(route('api.announcements.ratings', $announcement), ['member_document' => $member->document_number, 'stars' => 5])->assertNotFound();
        }

        $url = route('api.announcements.comments', $this->announcement(['is_approved' => true]));
        for ($i = 1; $i <= 3; $i++) {
            $this->postJson($url, ['member_document' => $member->document_number, 'body' => "Comentário {$i}"])->assertCreated();
        }
        $this->postJson($url, ['member_document' => $member->document_number, 'body' => 'Comentário 6'])->assertStatus(429);
    }

    public function test_members_rate_once_and_can_change_their_stars(): void
    {
        $author = $this->member();
        $first = $this->member();
        $second = $this->member();
        $announcement = $this->announcement(['is_approved' => true, 'member_id' => $author->id]);
        $url = $this->getJson('/api/public/announcements')->json('data.0.ratings_url');

        $this->postJson($url, ['member_document' => $first->document_number, 'stars' => 5])->assertCreated()->assertJsonPath('rating', ['average' => 5, 'count' => 1]);
        $this->postJson($url, ['member_document' => $second->document_number, 'stars' => 4])->assertCreated();
        // A second vote replaces the first one
        $this->postJson($url, ['member_document' => $first->document_number, 'stars' => 3])->assertOk()->assertJsonPath('message', 'A sua avaliação foi alterada.');
        $this->assertSame(2, $announcement->ratings()->count());
        $this->getJson('/api/public/announcements')->assertJsonPath('data.0.rating', ['average' => 3.5, 'count' => 2]);

        // Not their own announcement, not without being a member, only 1 to 5
        $this->postJson($url, ['member_document' => $author->document_number, 'stars' => 5])->assertJsonValidationErrors(['member_document' => 'Não pode avaliar o seu próprio anúncio.']);
        $this->postJson($url, ['member_document' => '000000000', 'stars' => 5])->assertJsonValidationErrors('member_document');
        $this->postJson($url, ['member_document' => $first->document_number, 'stars' => 6])->assertJsonValidationErrors(['stars' => 'Escolha de 1 a 5 estrelas.']);

        $this->actingAs(User::factory()->create())->get('/announcements')->assertSee('3,5 (2)');
    }

    public function test_donations_and_sales_take_up_to_three_photos(): void
    {
        $member = $this->member();
        $form = ['member_document' => $member->document_number, 'title' => 'ZZ Teste Doação', 'description' => 'Roupa', 'contact' => '912345678',
            'type' => 'donation', 'details' => ['condition' => 'used']];

        $this->post('/new-announcement', $form + ['photos' => [$this->png('a.png'), $this->png('b.png'), $this->png('c.png'), $this->png('d.png')]])->assertSessionHasErrors('photos');
        $this->post('/new-announcement', $form + ['photos' => [UploadedFile::fake()->create('x.pdf', 5, 'application/pdf')]])->assertSessionHasErrors('photos.0');
        $this->post('/new-announcement', $form + ['photos' => [$this->png('a.png'), $this->png('b.png')]])->assertRedirect('/announcement');
        $announcement = Announcement::where('title', 'ZZ Teste Doação')->firstOrFail();
        $this->assertCount(2, $announcement->photos);
        foreach ($announcement->photos as $path) {
            $this->assertFileExists(public_path($path));
        }
        // The card keeps the category's banner (the photos go apart, in a gallery)
        $this->assertSame(asset('images/announcements/categories/donation.svg'), $announcement->picture_url);
        $this->assertSame([asset($announcement->photos[0]), asset($announcement->photos[1])], $announcement->photo_urls);
        // Other categories take none
        $this->post('/new-announcement', ['title' => 'ZZ Teste Sem Fotos', 'photos' => [$this->png()]] + self::SERVICE + $form)->assertSessionHasErrors('photos');

        // The team removes one and adds another; deleting the announcement deletes its photos
        $user = User::factory()->create();
        [$kept, $removed] = $announcement->photos;
        $this->actingAs($user)->patch("/announcements/{$announcement->id}", ['member_id' => $member->id, 'title' => 'ZZ Teste Doação', 'description' => 'Roupa', 'contact' => '912345678',
            'type' => 'donation', 'details' => ['condition' => 'used'], 'remove_photos' => [$removed], 'photos' => [$this->png('nova.png')]])->assertRedirect('/announcements');
        $photos = $announcement->fresh()->photos;
        $this->assertCount(2, $photos);
        $this->assertSame($kept, $photos[0]);
        $this->assertFileDoesNotExist(public_path($removed));
        $this->actingAs($user)->delete("/announcements/{$announcement->id}");
        foreach ($photos as $path) {
            $this->assertFileDoesNotExist(public_path($path));
        }
    }

    public function test_a_member_has_up_to_three_sales_at_a_time(): void
    {
        $member = $this->member();
        $form = ['member_document' => $member->document_number, 'description' => 'Usado', 'contact' => '912345678', 'type' => 'sale', 'details' => ['price' => '10 €', 'condition' => 'used']];
        foreach ([1, 2, 3] as $n) {
            $this->post('/new-announcement', $form + ['title' => "ZZ Teste Venda {$n}"])->assertSessionHasNoErrors();
        }
        $this->post('/new-announcement', $form + ['title' => 'ZZ Teste Venda 4'])->assertSessionHasErrors('type');

        // An expired one no longer counts
        Announcement::where('title', 'ZZ Teste Venda 1')->update(['is_approved' => true, 'expires_at' => now()->subDay()]);
        $this->post('/new-announcement', $form + ['title' => 'ZZ Teste Venda 5'])->assertSessionHasNoErrors();
    }

    public function test_every_category_takes_up_to_three_at_a_time_per_member(): void
    {
        // More sendings than the per-minute limit, which is not what this is about
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $member = $this->member();
        $form = ['member_document' => $member->document_number, 'description' => 'X', 'contact' => '912345678'] + self::SERVICE;
        foreach ([1, 2, 3] as $n) {
            $this->postJson('/api/public/announcements', $form + ['title' => "ZZ Teste Serviço {$n}"])->assertCreated();
        }
        $this->postJson('/api/public/announcements', $form + ['title' => 'ZZ Teste Serviço 4'])->assertJsonValidationErrors('type');
        // Another category is counted apart; another member too
        $this->postJson('/api/public/announcements', ['type' => 'lessons', 'details' => ['subject' => 'Inglês', 'level' => 'adults', 'mode' => 'online'], 'title' => 'ZZ Teste Aulas'] + $form)->assertCreated();
        $this->postJson('/api/public/announcements', ['member_document' => $this->member()->document_number, 'title' => 'ZZ Teste Outro'] + $form)->assertCreated();
        // The team may still add more in the admin
        $this->actingAs(\App\Models\User::factory()->create())->post('/announcements', ['member_id' => $member->id, 'title' => 'ZZ Teste Admin', 'description' => 'X', 'contact' => '912345678'] + self::SERVICE)->assertRedirect('/announcements');
        $this->assertSame(4, Announcement::where('member_id', $member->id)->where('type', 'service')->count());
    }

    public function test_only_the_church_site_may_show_the_pages_inside_its_own(): void
    {
        $this->get('/announcement')->assertHeader('Content-Security-Policy', "frame-ancestors 'self' https://igrejaicd.pt https://www.igrejaicd.pt");
    }

    public function test_the_site_announces_with_its_own_form(): void
    {
        $member = $this->member();
        $this->getJson('/api/public/announcement-categories')->assertOk()
            ->assertJsonPath('data.service.label', 'Serviços')->assertJsonPath('data.service.fields.location.label', 'Localização')
            ->assertJsonPath('data.sale.photos', 3)->assertJsonPath('data.sale.limit', 3);
        $this->getJson("/api/public/members/check/{$member->document_number}")->assertJsonPath('exists', true);
        $this->getJson('/api/public/members/check/000000000')->assertJsonPath('exists', false);

        $this->post('/api/public/announcements', ['member_document' => $member->document_number, 'title' => 'ZZ Teste Do Site', 'description' => 'Roupa', 'contact' => '912345678',
            'type' => 'donation', 'details' => ['condition' => 'new'], 'photos' => [$this->png()]], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('title', 'A sua doação foi recebida!');
        $announcement = Announcement::where('title', 'ZZ Teste Do Site')->firstOrFail();
        $this->assertFalse($announcement->is_approved);
        $this->assertCount(1, $announcement->photos);

        $this->postJson('/api/public/announcements', ['member_document' => '000000000', 'type' => 'service'])->assertUnprocessable()
            ->assertJsonValidationErrors(['member_document', 'title', 'details.area']);
    }

    public function test_someone_becomes_a_member_from_the_site(): void
    {
        $form = ['full_name' => 'zz teste da silva', 'gender' => 'F', 'birthdate' => '02/03/1990', 'document_number' => '999 888 777', 'marital_status' => 'M',
            'phone_number' => '912 345 678', 'email' => 'ZZ@Teste.pt', 'zip_code' => '2700-123', 'address' => 'rua das flores', 'address_number' => '5', 'city' => 'amadora'];

        $this->postJson('/api/public/members', $form)->assertCreated();
        $member = Member::where('document_number', '999888777')->firstOrFail();
        $this->assertSame(['Zz', 'Teste da', 'Silva'], [$member->first_name, $member->middle_name, $member->last_name]);
        $this->assertSame(\App\Enums\MembershipStatus::PENDING, $member->membership_status);
        $this->assertSame('1990-03-02', \Carbon\Carbon::parse($member->date_of_birth)->toDateString());
        $this->assertSame(['zz@teste.pt', 'Rua das Flores 5', 'Amadora', 'F', 'M'], [$member->email, $member->address, $member->city, $member->gender, $member->marital_status]);

        // The same NIF again, wrong fields, a bot
        $this->postJson('/api/public/members', $form)->assertJsonValidationErrors(['document_number' => 'Este NIF já está registado: já é membro (ou aguarda a confirmação da equipa).']);
        $this->postJson('/api/public/members', ['full_name' => 'Só', 'zip_code' => '1234', 'birthdate' => '1990-03-02', 'gender' => 'X'] + $form)
            ->assertJsonValidationErrors(['full_name', 'zip_code', 'birthdate', 'gender']);
        $this->postJson('/api/public/members', ['website' => 'http://spam', 'document_number' => '111222333'] + $form)->assertJsonValidationErrors('website');

        // The email is optional
        $this->postJson('/api/public/members', ['email' => '', 'document_number' => '444555666'] + $form)->assertCreated();
        $this->assertNull(Member::where('document_number', '444555666')->value('email'));
    }

    private function announcement(array $data = []): Announcement
    {
        return Announcement::create($data + self::SERVICE + [
            'member_id' => $this->member()->id,
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
