# ZF-Expressive auto-wiring Action

Module allow use one `routes` pattern for all actions without re-declaration routes configuration.
Either action can be accessed with next unified path */module/action*. 
It's like to standard MVC pattern with it */controller/action*.

Such as Expressive application doesn't about any *controller* we use for this *module* keyword.

## Installation
Add to your `composer.json` repository declaration and run `composer update`
```
// composer.json

"require": {
	"stagem/zfc-action": "dev-master"
},
"repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/stagemteam/zfc-action"
    }
]
```

After that enable module in `config/config.php`
```
$aggregator = new ConfigAggregator([
    //...
    Stagem\Server\ConfigProvider::class,
    //...
], $cacheConfig['config_cache_path']);
```
and register auto-wiring route in `config/routes.php`
```
$app->injectRoutesFromConfig((new Stagem\ZfcAction\ConfigProvider())());
```

## Usage

### Standalone Usage
Map your module name with namespace in `src/Acme/Foo/config/module.config.php`
```
namespace Acme\Foo;

return [
    //...
    'middleware' => [
        'foo' => __NAMESPACE__
    ],
]
```

### Advanced Usage
Advanced usage allow use standardized and flexible approach with module naming on all application level.

> Before continue reading you should install `stagem/zfc-entity` 

## Custom implementation
If you want use in third party packages you have to prepare CurrentHelper for usage. You should call next code before 
*controller/action* dispatching:
```php
$this->currentHelper->setDefaultContext($actionOtControlletClassName);
$this->currentHelper->setResource($request->getAttribute('resource', self::DEFAULT_RESOURCE));
$this->currentHelper->setAction($request->getAttribute('action', self::DEFAULT_ACTION));
$this->currentHelper->setRequest($request);
$this->currentHelper->setRoute($route->getMatchedRoute());
$this->currentHelper->setMatchedParams($route->getMatchedParams());
```

