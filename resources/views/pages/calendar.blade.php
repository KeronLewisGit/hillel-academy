<x-layouts.site title="Calendar" description="Upcoming events, admissions dates and school closures at Hillel Academy.">
    <x-page-header title="School calendar" note="What’s on" lead="Admissions dates, school events, fixtures and closures. Dates are kept up to date by the school office." />
    <section class="wrap mt-12 md:mt-16">
        <nav class="tag-row" aria-label="Filter events">
            <a class="tag" href="{{ route('calendar') }}" @if(! $category) aria-current="true" @endif>Everything <span class="count">{{ $counts->sum() }}</span></a>
            @foreach (\App\Enums\EventCategory::cases() as $c)
                @if ($counts[$c->value] ?? 0)<a class="tag" href="{{ route('calendar', ['category' => $c->value]) }}" @if($category === $c) aria-current="true" @endif>{{ $c->label() }} <span class="count">{{ $counts[$c->value] }}</span></a>@endif
            @endforeach
        </nav>
        @forelse ($months as $month => $events)
            <h2 class="display-3 mt-10 border-b border-line pb-2">{{ $month }}</h2>
            <ul class="mt-4 grid gap-3">
                @foreach ($events as $e)
                    <li class="panel grid gap-3 p-5 sm:grid-cols-[5rem_1fr_auto] sm:items-start">
                        <span class="grid w-16 place-items-center rounded-lg bg-sky py-2 text-center leading-none"><span class="text-xs font-bold uppercase tracking-wider text-ink-2">{{ $e->starts_at->format('D') }}</span><span class="font-display text-3xl">{{ $e->starts_at->format('j') }}</span></span>
                        <span><span class="display-4 block">{{ $e->title }}</span><span class="block text-ink-2">{{ $e->whenLabel() }}@if ($e->location) · {{ $e->location }}@endif</span>@if ($e->description)<span class="measure mt-2 block text-ink-2">{{ $e->description }}</span>@endif</span>
                        <x-chip tone="neutral" :icon="$e->category === \App\Enums\EventCategory::Admissions ? 'cap' : 'calendar'">{{ $e->category->label() }}</x-chip>
                    </li>
                @endforeach
            </ul>
        @empty
            <p class="panel mt-8 p-6 text-ink-2">No upcoming events in this category.</p>
        @endforelse
    </section>
</x-layouts.site>
