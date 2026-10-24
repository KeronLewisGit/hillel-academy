<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.templates.index', ['templates' => MessageTemplate::orderByRaw('message_templates.key is null')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.templates.form', ['template' => new MessageTemplate]);
    }

    public function store(Request $request): RedirectResponse
    {
        MessageTemplate::create($this->validated($request));

        return to_route('admin.templates.index')->with('status', 'Template added.');
    }

    public function edit(MessageTemplate $template): View
    {
        return view('admin.templates.form', ['template' => $template]);
    }

    public function update(Request $request, MessageTemplate $template): RedirectResponse
    {
        $template->update($this->validated($request));

        return to_route('admin.templates.index')->with('status', 'Template saved.');
    }

    public function destroy(MessageTemplate $template): RedirectResponse
    {
        if ($template->key) {
            return back()->withErrors(['template' => 'Automatic templates can be edited but not deleted.']);
        }
        $template->delete();

        return to_route('admin.templates.index')->with('status', 'Template deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
        ]);
    }
}
