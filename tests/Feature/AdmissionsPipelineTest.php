<?php

namespace Tests\Feature;

use App\Enums\AdmissionStatus;
use App\Enums\DocumentType;
use App\Mail\FamilyMessage;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AssessmentSlot;
use App\Models\Family;
use App\Models\GradeCapacity;
use App\Models\Setting;
use App\Models\User;
use App\Services\Admissions;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Document review, assessment booking, capacity, the waitlist and offers. */
class AdmissionsPipelineTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private string $year;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $this->seed(TemplateSeeder::class);
        $this->staff = User::factory()->create(['role' => 'admissions']);
        $this->year = entry_years()[0];
    }

    private function seats(int $n, string $grade = 'g4'): void
    {
        GradeCapacity::updateOrCreate(['entry_year' => $this->year, 'grade' => $grade], ['seats' => $n]);
    }

    private function addDoc(Application $app, string $type): ApplicationDocument
    {
        return $app->documents()->create(['type' => $type, 'path' => UploadedFile::fake()->create($type.'.pdf', 5)->store('admissions/'.$app->id, 'local'), 'original_name' => $type.'.pdf', 'size' => 5000, 'status' => 'pending']);
    }

    public function test_accepting_every_required_document_moves_the_application_on(): void
    {
        $app = Application::factory()->submitted()->create(['grade' => 'g4']);
        $docs = collect(['birth_certificate', 'school_reports', 'immunisation'])->map(fn ($t) => $this->addDoc($app, $t));

        $this->actingAs($this->staff);
        $this->patch(route('admin.documents.update', $docs[0]), ['status' => 'accepted'])->assertRedirect();
        $this->patch(route('admin.documents.update', $docs[1]), ['status' => 'accepted']);
        $this->assertSame(AdmissionStatus::Submitted, $app->fresh()->status);

        $this->patch(route('admin.documents.update', $docs[2]), ['status' => 'accepted']);
        $this->assertSame(AdmissionStatus::DocsComplete, $app->fresh()->status);
        Mail::assertSent(FamilyMessage::class, fn ($m) => str_contains($m->mailSubject, 'Book'));
    }

    public function test_returning_a_document_needs_a_reason_and_emails_the_family(): void
    {
        $app = Application::factory()->submitted()->create();
        $doc = $this->addDoc($app, 'birth_certificate');
        $this->actingAs($this->staff);

        $this->patch(route('admin.documents.update', $doc), ['status' => 'rejected'])->assertSessionHasErrors('review_note');
        $this->patch(route('admin.documents.update', $doc), ['status' => 'rejected', 'review_note' => 'The scan is cut off'])->assertSessionHasNoErrors();

        $this->assertSame('rejected', $doc->fresh()->status);
        $this->assertContains(DocumentType::BirthCertificate, $app->fresh()->missingDocuments());
        Mail::assertSent(FamilyMessage::class, fn ($m) => str_contains($m->body, 'The scan is cut off'));
    }

    public function test_dashboard_leads_with_documents_awaiting_review(): void
    {
        $app = Application::factory()->submitted()->create();
        $this->addDoc($app, 'birth_certificate');
        $this->addDoc(Application::factory()->submitted()->create(), 'immunisation');
        // Drafts don't count: the office can't act on them yet.
        $this->addDoc(Application::factory()->create(), 'immunisation');

        $this->actingAs($this->staff)->get('/admin')->assertOk()
            ->assertSee('Waiting for document review')->assertSeeInOrder(['Waiting for document review', '2', 'applications']);
    }

    public function test_family_books_an_assessment_and_a_full_or_wrong_grade_slot_is_refused(): void
    {
        $family = Family::factory()->create();
        $app = Application::factory()->status(AdmissionStatus::DocsComplete)->for($family)->create(['grade' => 'g4']);
        $full = AssessmentSlot::create(['starts_at' => now()->addDays(4), 'location' => 'Hall', 'capacity' => 1]);
        Application::factory()->status(AdmissionStatus::AssessmentBooked)->create(['assessment_slot_id' => $full->id]);
        $older = AssessmentSlot::create(['starts_at' => now()->addDays(4), 'location' => 'Hall', 'capacity' => 5, 'grades' => ['g10', 'g11']]);
        $soon = AssessmentSlot::create(['starts_at' => now()->addHours(3), 'location' => 'Hall', 'capacity' => 5]);
        $open = AssessmentSlot::create(['starts_at' => now()->addDays(6)->setTime(9, 0), 'location' => 'Library', 'capacity' => 5, 'grades' => ['g4']]);

        $this->actingAs($family, 'family');
        foreach ([$full, $older, $soon] as $bad) {
            $this->post(route('family.assessment.book', $app), ['slot' => $bad->id])->assertSessionHasErrors('slot');
        }
        $this->assertNull($app->fresh()->assessment_slot_id);

        $this->get(route('family.applications.show', $app))->assertSee($open->starts_at->format('D j M, g:i a'));
        $this->post(route('family.assessment.book', $app), ['slot' => $open->id])->assertRedirect(route('family.applications.show', $app));
        $this->assertSame(AdmissionStatus::AssessmentBooked, $app->fresh()->status);
        $this->assertSame($open->id, $app->fresh()->assessment_slot_id);
        Mail::assertSent(FamilyMessage::class, fn ($m) => str_contains($m->mailSubject, 'Entrance assessment booked'));
    }

    public function test_cannot_book_before_documents_are_complete(): void
    {
        $family = Family::factory()->create();
        $app = Application::factory()->submitted()->for($family)->create();
        $slot = AssessmentSlot::create(['starts_at' => now()->addDays(4), 'location' => 'Hall', 'capacity' => 5]);
        $this->actingAs($family, 'family')->post(route('family.assessment.book', $app), ['slot' => $slot->id])->assertSessionHasErrors('slot');
    }

    public function test_offer_is_blocked_when_the_grade_is_full_and_waitlist_is_offered_instead(): void
    {
        $this->seats(1);
        Application::factory()->status(AdmissionStatus::Accepted)->create(['grade' => 'g4', 'entry_year' => $this->year]);
        $app = Application::factory()->status(AdmissionStatus::Assessed)->create(['grade' => 'g4', 'entry_year' => $this->year]);

        $this->actingAs($this->staff)->from(route('admin.applications.show', $app))
            ->patch(route('admin.applications.status', $app), ['status' => 'offered'])
            ->assertSessionHasErrors('status');
        $this->assertSame(AdmissionStatus::Assessed, $app->fresh()->status);

        // The board gets a JSON reason it can show in its toast.
        $this->patchJson(route('admin.applications.status', $app), ['status' => 'offered'])->assertStatus(422)->assertJsonFragment(['message' => "Grade 4 for {$this->year} is full (1 of 1 seats held). Waitlist the application or raise the grade’s capacity first."]);

        $this->patch(route('admin.applications.status', $app), ['status' => 'waitlisted'])->assertSessionHasNoErrors();
        $this->assertSame(AdmissionStatus::Waitlisted, $app->fresh()->status);
        Mail::assertSent(FamilyMessage::class, fn ($m) => str_contains($m->body, 'waiting list'));
    }

    public function test_seats_are_per_grade_and_per_year(): void
    {
        $this->seats(1, 'g4');
        $this->seats(2, 'g5');
        Application::factory()->status(AdmissionStatus::Offered)->create(['grade' => 'g4', 'entry_year' => $this->year]);
        $otherYear = Application::factory()->status(AdmissionStatus::Assessed)->create(['grade' => 'g4', 'entry_year' => '2030/31']);
        $g5 = Application::factory()->status(AdmissionStatus::Assessed)->create(['grade' => 'g5', 'entry_year' => $this->year]);

        $admissions = app(Admissions::class);
        $admissions->move($g5, AdmissionStatus::Offered, $this->staff);
        $this->assertSame(1, $admissions->seatsLeft($this->year, 'g5'));
        $this->assertSame(0, $admissions->seatsLeft($this->year, 'g4'));

        $this->expectException(ValidationException::class);
        $admissions->move($otherYear, AdmissionStatus::Offered, $this->staff); // 2030/31 has no seats configured
    }

    public function test_waitlist_is_first_come_first_served_and_a_declined_offer_frees_the_seat(): void
    {
        $this->seats(1);
        $family = Family::factory()->create();
        $offered = Application::factory()->status(AdmissionStatus::Offered)->for($family)->create(['grade' => 'g4', 'entry_year' => $this->year]);
        $second = Application::factory()->status(AdmissionStatus::Waitlisted)->create(['grade' => 'g4', 'entry_year' => $this->year, 'waitlisted_at' => now()->subDays(2)]);
        $first = Application::factory()->status(AdmissionStatus::Waitlisted)->create(['grade' => 'g4', 'entry_year' => $this->year, 'waitlisted_at' => now()->subDays(9)]);

        $this->assertSame(1, $first->waitlistPosition());
        $this->assertSame(2, $second->waitlistPosition());

        $admissions = app(Admissions::class);
        $this->assertSame(0, $admissions->seatsLeft($this->year, 'g4'));

        $this->actingAs($family, 'family')->post(route('family.offer.decline', $offered))->assertRedirect();
        $this->assertSame(AdmissionStatus::Withdrawn, $offered->fresh()->status);
        $this->assertSame(1, $admissions->seatsLeft($this->year, 'g4'));
        $this->assertTrue($admissions->nextOnWaitlist($this->year, 'g4')->is($first));

        $this->actingAs($this->staff, 'web')->get(route('admin.capacity.index', ['year' => $this->year]))->assertOk()->assertSee('Offer the free seat')->assertSee($first->student_name);
        $this->get('/admin?year='.$this->year)->assertSee('Seats free with families waiting');

        $this->patch(route('admin.applications.status', $first), ['status' => 'offered'])->assertSessionHasNoErrors();
        $this->assertSame(AdmissionStatus::Offered, $first->fresh()->status);
        $this->assertSame(1, $second->fresh()->waitlistPosition());
    }

    public function test_family_accepts_an_offer_but_not_after_it_expires(): void
    {
        $this->seats(5);
        $family = Family::factory()->create();
        $app = Application::factory()->status(AdmissionStatus::Assessed)->for($family)->create(['grade' => 'g4', 'entry_year' => $this->year]);
        Setting::saveMany(['offer_days' => '10']);

        $this->actingAs($this->staff)->patch(route('admin.applications.status', $app), ['status' => 'offered']);
        $app->refresh();
        $this->assertTrue($app->offer_expires_at->between(now()->addDays(9), now()->addDays(11)));
        Mail::assertSent(FamilyMessage::class, fn ($m) => str_contains($m->body, 'delighted'));

        $this->actingAs($family, 'family');
        $this->post(route('family.offer.accept', $app), [])->assertSessionHasErrors('confirm');
        $this->post(route('family.offer.accept', $app), ['confirm' => '1'])->assertRedirect();
        $this->assertSame(AdmissionStatus::Accepted, $app->fresh()->status);
        $this->assertNotNull($app->fresh()->offer_accepted_at);

        $late = Application::factory()->status(AdmissionStatus::Offered)->for($family)->create(['offer_expires_at' => now()->subDay()]);
        $this->post(route('family.offer.accept', $late), ['confirm' => '1'])->assertSessionHasErrors('offer');
        $this->assertSame(AdmissionStatus::Offered, $late->fresh()->status);
    }

    public function test_staff_can_message_a_family_from_a_template_and_it_shows_in_the_portal(): void
    {
        $family = Family::factory()->create(['email' => 'mum@example.com']);
        $app = Application::factory()->submitted()->for($family)->create(['student_first_name' => 'Kemar']);

        $this->actingAs($this->staff)->post(route('admin.applications.message', $app), ['subject' => 'Hello {student_first_name}', 'body' => 'Your reference is {reference}.'])->assertRedirect();

        Mail::assertSent(FamilyMessage::class, fn ($m) => $m->hasTo('mum@example.com') && $m->mailSubject === 'Hello Kemar' && str_contains($m->body, $app->reference));
        $this->actingAs($family, 'family')->get(route('family.applications.show', $app))->assertSee('Hello Kemar');
    }

    public function test_board_shows_columns_and_export_escapes_formulas(): void
    {
        Application::factory()->submitted()->create(['student_first_name' => '=HYPERLINK("x")', 'student_last_name' => 'Smith']);
        $this->actingAs($this->staff);
        $this->get(route('admin.applications.board'))->assertOk()->assertSee('Documents complete')->assertSee('Waitlisted');

        $csv = $this->get(route('admin.applications.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('Reference,Status', $csv);
    }

    public function test_staff_assessment_booking_and_capacity_update(): void
    {
        $app = Application::factory()->status(AdmissionStatus::DocsComplete)->create();
        $slot = AssessmentSlot::create(['starts_at' => now()->addDays(3), 'location' => 'Hall', 'capacity' => 2]);
        $this->actingAs($this->staff)->post(route('admin.applications.assessment', $app), ['slot' => $slot->id])->assertSessionHasNoErrors();
        $this->assertSame(AdmissionStatus::AssessmentBooked, $app->fresh()->status);

        $this->put(route('admin.capacity.update'), ['year' => $this->year, 'seats' => ['g4' => 22, 'nope' => 5]])->assertRedirect();
        $this->assertSame(22, GradeCapacity::where('grade', 'g4')->value('seats'));
        $this->assertSame(0, GradeCapacity::where('grade', 'nope')->count());
    }
}
