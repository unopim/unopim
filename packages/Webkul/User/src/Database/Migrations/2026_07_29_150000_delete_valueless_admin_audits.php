<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('audits')
            ->where('auditable_type', 'like', '%\\\\Admin')
            ->whereIn(DB::raw('coalesce(old_values, \'\')'), ['', '[]', '{}'])
            ->whereIn(DB::raw('coalesce(new_values, \'\')'), ['', '[]', '{}'])
            ->delete();
    }

    public function down(): void {}
};
