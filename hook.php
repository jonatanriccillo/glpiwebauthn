<?php
/**
 * Install / uninstall hooks for the WebAuthn plugin.
 */

function plugin_webauthn_install(): bool
{
    global $DB;

    if (
        !$DB->tableExists('glpi_plugin_webauthn_credentials')
        || !$DB->tableExists('glpi_plugin_webauthn_config')
        || !$DB->tableExists('glpi_plugin_webauthn_profiles')
    ) {
        $DB->runFile(Plugin::getPhpDir('webauthn') . '/sql/empty-1.0.0.sql');
    }

    plugin_webauthn_migrate($DB);

    PluginWebauthnProfile::initProfile();

    if (isset($_SESSION['glpiactiveprofile']['id'])) {
        PluginWebauthnProfile::createFirstAccess((int) $_SESSION['glpiactiveprofile']['id']);
    }

    return true;
}

function plugin_webauthn_migrate($DB): void
{
    $migration = new Migration(PLUGIN_WEBAUTHN_VERSION);

    if (
        !$DB->tableExists('glpi_plugin_webauthn_credentials')
        || !$DB->tableExists('glpi_plugin_webauthn_config')
        || !$DB->tableExists('glpi_plugin_webauthn_profiles')
    ) {
        $DB->runFile(Plugin::getPhpDir('webauthn') . '/sql/empty-1.0.0.sql');
    }

    $cred_table = 'glpi_plugin_webauthn_credentials';
    if ($DB->tableExists($cred_table)) {
        $cred_fields = [
            'name'                  => "VARCHAR(128) NOT NULL DEFAULT ''",
            'credential_id'         => 'VARBINARY(1024) NOT NULL',
            'credential_public_key' => 'TEXT NOT NULL',
            'type'                  => "VARCHAR(32) NOT NULL DEFAULT 'public-key'",
            'attestation_type'      => 'VARCHAR(32) DEFAULT NULL',
            'trust_path'            => 'TEXT',
            'aaguid'                => 'CHAR(36) DEFAULT NULL',
            'transports'            => 'VARCHAR(255) DEFAULT NULL',
            'sign_count'            => 'INT UNSIGNED NOT NULL DEFAULT 0',
            'user_handle'           => 'VARBINARY(64) NOT NULL',
            'is_active'             => 'TINYINT(1) NOT NULL DEFAULT 1',
            'last_used_at'          => 'TIMESTAMP NULL DEFAULT NULL',
            'date_creation'         => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
            'date_mod'              => 'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP',
        ];
        foreach ($cred_fields as $field => $type) {
            if (!$DB->fieldExists($cred_table, $field)) {
                $migration->addField($cred_table, $field, $type);
            }
        }
    }

    PluginWebauthnConfig::seedDefaults();
    PluginWebauthnProfile::initProfile();

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
