<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssessmentSlotController extends Controller
{
    public function index(): View
    {
        return view('admin.assessments', [
            'slots' => AssessmentSlot::upcoming()->with(['bookings' => fn ($q) => $q->orderBy('student_last_name')])->withCount('bookings')->get(),
        ]);
    }

    /** One slot, or the same time repeated weekly for a few weeks. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'location' => ['required', 'string', 'max:160'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'grades' => ['nullable', 'array'],
            'grades.*' => [Rule::in(array_keys(grades()))],
            'notes' => ['nullable', 'string', 'max:500'],
            'repeat_weeks' => ['nullable', 'integer', 'min:0', 'max:12'],
        ]);
        $start = Carbon::parse($data['starts_at']);
        $repeat = (int) ($data['repeat_weeks'] ?? 0);
        for ($i = 0; $i <= $repeat; $i++) {
            AssessmentSlot::create([
                'starts_at' => $start->copy()->addWeeks($i),
                'duration_minutes' => $data['duration_minutes'],
                'location' => $data['location'],
                'capacity' => $data['capacity'],
                'grades' => $data['grades'] ?? null ?: null,
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return back()->with('status', ($repeat + 1).' assessment '.str('session')->plural($repeat + 1).' added.');
    }

    public function destroy(AssessmentSlot $slot): RedirectResponse
    {
        if ($slot->bookings()->exists()) {
            return back()->withErrors(['slot' => 'Families are booked into this session. Move them to another session first.']);
        }
        $slot->delete();

        return back()->with('status', 'Session removed.');
    }
}
