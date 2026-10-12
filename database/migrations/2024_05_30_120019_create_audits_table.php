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
        $morphPrefix = config('audit.user.morph_prefix', 'user');

        Schema::connection($this->auditConnection())->create($this->auditTable(), function (Blueprint $table) use ($morphPrefix) {
            $table->bigIncrements('id');
            $table->string($morphPrefix.'_type')->nullable();
            $table->unsignedBigInteger($morphPrefix.'_id')->nullable();
            $table->string('event');
            $table->morphs('auditable');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();

            $table->index([$morphPrefix.'_id', $morphPrefix.'_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->auditConnection())->dropIfExists($this->auditTable());
    }

    /**
     * The connection the audit trail is stored on.
     */
    private function auditConnection(): ?string
    {
        return config('audit.drivers.database.connection') ?? config('database.default');
    }

    /**
     * The table the audit trail is stored in.
     */
    private function auditTable(): string
    {
        return config('audit.drivers.database.table') ?? 'audits';
    }
};
