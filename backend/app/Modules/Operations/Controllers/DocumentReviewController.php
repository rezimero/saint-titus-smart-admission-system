<?php

declare(strict_types=1);

namespace App\Modules\Operations\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StsamsAdmissionApplication;
use App\Models\StsamsApplicationDocument;
use App\Modules\Operations\Services\DocumentReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class DocumentReviewController extends Controller
{
    public function decide(Request $request, StsamsAdmissionApplication $application, StsamsApplicationDocument $document, DocumentReviewService $service): RedirectResponse
    {
        Gate::authorize('viewAny', StsamsAdmissionApplication::class);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['verified', 'rejected'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $service->decide($application, $document, $request->user(), $data['decision'], $data['note'] ?? null);
        return back()->with('success', 'Document review decision recorded in the audit trail.');
    }
}
