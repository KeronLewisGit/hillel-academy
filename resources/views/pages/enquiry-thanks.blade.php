<x-layouts.site title="Thank you">
    <x-page-header title="Thank you" note="Message received" :lead="$kind === 'general' ? 'Your message is with the school office. We will reply by email.' : 'Your enquiry is with the Admissions Office. We will reply by email.'" />
    <section class="wrap mt-12 flex flex-wrap gap-3">
        <a href="{{ route('apply') }}" class="btn btn-accent">Start an application</a>
        <a href="{{ route('visit') }}" class="btn btn-outline">Book a campus visit</a>
        <a href="{{ route('home') }}" class="btn btn-outline">Back to the home page</a>
    </section>
</x-layouts.site>
