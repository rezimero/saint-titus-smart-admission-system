<?php

declare(strict_types=1);

namespace App\Modules\Operations\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StsamsOperationsAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class OperationsAuditController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('reports.audit');
        $filter = $request->query('subject');
        $query = StsamsOperationsAudit::with('actor')->latest('id');
        if (in_array($filter, ['application', 'document', 'website_content', 'website_post', 'website_media', 'website_inquiry'], true)) {
            $query->where('subject_type', $filter);
        }
        return view('stsams.admin.operations-audit', [
            'audits' => $query->paginate(35)->withQueryString(),
        ]);
    }
}
