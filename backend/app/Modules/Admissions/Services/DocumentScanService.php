<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Services;

use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

final class DocumentScanService
{
    public function verify(string $absolutePath): string
    {
        $mode = config('stsams_documents.scan_mode', 'disabled');
        if ($mode === 'disabled' && !app()->isProduction()) {
            return 'unscanned'; // Development ONLY. Never label an unscanned file clean.
        }
        if ($mode !== 'clamav') {
            throw ValidationException::withMessages(['document' => 'Document antivirus scanning is required for production uploads.']);
        }
        $process = new Process([(string) config('stsams_documents.clamav_executable', 'clamscan'), '--no-summary', $absolutePath]);
        $process->setTimeout(35);
        try { $process->run(); } catch (\Throwable) {
            throw ValidationException::withMessages(['document' => 'Document security scanner unavailable. Please contact admissions support.']);
        }
        if ($process->getExitCode() !== 0) {
            throw ValidationException::withMessages(['document' => 'The document could not pass security scanning. Please upload another file.']);
        }
        return 'clean';
    }
}
