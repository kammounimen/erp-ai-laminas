<?php

declare(strict_types=1);

namespace Invoice\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

/**
 * JwtVerifier
 * -----------
 * Vérifie les tokens JWT émis par Django (SSO).
 * Utilise la même clé secrète et le même algorithme (HS256)
 * que côté Django (auth/erp_auth/settings.py → JWT_SECRET).
 */
class JwtVerifier
{
    public function __construct(
        private string $secret,
        private string $algorithm = 'HS256'
    ) {
    }

    /**
     * Vérifie un token JWT et retourne son contenu décodé.
     *
     * @return array{user_id: int, username: string, role: string}|null
     *         null si le token est invalide, expiré, ou mal formé
     */
    public function verify(string $token): ?array
    {
        if (trim($token) === '') {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));

            return [
                'user_id'  => $decoded->user_id  ?? null,
                'username' => $decoded->username ?? null,
                'role'     => $decoded->role     ?? null,
            ];

        } catch (ExpiredException $e) {
            // Token expiré (normal si l'utilisateur tarde à cliquer après génération)
            return null;
        } catch (SignatureInvalidException $e) {
            // Signature invalide → clé secrète différente ou token falsifié
            return null;
        } catch (\Exception $e) {
            // Tout autre problème de décodage (JSON invalide, format incorrect, etc.)
            return null;
        }
    }
}