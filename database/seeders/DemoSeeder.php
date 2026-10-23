<?php

namespace Database\Seeders;

use App\Enums\AdmissionStatus as S;
use App\Enums\EnquiryKind;
use App\Enums\EventCategory;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\AssessmentSlot;
use App\Models\CalendarEvent;
use App\Models\Enquiry;
use App\Models\Family;
use App\Models\GradeCapacity;
use App\Models\JobPosting;
use App\Models\NewsPost;
use App\Models\Setting;
use App\Models\TeacherApplication;
use App\Models\TourSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data for the pitch. Everything here is invented (families, seat numbers,
 * vacancies, tour and assessment times) and must never be loaded on the live site.
 * php artisan migrate:fresh --seeder=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
        fake()->seed(1969);

        $admin = User::create(['name' => 'Site Administrator', 'email' => 'admin@hillel.test', 'password' => Hash::make('password'), 'role' => 'admin']);
        $office = User::create(['name' => 'Admissions Office', 'email' => 'admissions@hillel.test', 'password' => Hash::make('password'), 'role' => 'admissions']);
        User::create(['name' => 'Communications', 'email' => 'comms@hillel.test', 'password' => Hash::make('password'), 'role' => 'communications']);

        Setting::saveMany(['notify_emails' => 'admissions@hillel.test']);
        $years = entry_years();
        $year = $years[0];

        // Illustrative capacity: one full grade (Grade 4) so the waitlist has something to show.
        foreach ($years as $y) {
            foreach (array_keys(grades()) as $g) {
                GradeCapacity::create(['entry_year' => $y, 'grade' => $g, 'seats' => match ($g) {
                    'k1' => 20, 'g4' => 3, 'g7', 'g12' => 12, default => 8
                }]);
            }
        }

        $slots = collect(range(1, 6))->map(fn (int $i) => AssessmentSlot::create([
            'starts_at' => now()->addWeeks($i)->next('Saturday')->setTime(9, 0),
            'duration_minutes' => 120,
            'location' => setting('assessment_location'),
            'capacity' => 8,
            'grades' => $i % 2 ? null : ['g7', 'g8', 'g9', 'g10', 'g11', 'g12', 'g13'],
            'notes' => 'Please bring a pencil case and a snack.',
        ]));
        $tours = collect(range(1, 5))->map(fn (int $i) => TourSlot::create(['starts_at' => now()->addWeeks($i)->next('Wednesday')->setTime(9, 30), 'duration_minutes' => 60, 'capacity' => 4]));

        Storage::disk('local')->put('admissions/demo/sample.pdf', "%PDF-1.4\n% demo document\n");
        $doc = fn (Application $a, string $type, string $status = 'accepted') => $a->documents()->create([
            'type' => $type, 'path' => 'admissions/demo/sample.pdf', 'original_name' => str_replace('_', '-', $type).'.pdf', 'size' => 182_000, 'mime' => 'application/pdf', 'status' => $status,
            'reviewed_at' => $status === 'pending' ? null : now()->subDays(2), 'reviewed_by' => $status === 'pending' ? null : $office->id,
        ]);

        // The demo family: one application half-way through the online form, one submitted.
        $demo = Family::create(['name' => 'Simone Clarke', 'email' => 'family@hillel.test', 'phone' => '876-555-0142']);
        $draft = Application::create(['family_id' => $demo->id, 'status' => S::Draft, 'step' => 3, 'student_first_name' => 'Maya', 'student_last_name' => 'Clarke', 'date_of_birth' => now()->subYears(9)->subMonths(3), 'nationality' => 'Jamaican', 'grade' => 'g4', 'entry_year' => $years[1] ?? $year, 'entry_term' => 'start', 'guardian_name' => 'Simone Clarke', 'guardian_relationship' => 'mother', 'guardian_email' => 'family@hillel.test', 'guardian_phone' => '876-555-0142', 'guardian_address' => 'Kingston 6, St. Andrew']);
        $sibling = Application::create(['family_id' => $demo->id, 'status' => S::Draft, 'student_first_name' => 'Jordan', 'student_last_name' => 'Clarke', 'date_of_birth' => now()->subYears(12)->subMonths(5), 'nationality' => 'Jamaican', 'grade' => 'g7', 'entry_year' => $year, 'entry_term' => 'start', 'guardian_name' => 'Simone Clarke', 'guardian_relationship' => 'mother', 'guardian_email' => 'family@hillel.test', 'guardian_phone' => '876-555-0142', 'guardian_address' => 'Kingston 6, St. Andrew', 'previous_school' => 'A Kingston preparatory school', 'step' => 5]);
        $sibling->moveTo(S::Submitted);
        $sibling->events()->create(['type' => 'status', 'title' => S::Submitted->familyLabel(), 'public' => true]);
        $doc($sibling, 'birth_certificate');
        $doc($sibling, 'school_reports', 'pending');
        $doc($sibling, 'immunisation', 'pending');
        unset($draft);

        // A spread of other families across the pipeline.
        $plan = [
            [S::Submitted, 6], [S::DocsComplete, 4], [S::AssessmentBooked, 5], [S::Assessed, 4],
            [S::Offered, 4], [S::Waitlisted, 2], [S::Accepted, 5], [S::Enrolled, 4], [S::Declined, 2], [S::Withdrawn, 1], [S::Draft, 4],
        ];
        $grades = array_keys(grades());
        $i = 0;
        foreach ($plan as [$status, $count]) {
            for ($n = 0; $n < $count; $n++, $i++) {
                $grade = match (true) {
                    $status === S::Waitlisted, $status === S::Accepted && $n < 2, $status === S::Offered && $n === 0 => 'g4',
                    default => ($g = $grades[($i * 7) % count($grades)]) === 'g4' ? 'g5' : $g,
                };
                $last = fake()->lastName();
                $family = Family::create(['name' => fake()->firstName().' '.$last, 'email' => 'family'.$i.'@example.com', 'phone' => '876-555-'.str_pad((string) (200 + $i), 4, '0', STR_PAD_LEFT)]);
                $submitted = now()->subDays(fake()->numberBetween(3, 70))->setTime(fake()->numberBetween(7, 21), fake()->numberBetween(0, 59));
                $a = Application::create([
                    'family_id' => $family->id, 'status' => S::Draft, 'step' => $status === S::Draft ? fake()->numberBetween(1, 4) : 5,
                    'student_first_name' => fake()->firstName(), 'student_last_name' => $last,
                    'date_of_birth' => now()->subYears(4 + array_search($grade, $grades, true))->subDays(fake()->numberBetween(0, 330)),
                    'nationality' => fake()->randomElement(['Jamaican', 'Jamaican', 'Jamaican', 'Canadian', 'American', 'British', 'Jamaican and American']),
                    'grade' => $grade, 'entry_year' => $n % 3 === 2 && isset($years[1]) ? $years[1] : $year, 'entry_term' => 'start',
                    'guardian_name' => $family->name, 'guardian_relationship' => fake()->randomElement(['mother', 'father']), 'guardian_email' => $family->email,
                    'guardian_phone' => $family->phone, 'guardian_address' => fake()->randomElement(['Kingston 6', 'Kingston 8', 'Kingston 10', 'Stony Hill', 'Portmore']).', Jamaica',
                    'previous_school' => in_array($grade, ['k1', 'k2'], true) ? null : fake()->randomElement(['A Kingston prep school', 'A school in Toronto', 'A school in Miami', 'A Kingston high school']),
                ]);
                if ($status === S::Draft) {
                    continue;
                }
                $a->forceFill(['created_at' => $submitted->copy()->subDay()])->saveQuietly();
                $a->moveTo(S::Submitted);
                $a->forceFill(['submitted_at' => $submitted])->saveQuietly();
                $required = array_map(fn ($t) => $t->value, $a->requiredDocuments());
                foreach ($required as $k => $type) {
                    $doc($a, $type, $status === S::Submitted ? ($k === 0 && $n % 2 ? 'accepted' : 'pending') : 'accepted');
                }
                if ($status === S::Submitted) {
                    continue;
                }
                $a->moveTo(S::DocsComplete);
                if (in_array($status, [S::AssessmentBooked, S::Assessed, S::Offered, S::Waitlisted, S::Accepted, S::Enrolled, S::Declined], true)) {
                    $slot = $slots[$i % 3];
                    $a->forceFill(['assessment_slot_id' => $status === S::AssessmentBooked ? $slot->id : null])->save();
                    $a->moveTo(S::AssessmentBooked);
                }
                if (in_array($status, [S::Assessed, S::Offered, S::Waitlisted, S::Accepted, S::Enrolled, S::Declined], true)) {
                    $a->moveTo(S::Assessed, $office);
                }
                if ($status === S::Waitlisted) {
                    $a->moveTo(S::Waitlisted, $office);
                    $a->forceFill(['waitlisted_at' => now()->subDays(20 - $n * 5)])->saveQuietly();
                } elseif (in_array($status, [S::Offered, S::Accepted, S::Enrolled, S::Withdrawn], true)) {
                    $a->moveTo(S::Offered, $office);
                    if ($status === S::Offered && $n === 1) {
                        $a->forceFill(['offer_expires_at' => now()->addDays(2)])->saveQuietly();
                    }
                    if (in_array($status, [S::Accepted, S::Enrolled], true)) {
                        $a->moveTo(S::Accepted);
                    }
                    if ($status === S::Enrolled) {
                        $a->moveTo(S::Enrolled, $office);
                    }
                    if ($status === S::Withdrawn) {
                        $a->moveTo(S::Withdrawn, null, 'Offer declined by the family.');
                    }
                } elseif ($status === S::Declined) {
                    $a->moveTo(S::Declined, $office);
                }
                $a->forceFill(['status_changed_at' => now()->subDays(fake()->numberBetween(0, 10))])->saveQuietly();
            }
        }

        foreach ([
            [EnquiryKind::Admissions, 'Relocating to Kingston next summer with two children. Do you have space in Grade 2 and Grade 6?', 'g2'],
            [EnquiryKind::Admissions, 'What is the process for joining in Grade 12?', 'g12'],
            [EnquiryKind::Admissions, 'Is there a sibling discount?', 'k1'],
            [EnquiryKind::General, 'Where can I buy uniforms for the new term?', null],
        ] as [$kind, $message, $grade]) {
            Enquiry::create(['kind' => $kind, 'name' => fake()->name(), 'email' => fake()->safeEmail(), 'phone' => '876-555-'.fake()->numerify('####'), 'child_name' => $grade ? fake()->firstName() : null, 'grade' => $grade, 'entry_year' => $grade ? $year : null, 'message' => $message]);
        }
        foreach ($tours->take(2) as $k => $t) {
            Enquiry::create(['kind' => EnquiryKind::Tour, 'tour_slot_id' => $t->id, 'party_size' => 2 + $k, 'name' => fake()->name(), 'email' => fake()->safeEmail(), 'phone' => '876-555-'.fake()->numerify('####'), 'child_name' => fake()->firstName(), 'grade' => 'g1', 'entry_year' => $year]);
        }

        // The real 2026–27 calendar is loaded by DatabaseSeeder; these add the demo assessment times.
        foreach ($slots->take(3) as $s) {
            CalendarEvent::create(['title' => 'Entrance assessments', 'starts_at' => $s->starts_at, 'ends_at' => $s->endsAt(), 'location' => 'Hillel Academy campus', 'category' => EventCategory::Admissions, 'description' => 'Demo session. Families whose documents are complete book a place from the family portal.']);
        }

        // News about the website itself, so nothing is claimed about school events.
        foreach ([
            ['Online applications are open', 'Families can now apply online for '.implode(' and ', $years).', save their progress and finish later.', "Applying to Hillel Academy now happens online. Start with a few details and you will receive a secure link to the family portal, where the application is completed in five short steps.\n\nDocuments such as birth certificates and school reports can be uploaded as scans or phone photos, and the portal shows exactly what is still needed."],
            ['Book a campus tour online', 'Pick a tour time that suits you and get a confirmation straight away.', "Tour times are now published on the Visit page. Choose a slot, tell us who is coming, and you will receive a confirmation email.\n\nIf none of the times suit, call the Admissions Office and we will arrange another."],
            ['Track your application in the family portal', 'See where your application stands, book an entrance assessment and reply to offers online.', "Every family that applies gets access to the family portal. There is no password to remember: we email a secure link whenever you need one.\n\nFrom the portal you can upload missing documents, book an entrance assessment once documents are complete, and accept an offer of a place."],
        ] as $k => [$title, $excerpt, $body]) {
            NewsPost::create(['title' => $title, 'slug' => Str::slug($title), 'excerpt' => $excerpt, 'body' => $body, 'published_at' => now()->subDays(3 + $k * 9), 'user_id' => $admin->id]);
        }

        $maths = JobPosting::create(['title' => 'Mathematics Teacher (sample vacancy)', 'slug' => 'mathematics-teacher', 'section' => 'high', 'employment_type' => 'full_time', 'summary' => 'Sample posting for the demo: teach mathematics across the high school.', 'description' => "This is a sample vacancy created for the website demonstration. The school’s own job descriptions replace it.\n\nThe careers section lets the school publish posts, accept applications with a CV, and track candidates from the dashboard.", 'requirements' => "Degree in mathematics or a related subject\nTeaching qualification\nExperience of international curricula is an advantage", 'start_date_text' => 'August '.substr($year, 0, 4), 'closes_on' => now()->addWeeks(6), 'overseas_welcome' => true, 'status' => JobStatus::Open]);
        JobPosting::create(['title' => 'Primary Class Teacher (sample vacancy)', 'slug' => 'primary-class-teacher', 'section' => 'prep', 'employment_type' => 'full_time', 'summary' => 'Sample posting for the demo: a class teacher in the prep school.', 'description' => 'This is a sample vacancy created for the website demonstration. The school’s own job descriptions replace it.', 'requirements' => "Bachelor’s degree in education\nPrimary teaching experience", 'closes_on' => now()->addWeeks(4), 'overseas_welcome' => true, 'status' => JobStatus::Open]);
        Storage::disk('local')->put('cvs/demo-cv.pdf', "%PDF-1.4\n% demo cv\n");
        foreach (['United Kingdom', 'Canada', 'Jamaica', 'South Africa'] as $k => $country) {
            TeacherApplication::create(['job_posting_id' => $k < 3 ? $maths->id : null, 'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'email' => fake()->safeEmail(), 'phone' => '+1 555 0100 '.$k, 'country' => $country, 'subjects' => $k < 3 ? 'Mathematics' : 'English', 'years_experience' => 3 + $k * 2, 'curricula' => ['igcse'], 'cv_path' => 'cvs/demo-cv.pdf', 'cv_name' => 'cv.pdf', 'status' => $k === 0 ? 'shortlisted' : 'new']);
        }
    }
}
