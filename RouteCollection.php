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
     * 添加路由
     * @param RouteInterface $route
     * @param string|null $name
     */
    public function add(RouteInterface $route, $name = null)
    {
        if (!is_null($name)) {
            $this->names[$name] = $route;
        }
        $action = $route->getAction();
        if (is_scalar($action)) {
            $this->actions[$action] = $route;
        }
        $this->routes[] = $route;
    }

    /**
     * 根据name获取route
     * @param string $name
     * @return Route|null
     */
    public function getByName($name)
    {
        return isset($this->names[$name]) ? $this->names[$name] : null;
    }

    /**
     * 根据action获取route
     * @param string $action
     * @return Route|null
     */
    public function getByAction($action)
    {
        return isset($this->actions[$action]) ? $this->actions[$action] : null;
    }

    /**
     * 获取全部的命名路由
     * @return array
     */
    public function getNameRoute()
    {
        return $this->names;
    }

    /**
     * 获取全部action路由
     * @return array
     */
    public function getActionRoutes()
    {
        return $this->actions;
    }

    /**
     * 获取所有的路由
     * @return array
     */
    public function all()
    {
        return $this->routes;
    }

    /**
     * 获取路由数量
     * @return int
     */
    public function count()
    {
        return count($this->routes);
    }

    /**
     * 实现接口
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->routes);
    }

    /**
     * 实现RouteBuilderTrait方法
     * @return $this
     */
    public function getRoutes()
    {
        return $this;
    }
}