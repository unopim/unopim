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
        $functionName = DB::getQueryGrammar()->wrap(PrefixedIndexNameBlueprint::nameFor(DB::getTablePrefix(), 'audit_before_insert_func'));

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                // Drop existing trigger if exists
                $this->dropMysqlTriggers();

                // Create MySQL trigger
                DB::unprepared('
                    CREATE TRIGGER '.DB::getQueryGrammar()->wrap(PrefixedIndexNameBlueprint::nameFor(DB::getTablePrefix(), 'audit_before_insert')).'
                    BEFORE INSERT ON '.$auditsTable.'
                    FOR EACH ROW
                    BEGIN
                        DECLARE max_version_id INT DEFAULT 0;
                        DECLARE old_version_id INT;

                        -- Find existing version_id with the same tags and exact created_at timestamp
                        SELECT version_id INTO old_version_id
                        FROM '.$auditsTable.'
                        WHERE tags = NEW.tags AND history_id = NEW.history_id AND created_at = NEW.created_at
                        LIMIT 1;

                        -- Get the maximum version_id for the same tags
                        SELECT MAX(version_id) INTO max_version_id
                        FROM '.$auditsTable.'
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
                $this->dropPostgresTriggerAndFunctions();

                // Create PostgreSQL trigger function + trigger
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
                        WHERE tags = NEW.tags AND history_id = NEW.history_id AND created_at = NEW.created_at
                        LIMIT 1;

                        -- Get the maximum version_id for the same tags
                        SELECT MAX(version_id) INTO max_version_id
                        FROM '.$auditsTable.'
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
        switch (DB::getDriverName()) {
            case 'mysql':
            case 'mariadb':
                $this->dropMysqlTriggers();
                break;

            case 'pgsql':
                $this->dropPostgresTriggerAndFunctions();
                break;
        }
    }

    private function dropMysqlTriggers(): void
    {
        $triggerName = PrefixedIndexNameBlueprint::nameFor(DB::getTablePrefix(), 'audit_before_insert');

        DB::unprepared('DROP TRIGGER IF EXISTS '.DB::getQueryGrammar()->wrap($triggerName));

        if ($triggerName === 'audit_before_insert') {
            return;
        }

        $legacyTriggerOnAudits = DB::select(
            'SELECT 1 FROM information_schema.TRIGGERS WHERE TRIGGER_NAME = ? AND EVENT_OBJECT_SCHEMA = ? AND EVENT_OBJECT_TABLE = ?',
            ['audit_before_insert', DB::getDatabaseName(), DB::getTablePrefix().'audits']
        );

        if ($legacyTriggerOnAudits) {
            DB::unprepared('DROP TRIGGER IF EXISTS `audit_before_insert`');
        }
    }

    private function dropPostgresTriggerAndFunctions(): void
    {
        $functionName = PrefixedIndexNameBlueprint::nameFor(DB::getTablePrefix(), 'audit_before_insert_func');

        DB::unprepared('
            DROP TRIGGER IF EXISTS audit_before_insert ON '.DB::getQueryGrammar()->wrapTable('audits').';
            DROP FUNCTION IF EXISTS '.DB::getQueryGrammar()->wrap($functionName).'();
        ');

        if ($functionName === 'audit_before_insert_func') {
            return;
        }

        $legacyFunctionInUse = DB::select(
            'SELECT 1 FROM pg_trigger t JOIN pg_proc p ON p.oid = t.tgfoid JOIN pg_namespace n ON n.oid = p.pronamespace WHERE p.proname = ? AND n.nspname = current_schema()',
            ['audit_before_insert_func']
        );

        if (! $legacyFunctionInUse) {
            DB::unprepared('DROP FUNCTION IF EXISTS audit_before_insert_func()');
        }
    }
};
