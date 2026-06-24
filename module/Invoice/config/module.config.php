<?php

declare(strict_types=1);

namespace Invoice;

use Invoice\Controller\InvoiceController;
use Invoice\Service\ApiClient;
use Invoice\Service\JwtVerifier;
use Invoice\Service\SsoAuthListener;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [

    // -------------------------------------------------------------------
    // Routes : chaque URL pointe vers une action du InvoiceController
    // -------------------------------------------------------------------
    'router' => [
        'routes' => [

            // http://projet_laminas.test/invoice  → Dashboard
            'invoice' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/invoice',
                    'defaults' => [
                        'controller' => InvoiceController::class,
                        'action'     => 'index',
                    ],
                ],
            ],

            // http://projet_laminas.test/invoice/upload → page d'upload
            'invoice-upload' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/invoice/upload',
                    'defaults' => [
                        'controller' => InvoiceController::class,
                        'action'     => 'upload',
                    ],
                ],
            ],

            // http://projet_laminas.test/invoice/validate → formulaire de validation
            // (après extraction, avant sauvegarde définitive)
            'invoice-validate' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/invoice/validate',
                    'defaults' => [
                        'controller' => InvoiceController::class,
                        'action'     => 'validate',
                    ],
                ],
            ],

            // http://projet_laminas.test/invoice/history → liste des factures
            'invoice-history' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/invoice/history',
                    'defaults' => [
                        'controller' => InvoiceController::class,
                        'action'     => 'history',
                    ],
                ],
            ],

            // http://projet_laminas.test/invoice/view/12 → détail d'une facture (id=12)
            'invoice-view' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/invoice/view/:id',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => InvoiceController::class,
                        'action'     => 'view',
                    ],
                ],
            ],

            // http://projet_laminas.test/invoice/search → recherche intelligente
            'invoice-search' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/invoice/search',
                    'defaults' => [
                        'controller' => InvoiceController::class,
                        'action'     => 'search',
                    ],
                ],
            ],
        ],
    ],

    // -------------------------------------------------------------------
    // Déclaration du contrôleur (factory simple : Laminas l'instancie
    // automatiquement avec ses dépendances, voir Module.php ci-après)
    // -------------------------------------------------------------------
    'controllers' => [
        'factories' => [
            InvoiceController::class => \Invoice\Controller\InvoiceControllerFactory::class,
        ],
    ],

    // -------------------------------------------------------------------
    // Déclaration du service ApiClient (créé une seule fois, réutilisé partout)
    // -------------------------------------------------------------------
    'service_manager' => [
        'factories' => [
            ApiClient::class       => \Invoice\Service\ApiClientFactory::class,
            JwtVerifier::class     => \Invoice\Service\JwtVerifierFactory::class,
            SsoAuthListener::class => \Invoice\Service\SsoAuthListenerFactory::class,
        ],
    ],

    // -------------------------------------------------------------------
    // Résolution des vues : nom_de_l_action → fichier .phtml correspondant
    // -------------------------------------------------------------------
    'view_manager' => [
        'template_path_stack' => [
            __NAMESPACE__ => __DIR__ . '/../view',
        ],
    ],
];