<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_promo_dismissals', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('admin_id')->unsigned();
            $table->string('banner');
            $table->string('version')->default('');
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->unique(['admin_id', 'banner', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_promo_dismissals');
    }
};
