<?php

namespace Stagem\ZfcAction;

return [
    // mvc
    'router' => require 'routes.zend.config.php',

    // middleware
    'routes' => require 'routes.slim.config.php',

    'dependencies' => [
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
];