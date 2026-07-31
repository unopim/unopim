<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('variant_structure_axes')) {
            return;
        }

        Schema::table('variant_structure_axes', function (Blueprint $table): void {
            if (! Schema::hasColumn('variant_structure_axes', 'level')) {
                $table->enum('level', ['level_1', 'level_2'])->default('level_1')->after('attribute_id');
            }
        });

        try {
            Schema::table('variant_structure_axes', function (Blueprint $table): void {
                $table->dropUnique('vsax_structure_position_unique');
            });
        } catch (Throwable) {
            //
        }

        try {
            Schema::table('variant_structure_axes', function (Blueprint $table): void {
                $table->unique(['variant_structure_id', 'level', 'position'], 'vsax_structure_level_position_unique');
            });
        } catch (Throwable) {
            //
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('variant_structure_axes')) {
            return;
        }

        try {
            Schema::table('variant_structure_axes', function (Blueprint $table): void {
                $table->dropUnique('vsax_structure_level_position_unique');
            });
        } catch (Throwable) {
            //
        }

        Schema::table('variant_structure_axes', function (Blueprint $table): void {
            if (Schema::hasColumn('variant_structure_axes', 'level')) {
                $table->dropColumn('level');
            }
        });

        try {
            Schema::table('variant_structure_axes', function (Blueprint $table): void {
                $table->unique(['variant_structure_id', 'position'], 'vsax_structure_position_unique');
            });
        } catch (Throwable) {
            //
        }
    }
};
