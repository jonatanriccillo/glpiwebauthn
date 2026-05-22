<?php

declare(strict_types=1);

namespace GlpiPlugin\Webauthn\Controller;

use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CredentialController extends AbstractController
{
    #[Route('/credentials', name: 'webauthn_credentials_list', methods: ['GET'])]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function list(Request $request): JsonResponse
    {
        $users_id = (int) Session::getLoginUserID();
        $rows     = \PluginWebauthnCredential::getActiveForUser($users_id);
        $out      = [];

        foreach ($rows as $row) {
            $out[] = [
                'id'           => (int) $row['id'],
                'name'         => $row['name'],
                'date_creation' => $row['date_creation'],
                'last_used_at' => $row['last_used_at'],
            ];
        }

        return new JsonResponse(['credentials' => $out]);
    }

    #[Route('/credentials/{id}', name: 'webauthn_credentials_revoke', methods: ['DELETE', 'POST'])]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function revoke(Request $request, int $id): JsonResponse
    {
        $users_id = (int) Session::getLoginUserID();
        $is_admin = Session::haveRight('plugin_webauthn', UPDATE);

        if ($is_admin && $request->request->has('users_id')) {
            $target = (int) $request->request->get('users_id');
            \PluginWebauthnCredential::revoke($id, $target);
        } else {
            \PluginWebauthnCredential::revoke($id, $users_id);
        }

        return new JsonResponse(['success' => true]);
    }
}
