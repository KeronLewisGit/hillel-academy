<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\User;
use App\Services\Admissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function show(ApplicationDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        // Shown inline so staff can read a scan without saving it to the office PC.
        return Storage::disk('local')->response($document->path, $document->original_name, ['Content-Disposition' => 'inline; filename="'.addslashes($document->original_name).'"']);
    }

    public function update(Request $request, ApplicationDocument $document, Admissions $admissions): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'rejected'])],
            'review_note' => ['nullable', 'required_if:status,rejected', 'string', 'max:500'],
        ], ['review_note.required_if' => 'Tell the family why the document needs to be sent again.']);
        /** @var User $user */
        $user = $request->user();
        $admissions->reviewDocument($document, $data['status'], $data['review_note'] ?? null, $user);

        return back()->with('status', $document->type->label().($data['status'] === 'accepted' ? ' accepted.' : ' returned to the family.'));
    }
}
