<x-layouts.site title="Apply online" description="Start an application to Hillel Academy, Kingston. Save as you go and finish later from the family portal.">
    <x-page-header title="Apply to Hillel Academy" note="Admissions" lead="Start with a few details. We’ll email you a secure link to the family portal, where you can complete the application in five short steps and save as you go." photo="campus-aerial" alt="" />
    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[1.1fr_1fr]">
        @if ($open)
            <form method="POST" action="{{ route('apply.store') }}" class="panel tabbed grid gap-5 p-6 sm:p-8" novalidate>
                @csrf
                <h2 class="display-3">Start an application</h2>
                <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
                <x-field name="parent_name" label="Your full name" required autocomplete="name" />
                <x-field name="email" label="Your email" type="email" required autocomplete="email" hint="We’ll send the link here. Use an address you check often." />
                <x-field name="student_first_name" label="Child’s first name" required autocomplete="off" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="grade" label="Grade applying for" :options="grades()" :value="$grade" required />
                    <x-field name="entry_year" label="School year" :options="array_combine(entry_years(), entry_years())" :value="entry_years()[0] ?? null" required />
                </div>
                <div>
                    <label class="choice"><input type="checkbox" name="consent" value="1" required @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror><span>I have read how Hillel Academy uses application information in the <a class="text-link" href="{{ route('privacy') }}">privacy notice</a>.</span></label>
                    <x-error for="consent" />
                </div>
                <button class="btn btn-accent justify-self-start" data-once>Email me my link<x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
                <p class="text-sm text-ink-3">Already started? <a class="text-link" href="{{ route('family.login') }}">Sign in to the family portal</a>.</p>
            </form>
        @else
            <div class="panel tabbed p-6 sm:p-8">
                <h2 class="display-3">Online applications are closed at the moment</h2>
                <p class="mt-3 text-ink-2">Please <a class="text-link" href="{{ route('enquire') }}">send us an enquiry</a> or call Admissions on {{ format_phone(setting('phone_admissions')) }} and we’ll tell you when places open.</p>
            </div>
        @endif
        <div>
            <x-margin-note>What you’ll need</x-margin-note>
            <ol class="mt-5 grid gap-4">
                @foreach (\App\Models\Application::STEP_NAMES as $n => $name)
                    <li class="flex gap-4"><span class="font-display grid h-10 w-10 flex-none place-items-center rounded-full bg-sky text-lg">{{ $n }}</span><span><span class="font-semibold">{{ $name }}</span><span class="block text-ink-2">{{ match ($n) { 1 => 'Name, date of birth, nationality and the grade and year you are applying for.', 2 => 'Contact details for a parent or guardian, plus a second contact if you like.', 3 => 'Current school and anything that helps us support your child.', 4 => 'Birth certificate, recent school reports and immunisation record (scans or phone photos).', default => 'Check everything, confirm the declaration and send. You get a reference number straight away.' } }}</span></span></li>
                @endforeach
            </ol>
            <p class="mt-6 text-ink-2">Prefer to talk first? <a class="text-link" href="{{ route('visit') }}">Book a campus visit</a> or call {{ format_phone(setting('phone_admissions')) }}.</p>
        </div>
    </section>
</x-layouts.site>
