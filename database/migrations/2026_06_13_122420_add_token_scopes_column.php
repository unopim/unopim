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
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('oauth_access_tokens', 'scopes')) {
                $table->string('scopes', 1000)->nullable();
            }
            if (!Schema::hasColumn('oauth_access_tokens', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable();
            }
        });
    }
};
