<x-layouts.admin title="Enquiries">
    <nav class="tag-row mb-4" aria-label="Filter">
        <a class="tag" href="{{ route('admin.enquiries.index', ['show' => $show]) }}" @if(! $kind) aria-current="true" @endif>All open <span class="count">{{ $counts->sum() }}</span></a>
        @foreach (\App\Enums\EnquiryKind::cases() as $k)
            <a class="tag" href="{{ route('admin.enquiries.index', ['kind' => $k->value, 'show' => $show]) }}" @if($kind === $k) aria-current="true" @endif>{{ $k->label() }} <span class="count">{{ $counts[$k->value] ?? 0 }}</span></a>
        @endforeach
        <a class="tag" href="{{ request()->fullUrlWithQuery(['show' => $show === 'all' ? 'open' : 'all']) }}">{{ $show === 'all' ? 'Hide handled' : 'Include handled' }}</a>
    </nav>
    <div class="grid gap-4">
        @forelse ($enquiries as $e)
            <article class="a-card p-5 {{ $e->handled_at ? 'opacity-70' : '' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="flex flex-wrap items-center gap-2"><x-chip :tone="$e->handled_at ? 'muted' : 'attention'" :icon="$e->kind === \App\Enums\EnquiryKind::Tour ? 'map-pin' : 'inbox'">{{ $e->kind->label() }}</x-chip><span class="text-sm text-ink-3">{{ $e->created_at->format('j M Y, g:i a') }}</span></p>
                        <h2 class="mt-2 font-semibold">{{ $e->name }}</h2>
                        <p class="text-sm"><a class="text-link" href="mailto:{{ $e->email }}">{{ $e->email }}</a>@if ($e->phone) · <a class="text-link" href="{{ tel_href($e->phone) }}">{{ format_phone($e->phone) }}</a>@endif</p>
                        @if ($e->child_name || $e->grade)<p class="mt-1 text-sm text-ink-2">Child: {{ $e->child_name ?: 'not given' }}@if ($e->grade) · {{ grade_label($e->grade) }} {{ $e->entry_year }}@endif</p>@endif
                        @if ($e->tourSlot)<p class="mt-1 text-sm text-ink-2">Tour: {{ $e->tourSlot->starts_at->format('D j M, g:i a') }} · {{ $e->party_size }} {{ \Illuminate\Support\Str::plural('person', $e->party_size) }}</p>@endif
                        @if ($e->message)<p class="measure mt-3 whitespace-pre-line">{{ $e->message }}</p>@endif
                    </div>
                    <form method="POST" action="{{ route('admin.enquiries.update', $e) }}">@csrf @method('PATCH')<input type="hidden" name="handled" value="{{ $e->handled_at ? 0 : 1 }}"><button class="a-btn {{ $e->handled_at ? 'a-btn-ghost' : 'a-btn-primary' }}">{{ $e->handled_at ? 'Reopen' : 'Mark handled' }}</button></form>
                </div>
            </article>
        @empty
            <p class="a-card p-6 text-ink-3">Nothing waiting.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $enquiries->links() }}</div>
</x-layouts.admin>
