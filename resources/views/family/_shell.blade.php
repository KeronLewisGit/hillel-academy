{{-- Thin portal bar under the page header: who is signed in, and a way out. --}}
<div class="border-b border-line bg-sky">
    <div class="wrap flex min-h-14 flex-wrap items-center justify-between gap-3 py-2 text-sm">
        <p class="flex items-center gap-2"><x-icon name="key" class="h-4 w-4" />Signed in as <strong>{{ auth('family')->user()->email }}</strong></p>
        <div class="flex items-center gap-2">
            <a class="btn btn-sm btn-outline" href="{{ route('family.dashboard') }}">My applications</a>
            <form method="POST" action="{{ route('family.logout') }}">@csrf<button class="btn btn-sm btn-outline"><x-icon name="log-out" class="h-4 w-4" />Sign out</button></form>
        </div>
    </div>
</div>
@if (session('demo_link'))
    <div class="wrap mt-6"><p class="panel border-dashed p-4 text-sm"><strong>Demo mode:</strong> email is not set up, so here is the link that was emailed: <a class="text-link break-all" href="{{ session('demo_link') }}">{{ session('demo_link') }}</a></p></div>
@endif
