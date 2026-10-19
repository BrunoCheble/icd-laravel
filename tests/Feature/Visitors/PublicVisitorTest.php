<?php

namespace Tests\Feature\Visitors;

use App\Enums\VisitorStatus;
use App\Modules\Visitors\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The reception's page to register visitors, on the test database (icd_test, emptied on every run).
 */
class PublicVisitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_family_is_registered_one_after_the_other(): void
    {
        $this->get('/visitor')->assertOk()->assertSee('name="created_by"', false);

        $this->post('/new-visitor', [
            'created_by' => 'zz teste receção', 'name' => 'zz teste maria', 'gender' => 'F', 'group' => 'adult',
            'invited_by_other' => 'who', 'invited_by' => 'joão silva', 'phone_number' => '+351 912 345 678', 'city' => 'lisboa',
        ])->assertRedirect('/visitor');

        $maria = Visitor::where('name', 'Zz Teste Maria')->firstOrFail();
        $this->assertSame(['912345678', 'Lisboa', 'João Silva', 'Zz Teste Receção', VisitorStatus::ACTIVED],
            [$maria->phone_number, $maria->city, $maria->invited_by, $maria->created_by, $maria->status]);

        // The next one of the family: who registers, phone, city and who invited come already filled
        $this->get('/visitor')->assertSee('Zz Teste Maria registado(a)', false)->assertSee('Zz Teste Receção')
            ->assertSee('value="912345678"', false)->assertSee('value="Lisboa"', false)->assertSee('value="João Silva"', false);

        // A new family: only who registers stays
        $this->post('/visitor/new-family')->assertRedirect('/visitor');
        $this->get('/visitor')->assertSee('Zz Teste Receção')->assertDontSee('value="912345678"', false);
    }

    public function test_how_they_heard_of_the_church_and_the_checks(): void
    {
        $this->post('/new-visitor', ['created_by' => 'ZZ Teste', 'name' => 'ZZ Teste Ana', 'gender' => 'F', 'invited_by_other' => 'instagram', 'invited_by' => 'ignored']);
        $this->assertSame('instagram', Visitor::where('name', 'ZZ Teste Ana')->value('invited_by'));

        $this->post('/new-visitor', ['created_by' => 'ZZ Teste', 'name' => '', 'gender' => 'X', 'invited_by_other' => 'tiktok'])
            ->assertSessionHasErrors(['name', 'gender', 'invited_by_other']);
        $this->post('/new-visitor', ['created_by' => 'ZZ Teste', 'name' => 'Spam', 'gender' => 'M', 'website' => 'http://spam'])->assertSessionHasErrors('website');
        $this->assertFalse(Visitor::where('name', 'Spam')->exists());

        // The old address of the form (a GET with the data in it) only goes back to the page
        $this->get('/new-visitor?name=X')->assertRedirect('/visitor');
        $this->assertFalse(Visitor::where('name', 'X')->exists());
    }
}
