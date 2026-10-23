<x-layouts.admin :title="$a->full_name">
    <x-slot:actions><a href="{{ route('admin.teachers.cv', $a) }}" class="a-btn a-btn-primary"><x-icon name="download" class="h-4 w-4" />Download CV</a><a href="{{ route('admin.teachers.index') }}" class="a-btn a-btn-ghost">All applicants</a></x-slot:actions>
    <div class="grid gap-5 xl:grid-cols-[1.5fr_1fr]">
        <section class="a-card p-5">
            <p class="text-sm text-ink-3">{{ $a->reference }} · received {{ $a->created_at->format('j M Y') }}</p>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                @foreach (array_filter(['Post' => $a->jobPosting->title ?? 'General application', 'Email' => $a->email, 'Phone' => $a->phone, 'Country' => $a->country, 'Current position' => $a->current_position, 'Subjects' => $a->subjects, 'Teaching experience' => $a->years_experience.' years', 'Curricula taught' => collect($a->curricula ?? [])->map(fn ($c) => \App\Models\TeacherApplication::CURRICULA[$c] ?? $c)->join(', '), 'Available from' => $a->available_from?->format('j F Y'), 'CV file' => $a->cv_name]) as $k => $v)
                    <div><dt class="text-sm text-ink-3">{{ $k }}</dt><dd class="font-medium break-words">{{ $v }}</dd></div>
                @endforeach
            </dl>
            @if ($a->qualifications)<h2 class="mt-5 font-semibold">Qualifications</h2><p class="measure mt-1 whitespace-pre-line">{{ $a->qualifications }}</p>@endif
            @if ($a->cover_letter)<h2 class="mt-5 font-semibold">Cover letter</h2><p class="measure mt-1 whitespace-pre-line">{{ $a->cover_letter }}</p>@endif
        </section>
        <form method="POST" action="{{ route('admin.teachers.update', $a) }}" class="a-card grid content-start gap-3 p-5">@csrf @method('PATCH')
            <label class="text-sm font-medium" for="status">Status</label>
            <select id="status" name="status" class="a-input">@foreach (\App\Enums\TeacherApplicationStatus::cases() as $s)<option value="{{ $s->value }}" @selected($a->status === $s)>{{ $s->label() }}</option>@endforeach</select>
            <label class="text-sm font-medium" for="staff_notes">Notes</label><textarea id="staff_notes" name="staff_notes" rows="6" class="a-input">{{ $a->staff_notes }}</textarea>
            <button class="a-btn a-btn-primary justify-self-start">Save</button>
        </form>
    </div>
</x-layouts.admin>
