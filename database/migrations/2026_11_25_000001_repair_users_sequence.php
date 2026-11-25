<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
DO $$
DECLARE
    r record;
    sequence_name text;
    max_id bigint;
BEGIN
    FOR r IN
        SELECT table_name, column_name
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND column_name = 'id'
          AND data_type IN ('bigint', 'integer', 'smallint')
        ORDER BY table_name
    LOOP
        sequence_name := pg_get_serial_sequence(format('%I.%I', 'public', r.table_name), r.column_name);
        IF sequence_name IS NOT NULL THEN
            EXECUTE format('SELECT COALESCE(MAX(%I), 0) FROM %I.%I', r.column_name, 'public', r.table_name)
            INTO max_id;
            PERFORM setval(sequence_name, GREATEST(max_id, 1), max_id > 0);
        END IF;
    END LOOP;
END $$;
SQL);
    }

    public function down(): void
    {
        // Sequence repair is intentionally not reversed.
    }
};
