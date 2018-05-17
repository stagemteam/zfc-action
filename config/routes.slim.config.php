<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2018 Stagem Team
 * This source file is subject to The MIT License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @category Stagem
 * @package Stagem_ZfcAction
 * @author Serhii Popov <popow.serhii@gmail.com>
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Stagem\ZfcAction;

use Zend\Stdlib\ArrayUtils;

$default = [
    'options' => [
        'conditions' => [
            'lang' => '[a-z]{2}',
        ],
        'defaults' => [
            'controller' => 'index',
            'action' => 'home',
            //'locale' => 'en'
        ],
    ],
];

return [
    ArrayUtils::merge($default, [
        'name' => 'default/home',
        'path' => '(/:lang)/',
        'middleware' => [\Stagem\Layout\Action\HomeAction::class, Page\RendererMiddleware::class],
        'allowed_methods' => ['GET'],
        'options' => [
            'defaults' => [
                'area' => 'home',
            ],
        ],
    ]),
    ArrayUtils::merge($default, [
        'name' => 'default',
        'path' => '(/:lang)(/:controller(/:action(/:id)))',
        //'path' => '/[:controller[/[:action[/[:id]]]]]',
        'middleware' => [Page\ConnectivePage::class, Page\RendererMiddleware::class],
        'options' => [
            'conditions' => [
                'controller' => '[a-zA-Z]?[a-zA-Z0-9_-]*',
                'action' => '[a-zA-Z]?[a-zA-Z0-9_-]*',
                'id' => '[1-9]\d*',
            ],
            'defaults' => [
                'controller' => 'index',
                'action' => 'index',
                'id' => '',
            ],
        ],
    ]),
    ArrayUtils::merge($default, [
        'name' => 'default/page',
        //'path' => '/[:controller[/[:action[/page:page]]]]',
        'path' => '(/:lang)(/:controller(/:action(/page/:page)))',
        'middleware' => [Page\ConnectivePage::class, Page\RendererMiddleware::class],
        'options' => [ // this don't work automatically, you need check this by hand
            'conditions' => [
                'controller' => '[a-zA-Z]?[a-zA-Z0-9_-]*',
                'action' => '[a-zA-Z]?[a-zA-Z0-9_-]*',
                'page' => '[1-9]\d*',
            ],
            'defaults' => [
                'controller' => 'index',
                'action' => 'index',
                'page' => '1',
            ],
        ],
    ]),
    ArrayUtils::merge($default, [
        'name' => 'default/more',
        //'path' => '/[:controller[/[:action[/:more]]]]',
        'path' => '(/:lang)(/:controller(/:action(/:more+)))',
        'middleware' => [Page\ConnectivePage::class, Page\RendererMiddleware::class],
        'options' => [
            'conditions' => [
                'controller' => '[a-zA-Z]?[a-zA-Z0-9_-]*',
                'action' => '[a-zA-Z]?[a-zA-Z0-9_-]*',
                'more' => '.*',
            ],
            'defaults' => [
                'controller' => 'index',
                'action' => 'index',
            ],
        ],
    ]),
];