<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_type_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('association_type_id')->unsigned();
            $table->string('locale');
            $table->string('name')->nullable();

            $table->unique(['association_type_id', 'locale']);
            $table->foreign('association_type_id')->references('id')->on('association_types')->onDelete('cascade');
            $table->index('association_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_type_translations');
    }
};
