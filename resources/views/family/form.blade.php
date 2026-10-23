@php
    use App\Http\Controllers\Family\ApplicationFormController as Form;
    $a = $application;
    $names = \App\Models\Application::STEP_NAMES;
@endphp
<x-layouts.site :title="'Application: '.$names[$step]" :mobile-bar="false">
    <x-page-header :title="'Application for '.$a->student_first_name" note="Online application · {{ $a->reference }}" :lead="$a->grade_label.' · '.$a->entry_year.'. Your answers are saved each time you continue.'" />
    @include('family._shell')

    <div class="wrap mt-10 max-w-4xl">
        <ol class="steps" aria-label="Application steps">
            @foreach ($names as $n => $name)
                @php($state = $n === $step ? 'current' : ($n < $a->step ? 'done' : 'todo'))
                <li data-state="{{ $state }}" @if($state === 'current') aria-current="step" @endif>
                    @if ($state === 'done' || ($state === 'todo' && $n <= $a->step))<a href="{{ route('family.applications.step', [$a, $n]) }}">@endif
                    <span class="n">{{ $n }}</span><span class="hidden sm:inline">{{ $name }}</span><span class="sr-only sm:hidden">{{ $name }}</span>@if ($state === 'done')<span class="sr-only"> (done)</span>@endif
                    @if ($state === 'done' || ($state === 'todo' && $n <= $a->step))</a>@endif
                </li>
            @endforeach
        </ol>

        <div class="panel step-panel rounded-t-none p-5 sm:p-8">
            <h2 class="display-3">{{ $names[$step] }}</h2>

            @if ($step === 4)
                {{-- Uploads post on their own, so each file is saved the moment it is chosen. --}}
                <p class="measure mt-2 text-ink-2">Upload a clear scan or phone photo of each document (PDF, JPG or PNG, up to 8 MB). You can also send them later from the portal, but the school can only book an entrance assessment once the required documents are accepted.</p>
                <ul class="mt-6 grid gap-4">
                    @foreach (\App\Enums\DocumentType::cases() as $type)
                        @php($files = $a->documents->where('type', $type))
                        <li class="rounded-lg border border-line p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2"><p class="font-semibold">{{ $type->label() }} @unless ($type->requiredFor($a->grade))<span class="opt font-medium text-ink-3">(optional)</span>@endunless</p>
                                @if ($files->isNotEmpty())<x-chip tone="good" icon="check">Uploaded</x-chip>@endif</div>
                            <p class="hint !mt-1">{{ $type->hint() }}</p>
                            @foreach ($files as $doc)
                                <p class="mt-2 flex flex-wrap items-center gap-2 text-sm"><x-icon name="file" class="h-4 w-4" /><a class="text-link underline" href="{{ route('family.documents.show', [$a, $doc]) }}">{{ $doc->original_name }}</a> <span class="text-ink-3">{{ $doc->humanSize() }}</span>
                                    @if ($doc->status === 'pending')<form method="POST" action="{{ route('family.documents.destroy', [$a, $doc]) }}" class="inline">@csrf @method('DELETE')<button class="inline-flex min-h-11 items-center gap-1 px-2 text-[color:var(--critical-text)] underline">Remove</button></form>@endif</p>
                            @endforeach
                            <form method="POST" action="{{ route('family.documents.store', $a) }}" enctype="multipart/form-data" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">@csrf
                                <input type="hidden" name="type" value="{{ $type->value }}">
                                <label class="sr-only" for="file-{{ $type->value }}">{{ $type->label() }} file</label>
                                <input class="input sm:flex-1" id="file-{{ $type->value }}" type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                                <button class="btn btn-outline btn-sm" data-once><x-icon name="upload" class="h-4 w-4" />Upload</button>
                            </form>
                            @if (old('type') === $type->value)<x-error for="file" />@endif
                        </li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('family.applications.step.save', [$a, 4]) }}" class="mt-8 flex flex-col-reverse gap-3 border-t border-line pt-6 sm:flex-row sm:justify-between">@csrf
                    <a class="btn btn-outline" href="{{ route('family.applications.step', [$a, 3]) }}"><x-icon name="arrow-left" class="h-4 w-4" />Back</a>
                    <button class="btn btn-accent">Continue to review<x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
                </form>

            @elseif ($step === 5)
                @if ($missing)
                    <div class="mt-4 rounded-lg bg-accent-soft p-4" role="alert"><p class="font-semibold">A few required answers are still blank</p>
                        <ul class="mt-1 list-disc pl-5">@foreach ($missing as $label => $s)<li><a class="text-link underline" href="{{ route('family.applications.step', [$a, $s]) }}">{{ ucfirst($label) }}</a> (step {{ $s }})</li>@endforeach</ul></div>
                @endif
                <dl class="mt-6 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                    @foreach (array_filter(['Student' => $a->student_name, 'Date of birth' => $a->date_of_birth?->format('j F Y'), 'Nationality' => $a->nationality, 'Grade' => $a->grade_label.' · '.$a->entry_year, 'Start' => Form::TERMS[$a->entry_term] ?? null, 'Parent / guardian' => $a->guardian_name, 'Email' => $a->guardian_email, 'Phone' => $a->guardian_phone, 'Current school' => $a->previous_school, 'Second contact' => $a->guardian2_name]) as $k => $v)
                        <div><dt class="text-sm text-ink-3">{{ $k }}</dt><dd class="font-semibold break-words">{{ $v }}</dd></div>
                    @endforeach
                    <div><dt class="text-sm text-ink-3">Documents</dt><dd class="font-semibold">{{ $a->documents->count() }} uploaded @if ($left = count($a->missingDocuments())) · {{ $left }} required still to send @endif</dd></div>
                </dl>
                <p class="mt-4 text-sm"><a class="text-link underline" href="{{ route('family.applications.step', [$a, 1]) }}">Change answers</a></p>
                <form method="POST" action="{{ route('family.applications.submit', $a) }}" class="mt-8 border-t border-line pt-6">@csrf
                    <label class="choice"><input type="checkbox" name="declaration" value="1" required @error('declaration') aria-invalid="true" aria-describedby="declaration-error" @enderror><span>I confirm the information is correct, I am the child’s parent or legal guardian, and I have read the <a class="text-link underline" href="{{ route('privacy') }}" target="_blank">privacy notice</a>.</span></label>
                    <x-error for="declaration" />
                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                        <a class="btn btn-outline" href="{{ route('family.applications.step', [$a, 4]) }}"><x-icon name="arrow-left" class="h-4 w-4" />Back</a>
                        <button class="btn btn-accent" data-once>Submit application<x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
                    </div>
                </form>

            @else
                <form method="POST" action="{{ route('family.applications.step.save', [$a, $step]) }}" class="mt-6 grid gap-5" novalidate>
                    @csrf
                    @if ($errors->any())<p class="rounded-lg bg-accent-soft p-4 font-semibold" role="alert">Please check the highlighted answers.</p>@endif
                    @if ($step === 1)
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field name="student_first_name" label="First name" :value="$a->student_first_name" required autocomplete="off" />
                            <x-field name="student_last_name" label="Last name" :value="$a->student_last_name" required autocomplete="off" />
                        </div>
                        <x-field name="student_preferred_name" label="Name your child likes to be called" :value="$a->student_preferred_name" />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field name="date_of_birth" label="Date of birth" type="date" :value="$a->date_of_birth?->toDateString()" required />
                            <x-field name="gender" label="Gender" :options="['female' => 'Female', 'male' => 'Male', 'other' => 'Prefer to describe in person']" :value="$a->gender" />
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field name="nationality" label="Nationality" :value="$a->nationality" required hint="List all, for example Jamaican and Canadian." />
                            <x-field name="first_language" label="First language" :value="$a->first_language" />
                        </div>
                        <div class="grid gap-5 sm:grid-cols-3">
                            <x-field name="grade" label="Grade applying for" :options="grades()" :value="$a->grade" required />
                            <x-field name="entry_year" label="School year" :options="array_combine(entry_years(), entry_years())" :value="$a->entry_year" required />
                            <x-field name="entry_term" label="Start" :options="Form::TERMS" :value="$a->entry_term" required />
                        </div>
                    @elseif ($step === 2)
                        <x-field name="guardian_name" label="Parent or guardian’s full name" :value="$a->guardian_name" required autocomplete="name" />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field name="guardian_relationship" label="Relationship to the child" :options="Form::RELATIONSHIPS" :value="$a->guardian_relationship" required />
                            <x-field name="guardian_occupation" label="Occupation" :value="$a->guardian_occupation" autocomplete="organization-title" />
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field name="guardian_email" label="Email" type="email" :value="$a->guardian_email ?: auth('family')->user()->email" required autocomplete="email" />
                            <x-field name="guardian_phone" label="Mobile phone" type="tel" :value="$a->guardian_phone" required autocomplete="tel" hint="Include the area code, e.g. 876 555 0123." />
                        </div>
                        <x-field name="guardian_address" label="Home address" type="textarea" :rows="3" :value="$a->guardian_address" required />
                        <fieldset class="grid gap-5 rounded-lg border border-line p-4 sm:p-5">
                            <legend class="px-1 font-semibold">Second parent or emergency contact <span class="opt font-medium text-ink-3">(optional)</span></legend>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-field name="guardian2_name" label="Full name" :value="$a->guardian2_name" :optional="false" />
                                <x-field name="guardian2_relationship" label="Relationship" :options="Form::RELATIONSHIPS" :value="$a->guardian2_relationship" :optional="false" />
                                <x-field name="guardian2_email" label="Email" type="email" :value="$a->guardian2_email" :optional="false" />
                                <x-field name="guardian2_phone" label="Phone" type="tel" :value="$a->guardian2_phone" :optional="false" />
                            </div>
                        </fieldset>
                    @elseif ($step === 3)
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field name="previous_school" label="Current or most recent school" :value="$a->previous_school" />
                            <x-field name="previous_school_country" label="Country of that school" :value="$a->previous_school_country" />
                        </div>
                        <x-field name="current_grade" label="Current grade or year there" :value="$a->current_grade" hint="As that school names it, for example “Year 5” or “Grade 4”." />
                        <x-field name="support_needs" label="Anything that helps us support your child" type="textarea" :value="$a->support_needs" hint="Learning support, allergies or medical needs. Only the Admissions Office can see this." />
                        <fieldset><legend class="label">Does your child have a brother or sister at Hillel now? <span class="opt">(optional)</span></legend>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <label class="choice"><input type="radio" name="sibling_at_hillel" value="1" @checked(old('sibling_at_hillel', $a->sibling_at_hillel) === true || old('sibling_at_hillel') === '1')> Yes</label>
                                <label class="choice"><input type="radio" name="sibling_at_hillel" value="0" @checked(old('sibling_at_hillel', $a->sibling_at_hillel) === false || old('sibling_at_hillel') === '0')> No</label>
                            </div>
                        </fieldset>
                        <x-field name="heard_about" label="How did you hear about Hillel?" :value="$a->heard_about" />
                    @endif

                    {{-- The primary button comes first in the markup so pressing Enter continues. --}}
                    <div class="mt-3 flex flex-col gap-3 border-t border-line pt-6 sm:flex-row-reverse sm:items-center sm:justify-between">
                        <button class="btn btn-accent" name="action" value="next">Save and continue<x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
                        <div class="flex flex-col gap-3 sm:flex-row">
                            @if ($step > 1)<a class="btn btn-outline" href="{{ route('family.applications.step', [$a, $step - 1]) }}"><x-icon name="arrow-left" class="h-4 w-4" />Back</a>@endif
                            <button class="btn btn-outline" name="action" value="later" formnovalidate>Save and finish later</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
        <p class="mt-4 text-sm text-ink-3">Need help? Call Admissions on <a class="text-link underline" href="{{ tel_href(setting('phone_admissions')) }}">{{ format_phone(setting('phone_admissions')) }}</a> and quote {{ $a->reference }}.</p>
    </div>
</x-layouts.site>
