<?php

namespace Stagem\ZfcAction;

return [
    // mvc
    'router' => require 'routes.zend.config.php',

    // middleware
    'routes' => require 'routes.slim.config.php',

    'actions' => [
        'action' => __NAMESPACE__ . '\Action',
    ],

    'dependencies' => [
        'invokables' => [
            Action\HomeAction::class => Action\HomeAction::class,
        ],

        'factories' => [
            Page\ConnectivePage::class => Page\Factory\ConnectivePageFactory::class,
            Page\RendererMiddleware::class => Page\Factory\RendererMiddlewareFactory::class,
        ],
    ],

    'controller_plugins' => [
        'factories' => [
            'goto' => Page\Plugin\Factory\GotoPluginFactory::class,
        ],
    ],
    
    'route_manager' => [
        'aliases' => [
            'Wildcard' => Router\Http\Wildcard::class,
            'wildcard' => Router\Http\Wildcard::class,
            'wildCard' => Router\Http\Wildcard::class,
            'WildCard' => Router\Http\Wildcard::class,
        ],
        'factories' => [
            Router\Http\Wildcard::class => RouteInvokableFactory::class,
            \Zend\Router\Http\Wildcard::class => RouteInvokableFactory::class,
            'zendmvcrouterhttpwildcard' => RouteInvokableFactory::class,
        ],
    ],

    // mvc
    'view_manager' => [
        'prefix_template_path_stack' => [
            'action::' => __DIR__ . '/../view/action',
        ],
    ],
];