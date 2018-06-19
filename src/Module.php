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
 * @package Stagem_ZfcRoute
 * @author Serhii Popov <popow.serhii@gmail.com>
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Stagem\ZfcAction;

use Stagem\ZfcAction\Router\Http\Wildcard;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\MvcEvent;

class Module
{
    public function getConfig()
    {
        $config = include __DIR__ . '/../config/module.config.php';
        $config['service_manager'] = $config['dependencies'];
        unset($config['dependencies']);
        unset($config['routes']);

        return $config;
    }

    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $container = $e->getApplication()->getServiceManager();

        $routePluginManager = $container->get('RoutePluginManager');

        // Allow to replace services
        $routePluginManager->setAllowOverride(true);

        // @see https://olegkrivtsov.github.io/using-zend-framework-3-book/html/en/Website_Operation/Service_Manager.html
        // We use this hardcode such as we cannot set mapping on config file level.
        // This method override any configuration (@see Zend\Router\Http\TreeRouteStack::init())
        $routePluginManager->setAlias('wildcard', Wildcard::class);
        $routePluginManager->setAlias('Wildcard', Wildcard::class);
        $routePluginManager->setAlias('wildCard', Wildcard::class);
        $routePluginManager->setAlias('WildCard', Wildcard::class);

        $routePluginManager->setAllowOverride(false);
    }
}