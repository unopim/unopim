<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the display section of an association type field.
     *
     * The form control that set it was removed, so every row holds the default and
     * nothing reads the column. Reversing restores that default rather than any
     * stored placement, which is all the column ever carried.
     */
    public function up(): void
    {
        if (! Schema::hasTable('association_type_fields')) {
            return;
        }

        if (! Schema::hasColumn('association_type_fields', 'section')) {
            return;
        }

        Schema::table('association_type_fields', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('association_type_fields')) {
            return;
        }

        if (Schema::hasColumn('association_type_fields', 'section')) {
            return;
        }

        Schema::table('association_type_fields', function (Blueprint $table) {
            $table->string('section', 10)->default('left');
        });
    }
};
