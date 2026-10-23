<x-layouts.site title="Book a campus visit" description="Book a campus tour of Hillel Academy, Upper Mark Way, Kingston 8.">
    <x-page-header title="Come and see Hillel" note="Campus visits" lead="The best way to know a school is to walk through it. Choose a tour time below and a member of the Admissions team will show you around." photo="campus-buildings" alt="" />
    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[1.3fr_1fr]">
        @if ($slots->isEmpty())
            <div class="panel tabbed p-6 sm:p-8">
                <h2 class="display-3">No tour times are published right now</h2>
                <p class="mt-3 text-ink-2">Please call Admissions on <a class="text-link" href="{{ tel_href(setting('phone_admissions')) }}">{{ format_phone(setting('phone_admissions')) }}</a> or <a class="text-link" href="{{ route('enquire') }}">send an enquiry</a> and we’ll arrange a time that suits you.</p>
            </div>
        @else
            <form method="POST" action="{{ route('visit.store') }}" class="panel tabbed grid content-start gap-5 p-6 sm:p-8" novalidate>
                @csrf
                <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
                <fieldset>
                    <legend class="font-display display-3">1. Choose a time</legend>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($slots as $s)
                            <label class="choice"><input type="radio" name="tour_slot_id" value="{{ $s->id }}" @checked((string) old('tour_slot_id') === (string) $s->id) required><span><span class="font-semibold">{{ $s->starts_at->format('l j F, g:i a') }}</span><span class="block text-sm text-ink-3">{{ $s->duration_minutes }} min · {{ $s->placesLeft() }} {{ \Illuminate\Support\Str::plural('place', $s->placesLeft()) }} left @if ($s->notes) · {{ $s->notes }}@endif</span></span></label>
                        @endforeach
                    </div>
                    <x-error for="tour_slot_id" />
                </fieldset>
                <h2 class="display-3 mt-2">2. Your details</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="name" label="Your name" required autocomplete="name" />
                    <x-field name="email" label="Email" type="email" required autocomplete="email" />
                    <x-field name="phone" label="Mobile phone" type="tel" required autocomplete="tel" />
                    <x-field name="party_size" label="How many people are coming?" :options="[1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6']" :value="2" required />
                    <x-field name="child_name" label="Child’s first name" />
                    <x-field name="grade" label="Grade they would join" :options="grades()" />
                </div>
                <x-field name="message" label="Anything we should know?" type="textarea" :rows="3" />
                <div>
                    <label class="choice"><input type="checkbox" name="consent" value="1" required @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror><span>Hillel Academy may use these details to arrange my visit (<a class="text-link" href="{{ route('privacy') }}">privacy notice</a>).</span></label>
                    <x-error for="consent" />
                </div>
                <button class="btn btn-accent justify-self-start" data-once>Book my visit<x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
            </form>
        @endif
        <aside class="grid content-start gap-6">
            <div class="panel p-6">
                <x-margin-note>Finding us</x-margin-note>
                <address class="mt-4 not-italic text-lg">{{ hillel('address.street') }}<br>{{ hillel('address.city') }}, {{ hillel('address.country_name') }}</address>
                <a class="btn btn-outline btn-sm mt-4" href="{{ hillel('map_url') }}" target="_blank" rel="noopener">Open in maps<x-icon name="external" class="h-4 w-4" /></a>
            </div>
            <div class="panel p-6">
                <x-margin-note>On the day</x-margin-note>
                <p class="mt-4 text-ink-2">A tour is a good time to ask about the curriculum, school life and the application process. If your plans change, just reply to the confirmation email or call Admissions.</p>
            </div>
        </aside>
    </section>
</x-layouts.site>
