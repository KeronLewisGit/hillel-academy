<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\TeacherApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CareersTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'first_name' => 'Hannah', 'last_name' => 'Okafor', 'email' => 'hannah@example.com', 'phone' => '+44 7700 900123',
            'country' => 'United Kingdom', 'subjects' => 'Chemistry', 'years_experience' => 6, 'curricula' => ['igcse', 'ib_dp'],
            'cv' => UploadedFile::fake()->create('cv.pdf', 400, 'application/pdf'), 'consent' => '1',
        ];
    }

    public function test_teacher_applies_with_a_cv_that_only_admins_can_download(): void
    {
        Storage::fake('local');
        Notification::fake();
        $job = JobPosting::factory()->create();

        $this->post('/careers/apply', $this->payload(['job_posting_id' => $job->id]))->assertRedirect(route('careers.thanks'));
        $a = TeacherApplication::firstOrFail();
        $this->assertStringStartsWith('HT-', $a->reference);
        Storage::disk('local')->assertExists($a->cv_path);
        $this->get(route('careers.thanks'))->assertSee($a->reference);

        $this->actingAs(User::factory()->create())->get(route('admin.teachers.cv', $a))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.teachers.cv', $a))->assertOk()->assertDownload();
        $this->get(route('admin.teachers.show', $a))->assertSee('Cambridge IGCSE');
    }

    public function test_cv_is_required_and_must_be_a_document(): void
    {
        Storage::fake('local');
        $this->post('/careers/apply', $this->payload(['cv' => null]))->assertSessionHasErrors(['cv' => 'Please attach your CV.']);
        $this->post('/careers/apply', $this->payload(['cv' => UploadedFile::fake()->image('me.png')]))->assertSessionHasErrors('cv');
        $this->post('/careers/apply', $this->payload(['cv' => UploadedFile::fake()->create('cv.pdf', 6000, 'application/pdf')]))->assertSessionHasErrors('cv');
        $this->post('/careers/apply', $this->payload(['curricula' => ['made_up']]))->assertSessionHasErrors('curricula.0');
        $this->assertSame(0, TeacherApplication::count());
    }

    public function test_admin_posts_and_closes_vacancies(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('admin.jobs.store'), ['title' => 'Head of Mathematics', 'section' => 'high', 'employment_type' => 'full_time', 'summary' => 'Lead the department.', 'description' => 'Details.', 'status' => 'open', 'overseas_welcome' => '1'])->assertRedirect();
        $job = JobPosting::firstOrFail();
        $this->assertSame('head-of-mathematics', $job->slug);
        $this->get('/careers')->assertSee('Head of Mathematics');

        TeacherApplication::factory()->for($job)->create();
        $this->delete(route('admin.jobs.destroy', $job));
        $this->assertModelExists($job);
        $this->assertFalse($job->fresh()->isOpen());
    }
}
