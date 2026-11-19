<?php

// Idempotent E2E prereq: an export job whose code contains a space. The create
// form has rejected such codes since #1182, so only installs older than that
// rule still carry them, and export-filter-persistence.spec.js checks that
// those jobs remain editable. Run once per env:
//   php artisan tinker tests/e2e-pw/scripts/seed-legacy-export-job.php

use Illuminate\Support\Facades\DB;

DB::table('job_instances')->updateOrInsert(
    ['code' => 'legacy category export'],
    [
        'entity_type'         => 'categories',
        'type'                => 'export',
        'action'              => 'fetch',
        'validation_strategy' => '',
        'allowed_errors'      => 0,
        'field_separator'     => ',',
        'file_path'           => '',
        'filters'             => json_encode(['file_format' => 'Csv']),
        'created_at'          => now(),
        'updated_at'          => now(),
    ],
);

echo "legacy export job ready\n";
