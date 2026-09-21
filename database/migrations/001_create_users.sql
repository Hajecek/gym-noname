-- 001_create_users.sql
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(64) NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    email VARCHAR(190) NOT NULL,
    username VARCHAR(30) NOT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    avatar_path VARCHAR(500) DEFAULT NULL,
    oauth_provider ENUM('google', 'apple') DEFAULT NULL,
    oauth_uid VARCHAR(190) DEFAULT NULL,
    role ENUM('user', 'staff', 'admin') NOT NULL DEFAULT 'user',
    plan ENUM('free', 'premium', 'family') NOT NULL DEFAULT 'free',
    status ENUM('pending', 'active', 'blocked', 'deleted') NOT NULL DEFAULT 'pending',
    locale VARCHAR(10) NOT NULL DEFAULT 'cs-CZ',
    timezone VARCHAR(64) NOT NULL DEFAULT 'Europe/Prague',
    default_currency CHAR(3) NOT NULL DEFAULT 'CZK',
    onboarding_completed TINYINT(1) NOT NULL DEFAULT 0,
    email_verified_at DATETIME DEFAULT NULL,
    last_login_at DATETIME DEFAULT NULL,
    last_login_ip VARCHAR(45) DEFAULT NULL,
    username_changed_at DATETIME DEFAULT NULL,
    mfa_enabled TINYINT(1) NOT NULL DEFAULT 0,
    terms_accepted_at DATETIME DEFAULT NULL,
    privacy_accepted_at DATETIME DEFAULT NULL,
    marketing_opt_in TINYINT(1) NOT NULL DEFAULT 0,
    marketing_opt_in_at DATETIME DEFAULT NULL,
    blocked_at DATETIME DEFAULT NULL,
    blocked_reason VARCHAR(500) DEFAULT NULL,
    deletion_requested_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_public_id (public_id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_oauth (oauth_provider, oauth_uid),
    KEY idx_users_status (status),
    KEY idx_users_role (role),
    KEY idx_users_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
