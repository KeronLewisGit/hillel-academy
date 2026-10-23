<x-layouts.site :title="$post->title" :description="$post->excerpt" :json-ld="[['@context' => 'https://schema.org', '@type' => 'NewsArticle', 'headline' => $post->title, 'datePublished' => $post->published_at->toAtomString(), 'image' => $post->imageUrl(), 'publisher' => ['@type' => 'Organization', 'name' => hillel('name')]]]">
    <x-page-header :title="$post->title" note="News · {{ $post->published_at->format('j F Y') }}" :lead="$post->excerpt" />
    <article class="wrap mt-12 md:mt-16">
        @if ($post->imageUrl())<figure class="mx-auto max-w-4xl"><img src="{{ $post->imageUrl() }}" alt="{{ $post->image_alt }}" class="w-full rounded-xl"></figure>@endif
        <div class="prose-copy measure mx-auto mt-10 text-lg">@foreach ($post->paragraphs() as $p)<p>{{ $p }}</p>@endforeach</div>
    </article>
    @if ($more->isNotEmpty())
        <section class="wrap mt-16"><x-margin-note>More news</x-margin-note>
            <ul class="mt-5 grid gap-4 md:grid-cols-3">@foreach ($more as $m)<li class="panel p-5"><p class="text-sm text-ink-3">{{ $m->published_at->format('j F Y') }}</p><a class="display-4 mt-1 block hover:text-link" href="{{ route('news.show', $m) }}">{{ $m->title }}</a></li>@endforeach</ul>
        </section>
    @endif
</x-layouts.site>
