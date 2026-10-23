<x-layouts.admin title="Campus tours">
    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <section class="grid content-start gap-4">
            @forelse ($slots as $s)
                <article class="a-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="font-semibold">{{ $s->starts_at->format('l j F Y, g:i a') }}</h2><p class="text-sm text-ink-3">{{ $s->duration_minutes }} minutes @if ($s->notes)· {{ $s->notes }}@endif</p></div>
                        <div class="flex items-center gap-3"><span class="num text-sm">{{ $s->bookings_count }} / {{ $s->capacity }} families</span>
                            @if ($s->bookings_count === 0)<form method="POST" action="{{ route('admin.tours.destroy', $s) }}" onsubmit="return confirm('Remove this tour time?')">@csrf @method('DELETE')<button class="a-btn a-btn-ghost w-11 px-0" aria-label="Remove tour time"><x-icon name="trash" class="h-4 w-4" /></button></form>@endif</div>
                    </div>
                    @if ($s->bookings->isNotEmpty())
                        <ul class="mt-3 divide-y divide-line text-sm">@foreach ($s->bookings as $b)<li class="flex flex-wrap justify-between gap-2 py-2"><span class="font-medium">{{ $b->name }} <span class="font-normal text-ink-3">({{ $b->party_size }} {{ \Illuminate\Support\Str::plural('person', $b->party_size) }})</span></span><span class="text-ink-3"><a class="text-link" href="mailto:{{ $b->email }}">{{ $b->email }}</a> · {{ format_phone($b->phone) }}@if ($b->grade) · {{ grade_label($b->grade) }}@endif</span></li>@endforeach</ul>
                    @endif
                </article>
            @empty
                <p class="a-card p-6 text-ink-3">No tour times published. The Visit page invites families to call instead.</p>
            @endforelse
        </section>
        <form method="POST" action="{{ route('admin.tours.store') }}" class="a-card grid content-start gap-3 p-5">@csrf
            <h2 class="font-semibold">Publish a tour time</h2>
            <label class="text-sm font-medium" for="starts_at">Starts</label><input id="starts_at" type="datetime-local" name="starts_at" class="a-input" required>
            <label class="text-sm font-medium" for="duration_minutes">Length (minutes)</label><input id="duration_minutes" type="number" name="duration_minutes" class="a-input" value="60" min="15" max="240" required>
            <label class="text-sm font-medium" for="capacity">Families per tour</label><input id="capacity" type="number" name="capacity" class="a-input" value="4" min="1" max="40" required>
            <label class="text-sm font-medium" for="notes">Note</label><input id="notes" name="notes" class="a-input" maxlength="255" placeholder="e.g. Meet at the main gate">
            <label class="text-sm font-medium" for="repeat_weeks">Repeat weekly for</label><select id="repeat_weeks" name="repeat_weeks" class="a-input"><option value="0">Just this once</option>@foreach (range(1, 8) as $n)<option value="{{ $n }}">{{ $n }} more {{ \Illuminate\Support\Str::plural('week', $n) }}</option>@endforeach</select>
            <button class="a-btn a-btn-primary mt-2">Publish</button>
        </form>
    </div>
</x-layouts.admin>
