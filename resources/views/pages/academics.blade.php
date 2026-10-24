<x-layouts.site title="Academics" description="Academics at Hillel Academy, Kingston: National Standards Curriculum in the Prep School, then IMYC, Cambridge IGCSE and the IB Diploma.">
    <x-page-header title="Learning at Hillel" note="Academics" :lead="hillel('academics.lead')" photo="{{ hillel('academics.photo') }}" />

    {{-- The whole journey at a glance, as binder tabs --}}
    <nav class="border-b border-line bg-surface" aria-label="Stages">
        <div class="wrap">
            <ol class="grid grid-cols-2 gap-2 py-4 lg:grid-cols-4">
                @foreach (hillel('stages') as $key => $s)
                    <li><a href="#{{ $key }}" class="flex h-full min-h-11 flex-col rounded-lg border border-line p-3 hover:border-ink"><span class="text-xs font-bold uppercase tracking-wider text-ink-3">{{ $s['grades'] }}</span><span class="font-semibold">{{ $s['name'] }}</span></a></li>
                @endforeach
            </ol>
        </div>
    </nav>

    @foreach (hillel('stages') as $key => $s)
        <section id="{{ $key }}" class="wrap mt-16 grid items-start gap-10 md:mt-20 lg:grid-cols-2" aria-labelledby="{{ $key }}-title">
            <x-photo :key="$s['photo']" :alt="hillel('photos.'.$s['photo'].'.alt')" sizes="(min-width: 1024px) 50vw, 100vw" class="aspect-[3/2] rounded-xl {{ $loop->even ? 'lg:order-2' : '' }}" :tab="$s['grades']" />
            <div>
                <x-margin-note>{{ $s['grades'] }} · {{ $s['ages'] }}</x-margin-note>
                <h2 id="{{ $key }}-title" class="display-2 mt-3">{{ $s['name'] }}</h2>
                <div class="prose-copy measure mt-5">@foreach ($s['detail'] as $p)<p>{{ $p }}</p>@endforeach</div>
            </div>
        </section>
    @endforeach

    <section class="wrap mt-20 flex flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
        <h2 class="display-3">Questions about the curriculum?</h2>
        <div class="flex flex-wrap gap-3"><a href="{{ route('enquire') }}" class="btn btn-primary">Ask Admissions</a><a href="{{ route('visit') }}" class="btn btn-outline">Book a visit</a></div>
    </section>
</x-layouts.site>
