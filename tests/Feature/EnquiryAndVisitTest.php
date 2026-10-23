<?php

namespace Tests\Feature;

use App\Enums\EnquiryKind;
use App\Models\Enquiry;
use App\Models\Setting;
use App\Models\TourSlot;
use App\Models\User;
use App\Notifications\OfficeAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EnquiryAndVisitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Setting::saveMany(['notify_emails' => 'office@hillel.test']);
    }

    public function test_admissions_enquiry_is_saved_and_the_office_is_emailed(): void
    {
        $this->post('/enquiries', ['name' => 'Andre Brown', 'email' => 'ANDRE@example.com', 'phone' => '876 555 0101', 'child_name' => 'Zara', 'grade' => 'g1', 'entry_year' => entry_years()[0], 'message' => 'Relocating from Toronto.', 'consent' => '1'])
            ->assertRedirect(route('enquiries.thanks'));

        $enquiry = Enquiry::firstOrFail();
        $this->assertSame(EnquiryKind::Admissions, $enquiry->kind);
        $this->assertSame('andre@example.com', $enquiry->email);
        $this->get(route('enquiries.thanks'))->assertOk()->assertSee('Thank you');
        Notification::assertSentOnDemand(OfficeAlert::class, fn (OfficeAlert $n) => str_contains($n->subject, 'Andre Brown'));
    }

    public function test_enquiry_validation_and_honeypot(): void
    {
        $this->post('/enquiries', ['name' => '', 'email' => 'nope', 'consent' => ''])->assertSessionHasErrors(['name', 'email', 'grade', 'entry_year', 'consent']);
        $this->post('/enquiries', ['kind' => 'general', 'name' => 'A', 'email' => 'a@example.com', 'consent' => '1'])->assertSessionHasErrors('message');
        $this->post('/enquiries', ['website' => 'spam', 'name' => 'Bot'])->assertRedirect(route('enquiries.thanks'));
        $this->assertSame(0, Enquiry::count());
    }

    public function test_general_message_from_contact_page(): void
    {
        $this->post('/enquiries', ['kind' => 'general', 'name' => 'A Parent', 'email' => 'a@example.com', 'message' => 'Where do I collect uniforms?', 'consent' => '1'])->assertRedirect();
        $this->assertSame(EnquiryKind::General, Enquiry::firstOrFail()->kind);
    }

    public function test_family_books_a_campus_tour(): void
    {
        $slot = TourSlot::create(['starts_at' => now()->addDays(3)->setTime(10, 0), 'capacity' => 2]);
        $this->get('/visit')->assertSee($slot->starts_at->format('l j F'));

        $this->post('/visit', ['tour_slot_id' => $slot->id, 'name' => 'The Lees', 'email' => 'lee@example.com', 'phone' => '876-555-0199', 'party_size' => 2, 'consent' => '1'])
            ->assertRedirect(route('visit.booked'));
        $this->get(route('visit.booked'))->assertSee($slot->starts_at->format('l j F Y'));
        $this->assertSame(EnquiryKind::Tour, Enquiry::firstOrFail()->kind);

        $this->actingAs(User::factory()->create())->get(route('admin.tours.index'))->assertSee('The Lees');
    }

    public function test_full_or_past_tours_cannot_be_booked(): void
    {
        $full = TourSlot::create(['starts_at' => now()->addDays(3), 'capacity' => 1]);
        Enquiry::factory()->create(['kind' => 'tour', 'tour_slot_id' => $full->id]);
        $soon = TourSlot::create(['starts_at' => now()->addHours(5), 'capacity' => 4]);

        $this->get('/visit')->assertDontSee($full->starts_at->format('l j F, g:i a'));
        foreach ([$full, $soon] as $slot) {
            $this->post('/visit', ['tour_slot_id' => $slot->id, 'name' => 'X', 'email' => 'x@example.com', 'phone' => '8765550100', 'party_size' => 1, 'consent' => '1'])
                ->assertSessionHasErrors('tour_slot_id');
        }
        $this->assertSame(1, Enquiry::count());
    }

    public function test_office_marks_enquiries_handled(): void
    {
        $enquiry = Enquiry::factory()->create(['name' => 'Waiting Parent']);
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.enquiries.index'))->assertSee('Waiting Parent');
        $this->patch(route('admin.enquiries.update', $enquiry), ['handled' => 1]);
        $this->assertNotNull($enquiry->fresh()->handled_at);
        $this->get(route('admin.enquiries.index'))->assertDontSee('Waiting Parent');
    }
}
