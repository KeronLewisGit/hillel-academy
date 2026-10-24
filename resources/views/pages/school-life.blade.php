<x-layouts.site title="School life" description="Sport, the arts, clubs, houses and traditions at Hillel Academy, Kingston.">
    <x-page-header title="School life" note="Home of the Panthers" :lead="hillel('school_life.lead')" photo="{{ hillel('school_life.photo') }}" />

    <section class="wrap mt-12 md:mt-16" aria-labelledby="activities-title">
        <x-margin-note>Co-curricular</x-margin-note>
        <h2 id="activities-title" class="display-2 mt-3">Sport, arts and service</h2>
        <div class="mt-8 grid gap-5 md:grid-cols-2">
            @foreach (hillel('school_life.groups') as $g)
                <article class="panel tabbed p-6" data-reveal style="--reveal-delay: {{ $loop->index * 60 }}ms">
                    <h3 class="display-4 flex items-center gap-2"><x-icon :name="$g['icon']" class="h-6 w-6 text-ink-2" />{{ $g['title'] }}</h3>
                    <ul class="mt-4 flex flex-wrap gap-2">@foreach ($g['items'] as $item)<li class="rounded-full bg-sky px-3 py-1 text-[0.9375rem]">{{ $item }}</li>@endforeach</ul>
                </article>
            @endforeach
        </div>
    </section>

    <section class="wrap mt-16 grid gap-6 md:grid-cols-2">
        @foreach (hillel('school_life.gallery') as $key)
            <x-photo :key="$key" :alt="hillel('photos.'.$key.'.alt')" sizes="(min-width: 768px) 50vw, 100vw" class="aspect-[3/2] rounded-xl" :tab="hillel('photos.'.$key.'.tab')" />
        @endforeach
    </section>

    <section class="ruled ruled-margin mt-20 bg-sky py-16 md:py-20" aria-labelledby="houses-title">
        <div class="wrap grid gap-10 lg:grid-cols-[1fr_1.2fr]">
            <div>
                <x-margin-note>Houses & traditions</x-margin-note>
                <h2 id="houses-title" class="display-2 mt-3">Four houses, one school</h2>
                <ul class="prose-copy measure mt-5">@foreach (hillel('school_life.traditions') as $t)<li>{{ $t }}</li>@endforeach</ul>
            </div>
            <ul class="grid grid-cols-2 gap-4">
                @foreach (hillel('school_life.houses') as $h)
                    <li class="panel overflow-hidden"><span class="block h-3" style="background: {{ $h['hex'] }}" aria-hidden="true"></span><div class="p-5"><p class="font-display display-3">{{ $h['name'] }}</p><p class="text-ink-2">{{ $h['colour'] }} house</p></div></li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="wrap mt-20 grid gap-10 lg:grid-cols-[1fr_1.2fr]">
        <div><x-margin-note>Coming up</x-margin-note><h2 class="display-2 mt-3">Fixtures, shows and events</h2><a href="{{ route('calendar') }}" class="btn btn-outline mt-6">Full calendar</a></div>
        <div>@include('partials.event-list', ['events' => $events])</div>
    </section>
</x-layouts.site>
