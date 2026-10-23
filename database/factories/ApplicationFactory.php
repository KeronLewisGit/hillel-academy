<?php

namespace Database\Factories;

use App\Enums\AdmissionStatus;
use App\Models\Application;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Application> */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'status' => AdmissionStatus::Draft,
            'step' => 1,
            'student_first_name' => fake()->firstName(),
            'student_last_name' => fake()->lastName(),
            'date_of_birth' => now()->subYears(9)->subDays(fake()->numberBetween(0, 300))->toDateString(),
            'nationality' => 'Jamaican',
            'grade' => 'g4',
            'entry_year' => entry_years()[0] ?? '2026/27',
            'entry_term' => 'start',
            'guardian_name' => fake()->name(),
            'guardian_relationship' => 'mother',
            'guardian_email' => fake()->safeEmail(),
            'guardian_phone' => '876-555-'.fake()->numerify('####'),
            'guardian_address' => fake()->streetAddress().', Kingston',
        ];
    }

    /** A complete, submitted application. */
    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => AdmissionStatus::Submitted,
            'step' => Application::STEPS,
            'submitted_at' => now()->subDays(fake()->numberBetween(1, 20)),
            'status_changed_at' => now()->subDays(1),
        ]);
    }

    public function status(AdmissionStatus $status): static
    {
        return $this->submitted()->state(fn () => ['status' => $status] + match ($status) {
            AdmissionStatus::Waitlisted => ['waitlisted_at' => now()->subDays(fake()->numberBetween(1, 30))],
            AdmissionStatus::Offered => ['offer_expires_at' => now()->addDays(10), 'decision_at' => now()->subDay()],
            default => [],
        });
    }
}
