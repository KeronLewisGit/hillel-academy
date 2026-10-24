<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Roles: admin (everything), admissions (families), communications (news + events only). */
class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_sign_in_and_role_home_pages(): void
    {
        $admissions = User::factory()->create(['email' => 'a@hillel.test']);
        $comms = User::factory()->communications()->create(['email' => 'c@hillel.test']);

        $this->post('/login', ['email' => 'A@hillel.test', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->post('/logout');
        $this->post('/login', ['email' => 'c@hillel.test', 'password' => 'password'])->assertRedirect(route('admin.news.index'));
        $this->post('/logout');
        $this->post('/login', ['email' => 'c@hillel.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
    }

    public function test_deactivated_staff_cannot_sign_in_and_are_signed_out(): void
    {
        $user = User::factory()->create(['email' => 'gone@hillel.test', 'active' => false]);
        $this->post('/login', ['email' => 'gone@hillel.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->actingAs($user)->get('/admin')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_communications_staff_only_reach_news_and_events(): void
    {
        $app = Application::factory()->submitted()->create();
        $this->actingAs(User::factory()->communications()->create());

        $this->get('/admin')->assertRedirect(route('admin.news.index'));
        $this->get(route('admin.news.index'))->assertOk();
        $this->get(route('admin.events.index'))->assertOk();
        foreach ([route('admin.applications.index'), route('admin.applications.show', $app), route('admin.applications.export'), route('admin.capacity.index'), route('admin.enquiries.index'), route('admin.templates.index'), route('admin.settings.edit'), route('admin.users.index'), route('admin.teachers.index')] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->patch(route('admin.applications.status', $app), ['status' => 'declined'])->assertForbidden();
    }

    public function test_admissions_staff_cannot_reach_admin_only_areas(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.applications.index'))->assertOk();
        $this->get(route('admin.news.index'))->assertForbidden();
        foreach ([route('admin.settings.edit'), route('admin.users.index'), route('admin.jobs.index'), route('admin.teachers.index')] as $url) {
            $this->get($url)->assertForbidden();
        }
        // The refusal is the school's own branded page, with the reason.
        $this->get(route('admin.settings.edit'))->assertForbidden()->assertSee('Not available')->assertSee('Your account does not have access to this area.')->assertSee('Home page');
    }

    public function test_admin_reaches_everything(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (['admin.dashboard', 'admin.applications.index', 'admin.applications.board', 'admin.capacity.index', 'admin.assessments.index', 'admin.tours.index', 'admin.enquiries.index', 'admin.templates.index', 'admin.news.index', 'admin.events.index', 'admin.jobs.index', 'admin.teachers.index', 'admin.settings.edit', 'admin.users.index', 'admin.news.create', 'admin.events.create', 'admin.jobs.create', 'admin.users.create', 'admin.templates.create'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_guests_are_sent_to_staff_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_admin_manages_staff_accounts_but_not_their_own_access(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $this->post(route('admin.users.store'), ['name' => 'New Person', 'email' => 'NEW@hillel.test', 'role' => 'communications', 'password' => 'long-enough-pass', 'active' => '1'])->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'new@hillel.test', 'role' => 'communications']);

        $this->put(route('admin.users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role' => 'admissions', 'active' => '1'])->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->isAdmin());
    }
}
