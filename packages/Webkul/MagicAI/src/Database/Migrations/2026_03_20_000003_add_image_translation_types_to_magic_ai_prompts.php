<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = DB::getTablePrefix().'magic_ai_prompts';

        if ($this->isPostgres()) {
            // PostgreSQL: change column type with CHECK constraint
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS magic_ai_prompts_type_check");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN type TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT magic_ai_prompts_type_check CHECK (type IN ('product', 'category', 'image', 'translation'))");
        } else {
            // MySQL: MODIFY with ENUM
            DB::statement("ALTER TABLE {$table} MODIFY COLUMN type ENUM('product', 'category', 'image', 'translation') NOT NULL DEFAULT 'product'");
        }
    }

    public function down(): void
    {
        $table = DB::getTablePrefix().'magic_ai_prompts';

        if ($this->isPostgres()) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS magic_ai_prompts_type_check");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN type TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT magic_ai_prompts_type_check CHECK (type IN ('product', 'category'))");
        } else {
            DB::statement("ALTER TABLE {$table} MODIFY COLUMN type ENUM('product', 'category') NOT NULL DEFAULT 'product'");
        }
    }

    protected function isPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
};
