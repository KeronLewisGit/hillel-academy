@php
    $sortable ??= false;
    $sort ??= null;
    $dir ??= 'desc';
    $th = function (string $label, string $col) use ($sortable, $sort, $dir) {
        if (! $sortable) {
            return e($label);
        }
        $active = ($sort ?? null) === $col;
        $next = $active && ($dir ?? 'desc') === 'desc' ? 'asc' : 'desc';
        return '<a class="inline-flex items-center gap-1 hover:text-ink" href="'.e(request()->fullUrlWithQuery(['sort' => $col, 'dir' => $next, 'page' => null])).'"'.($active ? ' aria-sort="'.(($dir ?? 'desc') === 'asc' ? 'ascending' : 'descending').'"' : '').'>'.e($label).($active ? ($dir === 'asc' ? ' ↑' : ' ↓') : '').'</a>';
    };
@endphp
<div class="overflow-x-auto">
    <table class="data-table">
        <thead><tr>
            <th>{!! $th('Student', 'student_last_name') !!}</th>
            <th>{!! $th('Grade', 'grade') !!}</th>
            <th>{!! $th('Status', 'status') !!}</th>
            <th>Documents</th>
            <th>Parent / guardian</th>
            <th class="r">{!! $th('Submitted', 'submitted_at') !!}</th>
        </tr></thead>
        <tbody>
            @forelse ($applications as $a)
                @php($pending = $a->documents->where('status', 'pending')->count())
                @php($missing = count($a->missingDocuments()))
                <tr>
                    <td><a class="font-semibold hover:text-link" href="{{ route('admin.applications.show', $a) }}">{{ $a->student_name }}</a><span class="block text-xs text-ink-3">{{ $a->reference }}</span></td>
                    <td class="whitespace-nowrap">{{ $a->grade_label }}<span class="block text-xs text-ink-3">{{ $a->entry_year }}</span></td>
                    <td><x-status :status="$a->status" /></td>
                    <td class="whitespace-nowrap">
                        @if ($pending)<x-chip tone="attention" icon="file">{{ $pending }} to review</x-chip>
                        @elseif ($missing)<x-chip tone="neutral" icon="clock">{{ $missing }} missing</x-chip>
                        @else<x-chip tone="good" icon="check">Complete</x-chip>@endif
                    </td>
                    <td>{{ $a->guardian_name ?: $a->family?->name }}<span class="block text-xs text-ink-3">{{ $a->guardian_phone ? format_phone($a->guardian_phone) : $a->family?->email }}</span></td>
                    <td class="r whitespace-nowrap text-sm">{{ $a->submitted_at?->format('j M Y') ?? 'Not submitted' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-10 text-center text-ink-3">No applications match.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
