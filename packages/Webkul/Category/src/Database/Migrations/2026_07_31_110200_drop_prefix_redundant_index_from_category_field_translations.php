<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Covered by the wider `(category_field_id, locale)` unique index. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('category_field_translations', 'category_field_translations_category_field_id_index')) {
            return;
        }

        Schema::table('category_field_translations', function (Blueprint $table): void {
            $table->dropIndex('category_field_translations_category_field_id_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasIndex('category_field_translations', 'category_field_translations_category_field_id_index')) {
            return;
        }

        Schema::table('category_field_translations', function (Blueprint $table): void {
            $table->index('category_field_id');
        });
    }
};
