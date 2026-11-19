<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Core\Helpers\Database\PostgresCheckConstraintHelper;

return new class extends Migration
{
    public function up(): void
    {
        $table = DB::getQueryGrammar()->wrapTable('magic_ai_prompts');

        if ($this->isPostgres()) {
            // PostgreSQL: change column type with CHECK constraint
            PostgresCheckConstraintHelper::dropColumnChecks('magic_ai_prompts', 'type');
            DB::statement("ALTER TABLE {$table} ALTER COLUMN type TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT magic_ai_prompts_type_check CHECK (type IN ('product', 'category', 'image', 'translation'))");
        } else {
            // MySQL: MODIFY with ENUM
            DB::statement("ALTER TABLE {$table} MODIFY COLUMN type ENUM('product', 'category', 'image', 'translation') NOT NULL DEFAULT 'product'");
        }
    }

    public function down(): void
    {
        $table = DB::getQueryGrammar()->wrapTable('magic_ai_prompts');

        if ($this->isPostgres()) {
            PostgresCheckConstraintHelper::dropColumnChecks('magic_ai_prompts', 'type');
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
