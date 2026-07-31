<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_publish_attempts', function (Blueprint $table): void {
            $table->id();

            $table->unsignedInteger('product_id');
            $table->foreign('product_id', 'pubpa_prod_fk')->references('id')->on('products')->cascadeOnDelete();

            $table->unsignedInteger('channel_id');
            $table->foreign('channel_id', 'pubpa_chan_fk')->references('id')->on('channels')->cascadeOnDelete();

            $table->string('type', 32);

            $table->json('locale_ids');

            $table->string('status', 20);

            $table->json('outcome')->nullable();

            $table->unsignedInteger('requested_by_id')->nullable();
            $table->foreign('requested_by_id', 'pubpa_admin_fk')->references('id')->on('admins')->nullOnDelete();

            $table->timestamps();

            $table->index(['product_id', 'channel_id', 'type'], 'pubpa_prod_chan_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_publish_attempts');
    }
};
