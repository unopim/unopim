<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('attributes', 'formula')) {
            return;
        }

        Schema::table('attributes', function (Blueprint $table): void {
            $table->text('formula')->nullable()->after('default_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Dropping the column is the whole rollback: calculated values are never
     * written into `products.values`, so no product data needs scrubbing.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('attributes', 'formula')) {
            return;
        }

        Schema::table('attributes', function (Blueprint $table): void {
            $table->dropColumn('formula');
        });
    }
};
