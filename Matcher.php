<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

use Slince\Routing\Exception\RouteNotFoundException;
use Slince\Routing\Exception\MethodNotAllowedException;

class Matcher
{
    /**
     * request context
     * @var RequestContext
     */
    protected $context;

    public function __construct(RequestContext $context = null)
    {
        $this->context = $context;
    }

    /**
     * Find the route that match the given path from the routes
     * @param string $path
     * @param RouteCollection $routes
     * @return Route
     */
    public function match($path, RouteCollection $routes)
    {
        $path = '/' . ltrim($path, '/');
        $route = is_null($this->context) ? $this->findRouteWithoutRequestContext($path, $routes)
            : $this->findRoute($path, $routes);
        $computedParameters = $this->computeRouteParameters($route);
        $route->setComputedParameters($computedParameters);
        return $route;
    }

    /**
     * Set the request context
     * @param RequestContext $context
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * Gets the request context
     * @return RequestContext $context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param string $path
     * @param RouteCollection $routes
     * @throws MethodNotAllowedException
     * @throws RouteNotFoundException
     * @return Route
     */
    protected function findRoute($path, RouteCollection $routes)
    {
        $requiredMethods = [];
        foreach ($routes as $route) {
            if ($this->matchSchema($route) && $this->matchHost($route) && $this->matchPath($path, $route)) {
                if ($this->matchMethod($route)) {
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
     * @param RouteCollection $routes
     * @throws RouteNotFoundException
     * @return Route
     */
    protected function findRouteWithoutRequestContext($path, RouteCollection $routes)
    {
        foreach ($routes as $route) {
            if ($this->matchPath($path, $route)) {
                return $route;
            }
        }
        throw new RouteNotFoundException();
    }

    /**
     * Checks whether the route matches the current request host
     * @param Route $route
     * @return boolean
     */
    protected function matchHost(Route $route)
    {
        if (empty($route->getHost())) {
            return true;
        }
        if (preg_match($route->compile()->getHostRegex(), $this->context->getHost(), $matches)) {
            $routeParameters = array_intersect_key($matches, array_flip($route->getVariables()));
            $route->setParameter('_hostMatches', $routeParameters);
            return true;
        }
        return false;
    }

    /**
     * Checks whether the route matches the current request method
     * @param Route $route
     * @return boolean
     */
    protected function matchMethod(Route $route)
    {
        if (!$route->getMethods()) {
            return true;
        }
        return in_array(strtolower($this->context->getMethod()), $route->getMethods());
    }

    /**
     * Checks whether the route matches the scheme
     * @param Route $route
     * @return boolean
     */
    protected function matchSchema(Route $route)
    {
        if (!$route->getSchemes()) {
            return true;
        }
        return in_array($this->context->getScheme(), $route->getSchemes());
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