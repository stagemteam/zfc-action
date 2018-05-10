<?php

namespace Stagem\ZfcAction;

return [
    // mvc
    'router' => require 'routes.zend.config.php',

    // middleware
    'routes' => require 'routes.slim.config.php',

    'dependencies' => [
        'factories' => [
            Page\ConnectivePage::class => Page\ConnectivePageFactory::class,
        ],
    ],
];