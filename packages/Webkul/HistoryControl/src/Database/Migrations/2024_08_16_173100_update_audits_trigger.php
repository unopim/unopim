<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        $tablePrefix = DB::getTablePrefix();

        switch ($driver) {
            case 'mysql':
                // Drop existing trigger if exists
                DB::unprepared('DROP TRIGGER IF EXISTS `audit_before_insert`');

                // Create MySQL trigger
                DB::unprepared('
                    CREATE TRIGGER audit_before_insert
                    BEFORE INSERT ON '.$tablePrefix.'audits
                    FOR EACH ROW
                    BEGIN
                        DECLARE max_version_id INT DEFAULT 0;
                        DECLARE old_version_id INT;

                        -- Find existing version_id with the same tags and exact created_at timestamp
                        SELECT version_id INTO old_version_id
                        FROM '.$tablePrefix.'audits
                        WHERE tags = NEW.tags AND history_id = NEW.history_id AND created_at = NEW.created_at
                        LIMIT 1;

                        -- Get the maximum version_id for the same tags
                        SELECT MAX(version_id) INTO max_version_id
                        FROM '.$tablePrefix.'audits
                        WHERE tags = NEW.tags AND history_id = NEW.history_id;

                        -- Assign version_id based on the findings
                        IF old_version_id IS NOT NULL THEN
                            SET NEW.version_id = old_version_id;
                        ELSEIF max_version_id IS NULL THEN
                            SET NEW.version_id = 1;
                        ELSE
                            SET NEW.version_id = max_version_id + 1;
                        END IF;
                    END;
                ');
                break;

            case 'pgsql':
                // Drop existing trigger and function
                DB::unprepared('
                    DROP TRIGGER IF EXISTS audit_before_insert ON '.$tablePrefix.'audits;
                    DROP FUNCTION IF EXISTS audit_before_insert_func();
                ');

                // Create PostgreSQL trigger function + trigger
                DB::unprepared('
                    CREATE OR REPLACE FUNCTION audit_before_insert_func()
                    RETURNS TRIGGER AS $$
                    DECLARE
                        max_version_id INT DEFAULT 0;
                        old_version_id INT;
                    BEGIN
                        -- Find existing version_id with the same tags and exact created_at timestamp
                        SELECT version_id INTO old_version_id
                        FROM '.$tablePrefix.'audits
                        WHERE tags = NEW.tags AND history_id = NEW.history_id AND created_at = NEW.created_at
                        LIMIT 1;

                        -- Get the maximum version_id for the same tags
                        SELECT MAX(version_id) INTO max_version_id
                        FROM '.$tablePrefix.'audits
                        WHERE tags = NEW.tags AND history_id = NEW.history_id;

                        -- Assign version_id based on the findings
                        IF old_version_id IS NOT NULL THEN
                            NEW.version_id := old_version_id;
                        ELSIF max_version_id IS NULL THEN
                            NEW.version_id := 1;
                        ELSE
                            NEW.version_id := max_version_id + 1;
                        END IF;

                        RETURN NEW;
                    END;
                    $$ LANGUAGE plpgsql;

                    CREATE TRIGGER audit_before_insert
                    BEFORE INSERT ON '.$tablePrefix.'audits
                    FOR EACH ROW
                    EXECUTE FUNCTION audit_before_insert_func();
                ');
                break;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        $tablePrefix = DB::getTablePrefix();

        switch ($driver) {
            case 'mysql':
                DB::unprepared('DROP TRIGGER IF EXISTS `audit_before_insert`');
                break;

            case 'pgsql':
                DB::unprepared('
                    DROP TRIGGER IF EXISTS audit_before_insert ON '.$tablePrefix.'audits;
                    DROP FUNCTION IF EXISTS audit_before_insert_func();
                ');
                break;
        }
    }
};
