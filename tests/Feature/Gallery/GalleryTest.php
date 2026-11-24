<?php

namespace Tests\Feature\Gallery;

use App\Models\User;
use App\Modules\Gallery\Models\GalleryEvent;
use App\Modules\Gallery\Models\GalleryPhoto;
use App\Modules\Gallery\Services\AddGalleryPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * The gallery: events with their photos (made light, with a small one), caption, cover, order, hidden, and what the
 * site gets (test database icd_test, emptied on every run; the photos' files are deleted after each test).
 */
class GalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        GalleryPhoto::query()->get()->each->deleteFiles();
        parent::tearDown();
    }

    private function event(string $name, string $date, bool $published = true): GalleryEvent
    {
        return GalleryEvent::create(['name' => $name, 'event_date' => $date, 'is_published' => $published]);
    }

    private function upload(GalleryEvent $event, int $width = 800, int $height = 1000): GalleryPhoto
    {
        $id = $this->post(route('gallery.photos.store', $event), ['photo' => UploadedFile::fake()->image('foto.png', $width, $height)], ['Accept' => 'application/json'])
            ->assertCreated()->json('id');

        return GalleryPhoto::query()->findOrFail($id);
    }

    public function test_an_event_gets_its_photos_made_light_with_a_small_one(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('gallery.store'), ['name' => 'ZZ Teste Santa Ceia', 'event_date' => '2026-10-11', 'is_published' => '1'])->assertRedirect();
        $event = GalleryEvent::query()->where('name', 'ZZ Teste Santa Ceia')->firstOrFail();
        $this->assertTrue($event->is_published);

        $photo = $this->upload($event, 3000, 2000);

        $this->assertSame([AddGalleryPhotoService::MAX, 1067], [$photo->width, $photo->height]);
        $this->assertSame(AddGalleryPhotoService::MAX, getimagesize(public_path($photo->path))[0]);
        $this->assertSame(AddGalleryPhotoService::THUMB, getimagesize(public_path($photo->thumb))[0]);
        $this->get(route('gallery.show', $event))->assertOk()->assertSee('ZZ Teste Santa Ceia')->assertSee($photo->thumbUrl());
        $this->get(route('gallery.index'))->assertOk()->assertSee('ZZ Teste Santa Ceia');
    }

    public function test_caption_cover_order_hidden_and_deleting(): void
    {
        $this->actingAs(User::factory()->create());
        $event = $this->event('ZZ Teste Batismos', '2026-09-01');
        [$first, $second, $third] = [$this->upload($event), $this->upload($event), $this->upload($event)];

        $this->patchJson(route('gallery.photos.update', $second), ['caption' => '  Batismo no rio  '])->assertOk();
        $this->assertSame('Batismo no rio', $second->fresh()->caption);

        $this->assertSame($first->id, $event->fresh()->cover()->id);
        $this->patch(route('gallery.photos.update', $third), ['is_cover' => 1])->assertRedirect();
        $this->assertSame($third->id, $event->fresh()->cover()->id);

        $this->patch(route('gallery.photos.move', $third), ['step' => -1])->assertRedirect();
        $this->assertSame([$first->id, $third->id, $second->id], $event->photos()->pluck('id')->all());

        // A hidden cover is not the cover: the first visible photo is.
        $this->patch(route('gallery.photos.update', $third), ['is_hidden' => 1]);
        $this->assertSame($first->id, $event->fresh()->cover()->id);

        $path = public_path($first->path);
        $this->delete(route('gallery.photos.destroy', $first))->assertRedirect();
        $this->assertFileDoesNotExist($path);
        $this->assertSame($second->id, $event->fresh()->cover()->id);

        $this->delete(route('gallery.destroy', $event))->assertRedirect(route('gallery.index'));
        $this->assertSame(0, GalleryPhoto::query()->count());
    }

    public function test_the_site_gets_the_published_events_with_visible_photos_the_cover_first(): void
    {
        $this->actingAs(User::factory()->create());
        $old = $this->event('ZZ Teste Antigo', '2026-08-01');
        $recent = $this->event('ZZ Teste Recente', '2026-10-01');
        $draft = $this->event('ZZ Teste Rascunho', '2026-10-05', false);
        $empty = $this->event('ZZ Teste Vazio', '2026-10-06');
        $this->upload($old);
        [$a, $b] = [$this->upload($recent), $this->upload($recent)];
        $hidden = $this->upload($recent);
        $this->upload($draft);
        $hidden->update(['is_hidden' => true]);
        $recent->update(['cover_photo_id' => $b->id]);
        $b->update(['caption' => 'Louvor']);

        $data = $this->getJson('/api/public/gallery')->assertOk()->json('data');

        $this->assertSame(['ZZ Teste Recente', 'ZZ Teste Antigo'], array_column($data, 'name'));
        $this->assertSame('2026-10-01', $data[0]['date']);
        $this->assertSame([$b->id, $a->id], array_column($data[0]['photos'], 'id'));
        $this->assertSame('Louvor', $data[0]['photos'][0]['caption']);
        $this->assertSame($b->thumbUrl(), $data[0]['cover']);
        $this->assertNotContains($empty->name, array_column($data, 'name'));
    }

    public function test_a_space_has_no_date_and_goes_to_the_sites_gallery_after_the_events(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('gallery.create', ['kind' => 'space']))->assertOk()->assertSee('value="space"', false);
        $this->post(route('gallery.store'), ['kind' => 'space', 'name' => 'ZZ Teste Nave', 'event_date' => '2026-10-01', 'is_published' => '1'])->assertRedirect();
        $this->post(route('gallery.store'), ['kind' => 'space', 'name' => 'ZZ Teste Cantina', 'is_published' => '1'])->assertRedirect();
        $this->post(route('gallery.store'), ['kind' => 'event', 'name' => 'ZZ Teste Sem data', 'is_published' => '1'])->assertSessionHasErrors('event_date');
        [$hall, $canteen] = [GalleryEvent::query()->where('name', 'ZZ Teste Nave')->firstOrFail(), GalleryEvent::query()->where('name', 'ZZ Teste Cantina')->firstOrFail()];
        $this->assertTrue($hall->isSpace());
        $this->assertNull($hall->event_date);
        $event = $this->event('ZZ Teste Culto', '2026-10-01');
        $draft = GalleryEvent::create(['kind' => GalleryEvent::SPACE, 'name' => 'ZZ Teste Fraldário', 'is_published' => false]);
        [$photo, $other] = [$this->upload($hall), $this->upload($canteen)];
        $this->upload($event);
        $this->upload($draft);

        $data = $this->getJson(route('api.gallery.index'))->assertOk()->json('data');
        $this->assertSame(['ZZ Teste Culto', 'ZZ Teste Nave', 'ZZ Teste Cantina'], array_column($data, 'name'));
        $this->assertSame(['2026-10-01', null, null], array_column($data, 'date'));
        $this->assertSame([$photo->id], array_column($data[1]['photos'], 'id'));
        $this->assertSame($other->thumbUrl(), $data[2]['cover']);

        $this->get(route('gallery.index'))->assertOk()->assertSeeInOrder(['ZZ Teste Culto', 'ZZ Teste Nave', 'ZZ Teste Cantina']);
        $this->get(route('gallery.show', $hall))->assertOk()->assertSee('ZZ Teste Nave');
    }

    public function test_a_user_needs_the_gallery_permission(): void
    {
        $event = $this->event('ZZ Teste Permissão', '2026-10-01');
        $reader = User::factory()->withPermissions(['gallery' => ['read']])->create();

        $this->actingAs($reader)->get(route('gallery.index'))->assertOk();
        $this->post(route('gallery.photos.store', $event), ['photo' => UploadedFile::fake()->image('a.png')])->assertForbidden();
        $this->delete(route('gallery.destroy', $event))->assertForbidden();

        $this->actingAs(User::factory()->withPermissions(['members' => ['read']])->create())->get(route('gallery.index'))->assertForbidden();
    }

    public function test_only_pictures_are_taken(): void
    {
        $this->actingAs(User::factory()->create());
        $event = $this->event('ZZ Teste Ficheiro', '2026-10-01');

        $this->post(route('gallery.photos.store', $event), ['photo' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertUnprocessable();
        $this->assertSame(0, $event->photos()->count());
    }
}
