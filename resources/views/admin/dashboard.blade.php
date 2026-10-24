@php
    $step = collect([1, 2, 5, 10, 20, 50, 100])->first(fn ($s) => $weeklyMax / $s <= 4) ?? 100;
    $yMax = (int) (ceil($weeklyMax / $step) * $step);
    $ticks = range(0, $yMax, $step);
    $pipeMax = max(1, $pipeline->max('count'));
@endphp
<x-layouts.admin title="Admissions overview">
    <x-slot:actions>
        <form method="GET" class="flex items-center gap-2"><label for="year" class="text-sm text-ink-2">Entry year</label>
            <select id="year" name="year" class="a-input" onchange="this.form.submit()">@foreach (entry_years() as $y)<option @selected($y === $year)>{{ $y }}</option>@endforeach</select>
            <noscript><button class="a-btn a-btn-ghost">Show</button></noscript>
        </form>
    </x-slot:actions>

    @if (\App\Support\Notify::recipients() === [])
        <div class="mb-5 flex gap-3 rounded-xl border border-line bg-surface px-5 py-4 shadow-sm" role="alert">
            <x-icon name="alert" class="mt-0.5 h-5 w-5 flex-none text-[color:var(--attention)]" />
            <p><span class="font-semibold">Nobody is emailed about new enquiries or applications.</span> An administrator can add addresses under <a class="text-link underline" href="{{ auth()->user()->isAdmin() ? route('admin.settings.edit') : '#' }}">Settings</a>.</p>
        </div>
    @endif

    {{-- The one number this screen exists for --}}
    <section class="a-card tabbed flex flex-col gap-5 p-6 md:flex-row md:items-center md:justify-between">
        @if ($awaitingDocs > 0)
            <div>
                <p class="text-sm font-semibold text-ink-2">Waiting for document review</p>
                <p class="mt-1 font-display text-5xl md:text-6xl">{{ number_format($awaitingDocs) }} <span class="font-sans text-2xl font-medium text-ink-2 md:text-3xl">{{ \Illuminate\Support\Str::plural('application', $awaitingDocs) }}</span></p>
                @if ($oldestPending)
                    <p class="mt-2 flex items-center gap-2 text-ink-2"><x-icon name="clock" class="h-4 w-4" />Oldest upload waiting since {{ $oldestPending->diffForHumans() }}. Families can’t book an assessment until their documents are accepted.</p>
                @endif
            </div>
            <a href="{{ route('admin.applications.index', ['docs' => 1, 'sort' => 'submitted_at', 'dir' => 'asc']) }}" class="a-btn a-btn-primary self-start px-5 md:self-center">Review documents<x-icon name="arrow-right" class="h-4 w-4" /></a>
        @else
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-2"><x-icon name="check" class="h-6 w-6 text-[color:var(--good)]" /></span>
                <div>
                    <p class="text-2xl font-semibold">Every document has been reviewed</p>
                    <p class="text-ink-2">New uploads from families appear here as they arrive.</p>
                </div>
            </div>
        @endif
    </section>

    @if ($freed->isNotEmpty())
        <section class="a-card mt-5 p-5" aria-labelledby="freed-title">
            <h2 id="freed-title" class="flex items-center gap-2 font-semibold"><x-icon name="star" class="h-5 w-5 text-[color:var(--attention)]" />Seats free with families waiting</h2>
            <ul class="mt-3 divide-y divide-line">
                @foreach ($freed as $row)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-2.5">
                        <span><strong>{{ $row['label'] }}</strong>: {{ $row['left'] }} {{ \Illuminate\Support\Str::plural('seat', $row['left']) }} free, {{ $row['waitlist'] }} waitlisted. Next in line: <a class="text-link underline" href="{{ route('admin.applications.show', $row['next_waitlisted']) }}">{{ $row['next_waitlisted']?->student_name }}</a></span>
                        <a class="a-btn a-btn-ghost" href="{{ route('admin.capacity.index', ['year' => $year]) }}">Open waitlist</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4" aria-label="Key figures">
        @foreach ($tiles as $tile)
            <x-stat-tile :label="$tile['label']" :value="$tile['value']" :note="$tile['note'] ?? null" :href="$tile['href']" />
        @endforeach
    </section>

    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[1.5fr_1fr]">
        <section class="a-card flex flex-col p-5" aria-labelledby="weekly-title">
            <h2 id="weekly-title" class="font-semibold">Applications submitted per week</h2>
            <p class="text-sm text-ink-3">Last {{ $weekly->count() }} weeks · {{ number_format($weekly->sum('count')) }} total · {{ $drafts }} more started but not yet submitted</p>
            <div class="relative mt-6 flex flex-1 flex-col" x-data="columnChart" @mouseleave="hide()">
                <div class="relative min-h-56 flex-1 pl-8" role="img" aria-label="Column chart of applications submitted per week over the last {{ $weekly->count() }} weeks, peaking at {{ $weeklyMax }}. The table below lists every value.">
                    @foreach ($ticks as $t)
                        <div class="absolute inset-x-0 flex items-center" style="bottom: {{ $yMax ? $t / $yMax * 100 : 0 }}%">
                            <span class="num w-7 -translate-y-px pr-2 text-right text-xs text-ink-3">{{ $t }}</span>
                            <span class="h-px flex-1 bg-line"></span>
                        </div>
                    @endforeach
                    <div class="absolute inset-y-0 left-8 right-0 flex items-end gap-1.5">
                        @foreach ($weekly as $w)
                            <div class="flex h-full flex-1 items-end justify-center" @mouseenter="show($el)" data-label="Week of {{ $w['date']->format('j M') }}" data-value="{{ $w['count'] }}">
                                <div class="bar w-full max-w-10 rounded-t bg-series transition-opacity" :class="tip && tip.label !== @js('Week of '.$w['date']->format('j M')) && 'opacity-40'" style="height: {{ $yMax ? $w['count'] / $yMax * 100 : 0 }}%; min-height: {{ $w['count'] ? '2px' : '0' }}"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-2 flex pl-8 text-xs text-ink-3">
                    @foreach ($weekly as $w)
                        <span class="relative h-4 flex-1">@if ($loop->index % 3 === 0)<span class="absolute left-1/2 -translate-x-1/2 whitespace-nowrap">{{ $w['date']->format('j M') }}</span>@endif</span>
                    @endforeach
                </div>
                <div x-show="tip" style="display:none" class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full rounded-lg bg-ink px-3 py-2 text-sm text-bg shadow-lg" :style="tip && `left:${tip.x}px; top:${tip.y - 8}px`">
                    <span class="block text-xs opacity-75" x-text="tip?.label"></span>
                    <span class="font-semibold" x-text="tip ? `${tip.value} ${tip.value === '1' ? 'application' : 'applications'}` : ''"></span>
                </div>
            </div>
            <details class="mt-4 text-sm">
                <summary class="cursor-pointer font-medium text-link">Show as table</summary>
                <table class="data-table mt-3"><thead><tr><th>Week of</th><th class="r">Submitted</th></tr></thead>
                    <tbody>@foreach ($weekly->reverse() as $w)<tr><td>{{ $w['date']->format('j M Y') }}</td><td class="r">{{ $w['count'] }}</td></tr>@endforeach</tbody>
                </table>
            </details>
        </section>

        <section class="a-card p-5" aria-labelledby="pipe-title">
            <h2 id="pipe-title" class="font-semibold">Pipeline for {{ $year }}</h2>
            <p class="text-sm text-ink-3">Submitted applications by stage · <a class="text-link underline" href="{{ route('admin.applications.board', ['year' => $year]) }}">open the board</a></p>
            <ul class="mt-4 space-y-2.5">
                @foreach ($pipeline as $p)
                    <li>
                        <a href="{{ route('admin.applications.index', ['status' => $p['status']->value, 'year' => $year]) }}" class="group grid grid-cols-[12.5rem_minmax(0,1fr)_2.5rem] items-center gap-3 text-sm">
                            <x-status :status="$p['status']" class="justify-self-start" />
                            <span class="h-4"><span class="block h-full rounded-r bg-series" style="width: {{ $p['count'] / $pipeMax * 100 }}%; min-width: {{ $p['count'] ? '2px' : '0' }}"></span></span>
                            <span class="num text-right font-semibold">{{ $p['count'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <section class="a-card p-5" aria-labelledby="assess-title">
            <div class="flex items-center justify-between"><h2 id="assess-title" class="font-semibold">Next assessment sessions</h2><a class="text-sm font-medium text-link" href="{{ route('admin.assessments.index') }}">Manage</a></div>
            <ul class="mt-3 divide-y divide-line">
                @forelse ($nextAssessments as $s)
                    <li class="flex items-center justify-between gap-3 py-2.5"><span><span class="font-medium">{{ $s->starts_at->format('D j M, g:i a') }}</span><span class="block text-sm text-ink-3">{{ $s->gradesLabel() }} · {{ $s->location }}</span></span><span class="num whitespace-nowrap text-sm">{{ $s->bookings_count }} / {{ $s->capacity }} booked</span></li>
                @empty
                    <li class="py-3 text-ink-3">No sessions scheduled. Families who are ready can’t book until you add one.</li>
                @endforelse
            </ul>
        </section>
        <section class="a-card p-5" aria-labelledby="tour-title">
            <div class="flex items-center justify-between"><h2 id="tour-title" class="font-semibold">Upcoming campus tours</h2><a class="text-sm font-medium text-link" href="{{ route('admin.tours.index') }}">Manage</a></div>
            <ul class="mt-3 divide-y divide-line">
                @forelse ($nextTours as $s)
                    <li class="flex items-center justify-between gap-3 py-2.5"><span class="font-medium">{{ $s->starts_at->format('D j M, g:i a') }}</span><span class="num text-sm">{{ $s->bookings_count }} / {{ $s->capacity }} families</span></li>
                @empty
                    <li class="py-3 text-ink-3">No tour times published.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <section class="a-card mt-5 overflow-hidden" aria-labelledby="recent-title">
        <div class="flex items-center justify-between p-5 pb-3">
            <h2 id="recent-title" class="font-semibold">Latest submitted applications</h2>
            <a href="{{ route('admin.applications.index') }}" class="text-sm font-medium text-link">View all</a>
        </div>
        @include('admin.applications._table', ['applications' => $recent, 'sortable' => false])
    </section>

    @if ($teachers !== null && $teachers > 0)
        <p class="mt-5 text-sm text-ink-2"><x-icon name="briefcase" class="inline h-4 w-4" /> {{ $teachers }} new teacher {{ \Illuminate\Support\Str::plural('application', $teachers) }} waiting. <a class="text-link underline" href="{{ route('admin.teachers.index', ['status' => 'new']) }}">Review</a></p>
    @endif
</x-layouts.admin>
