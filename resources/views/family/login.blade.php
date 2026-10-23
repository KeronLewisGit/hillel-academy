<x-layouts.site title="Family portal sign in" description="Sign in to the Hillel Academy family portal to continue an application, upload documents and book an assessment." :mobile-bar="false">
    <x-page-header title="Family portal" note="Parents & guardians" lead="Continue an application, send documents, book an entrance assessment and see decisions. There is no password: we email you a secure link." />
    <section class="wrap mt-12 grid gap-10 md:mt-16 lg:grid-cols-[1fr_1fr]">
        <form method="POST" action="{{ route('family.login.send') }}" class="panel tabbed p-6 sm:p-8" novalidate>
            @csrf
            <h2 class="display-3">Email me a sign-in link</h2>
            <p class="mt-2 text-ink-2">Use the email address you gave when you started your application.</p>
            <div class="mt-6">
                <label class="label" for="email">Email address</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                <x-error for="email" />
            </div>
            <button class="btn btn-primary mt-6 w-full sm:w-auto" data-once>Send my link<x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
        </form>
        <div class="prose-copy measure">
            <h2 class="!mt-0">New to Hillel?</h2>
            <p>You don’t need an account to start. <a href="{{ route('apply') }}">Start an online application</a> and we’ll email you a link to the portal, where your answers are saved as you go.</p>
            <h2>Can’t find the email?</h2>
            <p>Links arrive within a minute or two and work once, for {{ \App\Models\Family::LINK_MINUTES }} minutes. Check your spam or promotions folder, or call Admissions on <a href="{{ tel_href(setting('phone_admissions')) }}">{{ format_phone(setting('phone_admissions')) }}</a>.</p>
        </div>
    </section>
</x-layouts.site>
