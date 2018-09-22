<?php
namespace User;

use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'user' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/user[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '.*'
                    ],
                    'defaults' => [
                        'controller' => Controller\UserController::class,
                        'action' => 'login'
                    ]
                ]
            ],
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'user' => __DIR__ . '/../view'
        ]
    ]
];