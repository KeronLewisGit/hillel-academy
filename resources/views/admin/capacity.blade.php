<x-layouts.admin title="Capacity & waitlist">
    <x-slot:actions>
        <nav class="flex gap-2" aria-label="Entry year">@foreach ($years as $y)<a class="tag" href="{{ route('admin.capacity.index', ['year' => $y]) }}" @if($y === $year) aria-current="true" @endif>{{ $y }}</a>@endforeach</nav>
    </x-slot:actions>
    <p class="mb-4 max-w-3xl text-ink-2">Seats are the places you can offer in each grade for {{ $year }}. Offers out, accepted offers and enrolments all hold a seat; when a family declines or withdraws, the seat comes back and the next waitlisted family is shown.</p>
    <form method="POST" action="{{ route('admin.capacity.update') }}" class="a-card overflow-hidden">@csrf @method('PUT')
        <input type="hidden" name="year" value="{{ $year }}">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Grade</th><th class="r">Seats</th><th>Held</th><th class="r">Offered</th><th class="r">Accepted</th><th class="r">Enrolled</th><th class="r">Free</th><th class="r">In pipeline</th><th>Waitlist</th></tr></thead>
                <tbody>
                    @foreach ($rows as $r)
                        @php($full = $r['seats'] > 0 && $r['left'] === 0)
                        <tr>
                            <th scope="row" class="!static !border-b !border-line !bg-transparent !text-sm !normal-case !tracking-normal !text-ink">{{ $r['label'] }}<span class="block text-xs font-normal text-ink-3">{{ hillel('sections.'.$r['section'].'.name') }}</span></th>
                            <td class="r"><label class="sr-only" for="seats-{{ $r['grade'] }}">Seats for {{ $r['label'] }}</label><input id="seats-{{ $r['grade'] }}" type="number" min="0" max="500" name="seats[{{ $r['grade'] }}]" value="{{ $r['seats'] }}" class="a-input num w-20 text-right"></td>
                            <td class="min-w-36"><div class="meter" data-full="{{ $full ? 'true' : 'false' }}" role="img" aria-label="{{ $r['held'] }} of {{ $r['seats'] }} seats held"><span style="width: {{ $r['seats'] ? min(100, $r['held'] / $r['seats'] * 100) : 0 }}%"></span></div><span class="num text-xs text-ink-3">{{ $r['held'] }} / {{ $r['seats'] }}</span></td>
                            <td class="r">{{ $r['offered'] }}@if ($r['expired'])<span class="block text-xs text-ink-3">+{{ $r['expired'] }} expired</span>@endif</td><td class="r">{{ $r['accepted'] }}</td><td class="r">{{ $r['enrolled'] }}</td>
                            <td class="r">@if ($full)<x-chip tone="attention" icon="alert">Full</x-chip>@else{{ $r['left'] }}@endif</td>
                            <td class="r"><a class="inline-flex min-h-11 min-w-11 items-center justify-end text-link underline" href="{{ route('admin.applications.index', ['grade' => $r['grade'], 'year' => $year]) }}">{{ $r['in_pipeline'] }}</a></td>
                            <td>@if ($r['waitlist'])<a class="text-link underline" href="#wl-{{ $r['grade'] }}">{{ $r['waitlist'] }} waiting</a>@else<span class="text-ink-3">–</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex justify-end border-t border-line p-4"><button class="a-btn a-btn-primary">Save seats for {{ $year }}</button></div>
    </form>

    <h2 class="mt-8 font-display text-2xl">Waiting lists</h2>
    @forelse ($waitlists as $grade => $list)
        @php($row = collect($rows)->firstWhere('grade', $grade))
        <section id="wl-{{ $grade }}" class="a-card mt-4 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 p-5 pb-3"><h3 class="font-semibold">{{ grade_label($grade) }}</h3><span class="text-sm text-ink-2">{{ $row['left'] ?? 0 }} {{ \Illuminate\Support\Str::plural('seat', $row['left'] ?? 0) }} free</span></div>
            <table class="data-table"><thead><tr><th class="r">#</th><th>Student</th><th>Waitlisted</th><th class="r">Action</th></tr></thead><tbody>
                @foreach ($list as $i => $a)
                    <tr><td class="r num">{{ $i + 1 }}</td><td><a class="font-semibold hover:text-link" href="{{ route('admin.applications.show', $a) }}">{{ $a->student_name }}</a></td><td>{{ $a->waitlisted_at?->format('j M Y') }}</td>
                        <td class="r">@if ($loop->first && ($row['left'] ?? 0) > 0)<form method="POST" action="{{ route('admin.applications.status', $a) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="offered"><input type="hidden" name="note" value="Offered from the waiting list."><button class="a-btn a-btn-primary">Offer the free seat</button></form>@endif</td></tr>
                @endforeach
            </tbody></table>
        </section>
    @empty
        <p class="mt-3 text-ink-3">Nobody is waitlisted for {{ $year }}.</p>
    @endforelse
</x-layouts.admin>
