<x-layouts.site title="About" description="Hillel Academy: an independent, non-denominational, not-for-profit international school in Kingston, Jamaica, founded in 1969.">
    <x-page-header title="About Hillel Academy" note="Since {{ hillel('founded') }}" :lead="hillel('about.lead')" photo="{{ hillel('about.photo') }}" />
    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[1.4fr_1fr]">
        <div class="prose-copy measure">
            <h2 class="!mt-0">Our story</h2>
            @foreach (hillel('about.history') as $p)<p>{{ $p }}</p>@endforeach
            <h2>Vision</h2>
            <p>{{ hillel('about.vision') }}</p>
            <h2>Mission</h2>
            <blockquote class="border-l-4 border-[color:var(--accent)] pl-5 font-display text-2xl leading-snug text-ink">{{ hillel('about.mission') }}</blockquote>
        </div>
        <aside class="grid content-start gap-6">
            <div class="panel tabbed p-6">
                <x-margin-note>Key facts</x-margin-note>
                <dl class="mt-4 grid gap-4">
                    @foreach (hillel('about.facts') as $label => $value)
                        <div><dt class="text-sm text-ink-3">{{ $label }}</dt><dd class="font-semibold">{{ $value }}</dd></div>
                    @endforeach
                </dl>
            </div>
        </aside>
    </section>

    {{-- Hillel PRIDE: the five values, each on its own index tab --}}
    <section class="ruled ruled-margin mt-20 bg-sky py-16 md:py-20" aria-labelledby="values-title">
        <div class="wrap">
            <x-margin-note>Our values</x-margin-note>
            <h2 id="values-title" class="display-2 mt-3">Hillel PRIDE</h2>
            <p class="lead mt-4 text-ink-2">Five core values, shown every day in the words we use, the choices we make and the actions we take.</p>
            <ol class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
                @foreach (hillel('about.values') as $v)
                    <li class="flex flex-col" data-reveal style="--reveal-delay: {{ $loop->index * 70 }}ms"><span class="tab self-start font-display !text-lg">{{ $v['letter'] }}</span><div class="panel tabbed flex-1 rounded-tl-none p-5"><h3 class="display-4">{{ $v['name'] }}</h3><p class="mt-1 text-ink-2">{{ ucfirst($v['text']) }}</p></div></li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="wrap mt-20 grid gap-6 md:grid-cols-2">
        @foreach (hillel('about.gallery') as $key)
            <x-photo :key="$key" :alt="hillel('photos.'.$key.'.alt')" sizes="(min-width: 768px) 50vw, 100vw" class="aspect-[3/2] rounded-xl" :tab="hillel('photos.'.$key.'.tab')" />
        @endforeach
    </section>
</x-layouts.site>
