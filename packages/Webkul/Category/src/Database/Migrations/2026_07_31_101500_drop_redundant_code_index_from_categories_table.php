<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `code` carries both a unique constraint and a plain index, and the unique one
 * already serves every lookup. The duplicate only costs write time: inserting a
 * category shifts `_lft`/`_rgt` across the whole tree, and every shifted row has
 * to be re-entered into each index on the table.
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
