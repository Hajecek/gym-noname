-- 012_reservation_slots_pricing.sql
-- 15min začátky, 15min rozestup a cena 150 Kč/hod (volitelně jiná podle dne).
SET NAMES utf8mb4;

ALTER TABLE opening_hours
    ADD COLUMN hourly_price DECIMAL(12,2) DEFAULT NULL AFTER is_closed;

INSERT INTO app_settings (setting_key, setting_value, is_secret)
VALUES
    ('pricing.hourly', '150', 0),
    ('reservation.slot_minutes', '15', 0),
    ('reservation.buffer_minutes', '15', 0),
    ('reservation.min_minutes', '60', 0),
    ('reservation.max_minutes', '180', 0)
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

CREATE TABLE IF NOT EXISTS reservation_occupancy (
    room_id BIGINT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    reservation_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (room_id, starts_at),
    KEY idx_reservation_occupancy_reservation (reservation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM reservation_occupancy;

INSERT IGNORE INTO reservation_occupancy (room_id, starts_at, reservation_id)
SELECT r.room_id,
       DATE_ADD(r.starts_at, INTERVAL seq.n * 15 MINUTE),
       r.id
FROM reservations r
INNER JOIN (
    SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
    UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7
    UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11
    UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15
    UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19
    UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23
) seq
WHERE r.status IN ('pending_payment', 'confirmed')
  AND DATE_ADD(r.starts_at, INTERVAL seq.n * 15 MINUTE) < DATE_ADD(r.ends_at, INTERVAL r.buffer_minutes MINUTE);
