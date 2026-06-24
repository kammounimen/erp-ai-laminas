<?php

declare(strict_types=1);

namespace Invoice;

use Invoice\Service\SsoAuthListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\ModuleRouteListener;

/**
 * Module.php
 * ----------
 * Point d'entrée du module Invoice.
 *
 * - getConfig() charge les routes/contrôleurs/services (config/module.config.php)
 * - onBootstrap() branche le SsoAuthListener pour vérifier l'authentification
 *   (JWT depuis Django, ou session Laminas existante) avant chaque action.
 */
class Module
{
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event): void
    {
        $application  = $event->getApplication();
        $eventManager = $application->getEventManager();
        $serviceManager = $application->getServiceManager();

        // Nécessaire pour que les paramètres de route (:id, etc.) fonctionnent
        (new ModuleRouteListener())->attach($eventManager);

        /** @var SsoAuthListener $ssoListener */
        $ssoListener = $serviceManager->get(SsoAuthListener::class);

        // Se déclenche juste avant le dispatch du contrôleur, sur TOUTES
        // les routes de l'application (filtré au besoin dans le listener
        // lui-même si on veut un jour exclure certaines routes).
        $eventManager->attach(
            MvcEvent::EVENT_DISPATCH,
            $ssoListener,
            100 // priorité haute = exécuté avant le contrôleur
        );
    }
}