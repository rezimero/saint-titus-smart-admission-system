<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Services;

use App\Models\StsamsAdmissionApplication;
use App\Models\StsamsProgramme;

final class DocumentRequirements
{
    public function forProgramme(StsamsProgramme $programme): array
    {
        $overrides = $programme->documentRequirements()->pluck('requirement', 'document_type');
        $specs = config('stsams_documents.types', []);
        foreach ($specs as $key => &$spec) {
            $spec['key'] = $key;
            $spec['requirement'] = $overrides->get($key, $spec['default']);
        }
        unset($spec);
        return $specs;
    }

    public function missing(StsamsAdmissionApplication $application): array
    {
        $requirements = $this->forProgramme($application->programme);
        $present = $application->documents()->pluck('type')->all();
        $missing = [];
        foreach ($requirements as $type => $spec) {
            if ($spec['requirement'] === 'required' && !in_array($type, $present, true)) {
                $missing[] = $spec['label'];
            }
        }
        return $missing;
    }
}
