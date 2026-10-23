@php
    use App\Enums\AdmissionStatus as S;
    $a = $application;
    $at = $a->status->milestone();
    $missing = $a->missingDocuments();
@endphp
<x-layouts.site :title="$a->student_name.' · Application'" :mobile-bar="false">
    <x-page-header :title="$a->student_name" note="Application {{ $a->reference }}" :lead="$a->grade_label.' · '.$a->entry_year">
        <p class="mt-5"><x-status :status="$a->status" :family="true" class="!bg-white !text-ink" /></p>
    </x-page-header>
    @include('family._shell')

    @if (session('submitted'))
        <div class="wrap mt-8"><div class="panel tabbed p-6" role="status"><h2 class="display-3">Thank you. Your application is in.</h2><p class="measure mt-2 text-ink-2">Your reference is <strong class="num">{{ session('submitted') }}</strong>. We have emailed a confirmation. @if ($missing) Please send the remaining documents below; the Admissions Office checks them before inviting {{ $a->student_first_name }} to an entrance assessment.@endif</p></div></div>
    @endif

    <div class="wrap mt-10 grid gap-8 lg:grid-cols-[1fr_20rem]">
        <div class="grid content-start gap-8">
            {{-- What the family needs to do now comes first --}}
            @if ($a->offerIsOpen())
                <section class="panel tabbed p-6" aria-labelledby="offer-title">
                    <h2 id="offer-title" class="display-3">We are delighted to offer {{ $a->student_first_name }} a place</h2>
                    <p class="measure mt-2 text-ink-2">{{ $a->grade_label }} for {{ $a->entry_year }}. Please reply by <strong>{{ $a->offer_expires_at?->format('l j F Y') }}</strong>. If we don’t hear from you by then the place may be offered to another family.</p>
                    <form method="POST" action="{{ route('family.offer.accept', $a) }}" class="mt-5">@csrf
                        <label class="choice"><input type="checkbox" name="confirm" value="1" required @error('confirm') aria-invalid="true" aria-describedby="confirm-error" @enderror><span>I accept the offer of a place for {{ $a->student_first_name }} and understand the Admissions Office will contact me about fees and enrolment.</span></label>
                        <x-error for="confirm" />
                        <button class="btn btn-accent mt-4" data-once><x-icon name="check" class="h-4 w-4" />Accept the place</button>
                    </form>
                    <form method="POST" action="{{ route('family.offer.decline', $a) }}" class="mt-4" onsubmit="return confirm('Decline the place? This can’t be undone online.')">@csrf
                        <button class="inline-flex min-h-11 items-center text-ink-2 underline">Decline the place</button>
                    </form>
                    <x-error for="offer" />
                </section>
            @elseif ($a->status === S::Offered)
                <section class="panel p-6"><h2 class="display-4">The offer has expired</h2><p class="mt-2 text-ink-2">Please call Admissions on {{ format_phone(setting('phone_admissions')) }} if you would still like the place.</p></section>
            @elseif ($a->status === S::Accepted)
                <section class="panel tabbed p-6"><h2 class="display-3">You have accepted the place</h2><p class="measure mt-2 text-ink-2">The Admissions Office will contact you about fees, uniform and the first day. We look forward to welcoming {{ $a->student_first_name }}.</p></section>
            @elseif ($a->status === S::Waitlisted)
                <section class="panel p-6"><h2 class="display-3">{{ $a->student_first_name }} is on the waiting list</h2><p class="measure mt-2 text-ink-2">{{ $a->grade_label }} is full for {{ $a->entry_year }} at the moment.@if ($position) {{ $a->student_first_name }} is number <strong>{{ $position }}</strong> on the list.@endif We will contact you as soon as a place becomes available.</p></section>
            @elseif ($a->status === S::Declined)
                <section class="panel p-6"><h2 class="display-3">We are unable to offer a place</h2><p class="measure mt-2 text-ink-2">Thank you for applying to Hillel Academy. We have emailed you about this decision. Please contact the Admissions Office if you would like to talk it through.</p></section>
            @endif

            @if ($a->canBookAssessment())
                <section class="panel p-6" aria-labelledby="assess-title">
                    <h2 id="assess-title" class="display-3">{{ $a->assessmentSlot ? 'Your entrance assessment' : 'Book an entrance assessment' }}</h2>
                    @if ($a->assessmentSlot)
                        <p class="mt-3 flex items-start gap-2 text-lg"><x-icon name="calendar" class="mt-1 h-5 w-5 flex-none" /><span><strong>{{ $a->assessmentSlot->starts_at->format('l j F Y, g:i a') }}</strong><br><span class="text-ink-2">{{ $a->assessmentSlot->location }}@if ($a->assessmentSlot->notes) · {{ $a->assessmentSlot->notes }}@endif</span></span></p>
                    @else
                        <p class="measure mt-2 text-ink-2">{{ $a->student_first_name }}’s documents are complete. Choose a time below; you’ll get an email confirmation.</p>
                    @endif
                    @if ($slots->isNotEmpty())
                        <form method="POST" action="{{ route('family.assessment.book', $a) }}" class="mt-5">@csrf
                            <fieldset><legend class="label">{{ $a->assessmentSlot ? 'Need a different time?' : 'Available times' }}</legend>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ($slots as $s)
                                        <label class="choice"><input type="radio" name="slot" value="{{ $s->id }}" @checked($s->id === $a->assessment_slot_id) required><span><span class="font-semibold">{{ $s->starts_at->format('D j M, g:i a') }}</span><span class="block text-sm text-ink-3">{{ $s->seatsLeft() }} {{ \Illuminate\Support\Str::plural('place', $s->seatsLeft()) }} left</span></span></label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <x-error for="slot" />
                            <button class="btn btn-primary mt-4" data-once>{{ $a->assessmentSlot ? 'Change my time' : 'Book this time' }}</button>
                        </form>
                    @elseif (! $a->assessmentSlot)
                        <p class="mt-3 rounded-lg bg-sky p-4">No assessment times are open right now. The Admissions Office adds sessions regularly and will email you; you can also call {{ format_phone(setting('phone_admissions')) }}.</p>
                    @endif
                </section>
            @endif

            <section class="panel p-6" aria-labelledby="docs-title">
                <h2 id="docs-title" class="display-3">Documents</h2>
                @if (! $missing)<p class="mt-2 flex items-center gap-2 text-ink-2"><x-icon name="check-circle" class="h-5 w-5 text-[color:var(--good)]" />Every required document has been received.</p>@endif
                <ul class="mt-5 grid gap-4">
                    @foreach (\App\Enums\DocumentType::cases() as $type)
                        @php($latest = $a->latestDocument($type))
                        @php($needs = in_array($type, $missing, true))
                        <li class="rounded-lg border {{ $needs ? 'border-[color:var(--accent)]' : 'border-line' }} p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold">{{ $type->label() }} @unless ($type->requiredFor($a->grade))<span class="opt font-medium text-ink-3">(optional)</span>@endunless</p>
                                @if ($latest)<x-chip :tone="$latest->tone()" :icon="$latest->status === 'accepted' ? 'check' : ($latest->status === 'rejected' ? 'alert' : 'clock')">{{ $latest->statusLabel() }}</x-chip>@elseif ($needs)<x-chip tone="attention" icon="upload">Needed</x-chip>@endif
                            </div>
                            @if ($latest)<p class="mt-1 text-sm text-ink-2"><a class="text-link underline" href="{{ route('family.documents.show', [$a, $latest]) }}">{{ $latest->original_name }}</a> · sent {{ $latest->created_at->format('j M Y') }}</p>@endif
                            @if ($latest?->status === 'rejected' && $latest->review_note)<p class="mt-2 rounded bg-accent-soft p-3 text-sm"><strong>From the Admissions Office:</strong> {{ $latest->review_note }}</p>@endif
                            @if (($needs || ! $latest) && $a->acceptsUploads())
                                <form method="POST" action="{{ route('family.documents.store', $a) }}" enctype="multipart/form-data" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">@csrf
                                    <input type="hidden" name="type" value="{{ $type->value }}">
                                    <label class="sr-only" for="file-{{ $type->value }}">{{ $type->label() }} file</label>
                                    <input class="input sm:flex-1" id="file-{{ $type->value }}" type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                                    <button class="btn btn-outline btn-sm" data-once><x-icon name="upload" class="h-4 w-4" />Upload</button>
                                </form>
                                @if (old('type') === $type->value)<x-error for="file" />@endif
                            @endif
                        </li>
                    @endforeach
                </ul>
                <p class="hint">PDF, JPG or PNG, up to 8 MB each.</p>
            </section>

            @if ($timeline->isNotEmpty())
                <section aria-labelledby="updates-title">
                    <h2 id="updates-title" class="display-3">Updates</h2>
                    <ol class="mt-4 grid gap-3">
                        @foreach ($timeline as $e)
                            <li class="panel p-4"><p class="font-semibold">{{ $e->title }}</p><p class="text-sm text-ink-3">{{ $e->created_at->format('j F Y, g:i a') }}</p>@if ($e->body && $e->type === 'message')<div class="measure mt-2 whitespace-pre-line text-ink-2">{{ $e->body }}</div>@elseif ($e->body && $e->type === 'document')<p class="mt-1 text-ink-2">{{ $e->body }}</p>@endif</li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>

        <aside aria-labelledby="track-title" class="order-first lg:order-none">
            <div class="panel p-6 lg:sticky lg:top-28">
                <h2 id="track-title" class="display-4">Progress</h2>
                <ol class="track mt-5">
                    @foreach (S::milestones() as $key => $label)
                        @php($i = $loop->index)
                        @php($state = $i < $at ? 'done' : ($i === $at ? 'current' : 'todo'))
                        <li data-state="{{ $state }}" @if($state === 'current') aria-current="step" @endif>
                            <span class="dot">@if ($state === 'done')<x-icon name="check" class="h-4 w-4" />@else<span class="text-sm font-bold">{{ $i + 1 }}</span>@endif</span>
                            <span class="pt-1.5"><span class="font-semibold">{{ $label }}</span>@if ($state === 'current')<span class="block text-sm text-ink-2">{{ $a->status->familyLabel() }}</span>@endif</span>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-2 border-t border-line pt-4 text-sm text-ink-2">Questions? <a class="text-link underline" href="{{ tel_href(setting('phone_admissions')) }}">{{ format_phone(setting('phone_admissions')) }}</a> or <a class="text-link underline" href="mailto:{{ setting('email_admissions') }}">email Admissions</a>, quoting {{ $a->reference }}.</p>
            </div>
        </aside>
    </div>
</x-layouts.site>
