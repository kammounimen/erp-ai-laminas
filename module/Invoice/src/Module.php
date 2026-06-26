<?php

declare(strict_types=1);

namespace Invoice;

use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\ModuleRouteListener;

/**
 * Module.php
 * ----------
 * Point d'entrée du module Invoice.
 *
 * ⚠️ Le SSO JWT Django↔Laminas a été RETIRÉ (mauvaise architecture :
 * Django/agents Python restent privés à l'éditeur, le client n'a aucun
 * compte Django). L'authentification du client se fait maintenant via
 * Invoice\Service\AuthListener (login local Laminas, voir étape E).
 */
class Module
{
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event): void
    {
        $eventManager = $event->getApplication()->getEventManager();

        // Nécessaire pour que les paramètres de route (:id, etc.) fonctionnent
        (new ModuleRouteListener())->attach($eventManager);
    }
}