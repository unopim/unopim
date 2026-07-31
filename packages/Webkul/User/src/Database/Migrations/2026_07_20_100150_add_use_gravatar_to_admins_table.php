<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('admins', 'use_gravatar')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table): void {
            $table->boolean('use_gravatar')->default(true)->after('image');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('admins', 'use_gravatar')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table): void {
            $table->dropColumn('use_gravatar');
        });
    }
};
