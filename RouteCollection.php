<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

class RouteCollection implements \Countable, \IteratorAggregate
{
    use RouteBuilderTrait;

    /**
     * Array of routes
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Array of route names
     * @var Route[]
     */
    protected $names = [];

    /**
     * Array of route actions
     * @var Route[]
     */
    protected $actions = [];

    protected static $defaultOptions = [
        'prefix' => null,
        'host' => null,
        'methods' => [],
        'schemes' => [],
        'requirements' => [],
        'defaults' => []
    ];

    public function __construct(array $routes = [])
    {
        $this->routes = $routes;
    }

    /**
     * Add a route to the collection
     * @param Route $route
     */
    public function add(Route $route)
    {
        if ($route->getName()) {
            $this->names[$route->getName()] = $route;
        }
        $action = $route->getAction();
        if (is_scalar($action)) {
            $this->actions[$action] = $route;
        }
        $this->routes[] = $route;
    }

    /**
     * Finds the route by the given name
     * @param string $name
     * @return Route|null
     */
    public function getByName($name)
    {
        return isset($this->names[$name]) ? $this->names[$name] : null;
    }

    /**
     * Finds the route by the given action
     * @param string $action
     * @return Route|null
     */
    public function getByAction($action)
    {
        return isset($this->actions[$action]) ? $this->actions[$action] : null;
    }

    /**
     * Gets all named routes
     * @return Route[]
     */
    public function getNamedRoutes()
    {
        return $this->names;
    }

    /**
     * Creates a sub collection routes
     * @param string|array $options
     * @param \Closure $callback
     */
    public function group($options, \Closure $callback)
    {
        if (is_string($options)) {
            $options = ['prefix' => $options];
        }
        $collection = new RouteCollection([]);
        call_user_func($callback, $collection);
        $this->mergeSubCollection($collection, $options);
    }

    /**
     * Merges routes from an route collection
     * @param RouteCollection $collection
     * @param array $options
     */
    protected function mergeSubCollection(RouteCollection $collection, $options = [])
    {
        $extraParameters = array_diff_key($options, static::$defaultOptions);
        $options = array_replace(static::$defaultOptions, $options);
        foreach ($collection->all() as $route) {
            if ($options['prefix']) {
                $path =  trim($options['prefix'], '/') . '/' . trim($route->getPath(), '/');
                $route->setPath($path);
            }
            if (!$route->getHost()) {
                $route->setHost($options['host']);
            }
            if (!$route->getMethods()) {
                $route->setMethods((array)$options['methods']);
            }
            if (!$route->getSchemes()) {
                $route->setSchemes((array)$options['schemes']);
            }
            $route->addRequirements($options['requirements']);
            $route->addDefaults($options['defaults']);
            $route->addParameters($extraParameters);
            $this->add($route);
        }
    }

    /**
     * Gets all routes
     * @return Route[]
     */
    public function all()
    {
        return $this->routes;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->routes);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->routes);
    }
}