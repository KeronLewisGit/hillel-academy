<?php

namespace Database\Factories;

use App\Enums\EnquiryKind;
use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Enquiry> */
class EnquiryFactory extends Factory
{
    protected $model = Enquiry::class;

    public function definition(): array
    {
        return [
            'kind' => EnquiryKind::Admissions,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '876-555-'.fake()->numerify('####'),
            'child_name' => fake()->firstName(),
            'grade' => 'g1',
            'entry_year' => entry_years()[0] ?? '2026/27',
            'message' => 'We are relocating to Kingston and would like to know about places.',
        ];
    }
}
