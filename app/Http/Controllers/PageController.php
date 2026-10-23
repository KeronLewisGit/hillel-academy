<?php

namespace App\Http\Controllers;

use App\Enums\EventCategory;
use App\Models\CalendarEvent;
use App\Models\FeeBand;
use App\Models\JobPosting;
use App\Models\NewsPost;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'news' => NewsPost::published()->limit(3)->get(),
            'events' => CalendarEvent::upcoming()->limit(4)->get(),
        ]);
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function academics(): View
    {
        return view('pages.academics');
    }

    public function admissions(): View
    {
        return view('pages.admissions', [
            'fees' => FeeBand::ordered()->get(),
            'keyDates' => CalendarEvent::upcoming()->where('category', EventCategory::Admissions)->limit(6)->get(),
        ]);
    }

    public function schoolLife(): View
    {
        return view('pages.school-life', ['events' => CalendarEvent::upcoming()->whereIn('category', [EventCategory::Sports, EventCategory::Arts, EventCategory::Community])->limit(4)->get()]);
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function credits(): View
    {
        $used = collect((array) config('hillel.photos'))->pluck('credit')->unique();

        return view('pages.credits', ['credits' => collect((array) config('photo-credits'))->only($used->all())]);
    }

    public function sitemap(): Response
    {
        $static = ['home', 'about', 'academics', 'admissions', 'school-life', 'news.index', 'calendar', 'visit', 'apply', 'enquire', 'careers.index', 'contact', 'privacy', 'credits'];

        return response()->view('sitemap', [
            'urls' => collect($static)->map(fn (string $r) => ['loc' => route($r), 'lastmod' => null])
                ->merge(NewsPost::published()->get()->map(fn (NewsPost $p) => ['loc' => route('news.show', $p), 'lastmod' => $p->updated_at?->toAtomString()]))
                ->merge(JobPosting::open()->get()->map(fn (JobPosting $j) => ['loc' => route('careers.show', $j), 'lastmod' => $j->updated_at?->toAtomString()])),
        ])->header('Content-Type', 'application/xml');
    }
}
