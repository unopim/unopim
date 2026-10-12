<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('variant_structure_id')->nullable()->after('attribute_family_id');

            $table->foreign('variant_structure_id')
                ->references('id')->on('variant_structures')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['variant_structure_id']);
            $table->dropColumn('variant_structure_id');
        });
    }
};
