-- 009_create_mobile_api.sql
SET NAMES utf8mb4;

ALTER TABLE payments
    ADD COLUMN client_request_id CHAR(36) DEFAULT NULL AFTER public_id,
    ADD COLUMN metadata_json MEDIUMTEXT DEFAULT NULL AFTER status;

ALTER TABLE access_logs
    ADD COLUMN request_id CHAR(36) DEFAULT NULL AFTER door_id,
    ADD COLUMN operation_id CHAR(36) DEFAULT NULL AFTER request_id;

ALTER TABLE api_devices
    ADD COLUMN push_token VARCHAR(512) DEFAULT NULL AFTER platform,
    ADD COLUMN push_preferences_json MEDIUMTEXT DEFAULT NULL AFTER push_token;

CREATE TABLE IF NOT EXISTS door_commands (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    request_id CHAR(36) NOT NULL,
    door_id BIGINT UNSIGNED DEFAULT NULL,
    outcome ENUM('confirmedOpen', 'accepted', 'denied') NOT NULL,
    message VARCHAR(500) DEFAULT NULL,
    entry_until DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_door_commands_public_id (public_id),
    UNIQUE KEY uq_door_commands_user_request (user_id, request_id),
    KEY idx_door_commands_request (request_id),
    CONSTRAINT fk_door_commands_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_door_commands_door FOREIGN KEY (door_id) REFERENCES doors (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_idempotency (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    idempotency_key CHAR(36) NOT NULL,
    route VARCHAR(120) NOT NULL,
    status_code SMALLINT UNSIGNED NOT NULL DEFAULT 200,
    response_json MEDIUMTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_api_idempotency_user_key (user_id, idempotency_key),
    CONSTRAINT fk_api_idempotency_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
