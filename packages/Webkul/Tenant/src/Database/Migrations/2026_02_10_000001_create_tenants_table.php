<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('name');
            $table->string('domain')->unique();
            $table->enum('status', [
                'provisioning',
                'active',
                'suspended',
                'deleting',
                'deleted',
            ])->default('provisioning');
            $table->json('settings')->nullable();
            $table->char('es_index_uuid', 36)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed default tenant (id=1) per Decision D2.
        // All existing data will be migrated to tenant_id = 1 in subsequent migrations.
        DB::table('tenants')->insert([
            'id'            => 1,
            'uuid'          => Str::uuid()->toString(),
            'name'          => 'Default',
            'domain'        => 'default',
            'status'        => 'active',
            'settings'      => null,
            'es_index_uuid' => Str::uuid()->toString(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
