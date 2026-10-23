<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TourSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TourSlotController extends Controller
{
    public function index(): View
    {
        return view('admin.tours', ['slots' => TourSlot::upcoming()->with('bookings')->withCount('bookings')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240'],
            'capacity' => ['required', 'integer', 'min:1', 'max:40'],
            'notes' => ['nullable', 'string', 'max:255'],
            'repeat_weeks' => ['nullable', 'integer', 'min:0', 'max:12'],
        ]);
        $start = Carbon::parse($data['starts_at']);
        $repeat = (int) ($data['repeat_weeks'] ?? 0);
        for ($i = 0; $i <= $repeat; $i++) {
            TourSlot::create([
                'starts_at' => $start->copy()->addWeeks($i),
                'duration_minutes' => $data['duration_minutes'],
                'capacity' => $data['capacity'],
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return back()->with('status', ($repeat + 1).' tour '.str('time')->plural($repeat + 1).' added.');
    }

    public function destroy(TourSlot $slot): RedirectResponse
    {
        if ($slot->bookings()->exists()) {
            return back()->withErrors(['slot' => 'Families have booked this tour. Contact them before removing it.']);
        }
        $slot->delete();

        return back()->with('status', 'Tour time removed.');
    }
}
