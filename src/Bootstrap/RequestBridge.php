<?php

declare(strict_types=1);

namespace GlpiPlugin\Webauthn\Bootstrap;

use GlpiPlugin\Webauthn\Service\PolicyService;
use Html;
use Session;
use Toolbox;

final class RequestBridge
{
    public static function handlePostInit(): void
    {
        if (isAPI() || isCommandLine()) {
            return;
        }

        $path = self::currentPath();
        if ($path === '') {
            return;
        }

        $webDir = \Plugin::getWebDir('webauthn');
        if ($webDir !== '' && str_starts_with($path, $webDir)) {
            return;
        }

        if (isset($_SESSION['webauthn_prompt_shown'])) {
            return;
        }

        if (!isset($_SESSION['mfa_pre_auth']['user_id'])) {
            return;
        }

        $users_id = (int) $_SESSION['mfa_pre_auth']['user_id'];
        if ($users_id <= 0) {
            return;
        }

        if (!PolicyService::shouldUseWebAuthnFirst($users_id)) {
            return;
        }

        if (\PluginWebauthnCredential::countActiveForUser($users_id) === 0) {
            return;
        }

        if (preg_match('#/MFA/Prompt#i', $path) !== 1) {
            return;
        }

        global $CFG_GLPI;
        $_SESSION['webauthn_prompt_shown'] = true;
        Html::redirect(\Plugin::getWebDir('webauthn') . '/auth/prompt');
    }

    public static function renderLoginButton(): void
    {
        if (!PolicyService::passwordlessAllowed()) {
            return;
        }

        if (!self::isWebAuthnSupported()) {
            echo "<p class='text-muted small'>" . __('Passkeys are not supported in this browser.', 'webauthn') . '</p>';
            return;
        }

        global $CFG_GLPI;
        $base = \Plugin::getWebDir('webauthn');

        echo "<div class='webauthn-login-block mt-3 pt-3 border-top'>";
        echo "<p class='text-muted small mb-2'>" . __('Or sign in with a passkey', 'webauthn') . '</p>';
        echo "<div id='webauthn_login_error' class='alert alert-danger d-none mb-2' role='alert'></div>";
        echo "<button type='button' class='btn btn-outline-secondary w-100' id='webauthn_passwordless_btn' ";
        echo "data-plugin-base='" . htmlspecialchars($base) . "'>";
        echo "<i class='ti ti-fingerprint me-1'></i>";
        echo __('Sign in with passkey', 'webauthn');
        echo '</button></div>';
        echo Html::script($base . '/public/webauthn.js');
    }

    public static function isWebAuthnSupported(): bool
    {
        return true;
    }

    public static function currentPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path)) {
            return '/';
        }

        global $CFG_GLPI;
        $root = $CFG_GLPI['root_doc'] ?? '';
        if ($root !== '' && str_starts_with($path, $root)) {
            $path = substr($path, strlen($root)) ?: '/';
        }

        return $path;
    }

    public static function completeLogin(int $users_id, bool $remember_me = false, bool $noauto = false): void
    {
        global $CFG_GLPI;

        $_SESSION['mfa_pre_auth'] = [
            'user_id'     => $users_id,
            'username'    => self::usernameForUser($users_id),
            'remember_me' => $remember_me,
            'noauto'      => $noauto,
            'redirect'    => $_REQUEST['redirect'] ?? ($_GET['redirect'] ?? null),
        ];
        $_SESSION['mfa_success'] = true;
        unset($_SESSION['webauthn_prompt_shown']);

        $redirect = $_SESSION['mfa_pre_auth']['redirect'] ?? '';
        $qs       = $redirect !== '' ? ('?redirect=' . rawurlencode((string) $redirect)) : '';
        Html::redirect($CFG_GLPI['root_doc'] . '/front/login.php' . $qs);
    }

    private static function usernameForUser(int $users_id): string
    {
        $user = new \User();
        if ($user->getFromDB($users_id)) {
            return (string) ($user->fields['name'] ?? '');
        }
        return '';
    }
}
