<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\GradeCapacity;
use App\Services\Admissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Seats per grade per entry year, with the waitlist beside each grade. */
class CapacityController extends Controller
{
    public function index(Request $request, Admissions $admissions): View
    {
        $years = entry_years();
        $year = in_array(input_string($request->query('year')), $years, true) ? input_string($request->query('year')) : ($years[0] ?? '');

        $waitlists = Application::query()->where('status', 'waitlisted')->where('entry_year', $year)
            ->orderBy('waitlisted_at')->orderBy('id')->get()->groupBy('grade');

        return view('admin.capacity', ['year' => $year, 'years' => $years, 'rows' => $admissions->summary($year), 'waitlists' => $waitlists]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', Rule::in(entry_years())],
            'seats' => ['required', 'array'],
            'seats.*' => ['nullable', 'integer', 'min:0', 'max:500'],
        ]);
        foreach ($data['seats'] as $grade => $seats) {
            if (array_key_exists((string) $grade, grades())) {
                GradeCapacity::updateOrCreate(['entry_year' => $data['year'], 'grade' => $grade], ['seats' => (int) $seats]);
            }
        }

        return to_route('admin.capacity.index', ['year' => $data['year']])->with('status', 'Capacity for '.$data['year'].' saved.');
    }
}
