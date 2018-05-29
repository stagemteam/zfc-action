<?php

namespace Stagem\ZfcAction\Page;

// @todo wait until they will start to use Pst in codebase @see https://github.com/zendframework/zend-mvc/blob/master/src/MiddlewareListener.php#L11
class_alias('Interop\Http\Server\MiddlewareInterface', 'Interop\Http\ServerMiddleware\MiddlewareInterface');


use Popov\ZfcCurrent\CurrentHelper;
use Popov\ZfcEntity\Helper\ModuleHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// @todo wait until they will start to use Pst in codebase @see https://github.com/zendframework/zend-mvc/blob/master/src/MiddlewareListener.php#L11
//use Psr\Http\Server\MiddlewareInterface;
//use Psr\Http\Server\RequestHandlerInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;

use Zend\Diactoros\Response\HtmlResponse;

use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\EventManager;
use Zend\Diactoros\ServerRequest;
use Zend\Stdlib\Exception\RuntimeException;
use Zend\Filter\Word\DashToCamelCase;
use Zend\Mvc\InjectApplicationEventInterface;

class ConnectivePage implements MiddlewareInterface
{
    const DEFAULT_CONTROLLER = 'index';
    const DEFAULT_ACTION = 'index';

    protected $container;

    protected $config;

    protected $routeParams;

    /**
     * @var ModuleHelper
     */
    protected $moduleHelper;

    /**
     * @var CurrentHelper
     */
    protected $currentHelper;

    public function __construct(
        $container,
        array $config = [],
        CurrentHelper $currentHelper = null,
        ModuleHelper $moduleHelper = null
    )
    {
        $this->container = $container;
        $this->config = $config;
        $this->currentHelper = $currentHelper;
        $this->moduleHelper = $moduleHelper;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->routeParams = $this->currentHelper->currentRouteParams();

        // We use this approach for goto plugin compatibility
        $action = $this->get($this->routeParams['controller']);

        // We set defaultContext here in order to avoid override value during goto plugin is called
        $this->currentHelper->setDefaultContext($action);

        return $action->process($request, $handler);
    }

    public function setRouteParams($routeParams)
    {
        $this->routeParams = $routeParams;
    }

    public function get($resource)
    {
        $actionClass = $this->getActionClass($resource);

        $action = $this->container->get($actionClass);

        $this->configureEvent($action);
        $this->configureEventManager($action);
        $this->configurePluginManager($action);

        return $action;
    }

    protected function getActionClass($resource)
    {
        $filter = new DashToCamelCase();

        $name = [];

        #$name['namespace'] = $this->getNamespace(lcfirst($filter->filter($this->currentHelper->currentResource())));
        $name['namespace'] = $this->getNamespace(lcfirst($filter->filter($resource)));
        #$name['dir'] = 'Action';
        //$area = $route->getOptions()['area'] ?? RendererMiddleware::AREA_DEFAULT;
        $area = $this->routeParams['area'] ?? RendererMiddleware::AREA_DEFAULT;
        if ($area !== RendererMiddleware::AREA_DEFAULT) {
            $name['area'] = ucfirst($area);
        }
        $name['action'] = ucfirst($filter->filter($this->routeParams['action']));

        //unset($name['controller']);

        return implode('\\', $name) . 'Action';
    }

    protected function getNamespace($mnemo)
    {
        // There is no real case when need to use "moduleHelper" for getting module namespace from DB.
        // ZF3 use "controllers" key in configuration by default.
        // It follows from this that we use "actions" key for our implementation.
        #if ($this->moduleHelper && ($module = $this->moduleHelper->getBy($mnemo, 'mnemo'))) {
        #    $namespace = $module->getName();
        #} else

        $namespace = null;
        if (isset($this->config['actions'][$mnemo])) {
            $namespace = $this->config['actions'][$mnemo];
        } else {
            throw new RuntimeException(sprintf(
                'Module for "%s" in not registered in configuration or database',
                $mnemo
            ));
        }

        return $namespace;
    }

    protected function configureEvent($action)
    {
        if ($action instanceof InjectApplicationEventInterface) {
            $event = $this->container->get('Application')->getMvcEvent();
            $action->setEvent($event);
        }
    }

    protected function configureEventManager($action)
    {
        if ($action instanceof EventManagerAwareInterface) {
            $eventManager = $action->getEventManager();

            // If the instance has an EM WITH an SEM composed, do nothing.
            if ($eventManager instanceof EventManagerInterface
                && $eventManager->getSharedManager() instanceof SharedEventManagerInterface
            ) {
                return;
            }

            //$container->has('SharedEventManager') ? $container->get('SharedEventManager') : null
            $eventManager = new EventManager($this->container->get('SharedEventManager'));
            $action->setEventManager($eventManager);
        }
    }

    protected function configurePluginManager($action)
    {
        if (!method_exists($action, 'setPluginManager')) {
            return;
        }

        $action->setPluginManager($this->container->get('ControllerPluginManager'));
    }
}