<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdmissionStatus;
use App\Enums\EnquiryKind;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AssessmentSlot;
use App\Models\Enquiry;
use App\Models\TeacherApplication;
use App\Models\TourSlot;
use App\Models\User;
use App\Services\Admissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const int CHART_WEEKS = 12;

    public function __invoke(Request $request, Admissions $admissions): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->canAdmissions()) {
            return redirect($user->homeRoute());
        }

        $year = $this->year($request);
        $awaitingDocs = Application::awaitingDocumentReview();
        $oldestPending = ApplicationDocument::query()->where('status', 'pending')
            ->whereHas('application', fn ($q) => $q->where('status', '!=', AdmissionStatus::Draft))
            ->min('created_at');

        // Submitted applications per week, oldest first.
        $start = now()->startOfWeek()->subWeeks(self::CHART_WEEKS - 1);
        $submitted = Application::query()->whereNotNull('submitted_at')->where('submitted_at', '>=', $start)->pluck('submitted_at')
            ->countBy(fn (Carbon $d) => $d->copy()->startOfWeek()->toDateString());
        $weekly = collect(range(0, self::CHART_WEEKS - 1))->map(function (int $i) use ($start, $submitted) {
            $week = $start->copy()->addWeeks($i);

            return ['date' => $week, 'count' => (int) ($submitted[$week->toDateString()] ?? 0)];
        });

        $summary = collect($admissions->summary($year));

        return view('admin.dashboard', [
            'year' => $year,
            'awaitingDocs' => $awaitingDocs->count(),
            'oldestPending' => $oldestPending ? Carbon::parse($oldestPending) : null,
            'tiles' => [
                ['label' => 'New enquiries waiting', 'value' => Enquiry::open()->where('kind', EnquiryKind::Admissions)->count(), 'note' => ($general = Enquiry::open()->where('kind', EnquiryKind::General)->count()).' general '.str('message')->plural($general), 'href' => route('admin.enquiries.index')],
                ['label' => 'Ready to book an assessment', 'value' => Application::where('status', AdmissionStatus::DocsComplete)->count(), 'note' => ($sessions = AssessmentSlot::upcoming()->count()).' upcoming '.str('session')->plural($sessions), 'href' => route('admin.applications.index', ['status' => 'docs_complete'])],
                ['label' => 'Assessed, awaiting a decision', 'value' => Application::where('status', AdmissionStatus::Assessed)->count(), 'note' => 'Offer, waitlist or decline', 'href' => route('admin.applications.index', ['status' => 'assessed'])],
                ['label' => 'Offers awaiting a reply', 'value' => Application::where('status', AdmissionStatus::Offered)->count(), 'note' => Application::where('status', AdmissionStatus::Offered)->where('offer_expires_at', '<', now()->addDays(3))->count().' expire within 3 days', 'href' => route('admin.applications.index', ['status' => 'offered'])],
            ],
            'weekly' => $weekly,
            'weeklyMax' => max(1, (int) $weekly->max('count')),
            'pipeline' => collect(AdmissionStatus::board())->map(fn (AdmissionStatus $s) => ['status' => $s, 'count' => Application::where('status', $s)->where('entry_year', $year)->count()]),
            'drafts' => Application::where('status', AdmissionStatus::Draft)->count(),
            'tight' => $summary->filter(fn (array $r) => $r['seats'] > 0 && ($r['left'] === 0 || $r['waitlist'] > 0))->values(),
            'freed' => $summary->filter(fn (array $r) => $r['left'] > 0 && $r['waitlist'] > 0)->values(),
            'nextAssessments' => AssessmentSlot::upcoming()->withCount('bookings')->limit(4)->get(),
            'nextTours' => TourSlot::upcoming()->withCount('bookings')->limit(4)->get(),
            'recent' => Application::submitted()->with('family', 'documents')->latest('submitted_at')->limit(6)->get(),
            'teachers' => $user->isAdmin() ? TeacherApplication::awaitingReview()->count() : null,
        ]);
    }

    private function year(Request $request): string
    {
        $years = entry_years();
        $asked = input_string($request->query('year'));

        return in_array($asked, $years, true) ? $asked : (string) ($years[0] ?? date('Y').'/'.substr((string) (date('Y') + 1), 2));
    }
}
