<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

trait RouteBuilderTrait
{
    /**
     * Creates a new route with http scheme
     * @param string $path
     * @param mixed $action
     * @return Route
     */
    public function http($path, $action)
    {
        return $this->create($path, $action)->setSchemes(['http']);
    }

    /**
     * Creates a new route with https scheme
     * @param string $path
     * @param mixed $action
     * @return Route
     */
    public function https($path, $action)
    {
        return $this->create($path, $action)->setSchemes(['https']);
    }

    /**
     * Creates a new GET route
     * @param string $path
     * @param mixed $action
     * @return Route
     */
    public function get($path, $action)
    {
        return $this->create($path, $action)->setMethods([
            Route::GET,
            Route::HEAD
        ]);
    }

    /**
     * Creates a new POST route
     * @param string $path
     * @param mixed $action
     * @return Route
     */
    public function post($path, $action)
    {
        return $this->create($path, $action)->setMethods([
            Route::POST
        ]);
    }

    /**
     * Creates a new PUT route
     * @param string $path
     * @param mixed $action
     * @return Route
     */
    public function put($path, $action)
    {
        return $this->create($path, $action)->setMethods([
            Route::PUT
        ]);
    }

    /**
     * Creates a new PATCH route
     * @param string $path
     * @param mixed $action
     * @return Route
     */
    public function patch($path, $action)
    {
        return $this->create($path, $action)->setMethods([
            Route::PATCH
        ]);
    }

    /**
     * Creates a new DELETE route
     * @param string $path
     * @param mixed $action
     * @return Route
     */
    public function delete($path, $action)
    {
        return $this->create($path, $action)->setMethods([
            Route::DELETE
        ]);
    }

    /**
     * Creates a new route and registries it to the collection
     * @param string $path
     * @param mixed $action
     * @return Route
     */
    public function create($path, $action)
    {
        $route = new Route($path, $action);
        $this->add($route);
        return $route;
    }

    /**
     * Add a route to the collection
     * @param Route $route
     * @return Route
     */
    abstract public function add(Route $route);
}