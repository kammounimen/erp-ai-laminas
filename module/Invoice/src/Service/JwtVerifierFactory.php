<?php

declare(strict_types=1);

namespace Invoice\Service;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * JwtVerifierFactory
 * -------------------
 * Construit le service JwtVerifier avec la clé secrète lue depuis
 * config/autoload/jwt.global.php
 */
class JwtVerifierFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): JwtVerifier
    {
        $config = $container->get('config');
        $secret = $config['jwt']['secret'] ?? '';
        $algorithm = $config['jwt']['algorithm'] ?? 'HS256';

        return new JwtVerifier($secret, $algorithm);
    }
}