<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `code` is already unique. The duplicate costs write time on every insert,
 * which shifts `_lft`/`_rgt` across the whole tree and re-indexes each row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('categories', 'categories_code_index')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex('categories_code_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasIndex('categories', 'categories_code_index')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table): void {
            $table->index('code');
        });
    }
};
