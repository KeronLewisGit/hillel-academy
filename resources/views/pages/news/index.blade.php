<x-layouts.site title="News & events" description="News and upcoming events at Hillel Academy, Kingston.">
    <x-page-header title="News & events" note="From the school" lead="Stories from around the school and what’s coming up on the calendar." />
    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[2fr_1fr]">
        <div>
            @if ($posts->isEmpty())
                <p class="panel p-6 text-ink-2">No stories have been published yet.</p>
            @else
                <div class="grid gap-6 sm:grid-cols-2">
                    @foreach ($posts as $post)
                        <article class="panel flex flex-col overflow-hidden">
                            @if ($post->imageUrl())<img src="{{ $post->imageUrl() }}" alt="{{ $post->image_alt }}" class="aspect-[16/10] w-full object-cover" loading="lazy">@else<div class="ruled bg-sky px-5 pb-3 pt-6" aria-hidden="true"><span class="font-display text-3xl leading-none">{{ $post->published_at->format('j M') }}</span></div>@endif
                            <div class="flex flex-1 flex-col p-5">
                                <p class="text-sm text-ink-3"><time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('j F Y') }}</time></p>
                                <h2 class="display-4 mt-1"><a class="hover:text-link" href="{{ route('news.show', $post) }}">{{ $post->title }}</a></h2>
                                @if ($post->excerpt)<p class="mt-2 flex-1 text-ink-2">{{ $post->excerpt }}</p>@endif
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="mt-8">{{ $posts->links() }}</div>
            @endif
        </div>
        <aside>
            <x-margin-note>Coming up</x-margin-note>
            @include('partials.event-list', ['events' => $events])
            <a href="{{ route('calendar') }}" class="btn btn-outline mt-6">Full calendar<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </aside>
    </section>
</x-layouts.site>
