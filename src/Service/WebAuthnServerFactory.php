<?php

declare(strict_types=1);

namespace GlpiPlugin\Webauthn\Service;

use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;

final class WebAuthnServerFactory
{
    private static ?self $instance = null;

    private readonly \Symfony\Component\Serializer\SerializerInterface $serializer;

    private readonly AuthenticatorAttestationResponseValidator $attestationValidator;

    private readonly AuthenticatorAssertionResponseValidator $assertionValidator;

    private function __construct()
    {
        $attestationManager = AttestationStatementSupportManager::create();
        $attestationManager->add(NoneAttestationStatementSupport::create());

        $attestation = \PluginWebauthnConfig::get('attestation', 'none');
        if ($attestation === 'direct' || $attestation === 'indirect') {
            if (class_exists(\Webauthn\AttestationStatement\PackedAttestationStatementSupport::class)) {
                $attestationManager->add(\Webauthn\AttestationStatement\PackedAttestationStatementSupport::create());
            }
        }

        $factory = new WebauthnSerializerFactory($attestationManager);
        $this->serializer = $factory->create();

        $csmFactory = new CeremonyStepManagerFactory();
        $this->attestationValidator = AuthenticatorAttestationResponseValidator::create(
            $csmFactory->creationCeremony()
        );
        $this->assertionValidator = AuthenticatorAssertionResponseValidator::create(
            $csmFactory->requestCeremony()
        );
    }

    public static function get(): self
    {
        return self::$instance ??= new self();
    }

    public function serializer(): \Symfony\Component\Serializer\SerializerInterface
    {
        return $this->serializer;
    }

    public function attestationValidator(): AuthenticatorAttestationResponseValidator
    {
        return $this->attestationValidator;
    }

    public function assertionValidator(): AuthenticatorAssertionResponseValidator
    {
        return $this->assertionValidator;
    }
}
