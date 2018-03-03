<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2018 Serhii Popov
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

namespace Stagem\ZfcAction\Page;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Router;
use Zend\Expressive\Template;
use Zend\Stdlib\Exception\RuntimeException;
use Zend\View\Model\ViewModel;

class RendererMiddleware implements MiddlewareInterface
{
    const AREA_DEFAULT = 'default';

    /**
     * @var Router\RouterInterface
     */
    protected $router;

    /**
     * @var Template\TemplateRendererInterface
     */
    protected $renderer;


    public function __construct(Router\RouterInterface $router, Template\TemplateRendererInterface $template = null)
    {
        $this->router   = $router;
        $this->renderer = $template;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!($viewModel = $request->getAttribute(ViewModel::class))) {
            return $handler->handle($request);
        }

        $templates = $this->resolveTemplates($request);
        $viewModel->getVariable('layout') || $viewModel->setVariable('layout', $templates['layout']);

        $content = $this->renderer->render($templates['name'], $viewModel);

        return new HtmlResponse($content);
    }

    /**
     * Get template name based on module and action name
     *
     * @param $request
     * @return array
     */
    protected function resolveTemplates($request)
    {
        $route = $request->getAttribute(Router\RouteResult::class)->getMatchedRoute();

        $options = $route->getOptions();
        $module = $request->getAttribute('resource', $options['resource'] ?? '');
        $action = $request->getAttribute('action', $options['action'] ?? '');
        $area = $options['area'] ?? self::AREA_DEFAULT;

        if (!$module || !$action) {
            throw new RuntimeException(
                'Cannot resolve action name. ' .
                'Check if your route has "module" and "action" as named variables or add relative options to route'
            );
        }

        $layout = 'layout::' . $area;

        $templateName = '';
        if ($area !== self::AREA_DEFAULT) {
            $templateName .= $area . '-';
        }
        $templateName .= $module . '::' . $action;

        return [
            'layout' => $layout,
            'name' => $templateName,
        ];
    }
}