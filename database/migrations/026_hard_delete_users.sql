-- 026_hard_delete_users.sql
SET NAMES utf8mb4;

ALTER TABLE reservations
    MODIFY user_id BIGINT UNSIGNED NULL;

ALTER TABLE reservations
    DROP FOREIGN KEY fk_reservations_user;

ALTER TABLE reservations
    ADD CONSTRAINT fk_reservations_user
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL;

ALTER TABLE payments
    MODIFY user_id BIGINT UNSIGNED NULL;

ALTER TABLE payments
    DROP FOREIGN KEY fk_payments_user;

ALTER TABLE payments
    ADD CONSTRAINT fk_payments_user
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL;

-- Už soft-smazané účty uvolní OAuth a smažou se natvrdo.
UPDATE users
SET oauth_provider = NULL,
    oauth_uid = NULL
WHERE deleted_at IS NOT NULL
   OR status = 'deleted';

DELETE FROM users
WHERE deleted_at IS NOT NULL
   OR status = 'deleted';
