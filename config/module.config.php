<?php

namespace Stagem\ZfcAction;

return [
    'routes' => [
        [
            'name' => 'home',
            'path' => '/',
            'middleware' => [\Stagem\Layout\Action\HomeAction::class, Page\RendererMiddleware::class],
            'allowed_methods' => ['GET'],
            'options' => [ // this don't work automatically, you need check this by hand
                'resource' => 'layout',
                'action' => 'home',
            ]
        ],

        // for detail @see https://github.com/nikic/FastRoute/issues/103
        [
            'name' => 'default',
            'path' => '/{resource:[a-z-]{3,}}[/[{action:[a-z-]{3,}}[/{id:\d+}[/{more:.*}]]]]',
            'middleware' => [Page\ConnectivePage::class, Page\RendererMiddleware::class],
            'options' => [ // this don't work automatically, you need check this by hand
                'resource' => 'index',
                'action' => 'index',
            ]
            //'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
        ],
    ],

    'dependencies' => [
        'factories' => [
            Page\ConnectivePage::class => Page\ConnectivePageFactory::class,
        ],
    ]
];