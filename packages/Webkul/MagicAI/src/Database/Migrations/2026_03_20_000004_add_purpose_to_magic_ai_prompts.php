<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('magic_ai_prompts', function (Blueprint $table) {
            $table->string('purpose', 50)->default('text_generation')->after('type');
        });

        // Set existing product/category prompts as text_generation purpose
        DB::table('magic_ai_prompts')
            ->whereIn('type', ['product', 'category'])
            ->update(['purpose' => 'text_generation']);

        // Migrate image/translation types to purpose column and reset type to product
        DB::table('magic_ai_prompts')
            ->where('type', 'image')
            ->update(['purpose' => 'image_generation', 'type' => 'product']);

        DB::table('magic_ai_prompts')
            ->where('type', 'translation')
            ->update(['purpose' => 'translation', 'type' => 'product']);

        // Revert type enum back to only product/category
        $table = DB::getTablePrefix().'magic_ai_prompts';

        if ($this->isPostgres()) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS magic_ai_prompts_type_check");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN type TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT magic_ai_prompts_type_check CHECK (type IN ('product', 'category'))");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN type SET DEFAULT 'product'");
        } else {
            DB::statement("ALTER TABLE {$table} MODIFY COLUMN type ENUM('product', 'category') NOT NULL DEFAULT 'product'");
        }
    }

    public function down(): void
    {
        Schema::table('magic_ai_prompts', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });
    }

    protected function isPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
};
