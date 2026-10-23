<x-layouts.admin title="Vacancies">
    <x-slot:actions><a href="{{ route('admin.jobs.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />New vacancy</a></x-slot:actions>
    <section class="a-card overflow-hidden"><div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Post</th><th>Section</th><th>Status</th><th class="r">Applicants</th><th></th></tr></thead><tbody>
        @forelse ($jobs as $j)
            <tr><td class="font-semibold">{{ $j->title }}<span class="block text-xs font-normal text-ink-3">{{ $j->typeLabel() }}@if ($j->closes_on) · closes {{ $j->closes_on->format('j M Y') }}@endif</span></td><td>{{ $j->sectionLabel() }}</td>
                <td><x-chip :tone="$j->isOpen() ? 'good' : 'muted'" :icon="$j->isOpen() ? 'check' : 'dot'">{{ $j->isOpen() ? 'Open' : $j->status->label() }}</x-chip></td>
                <td class="r"><a class="text-link underline" href="{{ route('admin.teachers.index', ['job' => $j->id]) }}">{{ $j->applications_count }}</a></td>
                <td class="r"><a class="a-btn a-btn-ghost" href="{{ route('admin.jobs.edit', $j) }}">Edit</a></td></tr>
        @empty
            <tr><td colspan="5" class="py-10 text-center text-ink-3">No vacancies. The careers page still accepts general applications.</td></tr>
        @endforelse
    </tbody></table></div></section>
</x-layouts.admin>
