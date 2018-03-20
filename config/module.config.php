<?php

namespace Stagem\ZfcAction;

return [
    'routes' => require 'routes.slim.config.php',
    'dependencies' => [
        'factories' => [
            Page\ConnectivePage::class => Page\ConnectivePageFactory::class,
        ],
    ],
];