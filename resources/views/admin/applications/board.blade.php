<x-layouts.admin title="Pipeline board">
    <x-slot:actions>
        <a href="{{ route('admin.applications.index', request()->only('grade', 'year', 'q')) }}" class="a-btn a-btn-ghost"><x-icon name="list" class="h-4 w-4" />List</a>
    </x-slot:actions>
    @include('admin.applications._filters')
    <div x-data="pipeline" class="relative">
        <div x-show="toast" x-cloak x-transition role="status" class="fixed bottom-6 left-1/2 z-50 max-w-[90vw] -translate-x-1/2 rounded-lg bg-ink px-4 py-2.5 text-sm font-medium text-bg shadow-xl" x-text="toast"></div>
        <p class="mb-4 text-sm text-ink-2">Drag a card to another column to move it. Offers check the grade’s capacity first. Not offered or withdrawn: <a class="text-link underline" href="{{ route('admin.applications.index', ['status' => 'declined']) }}">{{ $closed }}</a> · Started, not submitted: <a class="text-link underline" href="{{ route('admin.applications.index', ['status' => 'draft']) }}">{{ $drafts }}</a></p>
        <div class="flex gap-3 overflow-x-auto pb-4">
            @foreach ($columns as $stage => $apps)
                @php($status = \App\Enums\AdmissionStatus::from($stage))
                <section class="kanban-col" data-stage="{{ $stage }}" :class="over === '{{ $stage }}' && 'drag-over'" @dragover.prevent="over = '{{ $stage }}'" @dragleave.self="over = null" @drop.prevent="drop('{{ $stage }}')" aria-label="{{ $status->label() }}">
                    <header class="flex items-center justify-between px-1.5 py-1.5"><h2 class="text-sm font-semibold"><x-status :status="$status" class="!bg-transparent !px-0" /></h2><span class="num rounded-full bg-surface px-2 py-0.5 text-xs font-bold" data-count>{{ $apps->count() }}</span></header>
                    <div class="flex min-h-[55vh] flex-col gap-2" data-list>
                        <p class="rounded-lg border border-dashed border-line-strong p-4 text-center text-xs text-ink-3 {{ $apps->count() ? 'hidden' : '' }}" data-empty>Drop applications here</p>
                        @foreach ($apps as $a)
                            @php($pending = $a->documents->where('status', 'pending')->count())
                            <article id="app-{{ $a->id }}" class="kanban-card" draggable="true" data-url="{{ route('admin.applications.status', $a) }}" data-name="{{ $a->student_name }}" @dragstart="start($event, {{ $a->id }})" @dragend="end()" :class="dragging === {{ $a->id }} && 'opacity-40'">
                                <a href="{{ route('admin.applications.show', $a) }}" class="font-semibold hover:text-link">{{ $a->student_name }}</a>
                                <p class="text-xs text-ink-3">{{ $a->grade_label }} · {{ $a->entry_year }} · {{ $a->reference }}</p>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @if ($pending)<x-chip tone="attention" icon="file" class="!text-[0.6875rem]">{{ $pending }} to review</x-chip>@endif
                                    @if ($a->assessmentSlot && $stage === 'assessment_booked')<x-chip tone="neutral" icon="calendar" class="!text-[0.6875rem]">{{ $a->assessmentSlot->starts_at->format('D j M') }}</x-chip>@endif
                                    @if ($stage === 'offered' && $a->offer_expires_at)<x-chip :tone="$a->offer_expires_at->lt(now()->addDays(3)) ? 'attention' : 'neutral'" icon="clock" class="!text-[0.6875rem]">Reply by {{ $a->offer_expires_at->format('j M') }}</x-chip>@endif
                                    @if ($stage === 'waitlisted')<x-chip tone="neutral" icon="clock" class="!text-[0.6875rem]">Since {{ $a->waitlisted_at?->format('j M') }}</x-chip>@endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
