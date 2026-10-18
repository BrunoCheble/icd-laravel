<?php

namespace Tests\Feature\Music;

use App\Modules\Music\Models\Setlist;
use App\Modules\Music\Services\ListSetlistsService;
use App\Modules\Music\Services\SaveSetlistService;

class SetlistsListTest extends MusicTestCase
{
    public function test_each_setlist_shows_its_songs_with_the_band_key_and_tempo(): void
    {
        $first = $this->song('First song');
        $second = $this->song('Second song');
        $setlist = $this->setlist($first, $second);
        $setlist->songs()->updateExistingPivot($second->id, ['musical_key' => 'A', 'bpm' => 76, 'minister_name' => 'Ana']);

        $this->actingAs($this->admin())->get('/setlists')
            ->assertOk()
            ->assertSeeInOrder(['Sunday', 'First song', 'Second song'])
            ->assertSee('76 BPM')
            ->assertSee('Ana')
            ->assertSee(route('setlists.practice', [$setlist, $second]), false);
    }

    public function test_the_next_setlist_is_open_and_marked(): void
    {
        $song = $this->song();
        $past = app(SaveSetlistService::class)->execute(['title' => 'Past', 'event_date' => now()->subWeek()->toDateString(), 'songs' => [['song_id' => $song->id]]]);
        $next = app(SaveSetlistService::class)->execute(['title' => 'Coming', 'event_date' => now()->addDays(2)->toDateString(), 'songs' => [['song_id' => $song->id]]]);
        app(SaveSetlistService::class)->execute(['title' => 'Later', 'event_date' => now()->addWeeks(3)->toDateString(), 'songs' => [['song_id' => $song->id]]]);

        $response = $this->actingAs($this->admin())->get('/setlists')->assertOk();

        $this->assertSame([$next->id], $response->viewData('open'));
        $this->assertSame($next->id, $response->viewData('nextId'));
        $this->assertNotSame($past->id, $response->viewData('nextId'));
    }

    public function test_open_goes_to_the_page_of_that_setlist(): void
    {
        $song = $this->song();
        $ids = [];
        foreach (range(1, ListSetlistsService::PER_PAGE + 5) as $day) {
            $ids[] = app(SaveSetlistService::class)->execute(['title' => "Setlist $day", 'event_date' => now()->subDays($day)->toDateString(), 'songs' => [['song_id' => $song->id]]])->id;
        }
        // Newest first: the oldest ones are on page 2.
        $oldest = end($ids);

        $response = $this->actingAs($this->admin())->get("/setlists?open={$oldest}")->assertOk();

        $this->assertSame(2, $response->viewData('setlists')->currentPage());
        $this->assertSame([$oldest], $response->viewData('open'));
        $response->assertSee("setlist-{$oldest}", false);
    }

    public function test_the_old_setlist_page_is_gone(): void
    {
        $setlist = $this->setlist($this->song());

        $this->actingAs($this->admin())->get("/setlists/{$setlist->id}")->assertStatus(405);
    }

    public function test_practising_an_empty_setlist_goes_back_to_it_in_the_list(): void
    {
        $setlist = Setlist::create(['title' => 'Empty', 'event_date' => now()->toDateString()]);

        $this->actingAs($this->admin())->get("/setlists/{$setlist->id}/practice")
            ->assertRedirect(route('setlists.index', ['open' => $setlist->id]));
    }
}
