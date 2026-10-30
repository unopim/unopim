<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Database\Schema\PrefixedIndexNameBlueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        $auditsTable = DB::getQueryGrammar()->wrapTable('audits');
        $triggerName = PrefixedIndexNameBlueprint::nameFor(DB::getTablePrefix(), 'audit_before_insert');
        $functionName = DB::getQueryGrammar()->wrap(PrefixedIndexNameBlueprint::nameFor(DB::getTablePrefix(), 'audit_before_insert_func'));

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                // MySQL trigger
                if (! DB::select('SELECT * FROM information_schema.TRIGGERS WHERE TRIGGER_NAME = ? AND EVENT_OBJECT_SCHEMA = ?', [$triggerName, DB::getDatabaseName()])) {
                    DB::unprepared('
                        CREATE TRIGGER '.DB::getQueryGrammar()->wrap($triggerName).'
                        BEFORE INSERT ON '.$auditsTable.'
                        FOR EACH ROW
                        BEGIN
                            DECLARE max_version_id INT DEFAULT 0;
                            DECLARE old_version_id INT;

                            -- Find existing version_id with the same tags and exact created_at timestamp
                            SELECT version_id INTO old_version_id
                            FROM '.$auditsTable.'
                            WHERE tags = NEW.tags AND url = NEW.url AND created_at = NEW.created_at
                            LIMIT 1;

                            -- Get the maximum version_id for the same tags
                            SELECT MAX(version_id) INTO max_version_id
                            FROM '.$auditsTable.'
                            WHERE tags = NEW.tags AND url = NEW.url;

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
                }
                break;

            case 'pgsql':
                // PostgreSQL trigger function + trigger
                DB::unprepared('
                    CREATE OR REPLACE FUNCTION '.$functionName.'()
                    RETURNS TRIGGER AS $$
                    DECLARE
                        max_version_id INT DEFAULT 0;
                        old_version_id INT;
                    BEGIN
                        -- Find existing version_id with the same tags and exact created_at timestamp
                        SELECT version_id INTO old_version_id
                        FROM '.$auditsTable.'
                        WHERE tags = NEW.tags AND url = NEW.url AND created_at = NEW.created_at
                        LIMIT 1;

                        -- Get the maximum version_id for the same tags
                        SELECT MAX(version_id) INTO max_version_id
                        FROM '.$auditsTable.'
                        WHERE tags = NEW.tags AND url = NEW.url;

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

                    DROP TRIGGER IF EXISTS audit_before_insert ON '.$auditsTable.';

                    CREATE TRIGGER audit_before_insert
                    BEFORE INSERT ON '.$auditsTable.'
                    FOR EACH ROW
                    EXECUTE FUNCTION '.$functionName.'();
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
        $auditsTable = DB::getQueryGrammar()->wrapTable('audits');

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                DB::unprepared('DROP TRIGGER IF EXISTS '.DB::getQueryGrammar()->wrap(PrefixedIndexNameBlueprint::nameFor(DB::getTablePrefix(), 'audit_before_insert')));
                break;

            case 'pgsql':
                DB::unprepared('
                    DROP TRIGGER IF EXISTS audit_before_insert ON '.$auditsTable.';
                    DROP FUNCTION IF EXISTS '.DB::getQueryGrammar()->wrap(PrefixedIndexNameBlueprint::nameFor(DB::getTablePrefix(), 'audit_before_insert_func')).'();
                ');
                break;
        }
    }
};
