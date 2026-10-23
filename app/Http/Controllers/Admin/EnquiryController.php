<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryKind;
use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $kind = EnquiryKind::tryFrom(input_string($request->query('kind')));
        $show = $request->query('show') === 'all' ? 'all' : 'open';

        return view('admin.enquiries', [
            'enquiries' => Enquiry::with('tourSlot')
                ->when($kind, fn ($q) => $q->where('kind', $kind))
                ->when($show === 'open', fn ($q) => $q->open())
                ->latest()->paginate(30)->withQueryString(),
            'kind' => $kind,
            'show' => $show,
            'counts' => Enquiry::open()->selectRaw('kind, count(*) as total')->groupBy('kind')->pluck('total', 'kind'),
        ]);
    }

    public function update(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $handled = $request->boolean('handled');
        $enquiry->forceFill(['handled_at' => $handled ? now() : null, 'handled_by' => $handled ? $request->user()?->id : null])->save();

        return back()->with('status', $handled ? 'Marked as handled.' : 'Reopened.');
    }
}
