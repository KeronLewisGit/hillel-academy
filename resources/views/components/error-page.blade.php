@props(['code', 'message' => null])
@php
    $copy = [
        '403' => ['Not available', 'You don’t have access to this page.'],
        '404' => ['Page not found', 'That page doesn’t exist, or it has moved. Try the home page or the admissions section.'],
        '419' => ['Page expired', 'The form was open too long. Go back, refresh the page and try again.'],
        '429' => ['Too many attempts', 'Please wait a minute and try again.'],
        '500' => ['Something went wrong', 'Sorry, there was a problem on our side. Please try again, or call the school.'],
        '503' => ['Back soon', 'The website is being updated and will be back shortly.'],
    ][$code] ?? ['Error', 'Something went wrong.'];
    // A specific reason given with abort(403, '…') reads better than the generic line.
    if ($message) {
        $copy[1] = $message;
    }
@endphp
<x-layouts.site :title="$copy[0]" :mobile-bar="false">
    <x-page-header :title="$copy[0]" :lead="$copy[1]" :note="'Error '.$code" />
    <section class="wrap mt-12 flex flex-wrap gap-3 md:mt-16">
        <a href="{{ route('home') }}" class="btn btn-primary">Home page</a>
        @if ($code === '404')<a href="{{ route('admissions') }}" class="btn btn-outline">Admissions</a>@endif
        <a href="{{ tel_href(setting('phone_main')) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(setting('phone_main')) }}</a>
    </section>
</x-layouts.site>
