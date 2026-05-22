<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginWebauthnProfile extends Profile
{
    public static $rightname = 'profile';

    public const SUPER_ADMIN_PROFILE_ID = 4;

    public static function getAllRights(bool $all = false): array
    {
        return [
            [
                'itemtype' => 'PluginWebauthnCredential',
                'label'    => __('WebAuthn', 'webauthn'),
                'field'    => 'plugin_webauthn',
            ],
        ];
    }

    public static function initProfile(): void
    {
        foreach (self::getAllRights() as $right) {
            if (countElementsInTable('glpi_profilerights', ['name' => $right['field']]) === 0) {
                ProfileRight::addProfileRights([$right['field']]);
            }
        }
    }

    public static function createFirstAccess(int $profiles_id): void
    {
        $rights = [];
        foreach (self::getAllRights(true) as $right) {
            $rights[$right['field']] = ALLSTANDARDRIGHT;
        }

        ProfileRight::updateProfileRights(self::SUPER_ADMIN_PROFILE_ID, $rights);

        if ($profiles_id !== self::SUPER_ADMIN_PROFILE_ID && $profiles_id > 0) {
            ProfileRight::updateProfileRights($profiles_id, $rights);
        }
    }

    public static function getProfileRules(int $profiles_id): array
    {
        global $DB;

        $defaults = [
            'webauthn_enforced' => 0,
            'webauthn_allowed'  => 1,
        ];

        if (!$DB->tableExists('glpi_plugin_webauthn_profiles')) {
            return $defaults;
        }

        $it = $DB->request([
            'FROM'  => 'glpi_plugin_webauthn_profiles',
            'WHERE' => ['profiles_id' => $profiles_id],
            'LIMIT' => 1,
        ]);

        if (count($it) === 0) {
            return $defaults;
        }

        $row = $it->current();
        return [
            'webauthn_enforced' => (int) $row['webauthn_enforced'],
            'webauthn_allowed'  => (int) $row['webauthn_allowed'],
        ];
    }

    public static function saveProfileRules(int $profiles_id, array $input): void
    {
        global $DB;

        if (!$DB->tableExists('glpi_plugin_webauthn_profiles')) {
            return;
        }

        $data = [
            'profiles_id'       => $profiles_id,
            'webauthn_enforced' => (int) ($input['webauthn_enforced'] ?? 0),
            'webauthn_allowed'  => (int) ($input['webauthn_allowed'] ?? 1),
        ];

        $existing = countElementsInTable('glpi_plugin_webauthn_profiles', ['profiles_id' => $profiles_id]);
        if ($existing > 0) {
            $DB->update('glpi_plugin_webauthn_profiles', $data, ['profiles_id' => $profiles_id]);
        } else {
            $DB->insert('glpi_plugin_webauthn_profiles', $data);
        }
    }

    public function showForm($profiles_id = 0, $openform = true, $closeform = true): void
    {
        $rules = self::getProfileRules((int) $profiles_id);

        echo "<div class='firstbloc'>";
        $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);
        if ($canedit && $openform) {
            $profile = new Profile();
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        }

        echo "<h3>" . __('WebAuthn', 'webauthn') . "</h3>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'><th colspan='2'>" . __('Passkey policy', 'webauthn') . "</th></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Allow passkeys for this profile', 'webauthn') . "</td><td>";
        Dropdown::showYesNo('webauthn_allowed', $rules['webauthn_allowed']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Require passkey registration', 'webauthn') . "</td><td>";
        Dropdown::showYesNo('webauthn_enforced', $rules['webauthn_enforced']);
        echo "</td></tr>";

        echo "</table>";

        $rights = self::getAllRights();
        $profile = new Profile();
        $profile->getFromDB($profiles_id);
        $profile->displayRightsChoiceMatrix(
            $rights,
            [
                'canedit'       => $canedit,
                'default_class' => 'tab_bg_2',
                'title'         => __('WebAuthn administration', 'webauthn'),
            ]
        );

        if ($canedit && $closeform) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            echo '</div>';
            Html::closeForm();
        }
        echo '</div>';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof Profile) {
            $profile = new self();
            $profile->showForm($item->getID());
            return true;
        }
        return false;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item instanceof Profile) {
            return self::createTabEntry(__('WebAuthn', 'webauthn'), 0, self::class, 'ti ti-fingerprint');
        }
        return '';
    }

    public static function itemUpdate(CommonDBTM $item): void
    {
        if (!$item instanceof Profile) {
            return;
        }
        if (!isset($_POST['webauthn_enforced']) && !isset($_POST['webauthn_allowed'])) {
            return;
        }
        self::saveProfileRules((int) $item->getID(), $_POST);
    }
}
