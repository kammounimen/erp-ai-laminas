<?php

declare(strict_types=1);

namespace Invoice\Service;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * ApiClientFactory
 * ----------------
 * Construit le service ApiClient en lui passant l'URL de l'API Python
 * lue depuis config/autoload/api.global.php
 */
class ApiClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ApiClient
    {
        $config = $container->get('config');
        $baseUrl = $config['api']['base_url'] ?? 'http://127.0.0.1:8001';
        $timeout = $config['api']['timeout'] ?? 60;

        return new ApiClient($baseUrl, $timeout);
    }
}