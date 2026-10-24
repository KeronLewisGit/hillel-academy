@props(['tone' => 'auto'])
{{--
    The school's crest (public/img/brand/hillel-crest.png, taken from hillelacademyjm.com)
    beside the wordmark. The crest carries its own blue edge and cream shield, so it reads
    on paper and on the navy footer and admin sidebar without a tile behind it.
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <img src="{{ asset('img/brand/hillel-crest.png') }}" alt="" width="217" height="244" class="h-12 w-auto flex-none" decoding="async">
    <span class="leading-none {{ $tone === 'light' ? 'text-white' : 'text-ink' }}">
        <span class="font-display block text-[1.45rem] tracking-[-0.01em]">Hillel Academy</span>
        <span class="mt-1 block whitespace-nowrap text-[0.6875rem] font-semibold uppercase tracking-[0.12em] {{ $tone === 'light' ? 'text-white/75' : 'text-ink-3' }}">Kingston · Jamaica · 1969</span>
    </span>
</span>
