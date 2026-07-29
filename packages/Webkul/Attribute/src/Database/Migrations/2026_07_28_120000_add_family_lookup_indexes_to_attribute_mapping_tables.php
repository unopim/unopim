<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every "attributes of this family" query walks families -> family groups ->
 * group mappings, but neither hop was indexed on the column it is joined by:
 * `attribute_group_mappings` is keyed (attribute_id, attribute_family_group_id),
 * which cannot serve a lookup by the second column alone, and the family id on
 * `attribute_family_group_mappings` carried only a foreign key, which PostgreSQL
 * does not index. Both sides therefore fell back to scanning every mapping row
 * in the catalogue to answer a question about a single family.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attribute_family_group_mappings', function (Blueprint $table): void {
            $table->index('attribute_family_id', 'afgm_family_idx');
        });

        Schema::table('attribute_group_mappings', function (Blueprint $table): void {
            $table->index('attribute_family_group_id', 'agm_family_group_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attribute_family_group_mappings', function (Blueprint $table): void {
            $table->dropIndex('afgm_family_idx');
        });

        Schema::table('attribute_group_mappings', function (Blueprint $table): void {
            $table->dropIndex('agm_family_group_idx');
        });
    }
};
