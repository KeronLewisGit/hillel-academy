<?php

namespace App\Http\Controllers;

use App\Enums\EventCategory;
use App\Models\CalendarEvent;
use App\Models\NewsPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        return view('pages.news.index', [
            'posts' => NewsPost::published()->paginate(9),
            'events' => CalendarEvent::upcoming()->limit(3)->get(),
        ]);
    }

    public function show(NewsPost $post): View
    {
        abort_unless($post->isPublished(), 404);

        return view('pages.news.show', [
            'post' => $post,
            'more' => NewsPost::published()->where('id', '!=', $post->id)->limit(3)->get(),
        ]);
    }

    /** The public calendar, grouped by month, optionally filtered by category. */
    public function calendar(Request $request): View
    {
        $category = EventCategory::tryFrom(input_string($request->query('category')));

        $events = CalendarEvent::upcoming()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->limit(120)->get();

        return view('pages.calendar', [
            'months' => $events->groupBy(fn (CalendarEvent $e) => $e->starts_at->format('F Y')),
            'category' => $category,
            'counts' => CalendarEvent::upcoming()->reorder()->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category'),
        ]);
    }
}
