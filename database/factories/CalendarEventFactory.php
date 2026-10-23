<?php

namespace Database\Factories;

use App\Enums\EventCategory;
use App\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CalendarEvent> */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'starts_at' => now()->addDays(fake()->numberBetween(2, 90))->setTime(9, 0),
            'category' => EventCategory::Community,
            'published' => true,
        ];
    }
}
