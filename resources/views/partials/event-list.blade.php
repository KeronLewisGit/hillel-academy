<ul class="mt-5 grid gap-3">
    @forelse ($events as $e)
        <li class="panel flex gap-4 p-4">
            <span class="date-tile" aria-hidden="true"><span class="date-tile-month">{{ $e->starts_at->format('M') }}</span><span class="date-tile-day">{{ $e->starts_at->format('j') }}</span></span>
            <span class="min-w-0"><span class="font-semibold">{{ $e->title }}</span><span class="block text-sm text-ink-3">{{ $e->whenLabel() }}@if ($e->location) · {{ $e->location }}@endif</span></span>
        </li>
    @empty
        <li class="text-ink-2">Nothing on the calendar yet.</li>
    @endforelse
</ul>
