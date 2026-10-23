<?php

namespace App\Http\Controllers\Family;

use App\Enums\AdmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AssessmentSlot;
use App\Services\Admissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PortalController extends Controller
{
    use Concerns;

    public function dashboard(Request $request): View
    {
        $family = $this->family($request);

        return view('family.dashboard', ['family' => $family, 'applications' => $family->applications()->with('documents')->get()]);
    }

    /** Apply for another child from inside the portal. */
    public function newChild(Request $request): RedirectResponse
    {
        abort_unless((bool) setting('applications_open'), 403, 'Online applications are closed at the moment.');
        $family = $this->family($request);
        $data = $request->validate([
            'student_first_name' => ['required', 'string', 'max:80'],
            'grade' => ['required', Rule::in(array_keys(grades()))],
            'entry_year' => ['required', Rule::in(entry_years())],
        ]);
        $last = $family->applications()->first();
        $application = Application::create($data + [
            'family_id' => $family->id,
            'status' => AdmissionStatus::Draft,
            'student_last_name' => $last?->student_last_name,
            'guardian_name' => $last->guardian_name ?? $family->name,
            'guardian_email' => $last->guardian_email ?? $family->email,
            'guardian_phone' => $last?->guardian_phone,
            'guardian_relationship' => $last?->guardian_relationship,
            'guardian_address' => $last?->guardian_address,
        ]);

        return to_route('family.applications.step', [$application, 1]);
    }

    public function show(Request $request, Application $application): View|RedirectResponse
    {
        $this->own($request, $application);
        if ($application->isDraft()) {
            return to_route('family.applications.step', [$application, max(1, min($application->step, Application::STEPS))]);
        }
        $application->load('documents', 'assessmentSlot');

        $slots = $application->canBookAssessment()
            ? AssessmentSlot::bookableFor($application->grade)->limit(40)->get()->filter(fn (AssessmentSlot $s) => $s->isBookableFor($application->grade))->values()
            : collect();

        return view('family.application', [
            'application' => $application,
            'timeline' => $application->events()->where('public', true)->get(),
            'slots' => $slots,
            'position' => $application->waitlistPosition(),
        ]);
    }

    public function bookAssessment(Request $request, Application $application, Admissions $admissions): RedirectResponse
    {
        $this->own($request, $application);
        $data = $request->validate(['slot' => ['required', 'integer']], ['slot.required' => 'Please choose an assessment time.']);
        $slot = $admissions->bookAssessment($application, (int) $data['slot']);

        return to_route('family.applications.show', $application)->with('status', 'Assessment booked for '.$slot->starts_at->format('l j F \a\t g:i a').'.');
    }

    public function acceptOffer(Request $request, Application $application, Admissions $admissions): RedirectResponse
    {
        $this->own($request, $application);
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Please tick the box to confirm you accept the place.']);
        $admissions->acceptOffer($application);

        return to_route('family.applications.show', $application)->with('status', 'Thank you. You have accepted the place for '.$application->student_first_name.'. The Admissions Office will contact you about the next steps.');
    }

    public function declineOffer(Request $request, Application $application, Admissions $admissions): RedirectResponse
    {
        $this->own($request, $application);
        $admissions->declineOffer($application);

        return to_route('family.applications.show', $application)->with('status', 'You have declined the place. Thank you for letting us know.');
    }
}
