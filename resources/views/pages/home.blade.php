@php
    $fees = \App\Models\FeeBand::ordered()->get();
@endphp
<x-layouts.site>
    {{-- Hero: ink-blue exercise-book page beside a big campus photo --}}
    {{-- The copy sits in .wrap so it starts on the same left edge as the header and every section; on desktop the photo is pinned to the right half. --}}
    <section class="on-dark relative isolate overflow-hidden bg-ink-900 text-white">
        <x-photo key="campus-buildings" :alt="hillel('photos.campus-buildings.alt')" :eager="true" sizes="(min-width: 1024px) 48vw, 100vw" class="hero-photo aspect-[3/2] lg:absolute lg:inset-y-0 lg:right-0 lg:z-10 lg:aspect-auto lg:w-[48%]" img-class="object-[52%_70%] lg:object-[64%_70%]" :tab="hillel('photos.campus-buildings.tab')" />
        <div class="ruled">
            <div class="wrap flex items-center pb-14 pt-10 lg:min-h-[38rem] lg:py-20">
                <div class="lg:max-w-[calc(52vw-max(0px,(100vw-var(--wrap-max))/2)-var(--gutter)-3rem)]">
                    <x-margin-note>{{ hillel('hero.note') }}</x-margin-note>
                    <h1 class="display-1 mt-5 max-w-[14ch]">{{ hillel('hero.title') }}</h1>
                    <p class="lead mt-6 text-white/85">{{ hillel('hero.lead') }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('apply') }}" class="btn btn-accent">Start an application<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                        <a href="{{ route('visit') }}" class="btn btn-on-dark">Book a campus visit</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="relative z-20 h-1.5 bg-accent" aria-hidden="true"></div>
    </section>

    {{-- At a glance: index-tab cards with sourced facts only --}}
    <section class="wrap mt-16 md:mt-20" aria-labelledby="glance-title">
        <x-margin-note id="glance-title">At a glance</x-margin-note>
        <dl class="mt-6 grid gap-x-5 gap-y-8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (hillel('facts') as $fact)
                <div class="flex flex-col" data-reveal style="--reveal-delay: {{ $loop->index * 70 }}ms">
                    <span class="tab self-start {{ $loop->first ? '' : 'tab-sky' }}">{{ $fact['tab'] }}</span>
                    <div class="panel tabbed flex-1 rounded-tl-none p-5 {{ $loop->first ? '' : '!border-t-[color:var(--sky)]' }}">
                        <dt class="sr-only">{{ $fact['tab'] }}</dt>
                        <dd><span class="font-display block text-[2rem] leading-tight">{{ $fact['value'] }}</span><span class="mt-1 block text-ink-2">{{ $fact['label'] }}</span></dd>
                    </div>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Schools within the school --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="sections-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><x-margin-note>From K1 to Grade 13</x-margin-note><h2 id="sections-title" class="display-2 mt-3">One campus, every stage</h2><p class="lead mt-3 text-ink-2">Jamaica’s National Standards Curriculum in the Prep School, then three international programmes in the High School.</p></div>
            <a href="{{ route('academics') }}" class="btn btn-outline">Academics<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach (hillel('home_stages') as $key)
                @php($stage = hillel('stages.'.$key))
                <article class="panel flex flex-col overflow-hidden" data-reveal style="--reveal-delay: {{ $loop->index * 90 }}ms">
                    <x-photo :key="$stage['photo']" alt="" sizes="(min-width: 768px) 33vw, 100vw" class="aspect-[3/2]" :tab="$stage['grades']" />
                    <div class="flex flex-1 flex-col p-6">
                        <h3 class="display-3">{{ $stage['name'] }}</h3>
                        <p class="mt-2 flex-1 text-ink-2">{{ $stage['summary'] }}</p>
                        <a class="mt-4 inline-flex min-h-11 items-center gap-1.5 font-semibold text-link" href="{{ route('academics') }}#{{ $key }}">More about {{ $stage['name'] }}<x-icon name="arrow-right" class="h-4 w-4" /></a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- How to apply: a ruled sky page --}}
    <section class="ruled ruled-margin mt-20 bg-sky py-16 md:mt-28 md:py-24" aria-labelledby="apply-title">
        <div class="wrap">
            <x-margin-note>Admissions</x-margin-note>
            <h2 id="apply-title" class="display-2 mt-3 max-w-3xl">Apply online, at your own pace</h2>
            <p class="lead mt-4 text-ink-2">Everything happens online, and you can stop and pick up again whenever you like. You’ll always know what happens next.</p>
            <ol class="mt-10 grid gap-5 md:grid-cols-4">
                @foreach (hillel('admissions_steps') as $step)
                    <li class="panel p-5" data-reveal style="--reveal-delay: {{ $loop->index * 80 }}ms">
                        <span class="font-display text-4xl text-[color:var(--accent)]" aria-hidden="true">{{ $loop->iteration }}</span>
                        <h3 class="display-4 mt-2">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-ink-2">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>
            <div class="mt-10 flex flex-wrap items-center gap-4">
                <a href="{{ route('apply') }}" class="btn btn-accent">Start an application<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                <a href="{{ route('admissions') }}" class="btn btn-outline">Grades, fees and dates</a>
                @if ($fees->isNotEmpty())<p class="text-ink-2">Tuition for international students {{ usd($fees->min('amount_usd')) }}–{{ usd($fees->max('amount_usd')) }} a term, as published for {{ setting('fees_year') }}.</p>@endif
            </div>
        </div>
    </section>

    {{-- Setting --}}
    <section class="wrap mt-20 grid items-center gap-10 md:mt-28 lg:grid-cols-2" aria-labelledby="place-title">
        <x-photo key="{{ hillel('place.photo') }}" :alt="hillel('photos.'.hillel('place.photo').'.alt')" sizes="(min-width: 1024px) 50vw, 100vw" class="aspect-[4/3] rounded-xl" :tab="hillel('photos.'.hillel('place.photo').'.tab')" />
        <div>
            <x-margin-note>Our setting</x-margin-note>
            <h2 id="place-title" class="display-2 mt-3">{{ hillel('place.title') }}</h2>
            <div class="prose-copy measure mt-5 text-ink-2">@foreach (hillel('place.body') as $p)<p>{{ $p }}</p>@endforeach</div>
            <a href="{{ route('about') }}" class="btn btn-outline mt-6">About Hillel<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
    </section>

    {{-- News and events --}}
    <section class="wrap mt-20 grid gap-12 md:mt-28 lg:grid-cols-[2fr_1fr]" aria-labelledby="news-title">
        <div>
            <x-margin-note>From the school</x-margin-note>
            <h2 id="news-title" class="display-2 mt-3">News</h2>
            @if ($news->isEmpty())
                <p class="panel mt-6 p-6 text-ink-2">School news will appear here as soon as the office publishes it.</p>
            @else
                {{-- Stacked rows on the same rhythm as the calendar list beside it, so both columns fill the band evenly. --}}
                <ul class="mt-5 grid gap-3">
                    @foreach ($news as $post)
                        <li>
                            <article class="panel group relative flex gap-4 p-4 sm:gap-5 sm:p-5">
                                @if ($post->imageUrl())
                                    <img src="{{ $post->imageUrl() }}" alt="" class="hidden aspect-square w-24 flex-none rounded-lg object-cover sm:block" loading="lazy">
                                @endif
                                <span class="date-tile @if ($post->imageUrl()) sm:hidden @endif" aria-hidden="true"><span class="date-tile-month">{{ $post->published_at->format('M') }}</span><span class="date-tile-day">{{ $post->published_at->format('j') }}</span></span>
                                <div class="min-w-0">
                                    <p class="text-sm text-ink-3"><time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('j F Y') }}</time></p>
                                    <h3 class="display-4 mt-1"><a class="after:absolute after:inset-0 group-hover:text-link" href="{{ route('news.show', $post) }}">{{ $post->title }}</a></h3>
                                    @if ($post->excerpt)<p class="mt-2 text-ink-2">{{ $post->excerpt }}</p>@endif
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            @endif
            <a href="{{ route('news.index') }}" class="btn btn-outline mt-6">All news</a>
        </div>
        <div>
            <x-margin-note>Coming up</x-margin-note>
            <h2 class="display-2 mt-3">Calendar</h2>
            @include('partials.event-list', ['events' => $events])
            <a href="{{ route('calendar') }}" class="btn btn-outline mt-6">Full calendar</a>
        </div>
    </section>

    {{-- Visit band --}}
    <section class="wrap mt-20 md:mt-28">
        <div class="on-dark ruled relative overflow-hidden rounded-2xl bg-ink-900 p-8 text-white sm:p-12">
            <div class="grid items-center gap-8 lg:grid-cols-[1.4fr_1fr]">
                <div><x-margin-note>Visit</x-margin-note><h2 class="display-2 mt-3">Walk the campus before you decide</h2><p class="lead mt-4 text-white/85">Pick a tour time online. Or call Admissions on {{ format_phone(setting('phone_admissions')) }}.</p></div>
                <div class="flex flex-wrap gap-3 lg:justify-end"><a href="{{ route('visit') }}" class="btn btn-accent">Book a visit<x-icon name="arrow-right" class="nudge h-4 w-4" /></a><a href="{{ route('enquire') }}" class="btn btn-on-dark">Ask a question</a></div>
            </div>
        </div>
    </section>
</x-layouts.site>
