<x-layouts.site title="Application sent">
    <x-page-header title="Thank you for applying" note="Careers" :lead="'Your reference is '.$reference.'. We will contact you if your application is shortlisted.'" />
    <section class="wrap mt-12"><a href="{{ route('careers.index') }}" class="btn btn-outline">Back to vacancies</a></section>
</x-layouts.site>
