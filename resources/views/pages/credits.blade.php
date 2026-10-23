<x-layouts.site title="Photo credits">
    <x-page-header title="Photo credits" note="Thank you" lead="Photographs on this site are used under the licences below. Photos that are not of Hillel Academy illustrate Kingston and Jamaica and will be replaced with the school’s own pictures." />
    <section class="wrap mt-12 md:mt-16">
        <ul class="grid gap-4 md:grid-cols-2">
            @foreach ($credits as $key => $c)
                <li class="panel p-5"><p class="font-semibold">{{ $c['title'] }}</p><p class="mt-1 text-ink-2">By {{ $c['author'] }} · <a class="text-link" href="{{ $c['license_url'] }}" rel="license noopener" target="_blank">{{ $c['license'] }}</a> · <a class="text-link" href="{{ $c['source'] }}" rel="noopener" target="_blank">Source</a></p>@if (! empty($c['changes']))<p class="mt-1 text-sm text-ink-3">{{ $c['changes'] }}</p>@endif</li>
            @endforeach
        </ul>
    </section>
</x-layouts.site>
