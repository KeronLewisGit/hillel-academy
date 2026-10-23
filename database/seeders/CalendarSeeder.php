<?php

namespace Database\Seeders;

use App\Enums\EventCategory as C;
use App\Models\CalendarEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Major dates from the school's published 2026–2027 Whole School Calendar
 * (hillelacademyjm.com, October 2026). Only seeds an empty calendar, so the
 * office's own entries are never duplicated or overwritten.
 */
class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        if (CalendarEvent::query()->exists()) {
            return;
        }

        $rows = [
            ['2026-09-02', null, 'Student and parent orientation', C::Academic],
            ['2026-09-07', null, 'Term 1 begins: all classes resume', C::Academic],
            ['2026-09-21', null, 'Yom Kippur: school closed', C::Holiday],
            ['2026-09-30', null, 'Panther Paws early release day (High School)', C::Academic],
            ['2026-10-16', null, 'Mid-term break', C::Holiday],
            ['2026-10-19', null, 'National Heroes’ Day: school closed', C::Holiday],
            ['2026-10-24', null, 'High School prizegiving: Evening of Excellence', C::Community],
            ['2026-10-30', null, 'Panther Paws early release day (High School)', C::Academic],
            ['2026-11-05', null, 'Parent–teacher consultation (Prep and High School)', C::Academic],
            ['2026-11-30', null, 'Panther Paws early release day (whole school)', C::Academic],
            ['2026-12-14', null, 'Carol and Hanukkah Hour', C::Arts],
            ['2026-12-15', null, 'School closes for the Christmas holidays (12 noon)', C::Holiday],
            ['2027-01-06', null, 'Term 2 begins: all classes resume', C::Academic],
            ['2027-01-08', null, 'Founders Day assembly', C::Community],
            ['2027-01-25', null, 'High School mock exams begin', C::Academic],
            ['2027-02-10', '2027-02-12', 'Ash Wednesday and mid-term break: school closed', C::Holiday],
            ['2027-02-25', null, 'Panther Paws early release day (whole school)', C::Academic],
            ['2027-03-02', null, 'High School Open Day', C::Admissions],
            ['2027-03-12', null, 'Prep and High School road race', C::Sports],
            ['2027-03-19', null, 'High School Sports Day', C::Sports],
            ['2027-03-23', null, 'Prep School Sports Day; school closes for the Easter holidays', C::Sports],
            ['2027-04-12', null, 'Term 3 begins: all classes resume', C::Academic],
            ['2027-04-13', null, 'High School parent–teacher consultation', C::Academic],
            ['2027-04-30', null, 'Panther Paws early release day (High School)', C::Academic],
            ['2027-05-05', null, 'Teachers’ Day: school ends early', C::Community],
            ['2027-05-06', '2027-05-07', 'Book fair', C::Community],
            ['2027-05-20', null, 'Panther Paws early release day (whole school)', C::Academic],
            ['2027-05-21', null, 'Labour Day: school closed (except external exams)', C::Holiday],
            ['2027-05-24', null, 'Mid-term break', C::Holiday],
            ['2027-06-15', null, 'Grade 13 graduation; High School end-of-year exams begin', C::Academic],
            ['2027-06-16', null, 'Grade 6 graduation', C::Academic],
            ['2027-06-17', null, 'Grade 11 graduation', C::Academic],
            ['2027-06-29', null, 'Prep School prizegiving', C::Community],
            ['2027-06-30', null, 'School closes for the summer holidays (12 noon)', C::Holiday],
        ];

        foreach ($rows as [$start, $end, $title, $category]) {
            CalendarEvent::create([
                'title' => $title,
                'starts_at' => Carbon::parse($start)->startOfDay(),
                'ends_at' => $end ? Carbon::parse($end)->startOfDay() : null,
                'all_day' => true,
                'location' => 'Hillel Academy',
                'category' => $category,
                'description' => null,
                'published' => true,
            ]);
        }
    }
}
