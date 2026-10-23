<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CalendarEvent;
use App\Models\FeeBand;
use App\Models\MessageTemplate;
use App\Models\NewsPost;
use App\Models\User;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** News, events, settings, fees and templates managed from the dashboard. */
class WebsiteContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_communications_publishes_news_with_a_photo(): void
    {
        Storage::fake('uploads');
        $this->actingAs(User::factory()->communications()->create());

        $this->post(route('admin.news.store'), ['title' => 'Science fair winners', 'body' => "First paragraph.\n\nSecond paragraph.", 'publish' => 'now', 'image' => UploadedFile::fake()->image('fair.jpg', 1200, 800), 'image_alt' => 'Students at the fair'])->assertRedirect(route('admin.news.index'));
        $post = NewsPost::firstOrFail();
        Storage::disk('uploads')->assertExists($post->image_path);
        $this->get(route('news.show', $post))->assertOk()->assertSee('Second paragraph.')->assertSee('Students at the fair');

        $this->post(route('admin.news.store'), ['title' => 'Tiny', 'body' => 'x', 'publish' => 'now', 'image' => UploadedFile::fake()->image('small.jpg', 300, 200), 'image_alt' => 'x'])->assertSessionHasErrors('image');
        $this->post(route('admin.news.store'), ['title' => 'Draft', 'body' => 'x', 'publish' => 'draft']);
        $this->assertNull(NewsPost::where('title', 'Draft')->value('published_at'));
    }

    public function test_communications_manages_the_calendar(): void
    {
        $this->actingAs(User::factory()->communications()->create());
        $this->post(route('admin.events.store'), ['title' => 'Open morning', 'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'), 'category' => 'admissions', 'published' => '1'])->assertRedirect();
        $this->post(route('admin.events.store'), ['title' => 'Bad', 'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'), 'ends_at' => now()->format('Y-m-d\TH:i'), 'category' => 'admissions'])->assertSessionHasErrors('ends_at');
        $this->assertSame(1, CalendarEvent::count());
        $this->get('/calendar')->assertSee('Open morning');
    }

    public function test_admin_edits_settings_and_fees(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->put(route('admin.settings.update'), ['phone_main' => '876-925-1980', 'phone_admissions' => '876-941-2912', 'email_admissions' => 'admissions@gohillel.com', 'fees_year' => '2027/28', 'entry_years' => '2027/28, 2028/29', 'offer_days' => 7, 'applications_open' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(['2027/28', '2028/29'], entry_years());

        $this->put(route('admin.settings.update'), ['phone_main' => 'x', 'phone_admissions' => 'x', 'email_admissions' => 'admissions@gohillel.com', 'fees_year' => 'next year', 'entry_years' => 'soon', 'offer_days' => 7])->assertSessionHasErrors(['fees_year', 'entry_years']);

        $this->post(route('admin.settings.fees.store'), ['label' => 'Grades 1 – 6', 'amount_usd' => 11000])->assertSessionHasNoErrors();
        $fee = FeeBand::firstOrFail();
        $this->put(route('admin.settings.fees.update', $fee), ['label' => 'Grades 1 – 6', 'amount_usd' => 11500])->assertSessionHasNoErrors();
        $this->get('/admissions')->assertSee('11,500')->assertSee('as published for 2027/28');
        $this->delete(route('admin.settings.fees.destroy', $fee));
        $this->assertSame(0, FeeBand::count());
    }

    public function test_templates_render_placeholders_and_automatic_ones_cannot_be_deleted(): void
    {
        $this->seed(TemplateSeeder::class);
        $this->seed(TemplateSeeder::class); // idempotent
        $this->assertSame(1, MessageTemplate::where('key', 'offered')->count());

        $this->actingAs(User::factory()->create());
        $auto = MessageTemplate::where('key', 'offered')->firstOrFail();
        $this->delete(route('admin.templates.destroy', $auto))->assertSessionHasErrors('template');
        $this->assertModelExists($auto);

        $this->put(route('admin.templates.update', $auto), ['name' => 'Offer', 'subject' => 'Good news for {student_first_name}', 'body' => 'Hi {parent_name}'])->assertRedirect();
        $app = Application::factory()->create(['student_first_name' => 'Ana', 'guardian_name' => 'Rosa']);
        $this->assertSame('Good news for Ana', MessageTemplate::render($auto->fresh()->subject, $app));
        $this->assertSame('Hi Rosa', MessageTemplate::render($auto->fresh()->body, $app));
    }
}
