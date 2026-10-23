<x-layouts.site title="Contact" description="Contact Hillel Academy, 51 Upper Mark Way, Kingston 8, Jamaica.">
    <x-page-header title="Contact us" note="Get in touch" lead="Call, email or send a message. For anything about places, fees or applying, the Admissions Office is the quickest route." />
    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[1fr_1.2fr]">
        <div class="grid content-start gap-5">
            <div class="panel tabbed p-6">
                <x-margin-note>Admissions</x-margin-note>
                <p class="mt-4"><a class="font-display text-3xl hover:text-link" href="{{ tel_href(setting('phone_admissions')) }}">{{ format_phone(setting('phone_admissions')) }}</a></p>
                <p class="mt-1"><a class="text-link break-all" href="mailto:{{ setting('email_admissions') }}">{{ setting('email_admissions') }}</a></p>
            </div>
            <div class="panel p-6">
                <x-margin-note>Main office</x-margin-note>
                <p class="mt-4"><a class="font-display text-2xl hover:text-link" href="{{ tel_href(setting('phone_main')) }}">{{ format_phone(setting('phone_main')) }}</a></p>
                @if (setting('office_hours'))<p class="mt-1 text-ink-2">{{ setting('office_hours') }}</p>@endif
                <ul class="mt-3 grid gap-1">@foreach (hillel('other_phones') as $label => $number)<li><a class="inline-flex min-h-11 items-center gap-2 hover:text-link" href="{{ tel_href($number) }}"><x-icon name="phone" class="h-4 w-4" />{{ $label }}: {{ format_phone($number) }}</a></li>@endforeach
                    <li><a class="inline-flex min-h-11 items-center gap-2 hover:text-link" href="mailto:{{ hillel('tuition_email') }}"><x-icon name="mail" class="h-4 w-4" />Fees: {{ hillel('tuition_email') }}</a></li></ul>
                <address class="mt-4 not-italic">{{ hillel('address.street') }}<br>{{ hillel('address.city') }}, {{ hillel('address.country_name') }}</address>
                <a class="btn btn-outline btn-sm mt-4" href="{{ hillel('map_url') }}" target="_blank" rel="noopener">Directions<x-icon name="external" class="h-4 w-4" /></a>
            </div>
            @if (setting('email_careers'))<div class="panel p-6"><x-margin-note>Careers</x-margin-note><p class="mt-4"><a class="text-link" href="mailto:{{ setting('email_careers') }}">{{ setting('email_careers') }}</a></p></div>@endif
        </div>
        @include('partials.enquiry-form', ['kind' => 'general'])
    </section>
</x-layouts.site>
