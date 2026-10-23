<ul class="mt-5 grid gap-3">
    @forelse ($events as $e)
        <li class="panel flex gap-4 p-4">
            <span class="grid w-14 flex-none place-items-center rounded-lg bg-sky py-2 text-center leading-none"><span class="text-xs font-bold uppercase tracking-wider text-ink-2">{{ $e->starts_at->format('M') }}</span><span class="font-display text-2xl">{{ $e->starts_at->format('j') }}</span></span>
            <span><span class="font-semibold">{{ $e->title }}</span><span class="block text-sm text-ink-3">{{ $e->whenLabel() }}@if ($e->location) · {{ $e->location }}@endif</span></span>
        </li>
    @empty
        <li class="text-ink-2">Nothing on the calendar yet.</li>
    @endforelse
</ul>
