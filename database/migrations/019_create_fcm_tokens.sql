-- 019_create_fcm_tokens.sql
-- Stejný účel jako Skrbla/Provikart: FCM token zařízení, ne jen sloupec na api_devices.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS fcm_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    device_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(512) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    environment ENUM('development', 'production') NOT NULL DEFAULT 'production',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME DEFAULT NULL,
    invalidated_at DATETIME DEFAULT NULL,
    invalid_reason VARCHAR(255) DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_fcm_tokens_hash (token_hash),
    KEY idx_fcm_tokens_user_active (user_id, is_active),
    KEY idx_fcm_tokens_device_active (device_id, is_active),
    CONSTRAINT fk_fcm_tokens_device FOREIGN KEY (device_id) REFERENCES api_devices (id) ON DELETE CASCADE,
    CONSTRAINT fk_fcm_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO fcm_tokens (user_id, device_id, token, token_hash, environment, is_active, last_used_at)
SELECT d.user_id, d.id, d.push_token, SHA2(d.push_token, 256), 'production', 1, d.last_seen_at
FROM api_devices d
WHERE d.push_token IS NOT NULL
  AND d.push_token LIKE '%:%'
  AND CHAR_LENGTH(d.push_token) >= 80
  AND NOT EXISTS (
      SELECT 1 FROM fcm_tokens t WHERE t.token_hash = SHA2(d.push_token, 256)
  );
