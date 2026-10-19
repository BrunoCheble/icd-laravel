<?php

namespace Tests\Feature\Account;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Accounts are made by the team and there is no email sending: registering and password recovery stay closed.
 */
class ClosedAccountRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_and_password_recovery_are_closed(): void
    {
        foreach (['/register', '/forgot-password', '/reset-password/some-token'] as $uri) {
            $this->get($uri)->assertNotFound();
        }
        $this->get('/login')->assertOk()->assertDontSee('forgot-password');
    }

    public function test_signing_out_goes_back_to_the_sign_in_page(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
