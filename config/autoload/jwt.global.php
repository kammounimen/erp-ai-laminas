<?php

/**
 * config/autoload/jwt.global.php
 * --------------------------------
 * Clé secrète partagée avec Django pour la vérification du SSO (JWT).
 *
 * ⚠️ CETTE CLÉ DOIT ÊTRE IDENTIQUE À CELLE DANS auth/.env (JWT_SECRET)
 * Si tu changes l'une, change l'autre.
 */

return [
    'jwt' => [
        'secret'    => 'erp-ai-shared-secret-2026-change-moi-en-prod',
        'algorithm' => 'HS256',
    ],
];