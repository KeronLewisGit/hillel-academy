<x-layouts.admin title="Calendar">
    <x-slot:actions>
        <a href="{{ route('admin.events.index', $past ? [] : ['show' => 'past']) }}" class="a-btn a-btn-ghost">{{ $past ? 'Upcoming' : 'Past events' }}</a>
        <a href="{{ route('admin.events.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />Add event</a>
    </x-slot:actions>
    <section class="a-card overflow-hidden"><div class="overflow-x-auto"><table class="data-table"><thead><tr><th>When</th><th>Event</th><th>Type</th><th>Shown</th><th></th></tr></thead><tbody>
        @forelse ($events as $e)
            <tr><td class="whitespace-nowrap text-sm">{{ $e->whenLabel() }}</td><td class="font-semibold">{{ $e->title }}<span class="block text-xs font-normal text-ink-3">{{ $e->location }}</span></td><td class="text-sm">{{ $e->category->label() }}</td>
                <td>@if ($e->published)<x-chip tone="good" icon="eye">Public</x-chip>@else<x-chip tone="muted" icon="dot">Hidden</x-chip>@endif</td>
                <td class="r"><a class="a-btn a-btn-ghost" href="{{ route('admin.events.edit', $e) }}">Edit</a></td></tr>
        @empty
            <tr><td colspan="5" class="py-10 text-center text-ink-3">Nothing on the calendar.</td></tr>
        @endforelse
    </tbody></table></div><div class="border-t border-line p-4">{{ $events->links() }}</div></section>
</x-layouts.admin>
