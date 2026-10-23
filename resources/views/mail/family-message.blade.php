<x-mail-layout :title="$mailSubject">
    @foreach ($paragraphs as $p)
        <p style="margin:0 0 14px">{!! nl2br(e($p)) !!}</p>
    @endforeach
</x-mail-layout>
