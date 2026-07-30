<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Category\Models\Category;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        if ($this->boundsAreConsistent()) {
            return;
        }

        DB::transaction(fn () => Category::fixTree());
    }

    public function down(): void {}

    private function boundsAreConsistent(): bool
    {
        $total = Category::query()->count();

        if ($total === 0) {
            return true;
        }

        $widest = Category::query()->max('_rgt');

        return (int) $widest === $total * 2;
    }
};
