<?php

declare(strict_types=1);

namespace GlpiPlugin\Webauthn\Service;

use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredential;

final class WebAuthnService
{
    private readonly CredentialRepository $credentials;

    public function __construct()
    {
        $this->credentials = new CredentialRepository();
    }

    public function requireHttps(): void
    {
        if (class_exists('Toolbox') && method_exists('Toolbox', 'isHTTPS') && \Toolbox::isHTTPS()) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        if (!$isHttps) {
            throw new \RuntimeException(__('WebAuthn requires HTTPS.', 'webauthn'));
        }
    }

    public function getRpEntity(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::create(
            \PluginWebauthnConfig::resolveRpName(),
            \PluginWebauthnConfig::resolveRpId(),
            null
        );
    }

    public function getUserEntity(int $users_id, ?string $displayName = null): PublicKeyCredentialUserEntity
    {
        $user = new \User();
        if (!$user->getFromDB($users_id)) {
            throw new \RuntimeException(__('User not found', 'webauthn'));
        }

        $name = $user->fields['name'] ?? ('user-' . $users_id);
        $display = $displayName ?? ($user->fields['realname'] ?? $name);

        return PublicKeyCredentialUserEntity::create(
            $name,
            \PluginWebauthnCredential::userHandle($users_id),
            $display,
            null
        );
    }

    public function createRegistrationOptions(int $users_id, string $credentialName): array
    {
        $this->requireHttps();

        $max = (int) \PluginWebauthnConfig::get('max_credentials_per_user', '10');
        if (\PluginWebauthnCredential::countActiveForUser($users_id) >= $max) {
            throw new \RuntimeException(__('Maximum number of passkeys reached.', 'webauthn'));
        }

        if (!PolicyService::userMayRegister($users_id)) {
            throw new \RuntimeException(__('Passkey registration is not allowed for your profile.', 'webauthn'));
        }

        $rp     = $this->getRpEntity();
        $user   = $this->getUserEntity($users_id);
        $handle = \PluginWebauthnCredential::userHandle($users_id);

        $exclude = [];
        foreach ($this->credentials->findAllForUserEntity($handle) as $record) {
            $exclude[] = $record->getPublicKeyCredentialDescriptor();
        }

        $resident = (int) \PluginWebauthnConfig::get('require_resident_key', '0') === 1
            || \PluginWebauthnConfig::get('mode') === 'passwordless';

        $selection = AuthenticatorSelectionCriteria::create(
            authenticatorAttachment: null,
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            residentKey: $resident
                ? AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED
                : AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_DISCOURAGED,
        );

        $options = PublicKeyCredentialCreationOptions::create(
            $rp,
            $user,
            random_bytes(32),
            excludeCredentials: $exclude,
            authenticatorSelection: $selection,
            attestation: \PluginWebauthnConfig::get('attestation', 'none'),
        );

        ChallengeStore::clear();
        ChallengeStore::put('registration', $options, [
            'users_id' => $users_id,
            'name'     => $credentialName,
        ]);

        return $this->optionsToArray($options);
    }

    public function verifyRegistration(string $jsonPayload): void
    {
        $this->requireHttps();
        $stored = ChallengeStore::get();
        if ($stored === null || ($stored['type'] ?? '') !== 'registration') {
            throw new \RuntimeException(__('Registration challenge expired.', 'webauthn'));
        }

        /** @var PublicKeyCredentialCreationOptions $options */
        $options = $stored['options'];
        $meta    = $stored['meta'] ?? [];
        $users_id = (int) ($meta['users_id'] ?? 0);
        $name     = (string) ($meta['name'] ?? 'Passkey');

        $jsonPayload = self::normalizeCredentialPayload($jsonPayload);

        $factory = WebAuthnServerFactory::get();
        $pkc     = $factory->serializer()->deserialize($jsonPayload, PublicKeyCredential::class, 'json');

        if (!$pkc->response instanceof \Webauthn\AuthenticatorAttestationResponse) {
            throw new \RuntimeException(__('Invalid attestation response.', 'webauthn'));
        }

        $record = $factory->attestationValidator()->check(
            $pkc->response,
            $options,
            \PluginWebauthnConfig::resolveRpId()
        );

        $this->credentials->saveCredentialRecord($record, $users_id, $name);
        ChallengeStore::clear();
    }

    public function createAuthenticationOptions(?int $users_id = null): array
    {
        $this->requireHttps();

        $allow = [];
        $userHandle = null;

        if ($users_id !== null && $users_id > 0) {
            $userHandle = \PluginWebauthnCredential::userHandle($users_id);
            foreach ($this->credentials->findAllForUserEntity($userHandle) as $record) {
                $allow[] = $record->getPublicKeyCredentialDescriptor();
            }
        }

        $resident = \PluginWebauthnConfig::get('mode') === 'passwordless'
            || (int) \PluginWebauthnConfig::get('require_resident_key', '0') === 1;

        if (!$resident && $allow === []) {
            if ($users_id === null || $users_id <= 0) {
                throw new \RuntimeException(
                    __('Enter your GLPI username before signing in with a passkey.', 'webauthn')
                );
            }
            throw new \RuntimeException(
                __('No passkeys registered for this user.', 'webauthn')
            );
        }

        $options = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            rpId: \PluginWebauthnConfig::resolveRpId(),
            allowCredentials: $resident ? [] : $allow,
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
        );

        ChallengeStore::clear();
        ChallengeStore::put('authentication', $options, [
            'users_id' => $users_id,
        ]);

        $payload = $this->optionsToArray($options);
        if ($resident) {
            unset($payload['allowCredentials']);
        }

        return $payload;
    }

    public function verifyAuthentication(string $jsonPayload): int
    {
        $this->requireHttps();
        $stored = ChallengeStore::get();
        if ($stored === null || ($stored['type'] ?? '') !== 'authentication') {
            throw new \RuntimeException(__('Authentication challenge expired.', 'webauthn'));
        }

        /** @var PublicKeyCredentialRequestOptions $options */
        $options = $stored['options'];

        $jsonPayload = self::normalizeCredentialPayload($jsonPayload);

        $factory = WebAuthnServerFactory::get();
        $pkc     = $factory->serializer()->deserialize($jsonPayload, PublicKeyCredential::class, 'json');

        if (!$pkc->response instanceof \Webauthn\AuthenticatorAssertionResponse) {
            throw new \RuntimeException(__('Invalid assertion response.', 'webauthn'));
        }

        $credentialId = $pkc->rawId;
        $record       = $this->credentials->findOneByCredentialId($credentialId);
        if ($record === null) {
            throw new \RuntimeException(__('Unknown passkey.', 'webauthn'));
        }

        $dbId = $this->credentials->getDbIdByCredentialId($credentialId);
        $oldCounter = $record->counter;

        $updated = $factory->assertionValidator()->check(
            $record,
            $pkc->response,
            $options,
            \PluginWebauthnConfig::resolveRpId(),
            $record->userHandle,
        );

        if ($updated->counter <= $oldCounter && $oldCounter > 0) {
            throw new \RuntimeException(__('Passkey clone detected.', 'webauthn'));
        }

        if ($dbId !== null) {
            $this->credentials->updateCounter($updated, $dbId);
        }

        ChallengeStore::clear();

        return $this->usersIdFromHandle($record->userHandle);
    }

    public function usersIdFromHandle(string $userHandle): int
    {
        global $DB;

        $it = $DB->request([
            'SELECT' => ['users_id'],
            'FROM'   => \PluginWebauthnCredential::getTable(),
            'WHERE'  => ['user_handle' => $userHandle, 'is_active' => 1],
            'LIMIT'  => 1,
        ]);

        if (count($it) === 0) {
            throw new \RuntimeException(__('User not found for passkey.', 'webauthn'));
        }

        return (int) $it->current()['users_id'];
    }

    private static function normalizeCredentialPayload(string $jsonPayload): string
    {
        $decoded = json_decode($jsonPayload, true);
        if (is_array($decoded) && isset($decoded['credential'])) {
            return json_encode($decoded['credential'], JSON_THROW_ON_ERROR);
        }

        return $jsonPayload;
    }

    private function optionsToArray(object $options): array
    {
        $factory = WebAuthnServerFactory::get();
        $json    = $factory->serializer()->serialize(
            $options,
            'json',
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                JsonEncode::OPTIONS                      => JSON_THROW_ON_ERROR,
            ]
        );

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
