<?php

/**
 * config/autoload/db_client.global.php
 * ---------------------------------------
 * Connexion de Laminas à SA base locale (erp_client_db) pour gérer
 * le login du client. Cette base est INDÉPENDANTE de invoices_db
 * (qui reste sur le serveur privé de l'éditeur).
 */

return [
    'db_client' => [
        'host'     => '127.0.0.1',
        'dbname'   => 'erp_client_db',
        'user'     => 'root',
        'password' => 'imenemnamariam123',
    ],
];