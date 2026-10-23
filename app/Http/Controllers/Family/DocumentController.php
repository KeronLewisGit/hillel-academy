<?php

namespace App\Http\Controllers\Family;

use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Notifications\OfficeAlert;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Admissions documents: stored privately, visible only to the family and staff. */
class DocumentController extends Controller
{
    use Concerns;

    public function store(Request $request, Application $application): RedirectResponse
    {
        $this->own($request, $application);
        abort_unless($application->acceptsUploads(), 403, 'This application is closed.');

        $data = $request->validate([
            'type' => ['required', Rule::enum(DocumentType::class)],
            'file' => ['required', 'file', 'max:'.ApplicationDocument::MAX_KB, 'mimes:'.implode(',', ApplicationDocument::TYPES)],
        ], [
            'file.required' => 'Please choose a file to upload.',
            'file.mimes' => 'Please upload a PDF, JPG or PNG file.',
            'file.max' => 'Each file must be 8 MB or smaller.',
            'file.uploaded' => 'Each file must be 8 MB or smaller.',
        ]);

        $file = $request->file('file');
        $type = DocumentType::from($data['type']);
        $application->documents()->create([
            'type' => $type,
            // Random name in a per-application folder on the private disk.
            'path' => $file->store('admissions/'.$application->id, 'local'),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'size' => (int) $file->getSize(),
            'mime' => $file->getMimeType(),
            'status' => 'pending',
        ]);

        if (! $application->isDraft()) {
            $application->events()->create(['type' => 'document', 'title' => $type->label().' uploaded', 'public' => true]);
            Notify::office(new OfficeAlert(
                'Document uploaded for '.$application->reference,
                ['Student' => $application->student_name, 'Document' => $type->label()],
                route('admin.applications.show', $application).'#documents',
                'Review documents',
            ));
        }

        return back()->with('status', $type->label().' uploaded.');
    }

    public function show(Request $request, Application $application, ApplicationDocument $document): StreamedResponse
    {
        $this->own($request, $application);
        abort_unless($document->application_id === $application->id && Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    /** A family can remove a file staff haven't reviewed yet (wrong upload). */
    public function destroy(Request $request, Application $application, ApplicationDocument $document): RedirectResponse
    {
        $this->own($request, $application);
        abort_unless($document->application_id === $application->id, 404);
        abort_unless($document->status === 'pending', 403, 'Reviewed documents can’t be removed.');

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return back()->with('status', 'File removed.');
    }
}
