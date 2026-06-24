<?php

declare(strict_types=1);

namespace Invoice\Service;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SsoAuthListenerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SsoAuthListener
    {
        $jwtVerifier = $container->get(JwtVerifier::class);
        return new SsoAuthListener($jwtVerifier);
    }
}