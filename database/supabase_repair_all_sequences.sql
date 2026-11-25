-- Jalankan sekali di Supabase SQL Editor
-- Menyelaraskan setiap sequence primary key di schema public
-- Aman dijalankan berulang-ulang

DO $$
DECLARE
    r record;
    sequence_name text;
    max_id bigint;
    table_exists boolean;
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
            RAISE NOTICE 'Sequence repaired: %.% -> %', r.table_name, r.column_name, max_id;
        END IF;
    END LOOP;
END $$;

-- Verifikasi users
SELECT id, name, email, role
FROM users
ORDER BY id;

SELECT last_value, is_called
FROM users_id_seq;
