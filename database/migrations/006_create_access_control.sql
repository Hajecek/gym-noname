-- 006_create_access_control.sql
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS doors (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    room_id BIGINT UNSIGNED DEFAULT NULL,
    name VARCHAR(120) NOT NULL,
    provider ENUM('mock', 'nuki') NOT NULL DEFAULT 'mock',
    external_id VARCHAR(120) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_known_state VARCHAR(40) DEFAULT NULL,
    last_known_door_state VARCHAR(40) DEFAULT NULL,
    last_battery_percent TINYINT UNSIGNED DEFAULT NULL,
    battery_critical TINYINT(1) NOT NULL DEFAULT 0,
    last_online_at DATETIME DEFAULT NULL,
    last_checked_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_doors_public_id (public_id),
    KEY idx_doors_room (room_id),
    CONSTRAINT fk_doors_room FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS access_permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    door_id BIGINT UNSIGNED NOT NULL,
    reservation_id BIGINT UNSIGNED DEFAULT NULL,
    valid_from DATETIME NOT NULL,
    valid_until DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_access_permissions_lookup (user_id, door_id, valid_from, valid_until),
    CONSTRAINT fk_access_permissions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_access_permissions_door FOREIGN KEY (door_id) REFERENCES doors (id) ON DELETE CASCADE,
    CONSTRAINT fk_access_permissions_reservation FOREIGN KEY (reservation_id) REFERENCES reservations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS access_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    reservation_id BIGINT UNSIGNED DEFAULT NULL,
    door_id BIGINT UNSIGNED DEFAULT NULL,
    authorization_result ENUM('granted', 'denied') NOT NULL,
    command_result ENUM('not_sent', 'accepted', 'failed', 'timeout', 'conflict') NOT NULL DEFAULT 'not_sent',
    lock_state VARCHAR(40) DEFAULT NULL,
    door_state VARCHAR(40) DEFAULT NULL,
    denial_reason VARCHAR(80) DEFAULT NULL,
    error_code VARCHAR(80) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_access_logs_user (user_id, created_at),
    KEY idx_access_logs_door (door_id, created_at),
    KEY idx_access_logs_result (authorization_result, created_at),
    CONSTRAINT fk_access_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_access_logs_reservation FOREIGN KEY (reservation_id) REFERENCES reservations (id) ON DELETE SET NULL,
    CONSTRAINT fk_access_logs_door FOREIGN KEY (door_id) REFERENCES doors (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS door_command_locks (
    door_id BIGINT UNSIGNED NOT NULL,
    locked_until DATETIME NOT NULL,
    PRIMARY KEY (door_id),
    CONSTRAINT fk_door_command_locks_door FOREIGN KEY (door_id) REFERENCES doors (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
