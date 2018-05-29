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

use Psr\Http\Message\ServerRequestInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Mvc\Controller\PluginManager;
use Zend\Mvc\Exception;
use Zend\Psr7Bridge\Psr7ServerRequest;
use Zend\ServiceManager\ServiceManager;
use Zend\Http\PhpEnvironment\Response as HttpResponse;
use Zend\Stdlib\DispatchableInterface as Dispatchable;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\ResponseInterface as Response;
use Zend\EventManager\EventInterface as Event;
use Zend\Router\RouteMatch;
use Zend\Mvc\MvcEvent;
use Stagem\ZfcAction\MiddlewareInterface;
use Zend\View\Model\ViewModel;

/**
 * Abstract action
 *
 * Convenience methods for pre-built plugins (@see __call):
 * @codingStandardsIgnoreStart
 * @method \Zend\View\Model\ModelInterface acceptableViewModelSelector(array $matchAgainst = null, bool $returnDefault = true, \Zend\Http\Header\Accept\FieldValuePart\AbstractFieldValuePart $resultReference = null)
 * @codingStandardsIgnoreEnd
 * @method \Zend\Mvc\Controller\Plugin\Forward forward()
 * @method \Zend\Mvc\Controller\Plugin\Params|mixed params(string $param = null, mixed $default = null)
 * @method \Zend\Mvc\Controller\Plugin\Redirect redirect()
 * @method \Zend\Mvc\Controller\Plugin\Url url()
 * @method \Stagem\ZfcAction\Page\Plugin\GotoPlugin goto()
 */
abstract class AbstractAction implements
    Dispatchable,
    MiddlewareInterface,
    EventManagerAwareInterface,
    InjectApplicationEventInterface
{
    use EventManagerAwareTrait;

    /**
     * @var MvcEvent
     */
    protected $event;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var PluginManager
     */
    protected $plugins;

    /**
     * Set an event to use during dispatch
     *
     * By default, will re-cast to MvcEvent if another event type is provided.
     *
     * @param  Event $e
     * @return void
     */
    public function setEvent(Event $e)
    {
        if (! $e instanceof MvcEvent) {
            $eventParams = $e->getParams();
            $e = new MvcEvent();
            $e->setParams($eventParams);
            unset($eventParams);
        }
        $this->event = $e;
    }

    /**
     * Get the attached event
     *
     * Will create a new MvcEvent if none provided.
     *
     * @return MvcEvent
     */
    public function getEvent()
    {
        if (! $this->event) {
            $this->setEvent(new MvcEvent());
        }

        return $this->event;
    }

    /**
     * Dispatch a request
     *
     * @events dispatch.pre, dispatch.post
     * @param  Request $request
     * @param  null|Response $response
     * @return Response|mixed
     */
    public function dispatch(Request $request, Response $response = null)
    {
        $this->request = $request;
        if (! $response) {
            $response = new HttpResponse();
        }
        $this->response = $response;

        $e = $this->getEvent();
        $e->setName(MvcEvent::EVENT_DISPATCH);
        $e->setRequest($request);
        $e->setResponse($response);
        $e->setTarget($this);

        $result = $this->getEventManager()->triggerEventUntil(function ($test) {
            return ($test instanceof Response);
        }, $e);

        if ($result->stopped()) {
            return $result->last();
        }

        return $e->getResult();
    }

    /**
     * Register the default events for this controller
     *
     * @return void
     */
    protected function attachDefaultListeners()
    {
        $events = $this->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'onDispatch']);
    }

    /**
     * Execute the request
     *
     * @param  MvcEvent $e
     * @return mixed
     * @throws Exception\DomainException
     */
    public function onDispatch(MvcEvent $e)
    {
        /** @var \Zend\Router\RouteMatch $routeMatch */
        $routeMatch = $e->getRouteMatch();
        if (! $routeMatch) {
            /**
             * @todo Determine requirements for when route match is missing.
             *       Potentially allow pulling directly from request metadata?
             */
            throw new Exception\DomainException('Missing route matches; unsure how to retrieve action');
        }

        $request = Psr7ServerRequest::fromZend($e->getRequest());
        foreach ($routeMatch->getParams() as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }
        $request = $request->withAttribute(RouteMatch::class, $routeMatch);

        $actionResponse = $this->action($request);

        $e->setResult($actionResponse);

        return $actionResponse;
    }

    abstract public function action(ServerRequestInterface $request);

    /**
     * Get plugin manager
     *
     * @return PluginManager
     */
    public function getPluginManager()
    {
        if (! $this->plugins) {
            $this->setPluginManager(new PluginManager(new ServiceManager()));
        }

        $this->plugins->setController($this);
        return $this->plugins;
    }

    /**
     * Set plugin manager
     *
     * @param PluginManager $plugins
     * @return AbstractAction
     */
    public function setPluginManager(PluginManager $plugins)
    {
        $this->plugins = $plugins;
        $this->plugins->setController($this);

        return $this;
    }

    /**
     * Get plugin instance
     *
     * @param  string     $name    Name of plugin to return
     * @param  null|array $options Options to pass to plugin constructor (if not already instantiated)
     * @return mixed
     */
    public function plugin($name, array $options = null)
    {
        return $this->getPluginManager()->get($name, $options);
    }

    /**
     * Method overloading: return/call plugins
     *
     * If the plugin is a functor, call it, passing the parameters provided.
     * Otherwise, return the plugin instance.
     *
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        $plugin = $this->plugin($method);
        if (is_callable($plugin)) {
            return call_user_func_array($plugin, $params);
        }

        return $plugin;
    }
}
