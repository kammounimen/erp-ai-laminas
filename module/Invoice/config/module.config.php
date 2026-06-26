<?php

declare(strict_types=1);

namespace Invoice;

use Invoice\Controller\InvoiceController;
use Invoice\Controller\AuthController;
use Invoice\Controller\UserController;
use Invoice\Service\ApiClient;
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

            // http://projet_laminas.test/login → page de connexion du client
            'login' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/login',
                    'defaults' => [
                        'controller' => AuthController::class,
                        'action'     => 'login',
                    ],
                ],
            ],

            // http://projet_laminas.test/logout
            'logout' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/logout',
                    'defaults' => [
                        'controller' => AuthController::class,
                        'action'     => 'logout',
                    ],
                ],
            ],

            // http://projet_laminas.test/users → liste (admin uniquement)
            'users' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/users',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action'     => 'index',
                    ],
                ],
            ],

            // http://projet_laminas.test/users/create
            'users-create' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/users/create',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action'     => 'create',
                    ],
                ],
            ],

            // http://projet_laminas.test/users/edit/3
            'users-edit' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/users/edit/:id',
                    'constraints' => ['id' => '[0-9]+'],
                    'defaults' => [
                        'controller' => UserController::class,
                        'action'     => 'edit',
                    ],
                ],
            ],

            // http://projet_laminas.test/users/delete/3
            'users-delete' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/users/delete/:id',
                    'constraints' => ['id' => '[0-9]+'],
                    'defaults' => [
                        'controller' => UserController::class,
                        'action'     => 'delete',
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
            AuthController::class    => \Invoice\Controller\AuthControllerFactory::class,
            UserController::class    => \Invoice\Controller\UserControllerFactory::class,
        ],
    ],

    // -------------------------------------------------------------------
    // Déclaration du service ApiClient (créé une seule fois, réutilisé partout)
    // -------------------------------------------------------------------
    'service_manager' => [
        'factories' => [
            ApiClient::class => \Invoice\Service\ApiClientFactory::class,
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