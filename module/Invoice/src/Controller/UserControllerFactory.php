<?php

declare(strict_types=1);

namespace Invoice\Controller;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UserControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): UserController
    {
        $config = $container->get('config');
        $dbConfig = $config['db_client'] ?? [];

        return new UserController(
            $dbConfig['host'] ?? '127.0.0.1',
            $dbConfig['dbname'] ?? 'erp_client_db',
            $dbConfig['user'] ?? 'root',
            $dbConfig['password'] ?? ''
        );
    }
}