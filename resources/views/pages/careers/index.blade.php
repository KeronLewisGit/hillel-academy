<x-layouts.site title="Careers" description="Teaching and staff vacancies at Hillel Academy, Kingston, Jamaica. Overseas applicants welcome.">
    <x-page-header title="Teach at Hillel" note="Careers" lead="Teach the IMYC, Cambridge IGCSE or the IB Diploma at Jamaica’s largest international school. Current vacancies are below; teachers from Jamaica and overseas are welcome to apply, and experienced teachers can send a general application at any time." photo="blue-mountains" alt="" />
    <section class="wrap mt-12 md:mt-16">
        @if ($jobs->isEmpty())
            <div class="panel tabbed measure p-6 sm:p-8"><h2 class="display-3">No vacancies are advertised right now</h2><p class="mt-3 text-ink-2">You are welcome to send a general application with your CV. We keep it on file and contact you when a suitable post opens.</p><a href="{{ route('careers.apply') }}" class="btn btn-accent mt-5">Send a general application</a></div>
        @else
            <div class="grid gap-5 md:grid-cols-2">
                @foreach ($jobs as $job)
                    <article class="panel tabbed flex flex-col p-6">
                        <p class="text-sm font-semibold text-ink-3">{{ $job->sectionLabel() }} · {{ $job->typeLabel() }}</p>
                        <h2 class="display-3 mt-2"><a class="hover:text-link" href="{{ route('careers.show', $job) }}">{{ $job->title }}</a></h2>
                        <p class="mt-2 flex-1 text-ink-2">{{ $job->summary }}</p>
                        <div class="mt-4 flex flex-wrap gap-2 text-sm">
                            @if ($job->overseas_welcome)<x-chip tone="neutral" icon="globe">Overseas applicants welcome</x-chip>@endif
                            @if ($job->closes_on)<x-chip tone="neutral" icon="calendar">Closes {{ $job->closes_on->format('j M Y') }}</x-chip>@endif
                        </div>
                        <a href="{{ route('careers.show', $job) }}" class="btn btn-outline mt-5 self-start">Read more<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    </article>
                @endforeach
            </div>
            <p class="mt-8 text-ink-2">Don’t see your subject? <a class="text-link" href="{{ route('careers.apply') }}">Send a general application</a>.</p>
        @endif
    </section>
</x-layouts.site>
