<x-layouts.site title="Check your email" :mobile-bar="false">
    <x-page-header title="Check your email" :note="($signIn ?? false) ? 'Family portal' : 'Online application'" />
    <section class="wrap mt-12 md:mt-16">
        <div class="panel tabbed measure p-6 sm:p-8">
            <x-icon name="mail" class="h-8 w-8 text-ink-2" />
            @if ($signIn ?? false)
                <p class="lead mt-4">If that address has an application with us, a sign-in link is on its way to <strong class="break-all">{{ $email ?: 'that address' }}</strong>.</p>
            @else
                <p class="lead mt-4">We have sent a secure link to <strong class="break-all">{{ $email ?: 'your email address' }}</strong>. Open it on this or any device to continue the application.</p>
            @endif
            <p class="mt-4 text-ink-2">The link works once and lasts {{ \App\Models\Family::LINK_MINUTES }} minutes. If it doesn’t arrive within a few minutes, check your spam folder or <a class="text-link" href="{{ route('family.login') }}">ask for a new link</a>.</p>
            @if ($demoLink)
                <p class="mt-6 rounded-lg border border-dashed border-line-strong bg-sky p-4 text-sm"><strong>Demo mode:</strong> email isn’t connected on this preview, so here is the link that was sent: <a class="text-link break-all" href="{{ $demoLink }}">{{ $demoLink }}</a></p>
            @endif
        </div>
    </section>
</x-layouts.site>
