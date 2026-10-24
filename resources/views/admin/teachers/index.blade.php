<x-layouts.admin title="Teacher applicants">
    <x-slot:actions>
        <form method="GET" class="flex w-full gap-2 sm:w-auto"><label for="job" class="sr-only">Vacancy</label><select id="job" name="job" class="a-input w-full sm:w-auto" onchange="this.form.submit()"><option value="">All vacancies</option>@foreach ($jobs as $j)<option value="{{ $j->id }}" @selected($job === $j->id)>{{ $j->title }}</option>@endforeach</select><noscript><button class="a-btn a-btn-ghost">Show</button></noscript></form>
    </x-slot:actions>
    <nav class="tag-row mb-4" aria-label="Filter by status">
        <a class="tag" href="{{ request()->fullUrlWithQuery(['status' => null]) }}" @if(! $status) aria-current="true" @endif>All <span class="count">{{ $counts->sum() }}</span></a>
        @foreach (\App\Enums\TeacherApplicationStatus::cases() as $s)<a class="tag" href="{{ request()->fullUrlWithQuery(['status' => $s->value]) }}" @if($status === $s) aria-current="true" @endif>{{ $s->label() }} <span class="count">{{ $counts[$s->value] ?? 0 }}</span></a>@endforeach
    </nav>
    <section class="a-card overflow-hidden"><div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Applicant</th><th>Post</th><th>Subjects</th><th>From</th><th class="r">Years</th><th>Status</th><th class="r">Received</th></tr></thead><tbody>
        @forelse ($applications as $a)
            <tr><td><a class="font-semibold hover:text-link" href="{{ route('admin.teachers.show', $a) }}">{{ $a->full_name }}</a><span class="block text-xs text-ink-3">{{ $a->reference }}</span></td><td>{{ $a->jobPosting->title ?? 'General application' }}</td><td class="text-sm">{{ $a->subjects }}</td><td class="text-sm">{{ $a->country }}</td><td class="r">{{ $a->years_experience }}</td>
                <td><x-chip :tone="$a->status->tone()" icon="dot">{{ $a->status->label() }}</x-chip></td><td class="r text-sm">{{ $a->created_at->format('j M Y') }}</td></tr>
        @empty
            <tr><td colspan="7" class="py-10 text-center text-ink-3">No applications.</td></tr>
        @endforelse
    </tbody></table></div><div class="border-t border-line p-4">{{ $applications->links() }}</div></section>
</x-layouts.admin>
