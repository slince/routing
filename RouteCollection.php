<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

class RouteCollection implements \Countable, \IteratorAggregate
{
    protected $options;

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

    public function __construct(array $routes = [], array $options = [])
    {
        $this->routes = $routes;
        $this->options = $options;
    }

    /**
     * Add a route to the collection
     * @param Route $route
     */
    public function addRoute(Route $route)
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
    public function getRouteByName($name)
    {
        return isset($this->names[$name]) ? $this->names[$name] : null;
    }

    /**
     * Finds the route by the given action
     * @param string $action
     * @return Route|null
     */
    public function getRouteByAction($action)
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
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }


    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }


    public function group($options, \Closure $callback)
    {
        $collection = new RouteCollection([], $options);
        call_user_func($callback, $collection);
        $this->mergeSubCollection($collection);
    }

    public function mergeSubCollection(RouteCollection $collection)
    {
        foreach ($collection->all() as $route) {
            if ($prefix = $this->getOption('prefix')) {
                $path = '/' . trim($prefix, '/') . '/' . trim($route->getPath(), '/');
                $route->setPath($path);
            }
            $route->addRequirements($this->getOption('requirements', []));
            $route->addDefaults($this->getOption('defaults', []));
            if (!$route->getHost()) {
                $route->setHost($this->getOption('host'));
            }
            if (!$route->getSchemes()) {
                $route->setSchemes($this->getOption('schemes', []));
            }
            $this->addRoute($route);
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