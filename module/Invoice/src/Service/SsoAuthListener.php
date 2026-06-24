<?php

declare(strict_types=1);

namespace Invoice\Service;

use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container as SessionContainer;

/**
 * SsoAuthListener
 * ----------------
 * Vérifie l'authentification avant chaque action du module Invoice :
 *
 * 1. Si l'URL contient ?token=xxx → vérifie le JWT (SSO depuis Django)
 *    → si valide, crée/rafraîchit la session Laminas locale
 * 2. Sinon, vérifie si une session Laminas locale existe déjà
 * 3. Si ni l'un ni l'autre → redirige vers Django (page de login)
 */
class SsoAuthListener
{
    /** URL de Django où rediriger si l'utilisateur n'est pas authentifié */
    private const DJANGO_LOGIN_URL = 'http://localhost:8000/';

    public function __construct(private JwtVerifier $jwtVerifier)
    {
    }

    public function __invoke(MvcEvent $event): ?\Laminas\Http\Response
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$request instanceof \Laminas\Http\Request) {
            return null;
        }

        $userSession = new SessionContainer('erp_user');

        // ── Cas 1 : un token est présent dans l'URL (arrivée depuis Django) ──
        $token = $request->getQuery('token');

        if ($token !== null && trim((string) $token) !== '') {
            $payload = $this->jwtVerifier->verify((string) $token);

            if ($payload !== null && !empty($payload['user_id'])) {
                // Token valide → on (re)crée la session locale Laminas
                $userSession->user_id  = $payload['user_id'];
                $userSession->username = $payload['username'];
                $userSession->role     = $payload['role'];

                return null; // accès autorisé, on continue normalement
            }

            // Token présent mais invalide/expiré → on bloque, pas de fallback silencieux
            return $this->redirectToLogin($response, 'Token invalide ou expiré.');
        }

        // ── Cas 2 : pas de token dans l'URL → vérifier la session existante ──
        if (!empty($userSession->user_id)) {
            return null; // déjà authentifié dans cette session Laminas
        }

        // ── Cas 3 : ni token ni session → accès refusé ──
        return $this->redirectToLogin($response, 'Authentification requise.');
    }

    private function redirectToLogin(\Laminas\Http\Response $response, string $reason): \Laminas\Http\Response
    {
        $response->setStatusCode(302);
        $response->getHeaders()->addHeaderLine('Location', self::DJANGO_LOGIN_URL);
        $response->setContent(
            '<p>Redirection vers la connexion... (' . htmlspecialchars($reason) . ')</p>'
        );
        return $response;
    }
}