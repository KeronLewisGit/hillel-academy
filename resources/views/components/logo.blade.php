@props(['tone' => 'auto'])
{{--
    Placeholder mark for the concept: an open exercise book with a marigold sun rising
    over it. Deliberately NOT the school crest, which should replace it once the school
    supplies artwork (see CLAUDE.md).
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 44 44" class="h-10 w-10 flex-none" aria-hidden="true">
        <rect width="44" height="44" rx="10" fill="{{ $tone === 'light' ? '#ffffff' : 'var(--ink-900)' }}"/>
        <circle cx="22" cy="19" r="7" fill="var(--accent)"/>
        <path d="M7 31.5c5-2.6 10-2.6 15 0 5-2.6 10-2.6 15 0V35c-5-2.6-10-2.6-15 0-5-2.6-10-2.6-15 0z" fill="{{ $tone === 'light' ? 'var(--ink-900)' : '#ffffff' }}"/>
        <path d="M22 31.5V35" stroke="{{ $tone === 'light' ? '#ffffff' : 'var(--ink-900)' }}" stroke-width="1.2"/>
    </svg>
    <span class="leading-none {{ $tone === 'light' ? 'text-white' : 'text-ink' }}">
        <span class="font-display block text-[1.45rem] tracking-[-0.01em]">Hillel Academy</span>
        <span class="mt-1 block whitespace-nowrap text-[0.6875rem] font-semibold uppercase tracking-[0.12em] {{ $tone === 'light' ? 'text-white/75' : 'text-ink-3' }}">Kingston · Jamaica · 1969</span>
    </span>
</span>
