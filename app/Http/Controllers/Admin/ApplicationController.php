<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AssessmentSlot;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\Admissions;
use App\Services\FamilyNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    private const array SORTS = ['submitted_at', 'student_last_name', 'grade', 'status', 'status_changed_at'];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $sort = in_array($request->query('sort'), self::SORTS, true) ? (string) $request->query('sort') : 'submitted_at';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $counts = Application::filter(array_merge($filters, ['status' => null]))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.applications.index', [
            'applications' => Application::with('family', 'documents')->filter($filters)
                ->orderBy($sort, $dir)->orderByDesc('id')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'sort' => $sort,
            'dir' => $dir,
            'counts' => $counts,
        ]);
    }

    public function board(Request $request): View
    {
        $filters = $this->filters($request);
        $apps = Application::with('documents', 'assessmentSlot')->filter(array_merge($filters, ['status' => null]))
            ->whereIn('status', AdmissionStatus::board())->orderByDesc('status_changed_at')->limit(600)->get();

        return view('admin.applications.board', [
            'columns' => collect(AdmissionStatus::board())->mapWithKeys(fn (AdmissionStatus $s) => [$s->value => $apps->where('status', $s)->values()]),
            'filters' => $filters,
            'closed' => Application::filter(array_merge($filters, ['status' => null]))->whereIn('status', [AdmissionStatus::Declined, AdmissionStatus::Withdrawn])->count(),
            'drafts' => Application::filter(array_merge($filters, ['status' => null]))->where('status', AdmissionStatus::Draft)->count(),
        ]);
    }

    public function show(Application $application, Admissions $admissions): View
    {
        $application->load('family', 'documents', 'events.user', 'assessmentSlot', 'assignee');

        return view('admin.applications.show', [
            'application' => $application,
            'siblings' => $application->family->applications()->where('id', '!=', $application->id)->get(),
            'seatsLeft' => $admissions->seatsLeft($application->entry_year, $application->grade),
            'seats' => $admissions->seats($application->entry_year, $application->grade),
            'position' => $application->waitlistPosition(),
            'slots' => AssessmentSlot::bookableFor($application->grade)->limit(30)->get(),
            'templates' => MessageTemplate::orderByRaw('key is not null')->orderBy('name')->get(),
            'staff' => User::where('active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $data = $request->validate([
            'staff_notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'assessment_notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'assigned_to' => ['sometimes', 'nullable', Rule::exists('users', 'id')],
        ]);
        $application->forceFill($data)->save();

        return back()->with('status', $application->reference.' saved.');
    }

    /** Move along the pipeline: from the board (JSON) or the decision panel (form). */
    public function status(Request $request, Application $application, Admissions $admissions): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(AdmissionStatus::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        /** @var User $user */
        $user = $request->user();

        try {
            $admissions->move($application, AdmissionStatus::from($data['status']), $user, $data['note'] ?? null);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->validator->errors()->first()], 422);
            }
            throw $e;
        }

        if ($request->expectsJson()) {
            return response()->json(['label' => $application->status->label()]);
        }

        return back()->with('status', $application->student_name.' moved to '.$application->status->label().'.');
    }

    public function message(Request $request, Application $application, FamilyNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
        ]);
        /** @var User $user */
        $user = $request->user();
        $sent = $notifier->custom($application, $data['subject'], $data['body'], $user);

        return back()->with('status', $sent ? 'Email sent to the family.' : 'The message was saved, but the email could not be sent. Check the mail settings.');
    }

    public function note(Request $request, Application $application): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $application->events()->create(['user_id' => $request->user()?->id, 'type' => 'note', 'body' => $data['body'], 'public' => false]);

        return back()->with('status', 'Note added.');
    }

    /** Staff book (or move) an assessment for a family that phoned in. */
    public function assessment(Request $request, Application $application, Admissions $admissions): RedirectResponse
    {
        $data = $request->validate(['slot' => ['required', 'integer']]);
        /** @var User $user */
        $user = $request->user();
        $slot = $admissions->bookAssessment($application, (int) $data['slot'], $user);

        return back()->with('status', 'Assessment booked for '.$slot->starts_at->format('D j M, g:i a').'.');
    }

    /** Email the family a fresh portal link (they lost theirs, or staff created the record). */
    public function invite(Application $application): RedirectResponse
    {
        $family = $application->family;

        return back()->with('status', $family->sendLoginLink()['sent']
            ? 'Portal link emailed to '.$family->email.'.'
            : 'The link could not be emailed. Check the mail settings.');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Application::with('family', 'documents', 'assessmentSlot')->filter($this->filters($request));

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // byte-order mark so Excel reads accents correctly
            fputcsv($out, ['Reference', 'Status', 'Submitted', 'Student first name', 'Student last name', 'Date of birth', 'Nationality', 'Grade', 'Entry year', 'Start', 'Previous school', 'Parent/guardian', 'Relationship', 'Email', 'Phone', 'Second contact', 'Second contact phone', 'Documents missing', 'Assessment', 'Offer expires', 'Waitlisted'], escape: '');
            foreach ($query->lazyByIdDesc(500) as $a) {
                /** @var Application $a */
                fputcsv($out, array_map(self::csvSafe(...), [
                    $a->reference, $a->status->label(), $a->submitted_at?->format('Y-m-d H:i'), $a->student_first_name, $a->student_last_name,
                    $a->date_of_birth?->format('Y-m-d'), $a->nationality, $a->grade_label, $a->entry_year, $a->entry_term, $a->previous_school,
                    $a->guardian_name, $a->guardian_relationship, $a->guardian_email ?: $a->family->email, $a->guardian_phone,
                    $a->guardian2_name, $a->guardian2_phone, count($a->missingDocuments()),
                    $a->assessmentSlot?->starts_at->format('Y-m-d H:i'), $a->offer_expires_at?->format('Y-m-d'), $a->waitlisted_at?->format('Y-m-d'),
                ]), escape: '');
            }
            fclose($out);
        }, 'hillel-applications-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Text starting with = + - @ would run as a formula in Excel, so it is prefixed with a quote. */
    private static function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        $status = AdmissionStatus::tryFrom(input_string($request->query('status')))?->value;
        $grade = array_key_exists(input_string($request->query('grade')), grades()) ? input_string($request->query('grade')) : null;
        $year = in_array(input_string($request->query('year')), entry_years(), true) ? input_string($request->query('year')) : null;
        $docs = $request->query('docs') === '1' ? '1' : null;
        $q = input_string($request->query('q')) ?: null;

        return compact('status', 'grade', 'year', 'docs', 'q');
    }
}
