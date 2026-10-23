<x-layouts.site title="Visit booked">
    <x-page-header title="See you soon" note="Campus visit booked" :lead="$when ? 'Your tour is booked for '.$when.'. We will email you if anything changes.' : 'Your tour request has been received.'" />
    <section class="wrap mt-12 flex flex-wrap gap-3">
        <a href="{{ route('admissions') }}" class="btn btn-primary">How admissions works</a>
        <a href="{{ route('apply') }}" class="btn btn-outline">Start an application</a>
    </section>
</x-layouts.site>
