<x-layouts.site title="My applications" :mobile-bar="false">
    <x-page-header title="Welcome, {{ \Illuminate\Support\Str::before($family->name, ' ') ?: $family->name }}" note="Family portal" lead="Every application you have started or sent to Hillel Academy is here." />
    @include('family._shell')
    <section class="wrap mt-10 grid gap-5 md:grid-cols-2">
        @foreach ($applications as $a)
            @php($missing = $a->isDraft() ? [] : $a->missingDocuments())
            <article class="panel tabbed flex flex-col p-6">
                <div class="flex flex-wrap items-center justify-between gap-2"><x-status :status="$a->status" :family="true" /><span class="num text-sm text-ink-3">{{ $a->reference }}</span></div>
                <h2 class="display-3 mt-3">{{ $a->student_name }}</h2>
                <p class="text-ink-2">{{ $a->grade_label }} · {{ $a->entry_year }}</p>
                @if ($a->isDraft())
                    <p class="mt-3 text-ink-2">Step {{ min($a->step, \App\Models\Application::STEPS) }} of {{ \App\Models\Application::STEPS }}: {{ \App\Models\Application::STEP_NAMES[min($a->step, \App\Models\Application::STEPS)] }}</p>
                    <a href="{{ route('family.applications.step', [$a, min($a->step, \App\Models\Application::STEPS)]) }}" class="btn btn-accent mt-5 self-start">Continue application<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                @else
                    @if ($missing)<p class="mt-3 flex items-center gap-2 text-ink-2"><x-icon name="upload" class="h-4 w-4" />{{ count($missing) }} {{ \Illuminate\Support\Str::plural('document', count($missing)) }} still needed</p>@endif
                    @if ($a->offerIsOpen())<p class="mt-3 flex items-center gap-2 font-semibold"><x-icon name="star" class="h-4 w-4" />Please reply to the offer by {{ $a->offer_expires_at?->format('j F Y') }}</p>@endif
                    @if ($a->canBookAssessment() && ! $a->assessment_slot_id)<p class="mt-3 flex items-center gap-2 font-semibold"><x-icon name="calendar" class="h-4 w-4" />Ready to book an entrance assessment</p>@endif
                    <a href="{{ route('family.applications.show', $a) }}" class="btn btn-primary mt-5 self-start">Open<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                @endif
            </article>
        @endforeach

        @if (setting('applications_open'))
            <form method="POST" action="{{ route('family.applications.store') }}" class="panel border-dashed p-6" x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">
                @csrf
                <h2 class="display-4">Applying for another child?</h2>
                <p class="mt-1 text-ink-2">We’ll copy your family details across so you only fill in what’s different.</p>
                <button type="button" class="btn btn-outline mt-4" x-show="!open" @click="open = true"><x-icon name="plus" class="h-4 w-4" />Add a child</button>
                <div class="mt-4 grid gap-4" x-show="open" x-cloak>
                    <div><label class="label" for="student_first_name">Child’s first name</label><input class="input" id="student_first_name" name="student_first_name" value="{{ old('student_first_name') }}" required><x-error for="student_first_name" /></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label" for="grade">Grade</label><select class="input" id="grade" name="grade" required><option value="">Choose</option>@foreach (grades() as $k => $label)<option value="{{ $k }}" @selected(old('grade') === $k)>{{ $label }}</option>@endforeach</select><x-error for="grade" /></div>
                        <div><label class="label" for="entry_year">School year</label><select class="input" id="entry_year" name="entry_year" required>@foreach (entry_years() as $y)<option @selected(old('entry_year') === $y)>{{ $y }}</option>@endforeach</select><x-error for="entry_year" /></div>
                    </div>
                    <button class="btn btn-accent self-start">Start application</button>
                </div>
            </form>
        @endif
    </section>
</x-layouts.site>
