<?php

/**
 * config/autoload/api.global.php
 * --------------------------------
 * Configuration de connexion à l'API Python (FastAPI) qui expose
 * les agents d'extraction et de recherche de factures.
 */

return [
    'api' => [
        'base_url' => 'http://127.0.0.1:8001',
        'timeout'  => 60, // secondes — l'extraction Mistral peut prendre du temps
    ],
];