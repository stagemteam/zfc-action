<?php
namespace Stagem\ZfcAction\Page;

use Popov\ZfcCurrent\CurrentHelper;
use Popov\ZfcEntity\Helper\ModuleHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
//use Interop\Http\Server\MiddlewareInterface;
use Psr\Http\Server\MiddlewareInterface;

use Zend\Expressive\Router\RouteResult;
use Zend\Diactoros\Response\HtmlResponse;

use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\EventManager;
use Zend\Stdlib\Exception\RuntimeException;
use Zend\View\Model\ViewModel;
use Zend\Expressive\Template;
use Zend\Filter\Word\DashToCamelCase;


class ConnectivePage implements MiddlewareInterface
{
    const DEFAULT_RESOURCE = 'index';
    const DEFAULT_ACTION = 'index';

    protected $actionFactory;

    protected $config;

    /**
     * @var ModuleHelper
     */
    protected $moduleHelper;

    /**
     * @var CurrentHelper
     */
    protected $currentHelper;

    public function __construct(
        $actionFactory,
        array $config = [],
        CurrentHelper $currentHelper = null,
        ModuleHelper $moduleHelper = null
    )
    {
        $this->actionFactory = $actionFactory;
        $this->config = $config;
        $this->currentHelper = $currentHelper;
        $this->moduleHelper = $moduleHelper;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $action = $this->getAction($request);

        return $action->process($request, $handler);
    }

    protected function getAction(ServerRequestInterface $request)
    {
        $actionClass = $this->getActionClass($request);
        $this->currentHelper->setDefaultContext($actionClass);

        $action = ($this->actionFactory)($actionClass);

        $this->configureEventManager($action);

        return $action;
    }

    protected function getActionClass($request)
    {
        $name = [];
        //$name['resource'] = lcfirst($this->currentHelper->currentResource());
        $name['namespace'] = $this->getNamespace(lcfirst($this->currentHelper->currentResource()));
        $name['dir'] = 'Action';
        //$area = $route->getOptions()['area'] ?? RendererMiddleware::AREA_DEFAULT;
        $area = $request->getAttribute('area', RendererMiddleware::AREA_DEFAULT);
        if ($area !== RendererMiddleware::AREA_DEFAULT) {
            $name['area'] = ucfirst($area);
        }
        $name['action'] = ucfirst($this->currentHelper->currentAction());

        //unset($name['resource']);

        return implode('\\', $name) . 'Action';
    }

    protected function getNamespace($mnemo)
    {
        $namespace = null;
        if ($this->moduleHelper && ($module = $this->moduleHelper->getBy($mnemo, 'mnemo'))) {
            $namespace = $module->getName();
        } elseif (isset($this->config['middleware'][$mnemo])) {
            $namespace = $this->config['middleware'][$mnemo];
        } else {
            throw new RuntimeException(sprintf(
                'Module for "%s" in not registered in configuration or database',
                $mnemo
            ));
        }

        return $namespace;
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
            $eventManager = new EventManager(($this->actionFactory)('SharedEventManager'));
            $action->setEventManager($eventManager);
        }
    }
}