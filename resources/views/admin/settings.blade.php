<x-layouts.admin title="Settings & fees">
    <div class="grid gap-5 2xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="a-card grid content-start gap-3 p-5">@csrf @method('PUT')
            <h2 class="font-semibold">Contact details</h2>
            @foreach (['phone_main' => 'Main office phone', 'phone_admissions' => 'Admissions phone', 'email_admissions' => 'Admissions email', 'email_careers' => 'Careers email (optional)', 'office_hours' => 'Office hours (optional)'] as $k => $label)
                <label class="text-sm font-medium" for="{{ $k }}">{{ $label }}</label><input id="{{ $k }}" name="{{ $k }}" class="a-input" value="{{ old($k, setting($k)) }}">
            @endforeach
            <h2 class="mt-4 font-semibold">Admissions</h2>
            <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="applications_open" value="1" @checked(setting('applications_open')) class="h-4 w-4"> Accept new online applications</label>
            <label class="text-sm font-medium" for="entry_years">Entry years families can apply for</label><input id="entry_years" name="entry_years" class="a-input" value="{{ old('entry_years', setting('entry_years')) }}" placeholder="2026/27, 2027/28">
            <label class="text-sm font-medium" for="offer_days">Days a family has to accept an offer</label><input id="offer_days" type="number" min="1" max="90" name="offer_days" class="a-input" value="{{ old('offer_days', setting('offer_days')) }}">
            <label class="text-sm font-medium" for="notify_emails">Email new enquiries and applications to <span class="font-normal text-ink-3">(comma separated)</span></label><input id="notify_emails" name="notify_emails" class="a-input" value="{{ old('notify_emails', setting('notify_emails')) }}">
            <h2 class="mt-4 font-semibold">Fees wording</h2>
            <label class="text-sm font-medium" for="fees_year">Fees are for school year</label><input id="fees_year" name="fees_year" class="a-input" value="{{ old('fees_year', setting('fees_year')) }}">
            <label class="text-sm font-medium" for="fees_note">Note under the fee table</label><textarea id="fees_note" name="fees_note" rows="3" class="a-input">{{ old('fees_note', setting('fees_note')) }}</textarea>
            <label class="text-sm font-medium" for="fees_source">Source line</label><input id="fees_source" name="fees_source" class="a-input" value="{{ old('fees_source', setting('fees_source')) }}">
            <button class="a-btn a-btn-primary mt-2 justify-self-start">Save settings</button>
        </form>

        <section class="a-card content-start p-5" aria-labelledby="fees-title">
            <h2 id="fees-title" class="font-semibold">Fee table ({{ setting('fees_year') }})</h2>
            <p class="mt-1 text-sm text-ink-2">Shown on the Admissions page. Tuition per term, in whole dollars: Jamaican students in J$, international students in US$.</p>
            <div class="mt-4 grid gap-3">
                @foreach ($fees as $fee)
                    <form method="POST" action="{{ route('admin.settings.fees.update', $fee) }}" class="grid gap-2 rounded-lg border border-line p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,8rem)_minmax(0,6rem)_minmax(0,4.5rem)] [&>input]:min-w-0">@csrf @method('PUT')
                        <label class="sr-only" for="fl-{{ $fee->id }}">Label</label><input id="fl-{{ $fee->id }}" name="label" class="a-input" value="{{ $fee->label }}" required>
                        <label class="sr-only" for="fg-{{ $fee->id }}">Grades</label><input id="fg-{{ $fee->id }}" name="grades" class="a-input" value="{{ $fee->grades }}" placeholder="Grades">
                        <label class="sr-only" for="fj-{{ $fee->id }}">J$ per term</label><input id="fj-{{ $fee->id }}" type="number" name="amount_jmd" class="a-input num" value="{{ $fee->amount_jmd }}" placeholder="J$">
                        <label class="sr-only" for="fa-{{ $fee->id }}">US$ per term</label><input id="fa-{{ $fee->id }}" type="number" name="amount_usd" class="a-input num" value="{{ $fee->amount_usd }}" required>
                        <label class="sr-only" for="fs-{{ $fee->id }}">Order</label><input id="fs-{{ $fee->id }}" type="number" name="sort_order" class="a-input num" value="{{ $fee->sort_order }}">
                        <label class="sr-only" for="fn-{{ $fee->id }}">Note</label><input id="fn-{{ $fee->id }}" name="note" class="a-input sm:col-span-4" value="{{ $fee->note }}" placeholder="Note (optional)">
                        <span class="flex gap-1"><button class="a-btn a-btn-ghost flex-1 px-2">Save</button></span>
                    </form>
                    <form method="POST" action="{{ route('admin.settings.fees.destroy', $fee) }}" class="-mt-2 text-right" onsubmit="return confirm('Remove this fee row?')">@csrf @method('DELETE')<button class="inline-flex min-h-11 items-center text-sm text-[color:var(--critical-text)] underline">Remove “{{ $fee->label }}”</button></form>
                @endforeach
            </div>
            <form method="POST" action="{{ route('admin.settings.fees.store') }}" class="mt-5 grid gap-2 rounded-lg bg-surface-2 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,8rem)_minmax(0,6rem)] [&>input]:min-w-0">@csrf
                <p class="text-sm font-semibold sm:col-span-4">Add a row</p>
                <label class="sr-only" for="nl">Label</label><input id="nl" name="label" class="a-input" placeholder="e.g. Grades 1–6" required>
                <label class="sr-only" for="ng">Grades</label><input id="ng" name="grades" class="a-input" placeholder="Grades covered">
                <label class="sr-only" for="nj">J$ per term</label><input id="nj" type="number" name="amount_jmd" class="a-input num" placeholder="J$ / term">
                <label class="sr-only" for="na">US$ per term</label><input id="na" type="number" name="amount_usd" class="a-input num" placeholder="US$ / term" required>
                <button class="a-btn a-btn-primary justify-self-start">Add</button>
            </form>
        </section>
    </div>
</x-layouts.admin>
