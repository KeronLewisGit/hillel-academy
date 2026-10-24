@props(['title', 'lead' => null, 'note' => null, 'photo' => null, 'alt' => ''])
{{--
    The one hero band every inner page uses, so titles start at the same height and
    left edge site-wide: ink-blue paper with faint ruled lines and a margin note.
    With a photo it sits behind a scrim. Extra content (actions, chips) goes in the slot.
--}}
<section {{ $attributes->merge(['class' => 'on-dark ruled relative isolate overflow-hidden bg-ink-900 text-white']) }}>
    @if ($photo)
        <div class="hero-photo absolute inset-0 -z-10">
            <x-photo :key="$photo" :alt="$alt" :eager="true" sizes="100vw" class="h-full w-full" />
            <div class="hero-scrim absolute inset-0"></div>
        </div>
    @endif
    <div class="wrap pb-12 pt-12 md:pb-20 md:pt-20">
        @if ($note)<x-margin-note>{{ $note }}</x-margin-note>@endif
        <h1 class="display-2 mt-4 max-w-4xl">{{ $title }}</h1>
        @if ($lead)<p class="lead mt-5 text-white/85">{{ $lead }}</p>@endif
        {{ $slot }}
    </div>
    <div class="h-1.5 bg-accent" aria-hidden="true"></div>
</section>
