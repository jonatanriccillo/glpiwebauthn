<?php

declare(strict_types=1);

namespace GlpiPlugin\Webauthn\Service;

use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

final class ChallengeStore
{
    private const SESSION_KEY = 'webauthn_challenge';

    public static function put(string $type, object $options, array $meta = []): void
    {
        $class = match ($type) {
            'registration'     => PublicKeyCredentialCreationOptions::class,
            'authentication'   => PublicKeyCredentialRequestOptions::class,
            default            => throw new \InvalidArgumentException('Unknown challenge type: ' . $type),
        };

        if (!$options instanceof $class) {
            throw new \InvalidArgumentException(sprintf(
                'Expected %s for type %s',
                $class,
                $type
            ));
        }

        $serializer = WebAuthnServerFactory::get()->serializer();
        $json       = $serializer->serialize(
            $options,
            'json',
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                JsonEncode::OPTIONS                      => JSON_THROW_ON_ERROR,
            ]
        );

        $_SESSION[self::SESSION_KEY] = [
            'type'         => $type,
            'optionsClass' => $class,
            'optionsJson'  => $json,
            'meta'         => $meta,
            'expiresAt'    => time() + 300,
        ];
    }

    public static function get(): ?array
    {
        $data = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($data)) {
            return null;
        }
        if (($data['expiresAt'] ?? 0) < time()) {
            self::clear();
            return null;
        }

        $class = $data['optionsClass'] ?? '';
        $json  = $data['optionsJson'] ?? '';
        if (!is_string($class) || $class === '' || !is_string($json) || $json === '') {
            self::clear();
            return null;
        }

        $serializer = WebAuthnServerFactory::get()->serializer();
        $options    = $serializer->deserialize($json, $class, 'json');

        return [
            'type'    => $data['type'] ?? '',
            'options' => $options,
            'meta'    => $data['meta'] ?? [],
        ];
    }

    public static function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }
}
