<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginWebauthnCredential extends CommonDBTM
{
    public static $rightname = 'config';

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_webauthn_credentials';
    }

    public static function getTypeName($nb = 0): string
    {
        return _n('Passkey', 'Passkeys', $nb, 'webauthn');
    }

    public static function getIcon(): string
    {
        return 'ti ti-fingerprint';
    }

    public static function userHandle(int $users_id): string
    {
        return hash('sha256', 'glpi:' . $users_id, true);
    }

    public static function countActiveForUser(int $users_id): int
    {
        return countElementsInTable(self::getTable(), [
            'users_id'  => $users_id,
            'is_active' => 1,
        ]);
    }

    public static function getActiveForUser(int $users_id): array
    {
        global $DB;

        $rows = [];
        foreach (
            $DB->request([
                'FROM'  => self::getTable(),
                'WHERE' => [
                    'users_id'  => $users_id,
                    'is_active' => 1,
                ],
                'ORDER' => 'date_creation DESC',
            ]) as $row
        ) {
            $rows[] = $row;
        }

        return $rows;
    }

    public static function findByCredentialId(string $credentialIdBinary): ?array
    {
        global $DB;

        $it = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'credential_id' => $credentialIdBinary,
                'is_active'     => 1,
            ],
            'LIMIT' => 1,
        ]);

        return count($it) > 0 ? $it->current() : null;
    }

    public static function revoke(int $id, int $users_id = 0): bool
    {
        global $DB;

        $where = ['id' => $id];
        if ($users_id > 0) {
            $where['users_id'] = $users_id;
        }

        return $DB->update(self::getTable(), ['is_active' => 0], $where) !== false;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item instanceof User && Session::haveRight('plugin_webauthn', READ)) {
            return self::createTabEntry(
                self::getTypeName(Session::getPluralNumber()),
                0,
                self::class,
                self::getIcon()
            );
        }
        if ($item instanceof Preference) {
            return self::createTabEntry(
                self::getTypeName(Session::getPluralNumber()),
                0,
                self::class,
                self::getIcon()
            );
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof User) {
            self::showAdminList((int) $item->getID());
            return true;
        }
        if ($item instanceof Preference) {
            self::showUserPreferences();
            return true;
        }
        return false;
    }

    public static function showUserPreferences(): void
    {
        $users_id = (int) Session::getLoginUserID();
        if ($users_id <= 0) {
            return;
        }

        if (!PluginWebauthnConfig::isOperational()) {
            echo "<div class='alert alert-info'>" . __('WebAuthn is disabled by the administrator.', 'webauthn') . '</div>';
            return;
        }

        self::renderManagementUI($users_id, true);
    }

    public static function showAdminList(int $users_id): void
    {
        if (!Session::haveRight('plugin_webauthn', READ)) {
            return;
        }

        echo "<h3>" . __('Passkeys', 'webauthn') . "</h3>";
        $is_self = (int) Session::getLoginUserID() === $users_id;
        self::renderManagementUI($users_id, $is_self);
        if (!$is_self) {
            echo "<p class='text-muted mt-2'>" . __('Users register passkeys from their own Preferences tab.', 'webauthn') . '</p>';
        }
    }

    private static function renderManagementUI(int $users_id, bool $can_register): void
    {
        global $CFG_GLPI;

        if (!PluginWebauthnConfig::isOperational()) {
            echo "<div class='alert alert-info'>" . __('WebAuthn is disabled by the administrator.', 'webauthn') . '</div>';
            return;
        }

        $creds = self::getActiveForUser($users_id);
        $max   = (int) PluginWebauthnConfig::get('max_credentials_per_user', '10');
        $may_register = true;
        if (class_exists('GlpiPlugin\\Webauthn\\Service\\PolicyService')) {
            $may_register = \GlpiPlugin\Webauthn\Service\PolicyService::userMayRegister($users_id);
        }
        $can_add = $can_register && count($creds) < $max && $may_register;

        $plugin_base = Plugin::getWebDir('webauthn');

        echo "<div id='webauthn-prefs' data-plugin-base='" . htmlspecialchars($plugin_base) . "'";
        echo " data-users-id='" . $users_id . "'>";
        echo "<div id='webauthn_error' class='alert alert-danger d-none mb-3' role='alert'></div>";

        if ($can_add) {
            echo "<div class='mb-3'>";
            echo "<label class='form-label'>" . __('Passkey name', 'webauthn') . "</label>";
            echo "<input type='text' class='form-control' id='webauthn_cred_name' maxlength='128' ";
            echo "placeholder='" . __('e.g. Work iPhone', 'webauthn') . "'>";
            echo "<button type='button' class='btn btn-primary mt-2' id='webauthn_register_btn'>";
            echo __('Add passkey', 'webauthn');
            echo "</button></div>";
        }

        echo "<table class='tab_cadre_fixehov'><tr>";
        echo "<th>" . __('Name', 'webauthn') . "</th><th>" . __('Created', 'webauthn') . "</th>";
        echo "<th>" . __('Last used', 'webauthn') . "</th><th></th></tr>";

        if (count($creds) === 0) {
            echo "<tr class='tab_bg_1'><td colspan='4'>" . __('No passkeys registered', 'webauthn') . '</td></tr>';
        }

        foreach ($creds as $row) {
            echo "<tr class='tab_bg_1'>";
            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
            echo '<td>' . Html::convDateTime($row['date_creation']) . '</td>';
            echo '<td>' . ($row['last_used_at'] ? Html::convDateTime($row['last_used_at']) : '—') . '</td>';
            echo '<td>';
            if ($can_register || Session::haveRight('plugin_webauthn', UPDATE)) {
                echo "<button type='button' class='btn btn-sm btn-outline-danger webauthn-revoke' data-id='" . (int) $row['id'] . "'>";
                echo __('Revoke', 'webauthn');
                echo '</button>';
            }
            echo '</td></tr>';
        }

        echo '</table></div>';

        $js = $plugin_base . '/public/webauthn.js';
        echo Html::script($js);
    }
}
