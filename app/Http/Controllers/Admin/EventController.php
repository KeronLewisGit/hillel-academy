<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventCategory;
use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $past = $request->query('show') === 'past';

        return view('admin.events.index', [
            'events' => CalendarEvent::query()
                ->when($past, fn ($q) => $q->where('starts_at', '<', now()->startOfDay())->orderByDesc('starts_at'),
                    fn ($q) => $q->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at'))
                ->paginate(40)->withQueryString(),
            'past' => $past,
        ]);
    }

    public function create(): View
    {
        return view('admin.events.form', ['event' => new CalendarEvent(['published' => true, 'category' => EventCategory::Community])]);
    }

    public function store(Request $request): RedirectResponse
    {
        CalendarEvent::create($this->validated($request));

        return to_route('admin.events.index')->with('status', 'Event added to the calendar.');
    }

    public function edit(CalendarEvent $event): View
    {
        return view('admin.events.form', ['event' => $event]);
    }

    public function update(Request $request, CalendarEvent $event): RedirectResponse
    {
        $event->update($this->validated($request));

        return to_route('admin.events.index')->with('status', 'Event saved.');
    }

    public function destroy(CalendarEvent $event): RedirectResponse
    {
        $event->delete();

        return to_route('admin.events.index')->with('status', 'Event deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:160'],
            'category' => ['required', Rule::enum(EventCategory::class)],
            'description' => ['nullable', 'string', 'max:3000'],
        ]);
        $data['all_day'] = $request->boolean('all_day');
        $data['published'] = $request->boolean('published');

        return $data;
    }
}
