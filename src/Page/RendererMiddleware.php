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

namespace Stagem\ZfcAction\Page;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// @todo wait until they will start to use Pst in codebase @see https://github.com/zendframework/zend-mvc/blob/master/src/MiddlewareListener.php#L11
//use Psr\Http\Server\MiddlewareInterface;
//use Psr\Http\Server\RequestHandlerInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;

use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Psr7Bridge\Psr7Response;
use Zend\View\Exception;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ModelInterface as Model;
use Zend\View\Renderer\RendererInterface;
use Zend\Mvc\View\Http\ViewManager;
use Zend\Http\Response\Stream as HttpStream;
use Zend\View\Renderer\TreeRendererInterface;
use Zend\View\ViewEvent;

class RendererMiddleware implements MiddlewareInterface
{
    const AREA_DEFAULT = 'default';

    /**
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * @var ViewManager
     */
    protected $view;

    public function __construct(RendererInterface $renderer, $view = null)
    {
        $this->renderer = $renderer;
        $this->view = $view;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!($viewModel = $request->getAttribute(ViewModel::class))) {
            return $handler->handle($request);
        }

        if ($viewModel instanceof HttpStream) {
            return Psr7Response::fromZend($viewModel);
        } else if ($viewModel instanceof JsonModel && $viewModel->terminate()) {
             return new JsonResponse($viewModel->getVariables());
        } else if ($request->hasHeader('X-Requested-With')) {
            $viewModel->setTerminal(true);
        }

        $templates = $this->resolveTemplates($request);
        $viewModel->getVariable('layout') || $viewModel->setVariable('layout', $templates['layout']);
        $viewModel->getTemplate() || $viewModel->setTemplate($templates['name']);

        if ($this->view) {
            // Usage: $sharedEvents->attach('Zend\View\View', MvcEvent::EVENT_RENDER, function($event){});
            $eventManager = $this->view->getView()->getEventManager(); // @todo Pass EventManager as arguments to constructor
            $eventManager->trigger('render', $viewModel, ['context' => $this, 'viewManager' => $this->view]);
        }

        $content = $this->render($viewModel);

        // "view" manager is set in MVC application only.
        // We reject usage "layout" plugin in middleware.
        // Instead if needed you should inject "layout" variable in your ViewModel.
        // By default "layout/default" template is usage.
        // This is correspond to "area" value in route option which is resolved to 'layout::' + $area.
        if ($this->view && !$viewModel->terminate()) {
            $layout = $this->view->getViewModel();
            $layout->setTemplate($viewModel->getVariable('layout'));
            $layout->setVariable('content', $content);

            $content = $this->renderer->render($layout);
        }

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
        #$module = $request->getAttribute('controller', $request->getAttribute('controller'));
        $module = $request->getAttribute('controller');
        $action = $request->getAttribute('action');
        $area = $request->getAttribute('area', self::AREA_DEFAULT);

        if (!$module || !$action) {
            throw new Exception\RuntimeException(
                'Cannot resolve action name. '
                . 'Check if your route has "resource" and "action" as named variables or add relative options to route'
            );
        }

        $layout = 'layout::' . $area;
        $templateName = ($area === self::AREA_DEFAULT)
            ? $module . '::' . $action
            : $module . '::' . $area . '/' . $action;

        return [
            'layout' => $layout,
            'name' => $templateName,
        ];
    }

    public function render(Model $model)
    {
        // If we have children, render them first, but only if:
        // a) the renderer does not implement TreeRendererInterface, or
        // b) it does, but canRenderTrees() returns false
        if ($model->hasChildren()
            && (! $this->renderer instanceof TreeRendererInterface
                || ! $this->renderer->canRenderTrees())
        ) {
            $this->renderChildren($model);
        }

        //$content = $this->renderer->render($viewModel->getTemplate(), $viewModel);
        return $this->renderer->render($model);
    }

    /**
     * Loop through children, rendering each
     *
     * @param  Model $model
     * @throws Exception\DomainException
     * @return void
     */
    protected function renderChildren(Model $model)
    {
        foreach ($model as $child) {
            if ($child->terminate()) {
                throw new Exception\DomainException('Inconsistent state; child view model is marked as terminal');
            }
            $child->setOption('has_parent', true);
            $result  = $this->render($child);
            $child->setOption('has_parent', null);
            $capture = $child->captureTo();
            if (! empty($capture)) {
                if ($child->isAppend()) {
                    $oldResult = $model->{$capture};
                    $model->setVariable($capture, $oldResult . $result);
                } else {
                    $model->setVariable($capture, $result);
                }
            }
        }
    }
}