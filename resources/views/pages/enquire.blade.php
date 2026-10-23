<x-layouts.site title="Admissions enquiry" description="Ask the Hillel Academy Admissions Office about places, fees and entry.">
    <x-page-header title="Ask the Admissions Office" note="Admissions" lead="Tell us a little about your child and we’ll reply with availability for their grade, the next steps and answers to your questions." />
    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[1.2fr_1fr]">
        @include('partials.enquiry-form', ['kind' => 'admissions'])
        <aside class="grid content-start gap-6">
            <div class="panel p-6"><x-margin-note>Call or email</x-margin-note>
                <p class="mt-4"><a class="font-display text-2xl hover:text-link" href="{{ tel_href(setting('phone_admissions')) }}">{{ format_phone(setting('phone_admissions')) }}</a></p>
                <p class="mt-1"><a class="text-link break-all" href="mailto:{{ setting('email_admissions') }}">{{ setting('email_admissions') }}</a></p>
                @if (setting('office_hours'))<p class="mt-2 text-ink-2">{{ setting('office_hours') }}</p>@endif
            </div>
            <div class="panel p-6"><x-margin-note>Ready to apply?</x-margin-note><p class="mt-4 text-ink-2">You can start an online application now and finish it later.</p><a href="{{ route('apply') }}" class="btn btn-accent mt-4">Start an application</a></div>
        </aside>
    </section>
</x-layouts.site>
