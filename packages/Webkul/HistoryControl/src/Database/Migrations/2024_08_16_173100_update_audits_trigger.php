<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `audit_before_insert`');

        $tablePrefix = DB::getTablePrefix();

        DB::unprepared('CREATE TRIGGER audit_before_insert
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
                    END;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `audit_before_insert`');
    }
};
