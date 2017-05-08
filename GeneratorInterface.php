<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

interface GeneratorInterface
{

    /**
     * Generates a url
     * @param RouteInterface $route
     * @param array $parameters
     * @param boolean $absolute
     * @return string
     */
    public function generate(RouteInterface $route, $parameters = [], $absolute = false);

    /**
     * Sets the context of the generator
     * @param RequestContext $context
     */
    public function setContext(RequestContext $context);

    /**
     * Gets the context of generator
     * @return RequestContext $context
     */
    public function getContext();
}