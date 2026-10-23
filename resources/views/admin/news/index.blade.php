<x-layouts.admin title="News">
    <x-slot:actions><a href="{{ route('admin.news.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />New story</a></x-slot:actions>
    <section class="a-card overflow-hidden"><div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Story</th><th>Status</th><th></th></tr></thead><tbody>
        @forelse ($posts as $p)
            <tr><td><span class="font-semibold">{{ $p->title }}</span>@if ($p->isPublished())<a class="ml-2 text-sm text-link" href="{{ route('news.show', $p) }}" target="_blank" rel="noopener">View</a>@endif</td>
                <td>@if ($p->isPublished())<x-chip tone="good" icon="check">Published {{ $p->published_at->format('j M Y') }}</x-chip>@elseif ($p->published_at)<x-chip tone="neutral" icon="clock">Scheduled {{ $p->published_at->format('j M Y') }}</x-chip>@else<x-chip tone="muted" icon="pencil">Draft</x-chip>@endif</td>
                <td class="r"><a class="a-btn a-btn-ghost" href="{{ route('admin.news.edit', $p) }}">Edit</a></td></tr>
        @empty
            <tr><td colspan="3" class="py-10 text-center text-ink-3">No stories yet.</td></tr>
        @endforelse
    </tbody></table></div><div class="border-t border-line p-4">{{ $posts->links() }}</div></section>
</x-layouts.admin>
