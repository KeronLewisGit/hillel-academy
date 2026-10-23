<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeBand;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Contact details, fees year, entry years and the published fee table. */
class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['fees' => FeeBand::ordered()->get()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone_main' => ['required', 'string', 'max:40'],
            'phone_admissions' => ['required', 'string', 'max:40'],
            'email_admissions' => ['required', 'email', 'max:255'],
            'email_careers' => ['nullable', 'email', 'max:255'],
            'office_hours' => ['nullable', 'string', 'max:160'],
            'fees_year' => ['required', 'regex:/^\d{4}\/\d{2}$/'],
            'fees_note' => ['nullable', 'string', 'max:1000'],
            'fees_source' => ['nullable', 'string', 'max:300'],
            'entry_years' => ['required', 'string', 'max:60', 'regex:/^\s*\d{4}\/\d{2}(\s*[,;]\s*\d{4}\/\d{2})*\s*$/'],
            'offer_days' => ['required', 'integer', 'min:1', 'max:90'],
            'notify_emails' => ['nullable', 'string', 'max:500'],
        ], [
            'fees_year.regex' => 'Write the fees year like 2026/27.',
            'entry_years.regex' => 'List entry years like 2026/27, 2027/28.',
        ]);
        $data['applications_open'] = $request->boolean('applications_open') ? '1' : '0';
        Setting::saveMany($data);

        return back()->with('status', 'Settings saved.');
    }

    public function storeFee(Request $request): RedirectResponse
    {
        FeeBand::create($this->fee($request));

        return back()->with('status', 'Fee row added.');
    }

    public function updateFee(Request $request, FeeBand $fee): RedirectResponse
    {
        $fee->update($this->fee($request));

        return back()->with('status', 'Fee row saved.');
    }

    public function destroyFee(FeeBand $fee): RedirectResponse
    {
        $fee->delete();

        return back()->with('status', 'Fee row removed.');
    }

    /** @return array<string, mixed> */
    private function fee(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'grades' => ['nullable', 'string', 'max:120'],
            'amount_jmd' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'amount_usd' => ['required', 'integer', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]) + ['sort_order' => 0];
    }
}
