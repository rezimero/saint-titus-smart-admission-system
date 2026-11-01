<?php

declare(strict_types=1);

return [
    // Programme overrides are stored in stsams_document_requirements; never assume regulatory approval.
    'max_kilobytes' => 5120,
    'types' => [
        'passport' => ['label' => 'Recent passport photograph', 'group' => 'Personal documents', 'default' => 'required', 'images_only' => true],
        'birth_certificate' => ['label' => 'Birth certificate / age declaration', 'group' => 'Personal documents', 'default' => 'required'],
        'origin_certificate' => ['label' => 'Certificate of local government / state of origin', 'group' => 'Personal documents', 'default' => 'required'],
        'nin_slip' => ['label' => 'NIN slip', 'group' => 'Personal documents', 'default' => 'optional'],
        'olevel_first' => ['label' => "O’Level result 1 (WAEC / NECO / NABTEB)", 'group' => 'Academic documents', 'default' => 'required'],
        'olevel_second' => ['label' => "O’Level result 2 (if applicable)", 'group' => 'Academic documents', 'default' => 'optional'],
        'jamb_result' => ['label' => 'JAMB result slip (where applicable)', 'group' => 'Academic documents', 'default' => 'conditional'],
        'jamb_letter' => ['label' => 'JAMB admission letter (where applicable)', 'group' => 'Academic documents', 'default' => 'conditional'],
        'prior_health_cert' => ['label' => 'Previous health qualification (if applicable)', 'group' => 'Other supporting documents', 'default' => 'conditional'],
        'professional_licence' => ['label' => 'Professional registration / licence (if applicable)', 'group' => 'Other supporting documents', 'default' => 'conditional'],
    ],
    'admission_tracks' => [
        'general' => 'Other programme / general',
        'chew' => 'Community Health Extension Worker (CHEW)',
        'nd_community_health' => 'ND Community Health',
        'nd_public_health' => 'ND Public Health',
    ],
    // Production uploads require a malware scanner. Locally, documents are explicitly marked unscanned.
    'scan_mode' => env('STSAMS_DOCUMENT_SCAN_MODE', 'disabled'), // disabled | clamav
    'clamav_executable' => env('STSAMS_CLAMSCAN_PATH', 'clamscan'),
];
