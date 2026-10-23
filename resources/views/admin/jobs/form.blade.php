<x-layouts.admin :title="$job->exists ? 'Edit vacancy' : 'New vacancy'">
    <form method="POST" action="{{ $job->exists ? route('admin.jobs.update', $job) : route('admin.jobs.store') }}" class="a-card grid max-w-3xl gap-3 p-5">@csrf @if ($job->exists) @method('PUT') @endif
        <label class="text-sm font-medium" for="title">Job title</label><input id="title" name="title" class="a-input" value="{{ old('title', $job->title) }}" required maxlength="160">
        <div class="grid gap-3 sm:grid-cols-3">
            <div><label class="text-sm font-medium" for="section">Section</label><select id="section" name="section" class="a-input mt-1 w-full">@foreach (hillel('sections') as $k => $s)<option value="{{ $k }}" @selected(old('section', $job->section) === $k)>{{ $s['name'] }}</option>@endforeach<option value="whole" @selected(old('section', $job->section) === 'whole')>Whole school</option></select></div>
            <div><label class="text-sm font-medium" for="employment_type">Type</label><select id="employment_type" name="employment_type" class="a-input mt-1 w-full">@foreach (\App\Models\JobPosting::TYPES as $k => $l)<option value="{{ $k }}" @selected(old('employment_type', $job->employment_type) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label class="text-sm font-medium" for="status">Status</label><select id="status" name="status" class="a-input mt-1 w-full">@foreach (\App\Enums\JobStatus::cases() as $s)<option value="{{ $s->value }}" @selected(old('status', $job->status?->value) === $s->value)>{{ $s->label() }}</option>@endforeach</select></div>
        </div>
        <label class="text-sm font-medium" for="summary">One-line summary</label><input id="summary" name="summary" class="a-input" value="{{ old('summary', $job->summary) }}" required maxlength="400">
        <label class="text-sm font-medium" for="description">About the role</label><textarea id="description" name="description" rows="8" class="a-input" required>{{ old('description', $job->description) }}</textarea>
        <label class="text-sm font-medium" for="requirements">Requirements <span class="font-normal text-ink-3">(one per line)</span></label><textarea id="requirements" name="requirements" rows="5" class="a-input">{{ old('requirements', $job->requirements) }}</textarea>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="text-sm font-medium" for="start_date_text">Start</label><input id="start_date_text" name="start_date_text" class="a-input mt-1 w-full" value="{{ old('start_date_text', $job->start_date_text) }}" placeholder="e.g. August 2027"></div>
            <div><label class="text-sm font-medium" for="closes_on">Closing date</label><input id="closes_on" type="date" name="closes_on" class="a-input mt-1 w-full" value="{{ old('closes_on', $job->closes_on?->toDateString()) }}"></div>
        </div>
        <label class="flex items-center gap-2"><input type="checkbox" name="overseas_welcome" value="1" @checked(old('overseas_welcome', $job->overseas_welcome)) class="h-4 w-4"> Open to applicants from overseas</label>
        <div class="flex flex-wrap gap-2"><button class="a-btn a-btn-primary">Save</button><a class="a-btn a-btn-ghost" href="{{ route('admin.jobs.index') }}">Cancel</a></div>
    </form>
    @if ($job->exists)
        <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}" class="mt-4" onsubmit="return confirm('Delete this vacancy?')">@csrf @method('DELETE')<button class="a-btn a-btn-ghost text-[color:var(--critical-text)]"><x-icon name="trash" class="h-4 w-4" />Delete vacancy</button></form>
    @endif
</x-layouts.admin>
