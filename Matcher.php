<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

use Psr\Http\Message\ServerRequestInterface;
use Slince\Routing\Exception\RouteNotFoundException;
use Slince\Routing\Exception\MethodNotAllowedException;

class Matcher
{
    /**
     * Routes collection
     * @var RouteCollection
     */
    protected $routes;

    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Find the route that match the given path from the routes
     * @param string $path
     * @return Route
     */
    public function match($path)
    {
        $path = '/' . ltrim($path, '/');
        return $this->doMatch($path);
    }

    /**
     * Find the route that match given request
     * @param ServerRequestInterface $request
     * @return Route
     */
    public function matchRequest(ServerRequestInterface $request)
    {
        return $this->doMatch($request);
    }

    /**
     * Do match
     * @param string|ServerRequestInterface $pathOrRequest
     * @return Route
     */
    protected function doMatch($pathOrRequest)
    {
        $route = $pathOrRequest instanceof ServerRequestInterface
            ? $this->findRouteFromRequest($pathOrRequest)
            : $this->findRoute($pathOrRequest);
        $computedParameters = $this->computeRouteParameters($route);
        $route->setComputedParameters($computedParameters);
        return $route;
    }

    /**
     * @param ServerRequestInterface $request
     * @throws MethodNotAllowedException
     * @throws RouteNotFoundException
     * @return Route
     */
    protected function findRouteFromRequest(ServerRequestInterface $request)
    {
        $requiredMethods = [];
        foreach ($this->routes as $route) {
            if (static::matchSchema($route, $request)
                && static::matchHost($route, $request)
                && static::matchPath($request->getUri()->getPath(), $route)
            ) {
                if (static::matchMethod($route, $request)) {
                    return $route;
                } else {
                    $requiredMethods = array_merge($requiredMethods, $route->getMethods());
                }
            }
        }
        if (!empty($requiredMethods)) {
            throw new MethodNotAllowedException($requiredMethods);
        }
        throw new RouteNotFoundException();
    }

    /**
     * @param string $path
     * @throws RouteNotFoundException
     * @return Route
     */
    protected function findRoute($path)
    {
        foreach ($this->routes as $route) {
            if (static::matchPath($path, $route)) {
                return $route;
            }
        }
        throw new RouteNotFoundException();
    }

    /**
     * Checks whether the route matches the current request host
     * @param Route $route
     * @param ServerRequestInterface $request
     * @return boolean
     */
    protected static function matchHost(Route $route, $request)
    {
        if (empty($route->getHost())) {
            return true;
        }
        if (preg_match($route->compile()->getHostRegex(), $request->getUri()->getHost(), $matches)) {
            $routeParameters = array_filter($matches, function($value, $key){
                return !is_int($key) && $value;
            }, ARRAY_FILTER_USE_BOTH);
            $route->setParameter('_hostMatches', $routeParameters);
            return true;
        }
        return false;
    }

    /**
     * Checks whether the route matches the current request method
     * @param Route $route
     * @param ServerRequestInterface $request
     * @return boolean
     */
    protected static function matchMethod(Route $route, $request)
    {
        if (!$route->getMethods()) {
            return true;
        }
        return in_array(strtoupper($request->getMethod()), $route->getMethods());
    }

    /**
     * Checks whether the route matches the scheme
     * @param Route $route
     * @param ServerRequestInterface $request
     * @return boolean
     */
    protected static function matchSchema(Route $route, $request)
    {
        if (!$route->getSchemes()) {
            return true;
        }
        return in_array($request->getUri()->getScheme(), $route->getSchemes());
    }

    /**
     * Checks whether the route matches the given path
     * @param string $path
     * @param Route $route
     * @return boolean
     */
    protected static function matchPath($path, Route $route)
    {
        if (preg_match($route->compile()->getPathRegex(), rawurldecode($path), $matches)) {
            $routeParameters = array_filter($matches, function($value, $key){
                return !is_int($key) && $value;
            }, ARRAY_FILTER_USE_BOTH);
            $route->setParameter('_pathMatches', $routeParameters);
            return true;
        }
        return false;
    }

    /**
     * 处理路由参数
     * @param Route $route
     * @return array
     */
    protected static function computeRouteParameters(Route $route)
    {
        return array_replace($route->getDefaults(),
            $route->getParameter('_hostMatches') ?: [],
            $route->getParameter('_pathMatches') ?: []
        );
    }
}