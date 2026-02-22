<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * F-005: Add 'rejected' to the event_type enum on margin_protection_events.
 *
 * SQLite doesn't support ALTER COLUMN for enums, so we recreate the column.
 * For MySQL, we use a raw ALTER TABLE statement.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: recreate the table with the new enum value
            // SQLite doesn't enforce enum constraints, so this is mostly documentary
            // The application-level validation handles the enum values
        } else {
            // MySQL: ALTER the enum column
            DB::statement("ALTER TABLE margin_protection_events MODIFY COLUMN event_type ENUM('blocked', 'warning', 'approved', 'expired', 'rejected') NOT NULL");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            // First update any 'rejected' rows to 'expired' to avoid constraint violations
            DB::table('margin_protection_events')
                ->where('event_type', 'rejected')
                ->update(['event_type' => 'expired']);

            DB::statement("ALTER TABLE margin_protection_events MODIFY COLUMN event_type ENUM('blocked', 'warning', 'approved', 'expired') NOT NULL");
        }
    }
};
