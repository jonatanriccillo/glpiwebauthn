<?php

declare(strict_types=1);

namespace GlpiPlugin\Webauthn\Controller;

use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Webauthn\Service\PolicyService;
use GlpiPlugin\Webauthn\Service\RateLimiter;
use GlpiPlugin\Webauthn\Service\WebAuthnService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthenticationController extends AbstractController
{
    #[Route('/auth/prompt', name: 'webauthn_auth_prompt', methods: ['GET'])]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function prompt(Request $request): Response
    {
        if (!\PluginWebauthnConfig::isOperational()) {
            return $this->redirectToLogin();
        }

        $pre = $_SESSION['mfa_pre_auth'] ?? null;
        if ($pre === null && !PolicyService::passwordlessAllowed()) {
            return $this->redirectToLogin();
        }

        global $CFG_GLPI;

        $vars = [
            'plugin_base' => \Plugin::getWebDir('webauthn'),
            'totp_url'    => $CFG_GLPI['root_doc'] . '/MFA/Prompt',
            'login_url'   => $CFG_GLPI['root_doc'] . '/',
            'redirect'    => $pre['redirect'] ?? '',
            'csrf_token'  => \Session::getNewCSRFToken(),
            'title'       => __('Verify with passkey', 'webauthn'),
        ];

        $template = dirname(__DIR__, 2) . '/templates/auth_prompt.html.twig';
        if (is_file($template)) {
            return $this->render($template, $vars);
        }

        return new Response(self::renderPromptFallback($vars));
    }

    private static function renderPromptFallback(array $v): string
    {
        $title = htmlspecialchars((string) $v['title']);
        $base  = htmlspecialchars((string) $v['plugin_base']);
        $totp  = htmlspecialchars((string) $v['totp_url']);
        $csrf  = htmlspecialchars((string) $v['csrf_token']);

        return <<<HTML
<!DOCTYPE html><html><head><meta charset="utf-8"><title>{$title}</title></head>
<body class="container py-5 text-center">
<h1>{$title}</h1>
<p><button type="button" class="btn btn-primary" id="webauthn_auth_btn" data-plugin-base="{$base}" data-csrf="{$csrf}">{$title}</button></p>
<p><a href="{$totp}">Use authenticator app</a></p>
<script src="{$base}/public/webauthn.js"></script>
<script>document.getElementById('webauthn_auth_btn')?.addEventListener('click', () => window.GlpiWebauthn?.authenticate({ pluginBase: '{$base}', csrf: '{$csrf}' }));</script>
</body></html>
HTML;
    }

    #[Route('/auth/options', name: 'webauthn_auth_options', methods: ['POST'])]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function options(Request $request): JsonResponse
    {
        if (!\PluginWebauthnConfig::isOperational()) {
            return new JsonResponse(['error' => __('Plugin disabled', 'webauthn')], 403);
        }

        $bucket = 'auth_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!RateLimiter::isAllowed($bucket)) {
            return new JsonResponse(['error' => __('Too many attempts', 'webauthn')], 429);
        }

        $users_id = null;
        if (isset($_SESSION['mfa_pre_auth']['user_id'])) {
            $users_id = (int) $_SESSION['mfa_pre_auth']['user_id'];
        } else {
            $body  = json_decode($request->getContent(), true);
            $login = trim((string) (
                (is_array($body) ? ($body['login_name'] ?? '') : '')
                ?: $request->request->get('login_name', '')
            ));
            if ($login !== '') {
                $users_id = self::resolveUserIdByLogin($login);
            }
        }

        try {
            $service = new WebAuthnService();
            $options = $service->createAuthenticationOptions($users_id);
            return new JsonResponse(['publicKey' => $options]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/auth/verify', name: 'webauthn_auth_verify', methods: ['POST'])]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function verify(Request $request): JsonResponse
    {
        if (!\PluginWebauthnConfig::isOperational()) {
            return new JsonResponse(['error' => __('Plugin disabled', 'webauthn')], 403);
        }

        $payload = $request->getContent();
        if ($payload === '') {
            $payload = json_encode($request->request->all('credential') ?: $request->request->all(), JSON_THROW_ON_ERROR);
        }

        try {
            $service  = new WebAuthnService();
            $users_id = $service->verifyAuthentication($payload);

            if (PolicyService::totpStillRequired($users_id)) {
                global $CFG_GLPI;
                $user = new \User();
                $user->getFromDB($users_id);
                $_SESSION['mfa_pre_auth'] = [
                    'user_id'     => $users_id,
                    'username'    => (string) ($user->fields['name'] ?? ''),
                    'remember_me' => (bool) ($_SESSION['mfa_pre_auth']['remember_me'] ?? false),
                    'noauto'      => (bool) ($_SESSION['mfa_pre_auth']['noauto'] ?? false),
                    'redirect'    => $_REQUEST['redirect'] ?? null,
                ];
                unset($_SESSION['webauthn_prompt_shown']);
                return new JsonResponse([
                    'success'  => true,
                    'next'     => $CFG_GLPI['root_doc'] . '/MFA/Prompt',
                    'totp_required' => true,
                ]);
            }

            $remember = (bool) ($_SESSION['mfa_pre_auth']['remember_me'] ?? false);
            $noauto   = (bool) ($_SESSION['mfa_pre_auth']['noauto'] ?? false);

            if (!isset($_SESSION['mfa_pre_auth'])) {
                $_SESSION['mfa_pre_auth'] = [
                    'user_id'     => $users_id,
                    'remember_me' => $remember,
                    'noauto'      => $noauto,
                    'redirect'    => $request->request->get('redirect'),
                ];
            }

            $_SESSION['mfa_success'] = true;
            unset($_SESSION['webauthn_prompt_shown']);

            global $CFG_GLPI;
            $redirect = $_SESSION['mfa_pre_auth']['redirect'] ?? '';
            $qs       = $redirect !== '' ? ('?redirect=' . rawurlencode((string) $redirect)) : '';

            return new JsonResponse([
                'success' => true,
                'next'    => $CFG_GLPI['root_doc'] . '/front/login.php' . $qs,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function redirectToLogin(): Response
    {
        global $CFG_GLPI;
        return $this->redirect($CFG_GLPI['root_doc'] . '/');
    }

    private static function resolveUserIdByLogin(string $login): ?int
    {
        $user = new \User();
        if ($user->getFromDBbyName($login)) {
            return (int) $user->getID();
        }
        return null;
    }
}
