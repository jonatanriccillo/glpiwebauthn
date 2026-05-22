<?php
/**
 * WebAuthn / FIDO2 passkeys plugin for GLPI 11.
 *
 * Licensed under GPLv3.
 */

use Glpi\Http\Firewall;
use Glpi\Plugin\Hooks;

define('PLUGIN_WEBAUTHN_VERSION', '1.0.1');
define('PLUGIN_WEBAUTHN_MIN_GLPI', '11.0.0');
define('PLUGIN_WEBAUTHN_MAX_GLPI', '11.9.99');

function plugin_webauthn_autoload(): void
{
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }
}

function plugin_init_webauthn(): void
{
    global $PLUGIN_HOOKS;

    plugin_webauthn_autoload();

    $PLUGIN_HOOKS['csrf_compliant']['webauthn'] = true;
    $PLUGIN_HOOKS['change_profile']['webauthn'] = ['PluginWebauthnProfile', 'initProfile'];
    $PLUGIN_HOOKS['config_page']['webauthn']      = 'front/config.form.php';

    Plugin::registerClass('PluginWebauthnProfile', ['addtabon' => 'Profile']);
    Plugin::registerClass('PluginWebauthnCredential', ['addtabon' => ['Preference', 'User']]);

    if (class_exists(Firewall::class)) {
        Firewall::addPluginStrategyForLegacyScripts(
            'webauthn',
            '#^(auth/|register/)#',
            Firewall::STRATEGY_NO_CHECK
        );
    }

    $PLUGIN_HOOKS[Hooks::POST_INIT]['webauthn']     = 'plugin_webauthn_post_init';
    $PLUGIN_HOOKS[Hooks::DISPLAY_LOGIN]['webauthn'] = 'plugin_webauthn_display_login';
    $PLUGIN_HOOKS['item_update']['webauthn']        = ['PluginWebauthnProfile', 'itemUpdate'];
}

function plugin_webauthn_post_init(): void
{
    if (!PluginWebauthnConfig::isOperational()) {
        return;
    }

    if (class_exists('GlpiPlugin\\Webauthn\\Bootstrap\\RequestBridge')) {
        \GlpiPlugin\Webauthn\Bootstrap\RequestBridge::handlePostInit();
    }
}

function plugin_webauthn_display_login(): void
{
    if (!PluginWebauthnConfig::isOperational()) {
        return;
    }

    $mode = PluginWebauthnConfig::get('mode', 'second_factor_optional');
    if (!in_array($mode, ['passwordless', 'second_factor_optional'], true)) {
        return;
    }

    if (!class_exists('GlpiPlugin\\Webauthn\\Bootstrap\\RequestBridge')) {
        return;
    }

    \GlpiPlugin\Webauthn\Bootstrap\RequestBridge::renderLoginButton();
}

function plugin_version_webauthn(): array
{
    return [
        'name'         => 'WebAuthn',
        'version'      => PLUGIN_WEBAUTHN_VERSION,
        'author'       => 'Jonatan Riccillo',
        'license'      => 'GPLv3',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_WEBAUTHN_MIN_GLPI,
                'max' => PLUGIN_WEBAUTHN_MAX_GLPI,
            ],
        ],
    ];
}

function plugin_webauthn_check_prerequisites(): bool
{
    if (version_compare(GLPI_VERSION, PLUGIN_WEBAUTHN_MIN_GLPI, 'lt')) {
        if (method_exists('Plugin', 'messageIncompatible')) {
            echo Plugin::messageIncompatible('core', PLUGIN_WEBAUTHN_MIN_GLPI);
        }
        return false;
    }

    if (!extension_loaded('openssl')) {
        echo __('PHP openssl extension is required.', 'webauthn');
        return false;
    }

    return true;
}

function plugin_webauthn_check_config($verbose = false): bool
{
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        return true;
    }

    if ($verbose) {
        echo __('Run composer install in the plugin directory.', 'webauthn');
    }

    return false;
}
