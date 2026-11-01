<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StsamsAdmissionApplication;
use App\Models\StsamsApplicationDocument;
use App\Modules\Admissions\Services\DocumentRequirements;
use App\Modules\Admissions\Services\DocumentScanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DocumentController extends Controller
{
    public function upload(Request $request, StsamsAdmissionApplication $application, DocumentRequirements $requirements, DocumentScanService $scanner): RedirectResponse
    {
        Gate::authorize('update', $application);
        $specs = $requirements->forProgramme($application->programme);
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys($specs))],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $type = $data['type'];
        if ($specs[$type]['requirement'] === 'not_applicable') {
            throw ValidationException::withMessages(['type' => 'This document is not applicable to your programme.']);
        }
        $file = $request->file('document');
        $mime = (string) $file->getMimeType();
        if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)
            || (($specs[$type]['images_only'] ?? false) && !in_array($mime, ['image/jpeg', 'image/png'], true))) {
            throw ValidationException::withMessages(['document' => 'This slot accepts only its specified PDF or image formats.']);
        }
        $path = $file->store("stsams/documents/{$application->id}", 'local');
        if ($path === false) { throw ValidationException::withMessages(['document' => 'Could not store this file.']); }
        try {
            $scan = $scanner->verify(Storage::disk('local')->path($path));
            $oldPaths = DB::transaction(function () use ($application, $request, $type, $path, $mime, $file, $scan): array {
                $locked = StsamsAdmissionApplication::query()->lockForUpdate()->findOrFail($application->id);
                if ($locked->status !== 'draft' || $locked->user_id !== $request->user()->id) {
                    throw ValidationException::withMessages(['document' => 'This application is no longer editable.']);
                }
                $previous = $locked->documents()->where('type', $type)->get();
                $locked->documents()->create([
                    'uploaded_by' => $request->user()->id,
                    'type' => $type,
                    'path' => $path,
                    'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                    'mime_type' => $mime,
                    'size_bytes' => $file->getSize(),
                    'scan_status' => $scan,
                    'sha256' => hash_file('sha256', Storage::disk('local')->path($path)),
                ]);
                if ($previous->isNotEmpty()) {
                    $locked->documents()->whereIn('id', $previous->pluck('id')->all())->delete();
                }
                return $previous->pluck('path')->all();
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
        Storage::disk('local')->delete($oldPaths);
        return back()->with('success', 'Document uploaded and saved. You can replace it while the application is a draft.');
    }

    public function remove(Request $request, StsamsAdmissionApplication $application, StsamsApplicationDocument $document): RedirectResponse
    {
        Gate::authorize('update', $application);
        abort_unless($document->application_id === $application->id, 404);
        $path = DB::transaction(function () use ($application, $document, $request): string {
            $locked = StsamsAdmissionApplication::query()->lockForUpdate()->findOrFail($application->id);
            abort_unless($locked->status === 'draft' && $locked->user_id === $request->user()->id, 403);
            $document->delete();
            return $document->path;
        });
        Storage::disk('local')->delete($path);
        return back()->with('success', 'Document removed.');
    }

    public function download(StsamsAdmissionApplication $application, StsamsApplicationDocument $document): StreamedResponse
    {
        return $this->deliver($application, $document, 'attachment');
    }

    public function preview(StsamsAdmissionApplication $application, StsamsApplicationDocument $document): StreamedResponse
    {
        return $this->deliver($application, $document, 'inline');
    }

    private function deliver(StsamsAdmissionApplication $application, StsamsApplicationDocument $document, string $disposition): StreamedResponse
    {
        Gate::authorize('view', $application);
        abort_unless($document->application_id === $application->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        // Never serve original user-controlled names as paths or inline HTML.
        $extension = match ($document->mime_type) {
            'application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', default => null,
        };
        abort_if($extension === null, 415);
        return Storage::disk('local')->response($document->path, 'stsams-document-'.$document->id.'.'.$extension, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "sandbox; default-src 'none'; style-src 'unsafe-inline'",
            'X-Frame-Options' => 'SAMEORIGIN',
        ], $disposition);
    }
}
