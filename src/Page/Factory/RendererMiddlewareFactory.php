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
 * @see https://samsonasik.wordpress.com/2016/03/01/start-using-middleware-approach-with-new-zend-mvc/
 */

namespace Stagem\ZfcAction\Page\Factory;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;
use Zend\Expressive\Template\TemplateRendererInterface;
use Stagem\ZfcAction\Page\RendererMiddleware;

class RendererMiddlewareFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $isExpressive = class_exists(Application::class);

        $viewRenderer = $isExpressive
            ? $container->get(TemplateRendererInterface::class)
            : $container->get('ViewRenderer');
        $viewManager = $container->get('ViewManager')
            ? $container->get('ViewManager')
            : null;

        return new RendererMiddleware($viewRenderer, $viewManager);
    }
}