<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TeacherApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Models\TeacherApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $status = TeacherApplicationStatus::tryFrom(input_string($request->query('status')));
        $job = ctype_digit(input_string($request->query('job'))) ? (int) $request->query('job') : null;

        return view('admin.teachers.index', [
            'applications' => TeacherApplication::with('jobPosting')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($job, fn ($q) => $q->where('job_posting_id', $job))
                ->latest()->paginate(30)->withQueryString(),
            'status' => $status,
            'job' => $job,
            'jobs' => JobPosting::orderBy('title')->get(['id', 'title']),
            'counts' => TeacherApplication::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(TeacherApplication $teacher): View
    {
        return view('admin.teachers.show', ['a' => $teacher->load('jobPosting')]);
    }

    public function update(Request $request, TeacherApplication $teacher): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::enum(TeacherApplicationStatus::class)],
            'staff_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);
        if (isset($data['status'])) {
            $teacher->moveTo(TeacherApplicationStatus::from($data['status']));
        }
        if (array_key_exists('staff_notes', $data)) {
            $teacher->staff_notes = $data['staff_notes'];
        }
        $teacher->save();

        return back()->with('status', $teacher->reference.' saved.');
    }

    /** CVs are private: streamed to signed-in administrators only. */
    public function cv(TeacherApplication $teacher): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($teacher->cv_path), 404);

        return Storage::disk('local')->download($teacher->cv_path, Str::slug($teacher->full_name).'-cv.'.pathinfo($teacher->cv_path, PATHINFO_EXTENSION));
    }
}
