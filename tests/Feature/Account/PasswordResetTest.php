<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Password recovery: a link sent by email, then a new password (on the test database, see tests/TestCase.php).
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_page_links_to_the_recovery_page(): void
    {
        $this->get('/login')->assertOk()->assertSee(route('password.request'));
        $this->get('/forgot-password')->assertOk();
    }

    public function test_a_link_is_emailed_to_a_known_user_only(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class);

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHasErrors('email');
    }

    public function test_the_link_opens_the_page_for_the_new_password(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) {
            $this->get('/reset-password/' . $notification->token)->assertOk();

            return true;
        });
    }

    public function test_the_password_is_changed_with_the_emailed_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->post('/reset-password', [
                'token'                 => $notification->token,
                'email'                 => $user->email,
                'password'              => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->post('/login', ['email' => $user->email, 'password' => 'new-password-123'])->assertRedirect();
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_a_wrong_token_changes_nothing(): void
    {
        $user = User::factory()->create();
        $before = $user->password;

        $this->post('/reset-password', [
            'token'                 => 'not-a-real-token',
            'email'                 => $user->email,
            'password'              => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertSame($before, $user->fresh()->password);
    }

    public function test_registering_stays_closed(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
