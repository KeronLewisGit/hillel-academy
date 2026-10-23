@php
    use App\Enums\AdmissionStatus as S;
    $a = $application;
    $next = match ($a->status) {
        S::Submitted => [S::DocsComplete],
        S::DocsComplete => [S::AssessmentBooked],
        S::AssessmentBooked => [S::Assessed],
        S::Assessed => [S::Offered, S::Waitlisted, S::Declined],
        S::Waitlisted => [S::Offered, S::Declined],
        S::Offered => [S::Accepted],
        S::Accepted => [S::Enrolled],
        default => [],
    };
    $rows = fn (array $pairs) => array_filter($pairs, fn ($v) => $v !== null && $v !== '');
@endphp
<x-layouts.admin :title="$a->student_name">
    <x-slot:actions>
        <form method="POST" action="{{ route('admin.applications.invite', $a) }}">@csrf<button class="a-btn a-btn-ghost"><x-icon name="key" class="h-4 w-4" />Email portal link</button></form>
        <a href="{{ route('admin.applications.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />All applications</a>
    </x-slot:actions>

    <div class="mb-5 flex flex-wrap items-center gap-3 text-sm text-ink-2">
        <x-status :status="$a->status" />
        <span class="num">{{ $a->reference }}</span>
        <span>{{ $a->grade_label }} · {{ $a->entry_year }} · {{ \App\Http\Controllers\Family\ApplicationFormController::TERMS[$a->entry_term] ?? $a->entry_term }}</span>
        @if ($a->submitted_at)<span>Submitted {{ $a->submitted_at->format('j M Y') }}</span>@endif
        @if ($position)<x-chip tone="neutral" icon="clock">Waitlist position {{ $position }}</x-chip>@endif
    </div>

    <div class="grid gap-5 xl:grid-cols-[1.55fr_1fr]">
        <div class="grid content-start gap-5">
            {{-- Documents first: reviewing them is the job that unblocks everything else --}}
            <section id="documents" class="a-card p-5" aria-labelledby="docs-title">
                <h2 id="docs-title" class="font-semibold">Documents</h2>
                <ul class="mt-3 divide-y divide-line">
                    @foreach (\App\Enums\DocumentType::cases() as $type)
                        @php($files = $a->documents->where('type', $type))
                        <li class="py-3">
                            <p class="flex flex-wrap items-center justify-between gap-2"><span class="font-medium">{{ $type->label() }} @unless ($type->requiredFor($a->grade))<span class="text-sm font-normal text-ink-3">(optional)</span>@endunless</span>
                                @if ($files->isEmpty())<x-chip :tone="$type->requiredFor($a->grade) ? 'neutral' : 'muted'" icon="clock">Not uploaded</x-chip>@endif</p>
                            @foreach ($files as $doc)
                                <div class="mt-2 flex flex-wrap items-center gap-3 rounded-lg bg-surface-2 px-3 py-2 text-sm">
                                    <a class="font-medium text-link underline" href="{{ route('admin.documents.show', $doc) }}" target="_blank" rel="noopener">{{ $doc->original_name }}</a>
                                    <span class="text-ink-3">{{ $doc->humanSize() }} · {{ $doc->created_at->format('j M, g:i a') }}</span>
                                    <x-chip :tone="$doc->tone()" :icon="$doc->status === 'accepted' ? 'check' : ($doc->status === 'rejected' ? 'x' : 'clock')">{{ $doc->statusLabel() }}</x-chip>
                                    @if ($doc->status === 'pending')
                                        <span class="ml-auto flex flex-wrap gap-2" x-data="{ reject: false }">
                                            <form method="POST" action="{{ route('admin.documents.update', $doc) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="accepted"><button class="a-btn a-btn-primary">Accept</button></form>
                                            <button type="button" class="a-btn a-btn-ghost" @click="reject = !reject" :aria-expanded="reject.toString()">Return…</button>
                                            <form method="POST" action="{{ route('admin.documents.update', $doc) }}" class="flex w-full flex-wrap gap-2" x-show="reject" x-cloak>@csrf @method('PATCH')<input type="hidden" name="status" value="rejected">
                                                <label class="sr-only" for="note-{{ $doc->id }}">Reason</label><input id="note-{{ $doc->id }}" name="review_note" class="a-input flex-1" placeholder="Why it needs resending (the family sees this)" required>
                                                <button class="a-btn a-btn-ghost">Send back</button></form>
                                        </span>
                                    @elseif ($doc->review_note)
                                        <span class="w-full text-ink-2">“{{ $doc->review_note }}”</span>
                                    @endif
                                </div>
                            @endforeach
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="a-card p-5" aria-labelledby="student-title">
                <h2 id="student-title" class="font-semibold">Student</h2>
                <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                    @foreach ($rows(['Full name' => $a->student_name, 'Preferred name' => $a->student_preferred_name, 'Date of birth' => $a->date_of_birth?->format('j F Y').($a->date_of_birth ? ' (age '.$a->date_of_birth->age.')' : ''), 'Gender' => $a->gender ? ucfirst($a->gender) : null, 'Nationality' => $a->nationality, 'First language' => $a->first_language, 'Current / previous school' => $a->previous_school, 'Country of that school' => $a->previous_school_country, 'Current grade' => $a->current_grade, 'Sibling at Hillel' => $a->sibling_at_hillel === null ? null : ($a->sibling_at_hillel ? 'Yes' : 'No'), 'Heard about us' => $a->heard_about]) as $k => $v)
                        <div><dt class="text-sm text-ink-3">{{ $k }}</dt><dd class="font-medium">{{ $v }}</dd></div>
                    @endforeach
                </dl>
                @if ($a->support_needs)<div class="mt-4 rounded-lg bg-accent-soft p-4"><p class="text-sm font-semibold">Learning or medical needs shared by the family</p><p class="mt-1 whitespace-pre-line">{{ $a->support_needs }}</p></div>@endif
            </section>

            <section class="a-card p-5" aria-labelledby="family-title">
                <h2 id="family-title" class="font-semibold">Family</h2>
                <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                    @foreach ($rows(['Parent / guardian' => $a->guardian_name.($a->guardian_relationship ? ' ('.(\App\Http\Controllers\Family\ApplicationFormController::RELATIONSHIPS[$a->guardian_relationship] ?? $a->guardian_relationship).')' : ''), 'Email' => $a->guardian_email, 'Phone' => $a->guardian_phone ? format_phone($a->guardian_phone) : null, 'Occupation' => $a->guardian_occupation, 'Home address' => $a->guardian_address, 'Second contact' => $a->guardian2_name, 'Second contact phone' => $a->guardian2_phone, 'Second contact email' => $a->guardian2_email, 'Portal account' => $a->family->email.($a->family->last_login_at ? ' · last signed in '.$a->family->last_login_at->diffForHumans() : ' · never signed in')]) as $k => $v)
                        <div><dt class="text-sm text-ink-3">{{ $k }}</dt><dd class="font-medium break-words">{{ $v }}</dd></div>
                    @endforeach
                </dl>
                @if ($siblings->isNotEmpty())
                    <p class="mt-4 text-sm text-ink-2">Also from this family: @foreach ($siblings as $s)<a class="text-link underline" href="{{ route('admin.applications.show', $s) }}">{{ $s->student_name }}</a> ({{ $s->grade_label }}, {{ $s->status->label() }})@if (! $loop->last), @endif @endforeach</p>
                @endif
            </section>

            <section class="a-card p-5" aria-labelledby="timeline-title">
                <h2 id="timeline-title" class="font-semibold">Timeline</h2>
                <form method="POST" action="{{ route('admin.applications.note', $a) }}" class="mt-3 flex gap-2">@csrf
                    <label for="note" class="sr-only">Add a private note</label><input id="note" name="body" class="a-input flex-1" placeholder="Add a private note (staff only)" required><button class="a-btn a-btn-ghost">Add</button>
                </form>
                <ol class="mt-4 space-y-3">
                    @foreach ($a->events as $e)
                        <li class="flex gap-3 text-sm">
                            <span class="mt-1 h-2.5 w-2.5 flex-none rounded-full {{ $e->public ? 'bg-accent' : 'bg-[color:var(--muted-dot)]' }}" aria-hidden="true"></span>
                            <div><p><span class="font-semibold">{{ $e->title ?? ucfirst($e->type) }}</span> <span class="text-ink-3">· {{ $e->created_at->format('j M Y, g:i a') }} · {{ $e->user?->name ?? 'System' }}{{ $e->public ? ' · seen by family' : '' }}</span></p>@if ($e->body)<p class="mt-0.5 whitespace-pre-line text-ink-2">{{ \Illuminate\Support\Str::limit($e->body, 600) }}</p>@endif</div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <div class="grid content-start gap-5">
            <section class="a-card tabbed p-5" aria-labelledby="decision-title">
                <h2 id="decision-title" class="font-semibold">Next step</h2>
                <p class="mt-1 text-sm text-ink-2">{{ $a->grade_label }} {{ $a->entry_year }}: <strong>{{ $seatsLeft }}</strong> of {{ $seats }} {{ \Illuminate\Support\Str::plural('seat', $seats) }} free. <a class="text-link underline" href="{{ route('admin.capacity.index', ['year' => $a->entry_year]) }}">Capacity</a></p>
                @if ($a->status === S::Offered && $a->offer_expires_at)<p class="mt-1 text-sm">Offer open until <strong>{{ $a->offer_expires_at->format('j M Y') }}</strong>.</p>@endif
                @if ($next)
                    <form method="POST" action="{{ route('admin.applications.status', $a) }}" class="mt-4 grid gap-3">@csrf @method('PATCH')
                        <label for="decision-note" class="text-sm font-medium">Note for the timeline <span class="font-normal text-ink-3">(optional, staff only)</span></label>
                        <textarea id="decision-note" name="note" rows="2" class="a-input"></textarea>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($next as $s)
                                <button name="status" value="{{ $s->value }}" class="a-btn {{ $loop->first ? 'a-btn-primary' : 'a-btn-ghost' }}" @if($s === S::Offered && $seatsLeft < 1 && ! $a->status->holdsSeat()) disabled title="Grade is full" @endif><x-icon :name="$s->icon()" class="h-4 w-4" />{{ match ($s) { S::DocsComplete => 'Mark documents complete', S::AssessmentBooked => 'Mark assessment booked', S::Assessed => 'Mark assessed', S::Offered => 'Offer a place', S::Waitlisted => 'Waitlist', S::Declined => 'Don’t offer', S::Accepted => 'Record acceptance', S::Enrolled => 'Mark enrolled', default => $s->label() } }}</button>
                            @endforeach
                        </div>
                        @if (in_array(S::Offered, $next, true) && $seatsLeft < 1)<p class="text-sm text-ink-2">This grade is full. Waitlist the application, or raise capacity first.</p>@endif
                    </form>
                @endif
                @if (! in_array($a->status, [S::Draft, S::Withdrawn, S::Enrolled, S::Declined], true))
                    <details class="mt-4 text-sm"><summary class="cursor-pointer font-medium text-link">Other moves</summary>
                        <form method="POST" action="{{ route('admin.applications.status', $a) }}" class="mt-3 flex gap-2">@csrf @method('PATCH')
                            <label for="any-status" class="sr-only">Move to</label>
                            <select id="any-status" name="status" class="a-input flex-1">@foreach (S::cases() as $s)@if ($s !== S::Draft && $s !== $a->status)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endif @endforeach</select>
                            <button class="a-btn a-btn-ghost">Move</button>
                        </form>
                    </details>
                @endif
            </section>

            @if ($a->canBookAssessment() || $a->assessmentSlot)
                <section class="a-card p-5" aria-labelledby="assess-title">
                    <h2 id="assess-title" class="font-semibold">Entrance assessment</h2>
                    @if ($a->assessmentSlot)<p class="mt-2"><x-icon name="calendar" class="inline h-4 w-4" /> {{ $a->assessmentSlot->starts_at->format('l j F, g:i a') }} · {{ $a->assessmentSlot->location }}</p>@endif
                    @if ($a->canBookAssessment())
                        @if ($slots->isEmpty())
                            <p class="mt-2 text-sm text-ink-2">No open sessions for {{ $a->grade_label }}. <a class="text-link underline" href="{{ route('admin.assessments.index') }}">Add one</a>.</p>
                        @else
                            <form method="POST" action="{{ route('admin.applications.assessment', $a) }}" class="mt-3 flex gap-2">@csrf
                                <label for="slot" class="sr-only">Session</label>
                                <select id="slot" name="slot" class="a-input flex-1">@foreach ($slots as $s)<option value="{{ $s->id }}" @selected($s->id === $a->assessment_slot_id)>{{ $s->starts_at->format('D j M, g:i a') }} ({{ $s->seatsLeft() }} left)</option>@endforeach</select>
                                <button class="a-btn a-btn-ghost">{{ $a->assessmentSlot ? 'Move' : 'Book' }}</button>
                            </form>
                        @endif
                    @endif
                    <form method="POST" action="{{ route('admin.applications.update', $a) }}" class="mt-4">@csrf @method('PATCH')
                        <label for="assessment_notes" class="text-sm font-medium">Assessment notes <span class="font-normal text-ink-3">(staff only)</span></label>
                        <textarea id="assessment_notes" name="assessment_notes" rows="3" class="a-input mt-1 w-full">{{ $a->assessment_notes }}</textarea>
                        <button class="a-btn a-btn-ghost mt-2">Save notes</button>
                    </form>
                </section>
            @endif

            <section class="a-card p-5" aria-labelledby="msg-title" x-data="composer(@js($templates->map(fn ($t) => ['id' => $t->id, 'subject' => $t->subject, 'body' => $t->body])))">
                <h2 id="msg-title" class="font-semibold">Email the family</h2>
                <form method="POST" action="{{ route('admin.applications.message', $a) }}" class="mt-3 grid gap-3">@csrf
                    <label for="tpl" class="text-sm font-medium">Start from a template</label>
                    <select id="tpl" class="a-input" x-model="choice" @change="pick()"><option value="">Blank message</option>@foreach ($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
                    <label for="subject" class="text-sm font-medium">Subject</label>
                    <input id="subject" name="subject" class="a-input" x-model="subject" required>
                    <label for="body" class="text-sm font-medium">Message</label>
                    <textarea id="body" name="body" rows="7" class="a-input" x-model="body" required></textarea>
                    <p class="text-xs text-ink-3">Placeholders such as {student_first_name} and {portal_link} are filled in when sent. The message also appears in the family’s portal.</p>
                    <button class="a-btn a-btn-primary justify-self-start" data-once><x-icon name="mail" class="h-4 w-4" />Send email</button>
                </form>
            </section>

            <section class="a-card p-5" aria-labelledby="staff-title">
                <h2 id="staff-title" class="font-semibold">Owner and notes</h2>
                <form method="POST" action="{{ route('admin.applications.update', $a) }}" class="mt-3 grid gap-3">@csrf @method('PATCH')
                    <label for="assigned_to" class="text-sm font-medium">Handled by</label>
                    <select id="assigned_to" name="assigned_to" class="a-input"><option value="">Unassigned</option>@foreach ($staff as $u)<option value="{{ $u->id }}" @selected($a->assigned_to === $u->id)>{{ $u->name }}</option>@endforeach</select>
                    <label for="staff_notes" class="text-sm font-medium">Notes <span class="font-normal text-ink-3">(staff only)</span></label>
                    <textarea id="staff_notes" name="staff_notes" rows="4" class="a-input">{{ $a->staff_notes }}</textarea>
                    <button class="a-btn a-btn-ghost justify-self-start">Save</button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.admin>
