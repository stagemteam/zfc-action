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

namespace Stagem\ZfcAction\Page\Plugin\Factory;

use Psr\Container\ContainerInterface;
use Stagem\ZfcAction\Page\ConnectivePage;
use Stagem\ZfcAction\Page\Plugin\GotoPlugin;

class GotoPluginFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new GotoPlugin($container->get(ConnectivePage::class));
    }
}