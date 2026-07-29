<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Passport templates replaced the global field mapping, the merchant-defined
     * custom fields and the completeness gate, so the rows those screens wrote are
     * orphaned. They are deleted rather than migrated: a mapping row carries no
     * family, so it cannot be expressed as a template field without guessing which
     * template it belonged to.
     */
    public function up(): void
    {
        DB::table('core_config')
            ->where('code', 'like', 'catalog.product_passport.mapping.%')
            ->orWhereIn('code', [
                'catalog.product_passport.custom_fields',
                'catalog.product_passport.settings.completeness_threshold',
                'catalog.product_passport.settings.support_url',
            ])
            ->delete();
    }

    public function down(): void {}
};
