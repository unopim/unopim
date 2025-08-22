<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::create('job_instances', function (Blueprint $table) use ($driver) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('entity_type');

            if ($driver === 'mysql') {
                $table->enum('type', ['import', 'export', 'system']);
            } else {

                $table->string('type');
            }

            $table->string('action');

            $table->string('validation_strategy')->default('skip');

            $table->integer('allowed_errors')->default(0);

            $table->char('field_separator', 1)->default(',');

            $table->string('file_path')->nullable();

            $table->string('images_directory_path')->nullable();
            $table->timestamps();
        });

        if ($driver === 'pgsql') {
            DB::statement(
                "ALTER TABLE job_instances 
                 ADD CONSTRAINT job_instances_type_check 
                 CHECK (type IN ('import','export','system'))"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_instances');
    }
};
