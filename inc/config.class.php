<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginWebauthnConfig extends CommonDBTM
{
    public static $rightname = 'config';

    public const DEFAULTS = [
        'enabled'                  => '0',
        'mode'                     => 'second_factor_optional',
        'prompt_priority'          => 'webauthn_first',
        'rp_id'                    => '',
        'rp_name'                  => 'GLPI',
        'attestation'              => 'none',
        'require_resident_key'     => '0',
        'max_credentials_per_user' => '10',
        'rate_limit_max'           => '5',
        'rate_limit_window'        => '900',
        'mfa_logic'                => 'or',
    ];

    public static function getTypeName($nb = 0): string
    {
        return __('WebAuthn', 'webauthn');
    }

    public static function seedDefaults(): void
    {
        global $DB;

        if (!$DB->tableExists('glpi_plugin_webauthn_config')) {
            return;
        }

        foreach (self::DEFAULTS as $k => $v) {
            $exists = countElementsInTable('glpi_plugin_webauthn_config', ['k' => $k]);
            if ($exists === 0) {
                $DB->insert('glpi_plugin_webauthn_config', ['k' => $k, 'v' => $v]);
            }
        }
    }

    public static function getAll(): array
    {
        global $DB;

        $cfg = self::DEFAULTS;
        if (!$DB->tableExists('glpi_plugin_webauthn_config')) {
            return $cfg;
        }

        foreach ($DB->request(['FROM' => 'glpi_plugin_webauthn_config']) as $row) {
            $cfg[$row['k']] = $row['v'];
        }

        return $cfg;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::getAll();
        return $all[$key] ?? $default;
    }

    public static function isEnabled(): bool
    {
        return (int) self::get('enabled', '0') === 1;
    }

    public static function isOperational(): bool
    {
        return self::isEnabled() && self::get('mode', 'second_factor_optional') !== 'off';
    }

    public static function set(string $key, string $value): bool
    {
        global $DB;

        return $DB->update(
            'glpi_plugin_webauthn_config',
            ['v' => $value],
            ['k' => $key]
        ) !== false
            || $DB->insert('glpi_plugin_webauthn_config', ['k' => $key, 'v' => $value]) !== false;
    }

    public static function saveMany(array $values): void
    {
        foreach ($values as $k => $v) {
            if (!array_key_exists($k, self::DEFAULTS)) {
                continue;
            }
            self::set($k, (string) $v);
        }
    }

    public static function resolveRpId(): string
    {
        global $CFG_GLPI;

        $configured = trim((string) self::get('rp_id', ''));
        if ($configured !== '') {
            return $configured;
        }

        $base = $CFG_GLPI['url_base_api'] ?? ($CFG_GLPI['url_base'] ?? '');
        if ($base === '') {
            return $_SERVER['HTTP_HOST'] ?? 'localhost';
        }

        $host = parse_url($base, PHP_URL_HOST);
        return is_string($host) && $host !== '' ? $host : 'localhost';
    }

    public static function resolveRpName(): string
    {
        $name = trim((string) self::get('rp_name', 'GLPI'));
        return $name !== '' ? $name : 'GLPI';
    }
}
