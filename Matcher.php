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
     * 查找匹配的route
     * @param string $path
     * @param RouteCollection $routes
     * @return Route
     */
    public function match($path, RouteCollection $routes)
    {
        $path = '/' . ltrim($path, '/');
        $route = is_null($this->context) ? $this->findRouteWithoutRequestContext($path, $routes)
            : $this->findRoute($path, $routes);
        $routeParameters = $this->getRouteParameters($route);
        $route->setParameters($routeParameters);
        return $route;
    }

    /**
     * 设置上下文
     * @param RequestContext $context
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * 获取上下文
     * @return RequestContext $context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * 找出匹配path的route
     * @param string $path
     * @param RouteCollection $routes
     * @throws MethodNotAllowedException
     * @throws RouteNotFoundException
     * @return Route
     */
    protected function findRoute($path, RouteCollection $routes)
    {
        $requireMethods = [];
        // 查找符合条件的route
        foreach ($routes as $route) {
            if ($this->matchSchema($route) && $this->matchHost($route) && $this->matchPath($path, $route)) {
                if ($this->matchMethod($route)) {
                    return $route;
                } else {
                    $requireMethods += $route->getMethods();
                }
            }
        }
        if (!empty($requireMethods)) {
            throw new MethodNotAllowedException($requireMethods);
        }
        throw new RouteNotFoundException();
    }

    /**
     * 找出匹配path的route，不考虑request上下文
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
     * 匹配host
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
     * 匹配method
     * @param Route $route
     * @return boolean
     */
    protected function matchMethod(Route $route)
    {
        if (empty($route->getMethods())) {
            return true;
        }
        return in_array(strtolower($this->context->getMethod()), $route->getMethods());
    }

    /**
     * 匹配scheme
     * @param Route $route
     * @return boolean
     */
    protected function matchSchema(Route $route)
    {
        //没有scheme直接忽略
        if (empty($route->getSchemes())) {
            return true;
        }
        return in_array($this->context->getScheme(), $route->getSchemes());
    }

    /**
     * 匹配path
     * @param string $path
     * @param Route $route
     * @return boolean
     */
    protected function matchPath($path, Route $route)
    {
        //如果没有path则直接忽略
        if (empty($route->getPath())) {
            return true;
        }
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
    protected function getRouteParameters(Route $route)
    {
        return array_replace($route->getDefaults(),
            $route->getParameter('_hostMatches', []),
            $route->getParameter('_pathMatches', [])
        );
    }
}