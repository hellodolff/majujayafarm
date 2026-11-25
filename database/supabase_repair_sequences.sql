-- Jalankan di Supabase SQL Editor
-- Memperbaiki sequence primary key agar tidak mencoba ID yang sudah dipakai

SELECT setval(
    pg_get_serial_sequence('users', 'id'),
    GREATEST(COALESCE((SELECT MAX(id) FROM users), 0), 1),
    EXISTS (SELECT 1 FROM users)
);

-- Cek hasil sequence
SELECT last_value, is_called
FROM users_id_seq;

-- Verifikasi urutan ID users
SELECT id, name, email, role
FROM users
ORDER BY id;
