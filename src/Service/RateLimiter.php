<?php

declare(strict_types=1);

namespace GlpiPlugin\Webauthn\Service;

final class RateLimiter
{
    private const SESSION_KEY = 'webauthn_rate';

    public static function isAllowed(string $bucket): bool
    {
        $max    = (int) \PluginWebauthnConfig::get('rate_limit_max', '5');
        $window = (int) \PluginWebauthnConfig::get('rate_limit_window', '900');
        $now    = time();

        if (!isset($_SESSION[self::SESSION_KEY][$bucket])) {
            $_SESSION[self::SESSION_KEY][$bucket] = ['count' => 0, 'reset' => $now + $window];
        }

        $entry = &$_SESSION[self::SESSION_KEY][$bucket];
        if ($now > ($entry['reset'] ?? 0)) {
            $entry = ['count' => 0, 'reset' => $now + $window];
        }

        if ($entry['count'] >= $max) {
            return false;
        }

        ++$entry['count'];
        return true;
    }
}
