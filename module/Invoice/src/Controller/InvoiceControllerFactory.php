<?php

declare(strict_types=1);

namespace Invoice\Controller;

use Invoice\Service\ApiClient;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * InvoiceControllerFactory
 * -------------------------
 * Construit InvoiceController en lui injectant le service ApiClient
 * (déjà configuré avec l'URL de l'API Python).
 */
class InvoiceControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): InvoiceController
    {
        $apiClient = $container->get(ApiClient::class);

        return new InvoiceController($apiClient);
    }
}