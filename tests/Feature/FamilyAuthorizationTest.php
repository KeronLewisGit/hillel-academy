<?php

namespace Tests\Feature;

use App\Enums\AdmissionStatus;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AssessmentSlot;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Family A must never see, change or download anything belonging to family B. */
class FamilyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Family $a;

    private Family $b;

    private Application $bApp;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->a = Family::factory()->create();
        $this->b = Family::factory()->create();
        Application::factory()->submitted()->for($this->a)->create();
        $this->bApp = Application::factory()->status(AdmissionStatus::DocsComplete)->for($this->b)->create(['student_first_name' => 'Secretchild']);
    }

    public function test_dashboard_lists_only_own_applications(): void
    {
        $this->actingAs($this->a, 'family')->get(route('family.dashboard'))->assertOk()->assertDontSee('Secretchild');
    }

    public function test_another_familys_application_is_not_found(): void
    {
        $this->actingAs($this->a, 'family');
        $this->get(route('family.applications.show', $this->bApp))->assertNotFound();
        $this->get(route('family.applications.step', [$this->bApp, 1]))->assertNotFound();
        $this->post(route('family.applications.step.save', [$this->bApp, 1]), ['student_first_name' => 'Hacked'])->assertNotFound();
        $this->post(route('family.applications.submit', $this->bApp), ['declaration' => '1'])->assertNotFound();
        $this->assertSame('Secretchild', $this->bApp->fresh()->student_first_name);
    }

    public function test_cannot_upload_to_or_download_from_another_family(): void
    {
        $path = UploadedFile::fake()->create('b.pdf', 10, 'application/pdf')->store('admissions/'.$this->bApp->id, 'local');
        $doc = $this->bApp->documents()->create(['type' => 'birth_certificate', 'path' => $path, 'original_name' => 'b.pdf', 'size' => 10, 'status' => 'pending']);
        $ownApp = $this->a->applications()->first();

        $this->actingAs($this->a, 'family');
        $this->post(route('family.documents.store', $this->bApp), ['type' => 'passport', 'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertNotFound();
        $this->get(route('family.documents.show', [$this->bApp, $doc]))->assertNotFound();
        // Mixing their own application with someone else's document id is also refused.
        $this->get(route('family.documents.show', [$ownApp, $doc]))->assertNotFound();
        $this->delete(route('family.documents.destroy', [$ownApp, $doc]))->assertNotFound();
        $this->assertSame(1, ApplicationDocument::count());
    }

    public function test_cannot_book_or_answer_offers_for_another_family(): void
    {
        $slot = AssessmentSlot::create(['starts_at' => now()->addDays(5), 'location' => 'Library', 'capacity' => 4]);
        $this->actingAs($this->a, 'family');
        $this->post(route('family.assessment.book', $this->bApp), ['slot' => $slot->id])->assertNotFound();
        $this->post(route('family.offer.accept', $this->bApp), ['confirm' => '1'])->assertNotFound();
        $this->post(route('family.offer.decline', $this->bApp))->assertNotFound();
        $this->assertNull($this->bApp->fresh()->assessment_slot_id);
    }

    public function test_portal_requires_family_sign_in_and_staff_sessions_do_not_count(): void
    {
        $this->get(route('family.dashboard'))->assertRedirect(route('family.login'));
        $this->get(route('family.applications.show', $this->bApp))->assertRedirect(route('family.login'));

        $staff = User::factory()->admin()->create();
        $this->actingAs($staff)->get(route('family.dashboard'))->assertRedirect(route('family.login'));
    }

    public function test_families_cannot_reach_the_staff_dashboard(): void
    {
        $this->actingAs($this->a, 'family')->get('/admin')->assertRedirect(route('login'));
        $this->actingAs($this->a, 'family')->get(route('admin.applications.show', $this->bApp))->assertRedirect(route('login'));
    }

    public function test_internal_notes_never_reach_the_family(): void
    {
        $this->bApp->update(['staff_notes' => 'Private staff remark']);
        $this->bApp->events()->create(['type' => 'note', 'body' => 'Internal timeline note', 'public' => false]);
        $this->bApp->events()->create(['type' => 'status', 'title' => 'Shared update', 'public' => true]);

        $this->actingAs($this->b, 'family')->get(route('family.applications.show', $this->bApp))
            ->assertOk()->assertSee('Shared update')->assertDontSee('Internal timeline note')->assertDontSee('Private staff remark');
    }
}
