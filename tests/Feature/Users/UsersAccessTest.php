<?php

namespace Tests\Feature\Users;

use App\Enums\MembershipStatus;
use App\Models\User;
use App\Modules\Members\Models\Member;
use App\Modules\Users\Services\GenerateTemporaryPasswordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Users of the admin with limited access, the temporary password sent by WhatsApp and its change on the first
 * sign-in (test database icd_test, emptied on every run).
 */
class UsersAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_user_reaches_only_the_modules_and_actions_given(): void
    {
        $user = User::factory()->withPermissions(['prayers' => ['read'], 'visitors' => ['read', 'write']])->create();
        $this->actingAs($user);

        $this->get('/prayers')->assertOk();
        $this->get('/visitors')->assertOk();
        $this->get('/visitors/create')->assertOk();
        $this->get('/members')->assertForbidden();
        $this->get('/songs')->assertForbidden();
        $this->get('/users')->assertForbidden();
        // Seeing, not editing nor deleting.
        $prayer = \App\Modules\Prayers\Models\Prayer::query()->create(['name' => 'ZZ Teste', 'request' => 'Pela família']);
        $this->delete("/prayers/{$prayer->id}")->assertForbidden();
        $this->assertModelExists($prayer);
        // One's own account stays reachable.
        $this->get('/profile')->assertOk();
    }

    public function test_without_the_dashboard_a_sign_in_goes_to_the_first_page_given(): void
    {
        $this->actingAs(User::factory()->withPermissions(['prayers' => ['read']])->create());

        $this->get('/dashboard')->assertRedirect(route('prayers.index'));
    }

    public function test_the_menu_shows_only_the_pages_the_user_may_see(): void
    {
        $this->actingAs(User::factory()->withPermissions(['prayers' => ['read']])->create());

        $menu = app(\App\Support\Menu::class)->resolve(request()->setUserResolver(fn () => auth()->user()));
        $routes = collect($menu)->flatMap(fn ($section) => $section['items'])->pluck('url')->all();
        $this->assertSame([route('prayers.index')], $routes);
    }

    public function test_only_administrators_manage_users(): void
    {
        $this->actingAs(User::factory()->withPermissions(['members' => ['read', 'write', 'update', 'delete']])->create());
        $this->get('/users/create')->assertForbidden();
        $this->post('/users', ['name' => 'ZZ Teste', 'phone_number' => '910000099'])->assertForbidden();
        $this->assertDatabaseMissing('users', ['name' => 'ZZ Teste']);
    }

    public function test_an_administrator_creates_a_user_with_a_temporary_password_for_whatsapp(): void
    {
        Carbon::setTestNow('2026-10-21 12:00:00');
        $member = Member::create([
            'first_name' => 'ZZ', 'last_name' => 'Teste', 'phone_number' => '912345678', 'document_number' => '123456789',
            'gender' => 'M', 'marital_status' => 'S', 'membership_status' => MembershipStatus::ACTIVED,
        ]);
        $this->actingAs(User::factory()->create());

        $response = $this->post('/users', [
            'name' => 'ZZ Teste',
            'phone_number' => '+351 912 345 678',
            'member_id' => $member->id,
            'is_admin' => '0',
            // Creating without seeing brings seeing; unknown modules and actions go away.
            'permissions' => ['songs' => ['write'], 'prayers' => ['read', 'write'], 'nope' => ['read']],
        ]);

        $user = User::query()->where('name', 'ZZ Teste')->firstOrFail();
        $response->assertRedirect(route('users.temporary-password.show', $user));
        $this->assertSame('912345678', $user->phone_number);
        $this->assertFalse($user->is_admin);
        $this->assertEquals(['songs' => ['read', 'write'], 'prayers' => ['read']], $user->permissions);
        $this->assertTrue($user->must_change_password);
        $this->assertSame('2026-10-23 12:00:00', $user->temporary_password_expires_at->format('Y-m-d H:i:s'));

        // Shown once, with the WhatsApp message to the member's mobile number.
        $page = $this->get(route('users.temporary-password.show', $user));
        $page->assertOk()->assertSee('https://wa.me/351912345678?text=', false)->assertSee('912345678');
        $password = $page->viewData('password');
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
        $this->get(route('users.temporary-password.show', $user))->assertRedirect(route('users.edit', $user));
    }

    public function test_a_user_signs_in_with_the_mobile_number_and_changes_the_temporary_password_first(): void
    {
        $user = User::factory()->withPermissions(['prayers' => ['read']])->create(['email' => null, 'phone_number' => '912345678']);
        $password = app(GenerateTemporaryPasswordService::class)->execute($user);

        $this->post('/login', ['email' => '00351 912 345 678', 'password' => $password])->assertRedirect();
        $this->assertAuthenticatedAs($user);

        // Everything else waits for the new password.
        $this->get('/prayers')->assertRedirect(route('password.change'));
        $this->get(route('password.change'))->assertOk()->assertSee(__('Choose your password'));
        $this->put(route('password.change.update'), ['password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');
        $this->put(route('password.change.update'), ['password' => 'nova-senha-1', 'password_confirmation' => 'nova-senha-1'])
            ->assertRedirect(route('prayers.index'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertNull($user->temporary_password_expires_at);
        $this->assertTrue(Hash::check('nova-senha-1', $user->password));
        $this->get('/prayers')->assertOk();
    }

    public function test_an_expired_temporary_password_does_not_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'zz-teste@example.com']);
        $password = app(GenerateTemporaryPasswordService::class)->execute($user);
        Carbon::setTestNow(now()->addHours(49));

        $this->post('/login', ['email' => 'zz-teste@example.com', 'password' => $password])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_the_last_administrator_and_oneself_are_kept(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $this->delete("/users/{$admin->id}")->assertSessionHas('error');
        $this->assertModelExists($admin);

        $this->put("/users/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'is_admin' => '0'])->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->is_admin);

        $other = User::factory()->withPermissions([])->create();
        $this->delete("/users/{$other->id}")->assertRedirect(route('users.index'));
        $this->assertModelMissing($other);
    }
}
