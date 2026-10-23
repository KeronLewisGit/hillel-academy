<?php

namespace Tests\Feature;

use App\Enums\EventCategory;
use App\Enums\JobStatus;
use App\Models\CalendarEvent;
use App\Models\FeeBand;
use App\Models\JobPosting;
use App\Models\NewsPost;
use App\Models\Setting;
use Database\Seeders\FeeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function pages(): array
    {
        return collect(['/', '/about', '/academics', '/admissions', '/school-life', '/news', '/calendar', '/visit', '/apply', '/admissions/enquire', '/careers', '/careers/apply', '/contact', '/privacy', '/photo-credits', '/family/login', '/login'])
            ->mapWithKeys(fn ($p) => [$p => [$p]])->all();
    }

    #[DataProvider('pages')]
    public function test_page_renders(string $path): void
    {
        $this->seed(FeeSeeder::class);
        NewsPost::factory()->create();
        CalendarEvent::factory()->create();
        JobPosting::factory()->create();

        $this->get($path)->assertOk()->assertSee('<html lang="en">', false);
    }

    public function test_every_inner_page_uses_the_shared_page_header(): void
    {
        foreach (['/about', '/academics', '/admissions', '/school-life', '/news', '/calendar', '/visit', '/apply', '/careers', '/contact', '/privacy', '/photo-credits'] as $path) {
            $this->get($path)->assertSee('on-dark ruled relative isolate', false);
        }
    }

    public function test_fees_come_from_the_table_with_the_published_year_note(): void
    {
        $this->seed(FeeSeeder::class);
        $this->assertGreaterThan(0, FeeBand::count());
        Setting::saveMany(['fees_year' => '2026/27']);
        $first = FeeBand::ordered()->first();

        $this->get('/admissions')->assertOk()->assertSee('as published for 2026/27')->assertSee(number_format($first->amount_usd));

        $first->update(['amount_usd' => 12345]);
        $this->get('/admissions')->assertSee('12,345');
    }

    public function test_contact_numbers_come_from_settings(): void
    {
        Setting::saveMany(['phone_admissions' => '876-941-2912']);
        $this->get('/')->assertSee('(876) 941-2912')->assertSee('tel:+18769412912', false);
        Setting::saveMany(['phone_admissions' => '876-000-1111']);
        $this->get('/contact')->assertSee('(876) 000-1111');
    }

    public function test_preview_mode_shows_banner_and_blocks_indexing(): void
    {
        $this->get('/')->assertDontSee('Concept preview')->assertHeaderMissing('X-Robots-Tag');
        config(['hillel.preview' => true]);
        $this->get('/')->assertSee('Concept preview prepared for Hillel Academy')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_news_drafts_and_future_posts_stay_hidden(): void
    {
        $live = NewsPost::factory()->create(['title' => 'Live story']);
        $draft = NewsPost::factory()->create(['title' => 'Draft story', 'published_at' => null]);
        $future = NewsPost::factory()->create(['title' => 'Future story', 'published_at' => now()->addWeek()]);

        $this->get('/news')->assertSee('Live story')->assertDontSee('Draft story')->assertDontSee('Future story');
        $this->get(route('news.show', $live))->assertOk();
        $this->get(route('news.show', $draft))->assertNotFound();
        $this->get(route('news.show', $future))->assertNotFound();
    }

    public function test_calendar_lists_upcoming_published_events_and_filters_by_type(): void
    {
        CalendarEvent::factory()->create(['title' => 'Sports day', 'category' => EventCategory::Sports]);
        CalendarEvent::factory()->create(['title' => 'Open morning', 'category' => EventCategory::Admissions]);
        CalendarEvent::factory()->create(['title' => 'Hidden thing', 'published' => false]);
        CalendarEvent::factory()->create(['title' => 'Old thing', 'starts_at' => now()->subMonth()]);

        $this->get('/calendar')->assertSee('Sports day')->assertSee('Open morning')->assertDontSee('Hidden thing')->assertDontSee('Old thing');
        $this->get('/calendar?category=sports')->assertSee('Sports day')->assertDontSee('Open morning');
        $this->get('/admissions')->assertSee('Open morning')->assertDontSee('Sports day');
    }

    public function test_closed_vacancies_are_not_shown(): void
    {
        $open = JobPosting::factory()->create(['title' => 'Physics Teacher']);
        $closed = JobPosting::factory()->create(['title' => 'Old Post', 'status' => JobStatus::Closed]);
        $expired = JobPosting::factory()->create(['title' => 'Expired Post', 'closes_on' => now()->subDay()]);

        $this->get('/careers')->assertSee('Physics Teacher')->assertDontSee('Old Post')->assertDontSee('Expired Post');
        $this->get(route('careers.show', $open))->assertOk()->assertSee('"@type":"JobPosting"', false);
        $this->get(route('careers.show', $closed))->assertNotFound();
        $this->get(route('careers.show', $expired))->assertNotFound();
    }

    public function test_sitemap_lists_public_pages_only(): void
    {
        $post = NewsPost::factory()->create();
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('admissions'))->assertSee(route('news.show', $post))
            ->assertDontSee('/admin')->assertDontSee('/family');
    }

    public function test_photo_credits_list_every_photo_used(): void
    {
        $response = $this->get('/photo-credits')->assertOk();
        foreach ((array) config('hillel.photos') as $photo) {
            $credit = config('photo-credits.'.$photo['credit']);
            $this->assertNotNull($credit, 'every photo needs a credit entry');
            $response->assertSee($credit['license']);
        }
    }

    public function test_every_photo_has_all_three_sizes_on_disk(): void
    {
        foreach (array_keys((array) config('hillel.photos')) as $key) {
            foreach ([480, 960, 1920] as $w) {
                $this->assertFileExists(public_path("img/{$key}-{$w}.webp"));
            }
        }
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_unknown_pages_use_the_branded_404(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('Hillel Academy');
    }
}
