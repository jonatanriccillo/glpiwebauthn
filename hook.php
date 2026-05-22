<?php
/**
 * Install / uninstall hooks for the WebAuthn plugin.
 */

function plugin_webauthn_install(): bool
{
    global $DB;

    if (!$DB->tableExists('glpi_plugin_webauthn_credentials')) {
        $DB->runFile(Plugin::getPhpDir('webauthn') . '/sql/empty-1.0.0.sql');
    } else {
        plugin_webauthn_migrate($DB);
    }

    PluginWebauthnConfig::seedDefaults();
    PluginWebauthnProfile::initProfile();

    if (isset($_SESSION['glpiactiveprofile']['id'])) {
        PluginWebauthnProfile::createFirstAccess((int) $_SESSION['glpiactiveprofile']['id']);
    }

    return true;
}

function plugin_webauthn_migrate($DB): void
{
    $migration = new Migration(PLUGIN_WEBAUTHN_VERSION);
    $migration->executeMigration();
}

function plugin_webauthn_uninstall(): bool
{
    global $DB;

    foreach (
        [
            'glpi_plugin_webauthn_credentials',
            'glpi_plugin_webauthn_config',
            'glpi_plugin_webauthn_profiles',
        ] as $table
    ) {
        if ($DB->tableExists($table)) {
            $DB->dropTable($table);
        }
    }

    foreach (['glpi_displaypreferences', 'glpi_logs'] as $glpi_table) {
        $DB->delete($glpi_table, ['itemtype' => ['LIKE', 'PluginWebauthn%']]);
    }

    ProfileRight::deleteProfileRights(['plugin_webauthn']);

    return true;
}
