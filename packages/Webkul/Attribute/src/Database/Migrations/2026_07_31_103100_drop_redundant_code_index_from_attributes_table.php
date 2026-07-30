<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `code` is already unique, and that index serves every lookup the plain one
 * would; keeping both only costs write time on each attribute save.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('attributes', 'attributes_code_index')) {
            return;
        }

        Schema::table('attributes', function (Blueprint $table): void {
            $table->dropIndex('attributes_code_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasIndex('attributes', 'attributes_code_index')) {
            return;
        }

        Schema::table('attributes', function (Blueprint $table): void {
            $table->index('code');
        });
    }
};
