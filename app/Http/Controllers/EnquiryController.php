<?php

namespace App\Http\Controllers;

use App\Enums\EnquiryKind;
use App\Models\Enquiry;
use App\Notifications\OfficeAlert;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admissions enquiries (from /admissions/enquire) and general messages (from /contact). */
class EnquiryController extends Controller
{
    public function create(Request $request): View
    {
        return view('pages.enquire', ['grade' => array_key_exists(input_string($request->query('grade')), grades()) ? input_string($request->query('grade')) : null]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) { // honeypot
            return to_route('enquiries.thanks');
        }

        $kind = $request->input('kind') === EnquiryKind::General->value ? EnquiryKind::General : EnquiryKind::Admissions;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', self::PHONE_REGEX],
            'child_name' => ['nullable', 'string', 'max:120'],
            'grade' => [$kind === EnquiryKind::Admissions ? 'required' : 'nullable', Rule::in(array_keys(grades()))],
            'entry_year' => [$kind === EnquiryKind::Admissions ? 'required' : 'nullable', Rule::in(entry_years())],
            'message' => [$kind === EnquiryKind::General ? 'required' : 'nullable', 'string', 'max:3000'],
            'consent' => ['accepted'],
        ], [
            'phone.regex' => 'Please enter a phone number with its area code, for example 876 555 0123.',
            'consent.accepted' => 'Please confirm we may use these details to reply to you.',
            'grade.required' => 'Please choose the grade your child would join.',
            'entry_year.required' => 'Please choose the school year your child would start.',
        ]);
        unset($data['consent']);
        $data['email'] = strtolower($data['email']);

        $enquiry = Enquiry::create($data + ['kind' => $kind]);

        Notify::office(new OfficeAlert(
            $kind->label().' from '.$enquiry->name,
            ['Email' => $enquiry->email, 'Phone' => $enquiry->phone, 'Child' => $enquiry->child_name, 'Grade' => $enquiry->grade ? grade_label($enquiry->grade).' ('.$enquiry->entry_year.')' : null, 'Message' => $enquiry->message],
            route('admin.enquiries.index'),
            replyTo: $enquiry->email,
        ));

        return to_route('enquiries.thanks')->with('enquiry_kind', $kind->value);
    }

    public function thanks(Request $request): View
    {
        return view('pages.enquiry-thanks', ['kind' => $request->session()->get('enquiry_kind', EnquiryKind::Admissions->value)]);
    }
}
