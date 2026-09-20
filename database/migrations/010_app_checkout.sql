-- 010_app_checkout.sql
-- Spusť v phpMyAdmin na produkci, pokud checkout vrací HTTP 500.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS api_idempotency (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    idempotency_key CHAR(36) NOT NULL,
    route VARCHAR(120) NOT NULL,
    status_code SMALLINT UNSIGNED NOT NULL DEFAULT 200,
    response_json MEDIUMTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_api_idempotency_user_key (user_id, idempotency_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS client_request_id CHAR(36) DEFAULT NULL AFTER public_id;

ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS metadata_json MEDIUMTEXT DEFAULT NULL AFTER status;
