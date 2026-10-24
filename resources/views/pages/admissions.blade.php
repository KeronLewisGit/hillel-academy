@php
    $bySection = collect(hillel('grades'))->groupBy('section', true);
@endphp
<x-layouts.site title="Admissions" description="How to apply to Hillel Academy, Kingston: entry by grade, the application steps, documents, tuition fees and key dates.">
    <x-page-header title="Admissions" note="Joining Hillel" lead="Families join Hillel from Jamaica and from more than 40 countries, at every grade from K1 to Grade 13. Here is how it works, what it costs and what to have ready." photo="{{ hillel('admissions_photo') }}">
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('apply') }}" class="btn btn-accent">Start an application<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            <a href="{{ route('visit') }}" class="btn btn-on-dark">Book a visit</a>
        </div>
    </x-page-header>

    <nav class="border-b border-line bg-surface" aria-label="On this page">
        <div class="wrap"><div class="tag-row py-3">
            <a class="tag" href="#process">How to apply</a><a class="tag" href="#assessment">Assessments</a><a class="tag" href="#entry">Entry by grade</a><a class="tag" href="#documents">Documents</a><a class="tag" href="#fees">Fees</a><a class="tag" href="#dates">Key dates</a>
        </div></div>
    </nav>

    <section id="process" class="wrap mt-16 md:mt-20" aria-labelledby="process-title">
        <x-margin-note>Step by step</x-margin-note>
        <h2 id="process-title" class="display-2 mt-3">How to apply</h2>
        <ol class="mt-10 grid gap-x-6 gap-y-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach (hillel('process') as $step)
                <li class="relative pl-14">
                    <span class="font-display absolute left-0 top-0 grid h-10 w-10 place-items-center rounded-full bg-ink-900 text-lg text-white" aria-hidden="true">{{ $loop->iteration }}</span>
                    <h3 class="display-4">{{ $step['title'] }}</h3>
                    <p class="mt-1.5 text-ink-2">{{ $step['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <section id="assessment" class="wrap mt-16" aria-labelledby="assess-title">
        <h3 id="assess-title" class="display-3">What the assessment involves</h3>
        <dl class="mt-6 grid gap-4 md:grid-cols-2">
            @foreach (hillel('assessment_by_grade') as $row)
                <div class="panel p-5"><dt class="font-semibold">{{ $row['grades'] }}</dt><dd class="mt-1 text-ink-2">{{ $row['text'] }}</dd></div>
            @endforeach
        </dl>
    </section>

    <section id="entry" class="ruled ruled-margin mt-20 bg-sky py-16 md:py-20" aria-labelledby="entry-title">
        <div class="wrap sm:pl-16">
            <x-margin-note>Entry by grade</x-margin-note>
            <h2 id="entry-title" class="display-2 mt-3">Which grade?</h2>
            <p class="lead mt-4 text-ink-2">The Prep School takes children aged 3 to 11 and the High School students aged 11 to 19. A guide to where children usually start:</p>
            <div class="mt-8 overflow-x-auto rounded-xl border border-line bg-surface">
                <table class="w-full text-left">
                    <caption class="sr-only">Grades, school sections and typical ages</caption>
                    <thead class="bg-surface-2 text-sm text-ink-2"><tr><th scope="col" class="px-3 py-3 sm:px-5 font-semibold">Section</th><th scope="col" class="px-3 py-3 sm:px-5 font-semibold">Grade</th><th scope="col" class="px-3 py-3 sm:px-5 font-semibold">Age by 31 August of the year they start</th></tr></thead>
                    <tbody>
                        @foreach ($bySection as $section => $grades)
                            @foreach ($grades as $key => $g)
                                <tr class="border-t border-line">
                                    @if ($loop->first)<th scope="rowgroup" rowspan="{{ count($grades) }}" class="px-3 py-3 sm:px-5 align-top font-display text-lg font-normal">{{ hillel('sections.'.$section.'.name') }}</th>@endif
                                    <td class="whitespace-nowrap px-3 py-3 font-semibold sm:px-5">{{ $g['label'] }}</td>
                                    <td class="num px-3 py-3 sm:px-5 text-ink-2">{{ $g['age'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-sm text-ink-3">{{ hillel('age_note') }}</p>
        </div>
    </section>

    <section id="documents" class="wrap mt-20 grid gap-10 lg:grid-cols-[1fr_1.2fr]" aria-labelledby="docs-title">
        <div>
            <x-margin-note>Have these ready</x-margin-note>
            <h2 id="docs-title" class="display-2 mt-3">Documents</h2>
            <p class="lead mt-4 text-ink-2">Upload scans or clear phone photos as part of the online application, or later from the family portal.</p>
        </div>
        <ul class="grid gap-3 sm:grid-cols-2">
            @foreach (\App\Enums\DocumentType::cases() as $type)
                <li class="panel p-5"><p class="flex items-center gap-2 font-semibold"><x-icon name="file" class="h-5 w-5 text-ink-3" />{{ $type->label() }}</p><p class="mt-1 text-sm text-ink-2">{{ $type->hint() }}@if ($type === \App\Enums\DocumentType::SchoolReports) Not needed for K1 and K2.@endif</p></li>
            @endforeach
        </ul>
    </section>

    <section id="fees" class="wrap mt-20" aria-labelledby="fees-title">
        <x-margin-note>Tuition</x-margin-note>
        <h2 id="fees-title" class="display-2 mt-3">Fees for {{ setting('fees_year') }}</h2>
        @if ($fees->isEmpty())
            <p class="panel mt-6 p-6 text-ink-2">The fee schedule is available from the Admissions Office on {{ format_phone(setting('phone_admissions')) }}.</p>
        @else
            <div class="mt-8 overflow-x-auto rounded-xl border border-line bg-surface">
                <table class="w-full text-left">
                    <caption class="sr-only">Tuition per term, as published for {{ setting('fees_year') }}</caption>
                    <thead class="bg-surface-2 text-sm text-ink-2"><tr><th scope="col" class="px-5 py-3 font-semibold">Grade</th><th scope="col" class="px-5 py-3 text-right font-semibold sm:hidden">Per term<span class="block font-normal">Jamaican / international</span></th><th scope="col" class="hidden px-5 py-3 text-right font-semibold sm:table-cell">Jamaican students<span class="block font-normal">per term</span></th><th scope="col" class="hidden px-5 py-3 text-right font-semibold sm:table-cell">International students<span class="block font-normal">per term</span></th></tr></thead>
                    <tbody>
                        @foreach ($fees as $fee)
                            <tr class="border-t border-line"><th scope="row" class="px-5 py-4 font-semibold">{{ $fee->label }}@if ($fee->grades)<span class="block text-sm font-normal text-ink-3">{{ $fee->grades }}</span>@endif @if ($fee->note)<span class="block text-sm font-normal text-ink-3">{{ $fee->note }}</span>@endif</th>
                                <td class="num px-5 py-4 text-right sm:hidden"><span class="block font-display text-lg">{{ $fee->amount_jmd ? jmd($fee->amount_jmd) : '–' }}</span><span class="block font-display text-lg">{{ usd($fee->amount_usd) }}</span></td>
                                <td class="num hidden px-5 py-4 text-right font-display text-xl sm:table-cell">{{ $fee->amount_jmd ? jmd($fee->amount_jmd) : '–' }}</td>
                                <td class="num hidden px-5 py-4 text-right font-display text-xl sm:table-cell">{{ usd($fee->amount_usd) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="measure mt-4 text-ink-2">Tuition per term, <strong>as published for {{ setting('fees_year') }}</strong>. {{ setting('fees_note') }}</p>
            <p class="mt-2 text-ink-2">Questions about fees: <a class="text-link" href="mailto:{{ hillel('tuition_email') }}">{{ hillel('tuition_email') }}</a></p>
            @if (setting('fees_source'))<p class="mt-1 text-sm text-ink-3">{{ setting('fees_source') }}</p>@endif
        @endif
    </section>

    <section id="dates" class="wrap mt-20 grid gap-10 lg:grid-cols-[1fr_1.2fr]" aria-labelledby="dates-title">
        <div>
            <x-margin-note>Key dates</x-margin-note>
            <h2 id="dates-title" class="display-2 mt-3">Admissions calendar</h2>
            <p class="lead mt-4 text-ink-2">Information evenings, open days and assessment dates. Kindergarten group sessions run from March to May; assessments for Grades 1 to 6 and 8 to 11 can be arranged at any time of year.</p>
            <a href="{{ route('calendar', ['category' => 'admissions']) }}" class="btn btn-outline mt-6">Full calendar</a>
        </div>
        <div>@include('partials.event-list', ['events' => $keyDates])</div>
    </section>

    <section class="wrap mt-20">
        <div class="on-dark ruled rounded-2xl bg-ink-900 p-8 text-white sm:p-12">
            <h2 class="display-2">Ready when you are</h2>
            <p class="lead mt-4 text-white/85">Start online now, or talk to a person first: {{ format_phone(setting('phone_admissions')) }} · {{ setting('email_admissions') }}</p>
            <div class="mt-8 flex flex-wrap gap-3"><a href="{{ route('apply') }}" class="btn btn-accent">Start an application</a><a href="{{ route('enquire') }}" class="btn btn-on-dark">Ask a question</a><a href="{{ route('family.login') }}" class="btn btn-on-dark">Family portal</a></div>
        </div>
    </section>
</x-layouts.site>
