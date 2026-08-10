<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Covering indexes for the dashboard's catalog-wide aggregates, which
 * otherwise resolve `status` and `created_at` through the clustered row —
 * ~90 GB and minutes per query at ten million products. The composite on
 * (updated_at, created_at) supersedes the single-column updated_at index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasIndex('products', 'products_type_status_idx')) {
                $table->index(['type', 'status'], 'products_type_status_idx');
            }

            if (! Schema::hasIndex('products', 'products_created_at_idx')) {
                $table->index('created_at', 'products_created_at_idx');
            }

            if (! Schema::hasIndex('products', 'products_updated_created_idx')) {
                $table->index(['updated_at', 'created_at'], 'products_updated_created_idx');
            }

            if (! Schema::hasIndex('products', 'products_completeness_idx')) {
                $table->index('avg_completeness_score', 'products_completeness_idx');
            }
        });

        if (Schema::hasIndex('products', 'products_updated_at_idx')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_updated_at_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasIndex('products', 'products_updated_at_idx')) {
                $table->index('updated_at', 'products_updated_at_idx');
            }

            foreach ([
                'products_type_status_idx',
                'products_created_at_idx',
                'products_updated_created_idx',
                'products_completeness_idx',
            ] as $index) {
                if (Schema::hasIndex('products', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }
};
