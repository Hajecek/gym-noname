-- 011_reservation_occupancy.sql
-- Unikátní zámek na stejný začátek termínu ve stejné místnosti.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS reservation_occupancy (
    room_id BIGINT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    reservation_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (room_id, starts_at),
    KEY idx_reservation_occupancy_reservation (reservation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO reservation_occupancy (room_id, starts_at, reservation_id)
SELECT room_id, starts_at, id
FROM reservations
WHERE status IN ('pending_payment', 'confirmed');
