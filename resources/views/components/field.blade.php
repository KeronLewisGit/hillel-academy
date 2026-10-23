@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null, 'options' => null, 'placeholder' => null, 'autocomplete' => null, 'rows' => 4, 'optional' => null])
{{-- Labelled form field with hint and error wired up for screen readers. --}}
@php
    $id = 'f-'.str_replace(['[', ']', '.'], '-', $name);
    $current = old($name, $value);
    $described = trim(($hint ? $id.'-hint ' : '').($errors->has($name) ? str_replace(['.', '[', ']'], '-', $name).'-error' : ''));
    $optional ??= ! $required;
@endphp
<div {{ $attributes }}>
    <label class="label" for="{{ $id }}">{{ $label }}@if ($optional) <span class="opt">(optional)</span>@endif</label>
    @if ($options !== null)
        <select class="input" id="{{ $id }}" name="{{ $name }}" @required($required) @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if($described) aria-describedby="{{ $described }}" @endif @error($name) aria-invalid="true" @enderror>
            <option value="">{{ $placeholder ?? 'Choose…' }}</option>
            @foreach ($options as $k => $v)<option value="{{ $k }}" @selected((string) $current === (string) $k)>{{ $v }}</option>@endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea class="input" id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required) @if($placeholder) placeholder="{{ $placeholder }}" @endif @if($described) aria-describedby="{{ $described }}" @endif @error($name) aria-invalid="true" @enderror>{{ $current }}</textarea>
    @else
        <input class="input" id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $current }}" @required($required) @if($placeholder) placeholder="{{ $placeholder }}" @endif @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if($described) aria-describedby="{{ $described }}" @endif @error($name) aria-invalid="true" @enderror>
    @endif
    @if ($hint)<span class="hint" id="{{ $id }}-hint">{{ $hint }}</span>@endif
    <x-error :for="$name" />
</div>
