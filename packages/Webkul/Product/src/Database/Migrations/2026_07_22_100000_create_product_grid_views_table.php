<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_grid_views', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('admin_id')->unsigned();
            $table->string('name');
            $table->boolean('is_shared')->default(false);
            $table->json('payload');
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->unique(['admin_id', 'name']);
            $table->index('is_shared');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_grid_views');
    }
};
