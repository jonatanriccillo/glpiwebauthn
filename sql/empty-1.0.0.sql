CREATE TABLE IF NOT EXISTS `glpi_plugin_webauthn_credentials` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `users_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(128) NOT NULL DEFAULT '',
    `credential_id` VARBINARY(1024) NOT NULL,
    `credential_public_key` TEXT NOT NULL,
    `type` VARCHAR(32) NOT NULL DEFAULT 'public-key',
    `attestation_type` VARCHAR(32) DEFAULT NULL,
    `trust_path` TEXT,
    `aaguid` CHAR(36) DEFAULT NULL,
    `transports` VARCHAR(255) DEFAULT NULL,
    `sign_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `user_handle` VARBINARY(64) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_used_at` TIMESTAMP NULL DEFAULT NULL,
    `date_creation` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `date_mod` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `credential_id` (`credential_id`(255)),
    KEY `users_id` (`users_id`),
    KEY `users_active` (`users_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_webauthn_config` (
    `k` VARCHAR(64) NOT NULL,
    `v` TEXT,
    PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_webauthn_profiles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `profiles_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `webauthn_enforced` TINYINT(1) NOT NULL DEFAULT 0,
    `webauthn_allowed` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `profiles_id` (`profiles_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
