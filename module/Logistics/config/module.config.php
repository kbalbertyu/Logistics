<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/10/2018
 * Time: 10:26 PM
 */
namespace Logistics;


use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'logistics' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/logistics[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '.*'
                    ],
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'inventory' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/inventory[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '.*'
                    ],
                    'defaults' => [
                        'controller' => Controller\InventoryController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'brand' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/brand[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '.*'
                    ],
                    'defaults' => [
                        'controller' => Controller\BrandController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'team' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/team[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '.*'
                    ],
                    'defaults' => [
                        'controller' => Controller\TeamController::class,
                        'action' => 'index'
                    ]
                ]
            ],
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'logistics' => __DIR__ . '/../view'
        ]
    ]
];