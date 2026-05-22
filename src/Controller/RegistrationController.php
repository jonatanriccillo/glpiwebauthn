<?php

declare(strict_types=1);

namespace GlpiPlugin\Webauthn\Controller;

use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Webauthn\Service\RateLimiter;
use GlpiPlugin\Webauthn\Service\WebAuthnService;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/register/options', name: 'webauthn_register_options', methods: ['POST'])]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function options(Request $request): JsonResponse
    {
        if (!\PluginWebauthnConfig::isEnabled()) {
            return new JsonResponse(['error' => __('Plugin disabled', 'webauthn')], 403);
        }

        $users_id = (int) Session::getLoginUserID();
        if ($users_id <= 0) {
            return new JsonResponse(['error' => __('Not authenticated', 'webauthn')], 401);
        }

        if (!RateLimiter::isAllowed('register_' . $users_id)) {
            return new JsonResponse(['error' => __('Too many attempts', 'webauthn')], 429);
        }

        $name = trim((string) $request->request->get('name', ''));
        if ($name === '') {
            return new JsonResponse(['error' => __('Passkey name is required', 'webauthn')], 400);
        }

        try {
            $service = new WebAuthnService();
            $options = $service->createRegistrationOptions($users_id, $name);
            return new JsonResponse(['publicKey' => $options]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/register/verify', name: 'webauthn_register_verify', methods: ['POST'])]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function verify(Request $request): JsonResponse
    {
        if (!\PluginWebauthnConfig::isEnabled()) {
            return new JsonResponse(['error' => __('Plugin disabled', 'webauthn')], 403);
        }

        $users_id = (int) Session::getLoginUserID();
        if ($users_id <= 0) {
            return new JsonResponse(['error' => __('Not authenticated', 'webauthn')], 401);
        }

        $payload = $request->getContent();
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            $decoded = $request->request->all();
        }
        if ($payload === '') {
            $payload = json_encode($decoded['credential'] ?? $decoded, JSON_THROW_ON_ERROR);
        }

        try {
            (new WebAuthnService())->verifyRegistration($payload);
            return new JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
