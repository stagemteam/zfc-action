<?php

namespace Stagem\ZfcAction;

return [
    'routes' => require 'routes.slim.config.php',

    /*'actions' => [
        'aliases' => [
            'serial' => Controller\SerialController::class,
        ],
        'factories' => [
            Controller\SerialController::class => Controller\Factory\SerialControllerFactory::class,
        ],
    ],*/

    'dependencies' => [
        'factories' => [
            Page\ConnectivePage::class => Page\ConnectivePageFactory::class,
        ],
    ],
];