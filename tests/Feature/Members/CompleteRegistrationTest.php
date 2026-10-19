<?php

namespace Tests\Feature\Members;

use App\Enums\MembershipStatus;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Services\GetRegistrationLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The page a pending member opens from the link sent by WhatsApp, on the test database (icd_test, emptied on every
 * run). The link must open only that member's page, only while valid, and only fill what is missing.
 */
class CompleteRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_link_shows_only_what_is_missing_and_nothing_already_known(): void
    {
        $member = $this->pending(['email' => 'carla.secret@example.com', 'phone_number' => '912345678', 'document_number' => '']);

        $this->get($this->link($member))
            ->assertOk()
            ->assertSee('Carla')
            ->assertSee('name="date_of_birth"', false)
            ->assertSee('name="document_number"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('carla.secret@example.com')
            ->assertDontSee('912345678')
            ->assertSee('<meta name="referrer" content="no-referrer">', false);
    }

    public function test_a_changed_or_expired_link_opens_nothing(): void
    {
        $carla = $this->pending();
        $other = $this->pending(['first_name' => 'Outra']);
        $link = $this->link($carla);

        // Another member's number with Carla's signature
        $this->get(str_replace("/completar-registo/{$carla->id}?", "/completar-registo/{$other->id}?", $link))
            ->assertOk()->assertSee('Este link já não é válido.')->assertDontSee('Outra')->assertDontSee('name="email"', false);
        // No signature, or a member that does not exist: the same answer
        $this->get("/completar-registo/{$carla->id}")->assertOk()->assertDontSee('Carla');
        $this->get(str_replace("/completar-registo/{$carla->id}?", '/completar-registo/999999?', $link))->assertOk()->assertSee('Este link já não é válido.');
        // Expired
        Carbon::setTestNow(now()->addDays(GetRegistrationLinkService::DAYS + 1));
        $this->get($link)->assertOk()->assertDontSee('Carla');
        Carbon::setTestNow();
    }

    public function test_a_post_needs_the_member_s_own_valid_link(): void
    {
        $carla = $this->pending();
        $other = $this->pending(['first_name' => 'Outra']);
        $forged = str_replace("/completar-registo/{$carla->id}?", "/completar-registo/{$other->id}?", $this->link($carla));

        $this->post("/completar-registo/{$other->id}", ['email' => 'hacker@example.com'])->assertForbidden();
        $this->post($forged, ['email' => 'hacker@example.com'])->assertForbidden();
        $this->assertNull($other->fresh()->email);
    }

    public function test_only_the_missing_fields_are_saved(): void
    {
        $member = $this->pending(['email' => 'carla@example.com']);
        $link = $this->link($member);

        $this->post($link, [
            'date_of_birth' => '1990-05-04',
            'phone_number' => '+351 912 345 678',
            'zip_code' => '1000001',
            'address' => 'rua das flores 12',
            'city' => 'lisboa',
            // Not missing, or not on the page: ignored
            'email' => 'other@example.com',
            'first_name' => 'Hacker',
            'membership_status' => MembershipStatus::ACTIVED,
        ])->assertRedirect($link);

        $member->refresh();
        $this->assertSame('1990-05-04', (string) Carbon::parse($member->date_of_birth)->toDateString());
        $this->assertSame('912345678', $member->phone_number);
        $this->assertSame('1000-001', $member->zip_code);
        $this->assertSame('Rua das Flores 12', $member->address);
        $this->assertSame('carla@example.com', $member->email);
        $this->assertSame('Carla', $member->first_name);
        $this->assertSame(MembershipStatus::PENDING, $member->membership_status);
    }

    public function test_the_answers_are_checked(): void
    {
        $this->member(['document_number' => '123456789']);
        $member = $this->pending(['document_number' => '']);
        $link = $this->link($member);

        $this->post($link, [])->assertSessionHasErrors('form');
        $this->post($link, ['document_number' => '123456789'])->assertSessionHasErrors('document_number');
        $this->post($link, ['phone_number' => '123'])->assertSessionHasErrors('phone_number');
        $this->post($link, ['date_of_birth' => now()->addDay()->toDateString()])->assertSessionHasErrors('date_of_birth');
    }

    public function test_the_spouse_name_goes_to_the_notes_and_a_complete_registration_says_thanks(): void
    {
        $member = $this->pending(['marital_status' => 'M', 'email' => 'carla@example.com', 'phone_number' => '912345678',
            'address' => 'Rua A', 'zip_code' => '1000-001', 'city' => 'Lisboa', 'date_of_birth' => '1990-01-01', 'document_number' => '987654321']);
        $link = $this->link($member);

        $this->get($link)->assertSee('Nome completo do seu marido');
        $this->post($link, ['spouse_name' => 'joão silva', 'date_joined' => '2015-06-20'])->assertRedirect($link);

        $this->assertStringContainsString('Cônjuge (indicado pelo membro): João Silva', $member->fresh()->notes);
        $this->get($link)->assertSee('Obrigado, Carla!')->assertDontSee('<form', false);
    }

    public function test_the_page_is_in_portuguese_and_in_english_when_asked(): void
    {
        $member = $this->pending();
        $link = $this->link($member);

        $this->get($link)->assertOk()->assertSee('<html lang="pt">', false)->assertSee('Olá, Carla!');
        // "lang" is not part of the signature: the link still works, in English
        $this->get("{$link}&lang=en")->assertOk()->assertSee('<html lang="en">', false)->assertSee('Hello, Carla!')->assertSee('Date of Birth');
        $this->get("{$link}&lang=xx")->assertOk()->assertSee('Olá, Carla!');

        // The form keeps the language: errors and the page after saving
        $this->post($link, ['lang' => 'en', 'date_of_birth' => now()->addDay()->toDateString()])->assertSessionHasErrors('date_of_birth');
        $this->post($link, ['lang' => 'en', 'date_of_birth' => '1990-05-04'])->assertRedirect("{$link}&lang=en");
    }

    private function link(Member $member): string
    {
        return app(GetRegistrationLinkService::class)->execute($member);
    }

    private function pending(array $data = []): Member
    {
        return $this->member($data + ['first_name' => 'Carla', 'gender' => 'F', 'membership_status' => MembershipStatus::PENDING]);
    }

    private function member(array $data = []): Member
    {
        static $document = 900000000;

        return Member::create($data + [
            'document_number' => (string) $document++,
            'first_name' => 'ZZ Teste',
            'last_name' => 'Membro',
            'gender' => 'M',
            'marital_status' => 'S',
            'membership_status' => MembershipStatus::ACTIVED,
        ]);
    }
}
