@php($admissions = $kind === 'admissions')
<form method="POST" action="{{ route('enquiries.store') }}" class="panel tabbed grid content-start gap-5 p-6 sm:p-8" id="enquiry" novalidate>
    @csrf
    <input type="hidden" name="kind" value="{{ $kind }}">
    <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
    <h2 class="display-3">{{ $admissions ? 'Admissions enquiry' : 'Send a message' }}</h2>
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field name="name" label="Your name" required autocomplete="name" />
        <x-field name="email" label="Email" type="email" required autocomplete="email" />
    </div>
    <x-field name="phone" label="Phone" type="tel" autocomplete="tel" hint="With area code, e.g. 876 555 0123." />
    @if ($admissions)
        <x-field name="child_name" label="Child’s first name" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="grade" label="Grade they would join" :options="grades()" :value="$grade ?? null" required />
            <x-field name="entry_year" label="School year" :options="array_combine(entry_years(), entry_years())" required />
        </div>
    @endif
    <x-field name="message" :label="$admissions ? 'Your questions' : 'Message'" type="textarea" :required="! $admissions" />
    <div>
        <label class="choice"><input type="checkbox" name="consent" value="1" required @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror><span>Hillel Academy may use these details to reply to me (<a class="text-link" href="{{ route('privacy') }}">privacy notice</a>).</span></label>
        <x-error for="consent" />
    </div>
    <button class="btn btn-primary justify-self-start" data-once>Send<x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
</form>
