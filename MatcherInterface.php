<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

interface MatcherInterface
{

    /**
     * 查找匹配的route
     * @param string $path
     * @param RouteCollection $routes
     * @return RouteInterface
     */
    public function match($path, RouteCollection $routes);

    /**
     * 设置上下文
     * @param RequestContext $context
     */
    public function setContext(RequestContext $context);

    /**
     * 获取上下文
     * @return RequestContext $context
     */
    public function getContext();
}