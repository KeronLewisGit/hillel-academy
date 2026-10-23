<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobPostingController extends Controller
{
    public function index(): View
    {
        return view('admin.jobs.index', ['jobs' => JobPosting::withCount('applications')->orderBy('status')->orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('admin.jobs.form', ['job' => new JobPosting(['status' => JobStatus::Draft, 'overseas_welcome' => true, 'employment_type' => 'full_time', 'section' => 'high'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Slug::unique(JobPosting::class, $data['title'], 'vacancy');
        JobPosting::create($data);

        return to_route('admin.jobs.index')->with('status', 'Vacancy saved.');
    }

    public function edit(JobPosting $job): View
    {
        return view('admin.jobs.form', ['job' => $job]);
    }

    public function update(Request $request, JobPosting $job): RedirectResponse
    {
        $job->update($this->validated($request));

        return to_route('admin.jobs.index')->with('status', 'Vacancy saved.');
    }

    public function destroy(JobPosting $job): RedirectResponse
    {
        if ($job->applications()->exists()) {
            $job->update(['status' => JobStatus::Closed]);

            return to_route('admin.jobs.index')->with('status', 'This vacancy has applications, so it was closed instead of deleted.');
        }
        $job->delete();

        return to_route('admin.jobs.index')->with('status', 'Vacancy deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'section' => ['required', Rule::in(array_merge(array_keys((array) config('hillel.sections')), ['whole']))],
            'employment_type' => ['required', Rule::in(array_keys(JobPosting::TYPES))],
            'summary' => ['required', 'string', 'max:400'],
            'description' => ['required', 'string', 'max:10000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'start_date_text' => ['nullable', 'string', 'max:80'],
            'closes_on' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(JobStatus::class)],
        ]);
        $data['overseas_welcome'] = $request->boolean('overseas_welcome');

        return $data;
    }
}
