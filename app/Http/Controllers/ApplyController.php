<?php

namespace App\Http\Controllers;

use App\Enums\AdmissionStatus;
use App\Mail\FamilyLoginLink;
use App\Models\Application;
use App\Models\Family;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Starting an application. The visitor gives an email, a name and the child's
 * grade; we create (or reuse) the family, add a draft, and email a sign-in link.
 * The response is identical whether or not the email was already known, so the
 * form can't be used to discover which families have applied.
 */
class ApplyController extends Controller
{
    public function create(Request $request): View
    {
        return view('pages.apply', [
            'grade' => array_key_exists(input_string($request->query('grade')), grades()) ? input_string($request->query('grade')) : null,
            'open' => (bool) setting('applications_open'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            return to_route('apply.check-email');
        }
        abort_unless((bool) setting('applications_open'), 403, 'Online applications are closed at the moment.');

        $data = $request->validate([
            'parent_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'student_first_name' => ['required', 'string', 'max:80'],
            'grade' => ['required', Rule::in(array_keys(grades()))],
            'entry_year' => ['required', Rule::in(entry_years())],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'Please confirm you have read how we use application data.',
            'grade.required' => 'Please choose the grade your child would join.',
        ]);
        $email = strtolower($data['email']);

        $family = Family::query()->firstOrCreate(['email' => $email], ['name' => $data['parent_name']]);

        // An existing family gets a new draft too; they will see it once they sign in.
        Application::create([
            'family_id' => $family->id,
            'status' => AdmissionStatus::Draft,
            'student_first_name' => $data['student_first_name'],
            'grade' => $data['grade'],
            'entry_year' => $data['entry_year'],
            'guardian_name' => $family->wasRecentlyCreated ? $data['parent_name'] : null,
            'guardian_email' => $family->wasRecentlyCreated ? $email : null,
        ]);

        $url = $family->loginUrl();
        try {
            Mail::to($family->email)->send(new FamilyLoginLink($family->name, $url, newApplication: true));
        } catch (\Throwable $e) {
            report($e); // the family can ask for a fresh link from /family/login
        }

        $request->session()->put('check_email', $email);
        if (hillel('demo_links')) {
            $request->session()->flash('demo_link', $url);
        }

        return to_route('apply.check-email');
    }

    public function checkEmail(Request $request): View
    {
        return view('pages.check-email', ['email' => $request->session()->get('check_email'), 'demoLink' => $request->session()->get('demo_link')]);
    }
}
