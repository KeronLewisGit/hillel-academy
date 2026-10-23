@php
    $ld = array_filter([
        '@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $job->title, 'description' => nl2br(e($job->description)),
        'datePosted' => $job->created_at?->toDateString(), 'validThrough' => $job->closes_on?->toDateString(),
        'employmentType' => strtoupper($job->employment_type === 'contract' ? 'CONTRACTOR' : $job->employment_type),
        'hiringOrganization' => ['@type' => 'School', 'name' => hillel('name'), 'sameAs' => route('home')],
        'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'streetAddress' => hillel('address.street'), 'addressLocality' => hillel('address.city'), 'addressCountry' => 'JM']],
    ]);
@endphp
<x-layouts.site :title="$job->title" :description="$job->summary" :json-ld="[$ld]">
    <x-page-header :title="$job->title" :note="$job->sectionLabel().' · '.$job->typeLabel()" :lead="$job->summary" />
    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[1.5fr_1fr]">
        <div class="prose-copy measure">
            @foreach (preg_split('/\R{2,}/', $job->description) as $p)<p>{{ $p }}</p>@endforeach
            @if ($job->requirementList())<h2>What we are looking for</h2><ul>@foreach ($job->requirementList() as $r)<li>{{ $r }}</li>@endforeach</ul>@endif
        </div>
        <aside class="panel tabbed content-start p-6">
            <dl class="grid gap-3">
                <div><dt class="text-sm text-ink-3">Section</dt><dd class="font-semibold">{{ $job->sectionLabel() }}</dd></div>
                <div><dt class="text-sm text-ink-3">Contract</dt><dd class="font-semibold">{{ $job->typeLabel() }}</dd></div>
                @if ($job->start_date_text)<div><dt class="text-sm text-ink-3">Start</dt><dd class="font-semibold">{{ $job->start_date_text }}</dd></div>@endif
                @if ($job->closes_on)<div><dt class="text-sm text-ink-3">Closing date</dt><dd class="font-semibold">{{ $job->closes_on->format('j F Y') }}</dd></div>@endif
                @if ($job->overseas_welcome)<div><dt class="text-sm text-ink-3">Overseas applicants</dt><dd class="font-semibold">Welcome</dd></div>@endif
            </dl>
            <a href="{{ route('careers.apply', ['job' => $job->slug]) }}" class="btn btn-accent mt-6 w-full">Apply for this post</a>
        </aside>
    </section>
</x-layouts.site>
