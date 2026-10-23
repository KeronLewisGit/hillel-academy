<?php

namespace Database\Factories;

use App\Models\JobPosting;
use App\Models\TeacherApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeacherApplication> */
class TeacherApplicationFactory extends Factory
{
    protected $model = TeacherApplication::class;

    public function definition(): array
    {
        return [
            'job_posting_id' => JobPosting::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'phone' => '+44 7700 '.fake()->numerify('######'),
            'country' => fake()->randomElement(['United Kingdom', 'Canada', 'Jamaica', 'United States']),
            'subjects' => 'Mathematics',
            'years_experience' => fake()->numberBetween(2, 20),
            'curricula' => ['igcse'],
            'cv_path' => 'cvs/example.pdf',
            'cv_name' => 'cv.pdf',
            'status' => 'new',
        ];
    }
}
