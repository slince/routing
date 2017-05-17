<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

class RouteBuilder
{
    /**
     * 路由前缀
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * @var RouteCollection
     */
    protected $routes;

    public function __construct($prefix, RouteCollection $routes)
    {
        $this->setPrefix($prefix);
        $this->routes = $routes;
    }

    public function setPrefix($prefix)
    {
        if (!empty($prefix)) {
            $this->prefix = '/' . trim($prefix, '/');
        }
    }

    /**
     * 获取当前的前缀
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * 获取routes
     * @return RouteCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * 创建一个普通路由，add别名
     * @param $path
     * @param $arguments
     * @return Route
     */
    public function http($path, $arguments)
    {
        return $this->add($path, $arguments);
    }

    /**
     * 创建一个https路由
     * @param $path
     * @param $arguments
     * @return $this
     */
    public function https($path, $arguments)
    {
        return $this->add($path, $arguments)->setSchemes([
            'https'
        ]);
    }

    /**
     * 创建一个get路由
     * @param $path
     * @param $arguments
     * @return $this
     */
    public function get($path, $arguments)
    {
        return $this->add($path, $arguments)->setMethods([
            Route::GET,
            Route::HEAD
        ]);
    }

    /**
     * 创建一个post路由
     * @param $path
     * @param $arguments
     * @return $this
     */
    public function post($path, $arguments)
    {
        return $this->add($path, $arguments)->setMethods([
            Route::POST
        ]);
    }

    /**
     * 创建一个put路由
     * @param $path
     * @param $arguments
     * @return $this
     */
    public function put($path, $arguments)
    {
        return $this->add($path, $arguments)->setMethods([
            Route::PUT
        ]);
    }

    /**
     * 创建一个patch路由
     * @param $path
     * @param $arguments
     * @return $this
     */
    public function patch($path, $arguments)
    {
        return $this->add($path, $arguments)->setMethods([
            Route::PATCH
        ]);
    }

    /**
     * 创建一个delete路由
     * @param $path
     * @param $arguments
     * @return $this
     */
    public function delete($path, $arguments)
    {
        return $this->add($path, $arguments)->setMethods([
            Route::DELETE
        ]);
    }

    /**
     * 创建并添加一个路由
     * @param $path
     * @param $arguments
     * @return Route
     */
    public function add($path, $arguments)
    {
        $name = null;
        $action = null;
        if (is_callable($arguments) || is_string($arguments)) {
            $action = $arguments;
        } elseif (is_array($arguments)) {
            $name = isset($arguments['name']) ? $arguments['name'] : null;
            $action = isset($arguments['action']) ? $arguments['action'] : reset($arguments);
        }
        $route = $this->newRoute($path, $action);
        $this->getRoutes()->add($route, $name);
        return $route;
    }

    /**
     * 创建一个路由
     * @param $path
     * @param $action
     * @return Route
     */
    public function newRoute($path, $action)
    {
        $path = $this->getPrefix() . '/' . trim($path, '/');
        return new Route($path, $action);
    }

    /**
     * 创建一个前缀
     * @param string $prefix
     * @param \Closure $callback
     */
    public function prefix($prefix, \Closure $callback)
    {
        $originPrefix = $this->getPrefix();
        $routeBuilder = new RouteBuilder($originPrefix . '/' . $prefix, $this->routes);
        call_user_func($callback, $routeBuilder);
    }
}