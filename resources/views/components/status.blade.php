@props(['status', 'family' => false])
{{-- Admission status chip: icon + text, colour only as reinforcement. --}}
<span {{ $attributes->merge(['class' => 'chip']) }} data-tone="{{ $status->tone() }}"><x-icon :name="$status->icon()" />{{ $family ? $status->familyLabel() : $status->label() }}</span>
