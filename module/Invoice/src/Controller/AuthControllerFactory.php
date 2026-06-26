<?php

declare(strict_types=1);

namespace Invoice\Controller;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AuthControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AuthController
    {
        $config = $container->get('config');
        $dbConfig = $config['db_client'] ?? [];

        return new AuthController(
            $dbConfig['host'] ?? '127.0.0.1',
            $dbConfig['dbname'] ?? 'erp_client_db',
            $dbConfig['user'] ?? 'root',
            $dbConfig['password'] ?? ''
        );
    }
}