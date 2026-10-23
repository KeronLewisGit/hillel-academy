<?php

namespace Tests\Feature;

use App\Enums\AdmissionStatus;
use App\Mail\FamilyLoginLink;
use App\Mail\FamilyMessage;
use App\Models\Application;
use App\Models\Family;
use App\Models\Setting;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Starting an application, signing in by emailed link, the five steps, and submitting. */
class FamilyApplyTest extends TestCase
{
    use RefreshDatabase;

    private function start(array $overrides = []): TestResponse
    {
        return $this->post('/apply', $overrides + [
            'parent_name' => 'Simone Clarke',
            'email' => 'Simone@Example.com',
            'student_first_name' => 'Maya',
            'grade' => 'g4',
            'entry_year' => entry_years()[0],
            'consent' => '1',
        ]);
    }

    private function linkFor(Family $family): string
    {
        return $family->loginUrl();
    }

    public function test_starting_an_application_creates_a_family_and_draft_and_emails_a_link(): void
    {
        Mail::fake();

        $this->start()->assertRedirect(route('apply.check-email'));

        $family = Family::where('email', 'simone@example.com')->firstOrFail();
        $this->assertSame('Maya', $family->applications()->first()->student_first_name);
        $this->assertSame(AdmissionStatus::Draft, $family->applications()->first()->status);
        Mail::assertSent(FamilyLoginLink::class, fn ($m) => $m->hasTo('simone@example.com') && $m->newApplication);
        $this->assertGuest('family');
    }

    public function test_starting_with_a_known_email_looks_identical_and_does_not_sign_in(): void
    {
        Mail::fake();
        $existing = Family::factory()->create(['email' => 'simone@example.com']);
        Application::factory()->submitted()->for($existing)->create();

        $first = $this->start(['email' => 'new.parent@example.com']);
        $second = $this->start();

        $this->assertSame($first->headers->get('Location'), $second->headers->get('Location'));
        $this->assertGuest('family');
        $this->get(route('apply.check-email'))->assertOk()->assertSee('Check your email')->assertDontSee('Demo mode');
        $this->assertSame(2, $existing->applications()->count(), 'a new draft is attached, visible only after the family signs in');
    }

    public function test_sign_in_link_request_never_reveals_whether_an_email_is_known(): void
    {
        Mail::fake();
        Family::factory()->create(['email' => 'known@example.com']);

        $known = $this->post('/family/login', ['email' => 'known@example.com']);
        $unknown = $this->post('/family/login', ['email' => 'nobody@example.com']);

        $this->assertSame($known->headers->get('Location'), $unknown->headers->get('Location'));
        Mail::assertSent(FamilyLoginLink::class, 1);
        $this->get(route('family.login.sent'))->assertOk()->assertSee('If that address has an application');
    }

    public function test_magic_link_signs_in_once_and_expires(): void
    {
        $family = Family::factory()->create();
        Application::factory()->submitted()->for($family)->create();
        $url = $this->linkFor($family);

        $this->get($url)->assertRedirect(route('family.dashboard'));
        $this->assertAuthenticatedAs($family, 'family');

        auth('family')->logout();
        $this->get($url)->assertRedirect(route('family.login'));
        $this->assertGuest('family');

        $expired = $this->linkFor($family);
        $this->travel(Family::LINK_MINUTES + 1)->minutes();
        $this->get($expired)->assertRedirect(route('family.login'));
        $this->assertGuest('family');
    }

    public function test_tampered_or_garbage_tokens_are_rejected(): void
    {
        $family = Family::factory()->create();
        $family->issueLoginToken();

        $this->get('/family/login/'.str_repeat('a', 48))->assertRedirect(route('family.login'));
        $this->get('/family/login/short')->assertRedirect(route('family.login'));
        $this->assertGuest('family');
        $this->assertNotNull($family->fresh()->login_token, 'only the hash is stored');
        $this->assertSame(64, strlen((string) $family->fresh()->login_token));
    }

    public function test_family_with_one_draft_lands_on_its_current_step(): void
    {
        $family = Family::factory()->create();
        $draft = Application::factory()->for($family)->create(['step' => 3]);

        $this->get($this->linkFor($family))->assertRedirect(route('family.applications.step', [$draft, 3]));
    }

    public function test_full_five_step_application_and_submission(): void
    {
        Mail::fake();
        Storage::fake('local');
        $this->seed(TemplateSeeder::class);
        $family = Family::factory()->create(['email' => 'parent@example.com']);
        $app = Application::factory()->for($family)->create(['step' => 1, 'student_last_name' => null, 'date_of_birth' => null, 'nationality' => null, 'guardian_name' => null]);
        $this->actingAs($family, 'family');

        $this->post(route('family.applications.step.save', [$app, 1]), [])->assertSessionHasErrors(['student_last_name', 'date_of_birth', 'nationality', 'entry_term']);

        $this->post(route('family.applications.step.save', [$app, 1]), [
            'student_first_name' => 'Maya', 'student_last_name' => 'Clarke', 'date_of_birth' => now()->subYears(9)->toDateString(),
            'nationality' => 'Jamaican', 'grade' => 'g4', 'entry_year' => entry_years()[0], 'entry_term' => 'start',
        ])->assertRedirect(route('family.applications.step', [$app, 2]));

        $this->get(route('family.applications.step', [$app, 4]))->assertRedirect(route('family.applications.step', [$app, 2]));

        $this->post(route('family.applications.step.save', [$app, 2]), [
            'guardian_name' => 'Simone Clarke', 'guardian_relationship' => 'mother', 'guardian_email' => 'parent@example.com',
            'guardian_phone' => '876 555 0123', 'guardian_address' => '1 Hope Road, Kingston 6',
        ])->assertRedirect(route('family.applications.step', [$app, 3]));
        $this->post(route('family.applications.step.save', [$app, 3]), ['previous_school' => 'Example Prep', 'sibling_at_hillel' => '0'])
            ->assertRedirect(route('family.applications.step', [$app, 4]));

        $this->post(route('family.documents.store', $app), ['type' => 'birth_certificate', 'file' => UploadedFile::fake()->create('birth.pdf', 300, 'application/pdf')])->assertSessionHasNoErrors();
        $this->post(route('family.applications.step.save', [$app, 4]))->assertRedirect(route('family.applications.step', [$app, 5]));

        $this->get(route('family.applications.step', [$app, 5]))->assertOk()->assertSee('Maya Clarke')->assertSee('Submit application');
        $this->post(route('family.applications.submit', $app), [])->assertSessionHasErrors('declaration');

        $this->post(route('family.applications.submit', $app), ['declaration' => '1'])->assertRedirect(route('family.applications.show', $app));

        $app->refresh();
        $this->assertSame(AdmissionStatus::Submitted, $app->status);
        $this->assertNotNull($app->submitted_at);
        $this->assertFalse((bool) $app->sibling_at_hillel);
        Mail::assertSent(FamilyMessage::class, fn ($m) => str_contains($m->mailSubject, $app->reference));
        $this->assertTrue($app->events()->where('public', true)->exists());

        // Submitted applications can no longer be edited.
        $this->get(route('family.applications.step', [$app, 1]))->assertRedirect(route('family.applications.show', $app));
        $this->post(route('family.applications.step.save', [$app, 1]), ['student_first_name' => 'Changed'])->assertForbidden();
    }

    public function test_submitting_with_missing_answers_sends_the_family_back(): void
    {
        $family = Family::factory()->create();
        $app = Application::factory()->for($family)->create(['step' => 5, 'guardian_phone' => null, 'nationality' => null]);

        $this->actingAs($family, 'family')
            ->post(route('family.applications.submit', $app), ['declaration' => '1'])
            ->assertRedirect(route('family.applications.step', [$app, 5]))
            ->assertSessionHasErrors('declaration');
        $this->assertSame(AdmissionStatus::Draft, $app->fresh()->status);
    }

    public function test_save_and_finish_later_keeps_partial_answers_and_emails_a_fresh_link(): void
    {
        Mail::fake();
        $family = Family::factory()->create();
        $app = Application::factory()->for($family)->create(['step' => 2, 'guardian_name' => null, 'guardian_phone' => null]);

        $this->actingAs($family, 'family')
            ->post(route('family.applications.step.save', [$app, 2]), ['action' => 'later', 'guardian_name' => 'Half Done'])
            ->assertRedirect(route('family.dashboard'));

        $this->assertSame('Half Done', $app->fresh()->guardian_name);
        $this->assertSame(2, $app->fresh()->step, 'a partial save does not advance the step');
        Mail::assertSent(FamilyLoginLink::class, fn ($m) => $m->hasTo($family->email));
    }

    public function test_demo_mode_shows_the_link_on_screen_only_when_enabled(): void
    {
        Mail::fake();
        config(['hillel.demo_links' => true]);
        $this->start();
        $this->get(route('apply.check-email'))->assertSee('Demo mode')->assertSee('/family/login/', false);
    }

    public function test_applications_can_be_closed_by_the_office(): void
    {
        Setting::saveMany(['applications_open' => '0']);
        $this->get('/apply')->assertOk()->assertSee('closed');
        $this->start()->assertForbidden();
    }

    public function test_family_can_add_a_second_child_with_details_copied(): void
    {
        $family = Family::factory()->create();
        Application::factory()->submitted()->for($family)->create(['guardian_name' => 'Pat Brown', 'student_last_name' => 'Brown']);

        $this->actingAs($family, 'family')
            ->post(route('family.applications.store'), ['student_first_name' => 'Ella', 'grade' => 'k1', 'entry_year' => entry_years()[0]])
            ->assertRedirect();

        $new = $family->applications()->first();
        $this->assertSame('Ella', $new->student_first_name);
        $this->assertSame('Brown', $new->student_last_name);
        $this->assertSame('Pat Brown', $new->guardian_name);
    }
}
