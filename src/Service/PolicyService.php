<?php

declare(strict_types=1);

namespace GlpiPlugin\Webauthn\Service;

final class PolicyService
{
    public static function userMayRegister(int $users_id): bool
    {
        if (!\PluginWebauthnConfig::isOperational()) {
            return false;
        }

        $profiles = \Profile_User::getUserProfiles($users_id);
        if ($profiles === []) {
            return true;
        }

        foreach ($profiles as $profile) {
            $rules = \PluginWebauthnProfile::getProfileRules((int) $profile['profiles_id']);
            if ((int) $rules['webauthn_allowed'] === 1) {
                return true;
            }
        }

        return false;
    }

    public static function isEnforcedForUser(int $users_id): bool
    {
        if (!\PluginWebauthnConfig::isOperational()) {
            return false;
        }

        foreach (\Profile_User::getUserProfiles($users_id) as $profile) {
            $rules = \PluginWebauthnProfile::getProfileRules((int) $profile['profiles_id']);
            if ((int) $rules['webauthn_enforced'] === 1) {
                return true;
            }
        }

        return false;
    }

    public static function shouldUseWebAuthnFirst(int $users_id): bool
    {
        if (\PluginWebauthnConfig::get('prompt_priority', 'webauthn_first') !== 'webauthn_first') {
            return false;
        }

        return \PluginWebauthnCredential::countActiveForUser($users_id) > 0;
    }

    public static function userHasTotp(int $users_id): bool
    {
        $totp = new \Glpi\Security\TOTPManager();

        return $totp->is2FAEnabled($users_id);
    }

    public static function shouldRedirectToPasskeyPrompt(int $users_id): bool
    {
        if (\PluginWebauthnCredential::countActiveForUser($users_id) === 0) {
            return false;
        }

        if (self::shouldUseWebAuthnFirst($users_id)) {
            return true;
        }

        return self::requiresSecondFactor($users_id) && !self::userHasTotp($users_id);
    }

    public static function requiresSecondFactor(int $users_id): bool
    {
        $mode = \PluginWebauthnConfig::get('mode', 'second_factor_optional');
        if ($mode === 'off') {
            return false;
        }
        if ($mode === 'passwordless') {
            return false;
        }

        if ($mode === 'second_factor' || self::isEnforcedForUser($users_id)) {
            return \PluginWebauthnCredential::countActiveForUser($users_id) > 0
                || self::isEnforcedForUser($users_id);
        }

        return false;
    }

    public static function passwordlessAllowed(): bool
    {
        if (!\PluginWebauthnConfig::isOperational()) {
            return false;
        }

        return in_array(
            \PluginWebauthnConfig::get('mode', 'second_factor_optional'),
            ['passwordless', 'second_factor_optional'],
            true
        );
    }

    public static function totpStillRequired(int $users_id): bool
    {
        if (\PluginWebauthnConfig::get('mfa_logic', 'or') !== 'and') {
            return false;
        }

        $totp = new \Glpi\Security\TOTPManager();
        return $totp->is2FAEnabled($users_id);
    }
}
