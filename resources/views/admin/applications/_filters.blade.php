<form method="GET" class="a-card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_auto_auto_auto_auto]" role="search">
    <label class="sr-only" for="f-q">Search</label>
    <input id="f-q" name="q" value="{{ $filters['q'] }}" class="a-input" placeholder="Student, parent, email or reference">
    <label class="sr-only" for="f-grade">Grade</label>
    <select id="f-grade" name="grade" class="a-input"><option value="">All grades</option>@foreach (grades() as $k => $label)<option value="{{ $k }}" @selected($filters['grade'] === $k)>{{ $label }}</option>@endforeach</select>
    <label class="sr-only" for="f-year">Entry year</label>
    <select id="f-year" name="year" class="a-input"><option value="">All entry years</option>@foreach (entry_years() as $y)<option @selected($filters['year'] === $y)>{{ $y }}</option>@endforeach</select>
    @isset($withStatus)
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="docs" value="1" @checked($filters['docs']) class="h-4 w-4"> Documents to review</label>
    @endisset
    <button class="a-btn a-btn-primary">Filter</button>
</form>
