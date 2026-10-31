<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StsamsAdmissionApplication;
use App\Models\StsamsApplicationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class DocumentController extends Controller
{
    public function upload(Request $request, StsamsAdmissionApplication $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        $request->validate([
            'type' => ['required', Rule::in(['passport', 'transcript', 'certificate', 'other'])],
            'document' => ['required', 'file', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:5120'],
        ]);
        $file = $request->file('document');
        $path = $file->store("stsams/documents/{$application->id}", 'local');
        if ($path === false) {
            return back()->withErrors(['document' => 'File storage failed. Please retry.']);
        }
        try {
            $application->documents()->create([
                'uploaded_by' => $request->user()->id,
                'type' => $request->input('type'),
                'path' => $path,
                'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                'mime_type' => (string) $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        } catch (Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
        return back()->with('success', 'Document uploaded to private storage.');
    }

    public function download(StsamsAdmissionApplication $application, StsamsApplicationDocument $document): StreamedResponse
    {
        Gate::authorize('view', $application);
        abort_unless($document->application_id === $application->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        $extension = match ($document->mime_type) {
            'application/pdf' => '.pdf', 'image/png' => '.png', 'image/jpeg' => '.jpg', default => '.bin',
        };
        $name = 'stsams-' . $document->id . $extension;
        return Storage::disk('local')->download($document->path, $name, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
