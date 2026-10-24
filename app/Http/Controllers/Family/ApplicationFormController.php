<?php

namespace App\Http\Controllers\Family;

use App\Enums\AdmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Notifications\OfficeAlert;
use App\Services\FamilyNotifier;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The five-step online application. Each step is its own page and its own POST,
 * so it works without JavaScript and every answer is saved as the family goes.
 * "Save and finish later" stores whatever is filled in and emails a fresh link.
 */
class ApplicationFormController extends Controller
{
    use Concerns;

    public const array TERMS = ['start' => 'Start of the school year', 'mid' => 'Mid-year (January)', 'asap' => 'As soon as a place is available'];

    public const array RELATIONSHIPS = ['mother' => 'Mother', 'father' => 'Father', 'guardian' => 'Legal guardian', 'grandparent' => 'Grandparent', 'other' => 'Other'];

    public function edit(Request $request, Application $application, int $step): View|RedirectResponse
    {
        $this->own($request, $application);
        if (! $application->isDraft()) {
            return to_route('family.applications.show', $application);
        }
        if ($step < 1 || $step > Application::STEPS) {
            abort(404);
        }
        // Can't skip ahead past the first unfinished step.
        if ($step > $application->step) {
            return to_route('family.applications.step', [$application, $application->step]);
        }
        $application->load('documents');

        return view('family.form', [
            'application' => $application,
            'step' => $step,
            'missing' => $step === Application::STEPS ? $this->missingAnswers($application) : [],
        ]);
    }

    public function update(Request $request, Application $application, int $step): RedirectResponse
    {
        $this->own($request, $application);
        abort_unless($application->isDraft(), 403, 'This application has been submitted and can no longer be edited.');
        abort_unless($step >= 1 && $step < Application::STEPS, 404);

        $later = $request->input('action') === 'later';
        $rules = self::rules($step, strict: ! $later);
        $data = $request->validate($rules, self::messages(), self::attributes());

        if ($step === 3) {
            $data['sibling_at_hillel'] = $request->has('sibling_at_hillel') ? $request->boolean('sibling_at_hillel') : null;
        }
        if (isset($data['guardian_email'])) {
            $data['guardian_email'] = strtolower((string) $data['guardian_email']);
        }
        // Columns that can never be empty keep their old value on a partial save.
        foreach (['student_first_name', 'grade', 'entry_year'] as $required) {
            if (array_key_exists($required, $data) && blank($data[$required])) {
                unset($data[$required]);
            }
        }

        $application->fill($data);
        if (! $later) {
            $application->step = max($application->step, $step + 1);
        }
        $application->save();

        if ($later) {
            return $this->savedForLater($request);
        }

        return to_route('family.applications.step', [$application, $step + 1]);
    }

    public function submit(Request $request, Application $application, FamilyNotifier $notifier): RedirectResponse
    {
        $this->own($request, $application);
        abort_unless($application->isDraft(), 403, 'This application has already been submitted.');
        $request->validate(['declaration' => ['accepted']], ['declaration.accepted' => 'Please confirm the declaration before submitting.']);

        if ($missing = $this->missingAnswers($application)) {
            return to_route('family.applications.step', [$application, Application::STEPS])
                ->withErrors(['declaration' => 'Some required answers are missing: '.implode(', ', array_keys($missing)).'.']);
        }

        $application->moveTo(AdmissionStatus::Submitted, null, 'Submitted online by the family.');
        $notifier->statusChanged($application);

        Notify::office(new OfficeAlert(
            'New application '.$application->reference.': '.$application->student_name,
            ['Grade' => $application->grade_label.' ('.$application->entry_year.')', 'Parent' => $application->guardian_name, 'Email' => $application->guardian_email, 'Phone' => $application->guardian_phone, 'Documents missing' => (string) count($application->missingDocuments())],
            route('admin.applications.show', $application),
        ));

        return to_route('family.applications.show', $application)->with('submitted', $application->reference);
    }

    /**
     * "Save and finish later": a fresh sign-in link by email, at most a few an hour
     * (the same cap as the sign-in form), so the button can't flood an inbox.
     */
    private function savedForLater(Request $request): RedirectResponse
    {
        $family = $this->family($request);
        $key = 'family-later|'.$family->id;
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return to_route('family.dashboard')->with('status', 'Your answers are saved. We have already emailed you a link recently, so please use that one to finish later.');
        }
        RateLimiter::hit($key, 3600);

        $link = $family->sendLoginLink(newApplication: true);
        $redirect = to_route('family.dashboard')->with('status', 'Your answers are saved. We have emailed a link to '.$family->email.' so you can finish later.');
        if (demo_links()) {
            $redirect->with('demo_link', $link['url']);
        }

        return $redirect;
    }

    /**
     * Validation rules per step. Partial saves drop "required" so a half-filled
     * page can still be stored.
     *
     * @return array<string, mixed>
     */
    public static function rules(int $step, bool $strict = true): array
    {
        $req = $strict ? 'required' : 'nullable';
        $rules = match ($step) {
            1 => [
                'student_first_name' => [$req, 'string', 'max:80'],
                'student_last_name' => [$req, 'string', 'max:80'],
                'student_preferred_name' => ['nullable', 'string', 'max:80'],
                'date_of_birth' => [$req, 'date', 'before:today', 'after:'.now()->subYears(21)->toDateString()],
                'gender' => ['nullable', Rule::in(['female', 'male', 'other'])],
                'nationality' => [$req, 'string', 'max:80'],
                'first_language' => ['nullable', 'string', 'max:80'],
                'grade' => [$req, Rule::in(array_keys(grades()))],
                'entry_year' => [$req, Rule::in(entry_years())],
                'entry_term' => [$req, Rule::in(array_keys(self::TERMS))],
            ],
            2 => [
                'guardian_name' => [$req, 'string', 'max:160'],
                'guardian_relationship' => [$req, Rule::in(array_keys(self::RELATIONSHIPS))],
                'guardian_email' => [$req, 'email:rfc', 'max:255'],
                'guardian_phone' => [$req, 'string', 'max:40', self::PHONE_REGEX],
                'guardian_address' => [$req, 'string', 'max:500'],
                'guardian_occupation' => ['nullable', 'string', 'max:120'],
                'guardian2_name' => ['nullable', 'string', 'max:160'],
                'guardian2_relationship' => ['nullable', 'required_with:guardian2_name', Rule::in(array_keys(self::RELATIONSHIPS))],
                'guardian2_email' => ['nullable', 'email:rfc', 'max:255'],
                'guardian2_phone' => ['nullable', 'string', 'max:40', self::PHONE_REGEX],
            ],
            3 => [
                'previous_school' => ['nullable', 'string', 'max:160'],
                'previous_school_country' => ['nullable', 'string', 'max:80'],
                'current_grade' => ['nullable', 'string', 'max:40'],
                'support_needs' => ['nullable', 'string', 'max:3000'],
                'heard_about' => ['nullable', 'string', 'max:120'],
            ],
            default => [],
        };
        if (! $strict) {
            // A partial save may leave anything blank.
            $rules = array_map(fn (array $r) => array_values(array_filter($r, fn ($x) => ! is_string($x) || ! str_starts_with($x, 'required'))), $rules);
        }

        return $rules;
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'date_of_birth.before' => 'The date of birth must be in the past.',
            'date_of_birth.after' => 'Please check the date of birth.',
            'guardian_phone.regex' => 'Please enter a phone number including the area code.',
            'guardian2_phone.regex' => 'Please enter a phone number including the area code.',
        ];
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return [
            'student_first_name' => 'first name', 'student_last_name' => 'last name', 'date_of_birth' => 'date of birth',
            'entry_year' => 'school year', 'entry_term' => 'start', 'guardian_name' => 'parent or guardian name',
            'guardian_relationship' => 'relationship', 'guardian_email' => 'email', 'guardian_phone' => 'phone',
            'guardian_address' => 'home address', 'guardian2_relationship' => 'second contact’s relationship',
        ];
    }

    /**
     * Required answers still blank, as label => step number.
     *
     * @return array<string, int>
     */
    private function missingAnswers(Application $application): array
    {
        $missing = [];
        foreach ([1, 2] as $step) {
            $v = Validator::make($application->only(array_keys(self::rules($step))) + [
                'date_of_birth' => $application->date_of_birth?->toDateString(),
            ], self::rules($step), [], self::attributes());
            foreach (array_keys($v->errors()->messages()) as $field) {
                $missing[self::attributes()[$field] ?? str_replace('_', ' ', $field)] = $step;
            }
        }

        return $missing;
    }
}
