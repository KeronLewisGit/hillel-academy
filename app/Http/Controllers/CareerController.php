<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use App\Models\TeacherApplication;
use App\Notifications\OfficeAlert;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Teaching vacancies and the application form with CV upload. */
class CareerController extends Controller
{
    public function index(): View
    {
        return view('pages.careers.index', ['jobs' => JobPosting::open()->orderBy('section')->orderBy('title')->get()]);
    }

    public function show(JobPosting $job): View
    {
        abort_unless($job->isOpen(), 404);

        return view('pages.careers.show', ['job' => $job]);
    }

    public function create(Request $request): View
    {
        $jobs = JobPosting::open()->orderBy('title')->get();

        return view('pages.careers.apply', ['jobs' => $jobs, 'selected' => $jobs->firstWhere('slug', input_string($request->query('job')))]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            return to_route('careers.thanks');
        }

        $data = $request->validate([
            'job_posting_id' => ['nullable', 'integer', Rule::exists('job_postings', 'id')->where('status', 'open')],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:40', self::PHONE_REGEX],
            'country' => ['required', 'string', 'max:80'],
            'current_position' => ['nullable', 'string', 'max:160'],
            'subjects' => ['required', 'string', 'max:200'],
            'years_experience' => ['required', 'integer', 'min:0', 'max:60'],
            'curricula' => ['nullable', 'array'],
            'curricula.*' => [Rule::in(array_keys(TeacherApplication::CURRICULA))],
            'qualifications' => ['nullable', 'string', 'max:2000'],
            'available_from' => ['nullable', 'date', 'after_or_equal:today'],
            'cover_letter' => ['nullable', 'string', 'max:6000'],
            'cv' => ['required', 'file', 'max:5120', 'mimes:'.implode(',', TeacherApplication::CV_TYPES)],
            'consent' => ['accepted'],
        ], [
            'cv.required' => 'Please attach your CV.',
            'cv.mimes' => 'Your CV must be a PDF or Word document.',
            'cv.max' => 'Your CV must be 5 MB or smaller.',
            'cv.uploaded' => 'Your CV must be 5 MB or smaller.',
            'phone.regex' => 'Please enter a phone number including the country code.',
            'consent.accepted' => 'Please confirm we may keep your application on file.',
        ]);

        $file = $request->file('cv');
        unset($data['cv'], $data['consent']);
        $data['email'] = strtolower($data['email']);
        $data['cv_path'] = $file->store('cvs', 'local');
        $data['cv_name'] = mb_substr($file->getClientOriginalName(), 0, 200);

        $application = TeacherApplication::create($data);
        $application->load('jobPosting');

        Notify::office(new OfficeAlert(
            'Teacher application: '.$application->full_name,
            ['Post' => $application->jobPosting->title ?? 'General application', 'Subjects' => $application->subjects, 'Country' => $application->country, 'Experience' => $application->years_experience.' years', 'Reference' => $application->reference],
            route('admin.teachers.show', $application),
        ));

        $request->session()->put('career_reference', $application->reference);

        return to_route('careers.thanks');
    }

    public function thanks(Request $request): View|RedirectResponse
    {
        $reference = $request->session()->get('career_reference');

        return $reference ? view('pages.careers.thanks', ['reference' => $reference]) : to_route('careers.index');
    }
}
