<x-layouts.site title="Sign in to the family portal" :mobile-bar="false">
    <x-page-header title="Welcome back" note="Family portal" lead="One more step: press the button to sign in. This keeps your link safe if an email scanner opens it before you do." />
    <section class="wrap mt-12 md:mt-16">
        <form method="POST" action="{{ route('family.login.confirm', $token) }}" class="panel tabbed measure p-6 sm:p-8">
            @csrf
            <x-icon name="key" class="h-8 w-8 text-ink-2" />
            <h2 class="display-3 mt-4">Continue to the family portal</h2>
            <p class="mt-2 text-ink-2">The link works once. After you sign in you can continue the application, upload documents and see any updates from the Admissions Office.</p>
            <button class="btn btn-accent mt-6" data-once>Sign in<x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
        </form>
    </section>
</x-layouts.site>
