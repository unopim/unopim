<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** `code` is already unique, so the plain copy only costs write time. */
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
