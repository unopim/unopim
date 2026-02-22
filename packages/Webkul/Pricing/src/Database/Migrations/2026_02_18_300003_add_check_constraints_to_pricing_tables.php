<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * F-007: Add CHECK constraints to prevent invalid monetary/percentage values.
 *
 * Enforced in MySQL 8.0.16+ and SQLite 3.25+.
 * These constraints act as a safety net when application validation is bypassed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite CHECK constraints must be defined at table creation time.
            // They cannot be added via ALTER TABLE. Skip for SQLite.
            // Application-level validation handles these constraints.
            return;
        }

        // product_costs: amount must be non-negative
        DB::statement('ALTER TABLE product_costs ADD CONSTRAINT chk_product_costs_amount CHECK (amount >= 0)');

        // channel_costs: percentages must be between 0 and 100, fees non-negative
        DB::statement('ALTER TABLE channel_costs ADD CONSTRAINT chk_channel_costs_commission CHECK (commission_percentage BETWEEN 0 AND 100)');
        DB::statement('ALTER TABLE channel_costs ADD CONSTRAINT chk_channel_costs_payment_pct CHECK (payment_processing_percentage BETWEEN 0 AND 100)');
        DB::statement('ALTER TABLE channel_costs ADD CONSTRAINT chk_channel_costs_fixed_fee CHECK (fixed_fee_per_order >= 0)');
        DB::statement('ALTER TABLE channel_costs ADD CONSTRAINT chk_channel_costs_payment_fee CHECK (payment_fixed_fee >= 0)');

        // pricing_strategies: margin percentages must be between 0 and 99.99
        DB::statement('ALTER TABLE pricing_strategies ADD CONSTRAINT chk_strategies_min_margin CHECK (minimum_margin_percentage BETWEEN 0 AND 99.99)');
        DB::statement('ALTER TABLE pricing_strategies ADD CONSTRAINT chk_strategies_target_margin CHECK (target_margin_percentage BETWEEN 0 AND 99.99)');
        DB::statement('ALTER TABLE pricing_strategies ADD CONSTRAINT chk_strategies_premium_margin CHECK (premium_margin_percentage BETWEEN 0 AND 99.99)');
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE product_costs DROP CONSTRAINT IF EXISTS chk_product_costs_amount');
        DB::statement('ALTER TABLE channel_costs DROP CONSTRAINT IF EXISTS chk_channel_costs_commission');
        DB::statement('ALTER TABLE channel_costs DROP CONSTRAINT IF EXISTS chk_channel_costs_payment_pct');
        DB::statement('ALTER TABLE channel_costs DROP CONSTRAINT IF EXISTS chk_channel_costs_fixed_fee');
        DB::statement('ALTER TABLE channel_costs DROP CONSTRAINT IF EXISTS chk_channel_costs_payment_fee');
        DB::statement('ALTER TABLE pricing_strategies DROP CONSTRAINT IF EXISTS chk_strategies_min_margin');
        DB::statement('ALTER TABLE pricing_strategies DROP CONSTRAINT IF EXISTS chk_strategies_target_margin');
        DB::statement('ALTER TABLE pricing_strategies DROP CONSTRAINT IF EXISTS chk_strategies_premium_margin');
    }
};
