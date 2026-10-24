<?php

namespace App\Http\Controllers;

use App\Enums\EnquiryKind;
use App\Mail\FamilyMessage;
use App\Models\Enquiry;
use App\Models\TourSlot;
use App\Notifications\OfficeAlert;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Campus tours: families pick one of the office's published tour times. */
class VisitController extends Controller
{
    public function create(): View
    {
        return view('pages.visit', ['slots' => TourSlot::available()->withCount('bookings')->limit(24)->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            return to_route('visit.booked');
        }

        $data = $request->validate([
            'tour_slot_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:40', self::PHONE_REGEX],
            'party_size' => ['required', 'integer', 'min:1', 'max:6'],
            'child_name' => ['nullable', 'string', 'max:120'],
            'grade' => ['nullable', Rule::in(array_keys(grades()))],
            'entry_year' => ['nullable', Rule::in(entry_years())],
            'message' => ['nullable', 'string', 'max:1500'],
            'consent' => ['accepted'],
        ], [
            'tour_slot_id.required' => 'Please choose a tour time.',
            'phone.regex' => 'Please enter a phone number with its area code.',
            'consent.accepted' => 'Please confirm we may use these details to arrange your visit.',
        ]);
        unset($data['consent']);
        $data['email'] = strtolower($data['email']);

        $enquiry = DB::transaction(function () use ($data) {
            $slot = TourSlot::query()->lockForUpdate()->withCount('bookings')->find($data['tour_slot_id']);
            if (! $slot || $slot->starts_at->lte(now()->addDay()) || $slot->placesLeft() < 1) {
                throw ValidationException::withMessages(['tour_slot_id' => 'That tour has just filled up. Please choose another time.']);
            }

            return Enquiry::create($data + ['kind' => EnquiryKind::Tour]);
        });
        $enquiry->load('tourSlot');

        Notify::office(new OfficeAlert(
            'Campus visit booked: '.$enquiry->tourSlot?->starts_at->format('D j M, g:i a'),
            ['Family' => $enquiry->name, 'Email' => $enquiry->email, 'Phone' => $enquiry->phone, 'People' => (string) $enquiry->party_size, 'Child' => $enquiry->child_name, 'Grade' => $enquiry->grade ? grade_label($enquiry->grade) : null, 'Note' => $enquiry->message],
            route('admin.tours.index'),
            replyTo: $enquiry->email,
        ));

        // Confirmation to the family, so they have the time in their inbox.
        $when = $enquiry->tourSlot?->starts_at->format('l j F Y \a\t g:i a');
        try {
            Mail::to($enquiry->email)->send(new FamilyMessage(
                'Your visit to Hillel Academy: '.$when,
                "Dear {$enquiry->name},\n\nThank you for booking a campus visit. We look forward to seeing you on {$when} at ".hillel('address.street').', '.hillel('address.city').".\n\nIf your plans change, please reply to this email or call Admissions on ".format_phone((string) setting('phone_admissions')).".\n\nWith best wishes,\nThe Admissions Office",
            ));
        } catch (\Throwable $e) {
            report($e);
        }

        $request->session()->put('tour_booked', $enquiry->tourSlot?->starts_at->format('l j F Y \a\t g:i a'));

        return to_route('visit.booked');
    }

    public function booked(Request $request): View|RedirectResponse
    {
        $when = $request->session()->get('tour_booked');

        return view('pages.visit-booked', ['when' => $when]);
    }
}
