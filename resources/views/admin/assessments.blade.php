<x-layouts.admin title="Assessment sessions">
    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <section class="grid content-start gap-4">
            @forelse ($slots as $s)
                <article class="a-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="font-semibold">{{ $s->starts_at->format('l j F Y, g:i a') }} – {{ $s->endsAt()->format('g:i a') }}</h2><p class="text-sm text-ink-3">{{ $s->location }} · {{ $s->gradesLabel() }}@if ($s->notes) · {{ $s->notes }}@endif</p></div>
                        <div class="flex items-center gap-3"><span class="num text-sm">{{ $s->bookings_count }} / {{ $s->capacity }} booked</span>
                            @if ($s->bookings_count === 0)<form method="POST" action="{{ route('admin.assessments.destroy', $s) }}" onsubmit="return confirm('Remove this session?')">@csrf @method('DELETE')<button class="a-btn a-btn-ghost w-11 px-0" aria-label="Remove session"><x-icon name="trash" class="h-4 w-4" /></button></form>@endif</div>
                    </div>
                    @if ($s->bookings->isNotEmpty())
                        <ul class="mt-3 divide-y divide-line text-sm">@foreach ($s->bookings as $a)<li class="flex justify-between py-2"><a class="font-medium hover:text-link" href="{{ route('admin.applications.show', $a) }}">{{ $a->student_name }}</a><span class="text-ink-3">{{ $a->grade_label }} · {{ $a->status->label() }}</span></li>@endforeach</ul>
                    @endif
                </article>
            @empty
                <p class="a-card p-6 text-ink-3">No upcoming sessions. Families whose documents are complete will see “no times available” until you add one.</p>
            @endforelse
        </section>
        <form method="POST" action="{{ route('admin.assessments.store') }}" class="a-card grid content-start gap-3 p-5">@csrf
            <h2 class="font-semibold">Add a session</h2>
            <label class="text-sm font-medium" for="starts_at">Starts</label><input id="starts_at" type="datetime-local" name="starts_at" class="a-input" required value="{{ old('starts_at') }}">
            <label class="text-sm font-medium" for="duration_minutes">Length (minutes)</label><input id="duration_minutes" type="number" name="duration_minutes" class="a-input" value="{{ old('duration_minutes', 90) }}" min="15" max="480" required>
            <label class="text-sm font-medium" for="location">Where</label><input id="location" name="location" class="a-input" value="{{ old('location', setting('assessment_location')) }}" required>
            <label class="text-sm font-medium" for="capacity">Students per session</label><input id="capacity" type="number" name="capacity" class="a-input" value="{{ old('capacity', 6) }}" min="1" max="100" required>
            <fieldset><legend class="text-sm font-medium">Grades <span class="font-normal text-ink-3">(none ticked = any grade)</span></legend>
                <div class="mt-2 grid grid-cols-2 gap-1 text-sm">@foreach (grades() as $k => $label)<label class="flex min-h-9 items-center gap-2"><input type="checkbox" name="grades[]" value="{{ $k }}" class="h-4 w-4"> {{ $label }}</label>@endforeach</div>
            </fieldset>
            <label class="text-sm font-medium" for="notes">Note for families</label><input id="notes" name="notes" class="a-input" maxlength="500" placeholder="e.g. bring a pencil case">
            <label class="text-sm font-medium" for="repeat_weeks">Repeat weekly for</label><select id="repeat_weeks" name="repeat_weeks" class="a-input"><option value="0">Just this once</option>@foreach (range(1, 8) as $n)<option value="{{ $n }}">{{ $n }} more {{ \Illuminate\Support\Str::plural('week', $n) }}</option>@endforeach</select>
            <button class="a-btn a-btn-primary mt-2">Add session</button>
        </form>
    </div>
</x-layouts.admin>
