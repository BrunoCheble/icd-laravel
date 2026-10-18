<?php

namespace Tests\Feature\Account;

use Tests\TestCase;

/**
 * Accounts are made by the team and there is no email sending: registering and password recovery stay closed.
 */
class ClosedAccountRoutesTest extends TestCase
{
    public function test_registering_and_password_recovery_are_closed(): void
    {
        foreach (['/register', '/forgot-password', '/reset-password/some-token'] as $uri) {
            $this->get($uri)->assertNotFound();
        }
        $this->get('/login')->assertOk()->assertDontSee('forgot-password');
    }
}
