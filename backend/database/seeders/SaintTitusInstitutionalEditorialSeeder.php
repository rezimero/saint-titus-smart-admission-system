<?php

namespace Database\Seeders;

use App\Models\InstitutionPost;
use Illuminate\Database\Seeder;

/**
 * Creates an editable but UNPUBLISHED accreditation newsroom draft.
 * Does not alter existing posts or automatically grant admissions access.
 */
class SaintTitusInstitutionalEditorialSeeder extends Seeder
{
    public function run(): void
    {
        InstitutionPost::query()->firstOrCreate(
            ['slug' => 'nbte-accreditation-nd-community-health-chew'],
            [
                'category' => 'news',
                'title' => 'Saint Titus College Secures NBTE Accreditation for ND in CHEW',
                'summary' => 'Saint Titus College of Health Sciences and Technology, Benin City, has received NBTE accreditation to run a National Diploma programme in Community Health Extension Worker (CHEW).',
                'body' => 'Saint Titus College of Health Sciences and Technology, Benin City, announces a milestone in its development: accreditation from the National Board for Technical Education (NBTE) to run a National Diploma (ND) programme in Community Health Extension Worker (CHEW).' . PHP_EOL . PHP_EOL . 'The College currently runs JCHEW and CHEW programmes under the regulatory supervision of the Community Health Practitioners Registration Board of Nigeria (CHPRBN). This new NBTE accreditation supports the College’s commitment to developing competent, ethical and community-oriented healthcare practitioners.' . PHP_EOL . PHP_EOL . 'An official application timetable, entry requirements and any admission fees will be published separately. This accreditation announcement does not itself mean that applications for the ND programme are open.',
                'is_published' => false,
                'published_at' => null,
                'created_by' => null,
            ]
        );
    }
}
