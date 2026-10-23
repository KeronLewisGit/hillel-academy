<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<JobPosting> */
class JobPostingFactory extends Factory
{
    protected $model = JobPosting::class;

    public function definition(): array
    {
        $title = fake()->randomElement(['Mathematics Teacher', 'Biology Teacher', 'Grade 3 Class Teacher', 'English Teacher']);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'section' => 'high',
            'employment_type' => 'full_time',
            'summary' => 'Teach a growing department in a warm, international school community.',
            'description' => fake()->paragraph(),
            'requirements' => "Bachelor’s degree in the subject\nTeaching qualification",
            'status' => JobStatus::Open,
            'overseas_welcome' => true,
        ];
    }
}
