<?php

include dirname(__DIR__, 3) . '/inc/includes.php';

Session::checkRight('config', UPDATE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'mode',
        'prompt_priority',
        'rp_id',
        'rp_name',
        'attestation',
        'max_credentials_per_user',
        'rate_limit_max',
        'rate_limit_window',
        'mfa_logic',
    ];
    $save = [
        'enabled'              => isset($_POST['enabled']) ? '1' : '0',
        'require_resident_key' => isset($_POST['require_resident_key']) ? '1' : '0',
    ];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $save[$f] = (string) $_POST[$f];
        }
    }
    PluginWebauthnConfig::saveMany($save);
    Session::addMessageAfterRedirect(__('Configuration saved', 'webauthn'), false, INFO);
    Html::back();
}

Html::header(
    PluginWebauthnConfig::getTypeName(),
    $_SERVER['PHP_SELF'],
    'config',
    'PluginWebauthnConfig'
);

$cfg    = PluginWebauthnConfig::getAll();
$action = Plugin::getWebDir('webauthn') . '/front/config.form.php';

echo "<form method='post' action='" . htmlspecialchars($action) . "'>";
echo "<table class='tab_cadre_fixe' style='max-width:720px;margin:1em auto'>";
echo "<tr class='tab_bg_1'><th colspan='2'>" . __('WebAuthn settings', 'webauthn') . "</th></tr>";

echo "<tr class='tab_bg_1'><td>" . __('Enabled', 'webauthn') . "</td><td>";
Dropdown::showYesNo('enabled', (int) ($cfg['enabled'] ?? 0));
echo "</td></tr>";

echo "<tr class='tab_bg_1'><td>" . __('Mode', 'webauthn') . "</td><td>";
Dropdown::showFromArray('mode', [
    'off'                    => __('Off', 'webauthn'),
    'second_factor_optional' => __('Second factor (optional)', 'webauthn'),
    'second_factor'          => __('Second factor (required when passkeys exist)', 'webauthn'),
    'passwordless'           => __('Passwordless allowed', 'webauthn'),
], ['value' => $cfg['mode'] ?? 'second_factor_optional']);
echo "</td></tr>";

echo "<tr class='tab_bg_1'><td>" . __('Prompt priority', 'webauthn') . "</td><td>";
Dropdown::showFromArray('prompt_priority', [
    'webauthn_first' => __('Passkey first', 'webauthn'),
    'totp_first'     => __('Authenticator app first', 'webauthn'),
], ['value' => $cfg['prompt_priority'] ?? 'webauthn_first']);
echo "</td></tr>";

echo "<tr class='tab_bg_1'><td>" . __('MFA logic when TOTP and passkey both exist', 'webauthn') . "</td><td>";
Dropdown::showFromArray('mfa_logic', [
    'or'  => __('Either passkey or TOTP', 'webauthn'),
    'and' => __('Both required (strict)', 'webauthn'),
], ['value' => $cfg['mfa_logic'] ?? 'or']);
echo "</td></tr>";

echo "<tr class='tab_bg_1'><td>RP ID</td><td>";
echo "<input type='text' class='form-control' name='rp_id' value='" . htmlspecialchars($cfg['rp_id'] ?? '') . "' ";
echo "placeholder='" . htmlspecialchars(PluginWebauthnConfig::resolveRpId()) . "'>";
echo "<br><small class='text-muted'>" . __('Leave empty to derive from GLPI URL', 'webauthn') . "</small>";
echo "</td></tr>";

echo "<tr class='tab_bg_1'><td>RP " . __('Name', 'webauthn') . "</td><td>";
echo "<input type='text' class='form-control' name='rp_name' value='" . htmlspecialchars($cfg['rp_name'] ?? 'GLPI') . "'>";
echo "</td></tr>";

echo "<tr class='tab_bg_1'><td>" . __('Attestation', 'webauthn') . "</td><td>";
Dropdown::showFromArray('attestation', [
    'none'     => 'none',
    'indirect' => 'indirect',
    'direct'   => 'direct',
], ['value' => $cfg['attestation'] ?? 'none']);
echo "</td></tr>";

echo "<tr class='tab_bg_1'><td>" . __('Require resident key', 'webauthn') . "</td><td>";
Dropdown::showYesNo('require_resident_key', (int) ($cfg['require_resident_key'] ?? 0));
echo "</td></tr>";

echo "<tr class='tab_bg_1'><td>" . __('Max passkeys per user', 'webauthn') . "</td><td>";
echo "<input type='number' class='form-control' name='max_credentials_per_user' min='1' max='50' ";
echo "value='" . (int) ($cfg['max_credentials_per_user'] ?? 10) . "'>";
echo "</td></tr>";

echo "<tr class='tab_bg_1'><td>" . __('Rate limit (attempts)', 'webauthn') . "</td><td>";
echo "<input type='number' class='form-control' name='rate_limit_max' min='1' value='" . (int) ($cfg['rate_limit_max'] ?? 5) . "'>";
echo "</td></tr>";

echo "<tr class='tab_bg_1'><td>" . __('Rate limit window (seconds)', 'webauthn') . "</td><td>";
echo "<input type='number' class='form-control' name='rate_limit_window' min='60' value='" . (int) ($cfg['rate_limit_window'] ?? 900) . "'>";
echo "</td></tr>";

echo "<tr class='tab_bg_1'><td colspan='2' class='center'>";
echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
echo "</td></tr>";

echo '</table>';
Html::closeForm();

Html::footer();
