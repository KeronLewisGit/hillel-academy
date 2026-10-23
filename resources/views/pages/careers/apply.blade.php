<x-layouts.site title="Apply to teach" :mobile-bar="false">
    <x-page-header title="Apply to teach at Hillel" note="Careers" :lead="$selected ? 'Applying for: '.$selected->title : 'A general application, kept on file for future vacancies.'" />
    <section class="wrap mt-12 md:mt-16">
        <form method="POST" action="{{ route('careers.store') }}" enctype="multipart/form-data" class="panel tabbed mx-auto grid max-w-3xl gap-5 p-6 sm:p-8" novalidate>
            @csrf
            <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
            <x-field name="job_posting_id" label="Post" :options="$jobs->pluck('title', 'id')->all()" :value="$selected?->id" placeholder="General application" />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="first_name" label="First name" required autocomplete="given-name" />
                <x-field name="last_name" label="Last name" required autocomplete="family-name" />
                <x-field name="email" label="Email" type="email" required autocomplete="email" />
                <x-field name="phone" label="Phone" type="tel" required autocomplete="tel" hint="Include the country code, e.g. +44." />
                <x-field name="country" label="Country you live in now" required autocomplete="country-name" />
                <x-field name="current_position" label="Current position" />
                <x-field name="subjects" label="Subjects or grades you teach" required />
                <x-field name="years_experience" label="Years of teaching experience" type="number" required />
            </div>
            <fieldset><legend class="label">Curricula you have taught <span class="opt">(optional)</span></legend>
                <div class="grid gap-2 sm:grid-cols-2">@foreach (\App\Models\TeacherApplication::CURRICULA as $k => $label)<label class="choice"><input type="checkbox" name="curricula[]" value="{{ $k }}" @checked(in_array($k, old('curricula', []), true))> {{ $label }}</label>@endforeach</div>
                <x-error for="curricula.0" />
            </fieldset>
            <x-field name="qualifications" label="Degrees and teaching qualifications" type="textarea" :rows="3" />
            <x-field name="available_from" label="Available from" type="date" />
            <x-field name="cover_letter" label="Cover letter" type="textarea" :rows="6" />
            <div>
                <label class="label" for="cv">CV <span class="opt">(PDF or Word, up to 5 MB)</span></label>
                <input class="input" id="cv" name="cv" type="file" accept=".pdf,.doc,.docx" required @error('cv') aria-invalid="true" aria-describedby="cv-error" @enderror>
                <x-error for="cv" />
            </div>
            <div>
                <label class="choice"><input type="checkbox" name="consent" value="1" required @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror><span>Hillel Academy may keep my application on file for recruitment (<a class="text-link" href="{{ route('privacy') }}">privacy notice</a>).</span></label>
                <x-error for="consent" />
            </div>
            <button class="btn btn-accent justify-self-start" data-once>Send application<x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
        </form>
    </section>
</x-layouts.site>
