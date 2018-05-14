<?php

namespace Stagem\ZfcAction\Page;

use Psr\Container\ContainerInterface;
use Popov\ZfcCurrent\CurrentHelper;
use Popov\ZfcEntity\Helper\ModuleHelper;

class ConnectivePageFactory
{
    public function __invoke(ContainerInterface $container)
    {
        #$actionFactory = function ($actionName) use ($container) {
        #    return $container->get($actionName);
        #};

        $moduleHelper = $container->has(ModuleHelper::class) ? $container->get(ModuleHelper::class) : null;
        $currentHelper = $container->has(CurrentHelper::class) ? $container->get(CurrentHelper::class) : null;
        $config = $container->get('config');

        $connective = new ConnectivePage($container, $config, $currentHelper, $moduleHelper);

        return $connective;
    }
}