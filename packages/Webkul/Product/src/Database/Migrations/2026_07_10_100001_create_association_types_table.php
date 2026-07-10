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
        Schema::create('association_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->boolean('status')->default(1);
            $table->integer('position')->nullable();
            $table->boolean('is_user_defined')->default(1);
            $table->timestamps();

            $table->index('code');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('association_types');
    }
};
