-- 003_create_memberships.sql
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS membership_plans (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,
    type ENUM('single', 'pack', 'monthly', 'credit', 'voucher') NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'CZK',
    entries INT UNSIGNED DEFAULT NULL,
    duration_days INT UNSIGNED DEFAULT NULL,
    max_guests TINYINT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_membership_plans_public_id (public_id),
    UNIQUE KEY uq_membership_plans_slug (slug),
    KEY idx_membership_plans_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS memberships (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'active', 'expired', 'cancelled', 'suspended') NOT NULL DEFAULT 'pending',
    entries_remaining INT UNSIGNED DEFAULT NULL,
    credit_remaining DECIMAL(12,2) DEFAULT NULL,
    starts_at DATETIME DEFAULT NULL,
    ends_at DATETIME DEFAULT NULL,
    auto_renew TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_memberships_public_id (public_id),
    KEY idx_memberships_user (user_id, status),
    KEY idx_memberships_dates (starts_at, ends_at),
    CONSTRAINT fk_memberships_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_memberships_plan FOREIGN KEY (plan_id) REFERENCES membership_plans (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS membership_transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    membership_id BIGINT UNSIGNED NOT NULL,
    type ENUM('purchase', 'renewal', 'entry_use', 'credit_use', 'refund', 'admin_adjust', 'expire') NOT NULL,
    entries_delta INT NOT NULL DEFAULT 0,
    credit_delta DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    note VARCHAR(255) DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_membership_transactions_membership (membership_id),
    CONSTRAINT fk_membership_transactions_membership FOREIGN KEY (membership_id) REFERENCES memberships (id) ON DELETE CASCADE,
    CONSTRAINT fk_membership_transactions_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gift_vouchers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    code_hash CHAR(64) NOT NULL,
    code_hint VARCHAR(12) NOT NULL,
    plan_id BIGINT UNSIGNED DEFAULT NULL,
    amount DECIMAL(12,2) DEFAULT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'CZK',
    redeemed_by BIGINT UNSIGNED DEFAULT NULL,
    redeemed_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_gift_vouchers_public_id (public_id),
    UNIQUE KEY uq_gift_vouchers_hash (code_hash),
    CONSTRAINT fk_gift_vouchers_plan FOREIGN KEY (plan_id) REFERENCES membership_plans (id) ON DELETE SET NULL,
    CONSTRAINT fk_gift_vouchers_user FOREIGN KEY (redeemed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
