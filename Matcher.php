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
     * @var Route[]
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
            if ($this->matchSchema($route, $request)
                && $this->matchHost($route, $request)
                && $this->matchPath($request->getUri()->getPath(), $route)
            ) {
                if ($this->matchMethod($route, $request)) {
                    return $route;
                } else {
                    $requiredMethods += $route->getMethods();
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
            if ($this->matchPath($path, $route)) {
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
    protected function matchHost(Route $route, $request)
    {
        if (empty($route->getHost())) {
            return true;
        }
        if (preg_match($route->compile()->getHostRegex(), $request->getUri()->getHost(), $matches)) {
            $routeParameters = array_intersect_key($matches, array_flip($route->getVariables()));
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
    protected function matchMethod(Route $route, $request)
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
    protected function matchSchema(Route $route, $request)
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
    protected function matchPath($path, Route $route)
    {
        if (preg_match($route->compile()->getPathRegex(), rawurldecode($path), $matches)) {
            $routeParameters = array_intersect_key($matches, array_flip($route->getVariables()));
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
    protected function computeRouteParameters(Route $route)
    {
        return array_replace($route->getDefaults(),
            $route->getParameter('_hostMatches', []),
            $route->getParameter('_pathMatches', [])
        );
    }
}