<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

class RouteCollection implements \Countable, \IteratorAggregate
{
    /**
     * Array of routes
     * @var array
     */
    protected $routes = [];

    /**
     * Array of route names
     * @var array
     */
    protected $names = [];

    /**
     * Array of route actions
     * @var array
     */
    protected $actions = [];

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
    public function getNamedRoute()
    {
        return $this->names;
    }

    /**
     * Gets all routes
     * @return array
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