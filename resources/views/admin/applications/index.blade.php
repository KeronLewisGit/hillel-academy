<x-layouts.admin title="Applications">
    <x-slot:actions>
        <a href="{{ route('admin.applications.board', request()->only('grade', 'year', 'q')) }}" class="a-btn a-btn-ghost"><x-icon name="columns" class="h-4 w-4" />Board</a>
        <a href="{{ route('admin.applications.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
    </x-slot:actions>

    @include('admin.applications._filters', ['withStatus' => true])

    <nav class="tag-row mb-4" aria-label="Filter by status">
        <a class="tag" href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}" @if(! $filters['status']) aria-current="true" @endif>All <span class="count">{{ $counts->sum() }}</span></a>
        @foreach (\App\Enums\AdmissionStatus::cases() as $s)
            <a class="tag" href="{{ request()->fullUrlWithQuery(['status' => $s->value, 'page' => null]) }}" @if($filters['status'] === $s->value) aria-current="true" @endif>{{ $s->label() }} <span class="count">{{ $counts[$s->value] ?? 0 }}</span></a>
        @endforeach
    </nav>

    <section class="a-card overflow-hidden">
        @include('admin.applications._table', ['sortable' => true])
        <div class="border-t border-line p-4">{{ $applications->links() }}</div>
    </section>
</x-layouts.admin>
