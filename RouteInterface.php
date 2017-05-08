<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

interface RouteInterface
{
    /**
     * Sets the route path
     * @param string $path
     * @return RouteInterface
     */
    public function setPath($path);

    /**
     * Gets the route path
     * @return string path
     */
    public function getPath();

    /**
     * Gets the path regex
     * @return string
     */
    public function getPathRegex();

    /**
     * Sets the route parameters
     * @param string $name
     * @param mixed $parameter
     * @return RouteInterface
     */
    public function setParameter($name, $parameter);

    /**
     * Gets the route parameters
     * @param string $name
     * @param string $default
     * @return RouteInterface
     */
    public function getParameter($name, $default = null);

    /**
     * Checks whether the parameter exists
     * @param string $name
     * @return mixed
     */
    public function hasParameter($name);

    /**
     * Sets all parameters
     * @param array $parameters
     * @return RouteInterface
     */
    public function setParameters(array $parameters);

    /**
     * Gets all parameters
     * @return array
     */
    public function getParameters();

    /**
     * Sets the requirements of the route
     * @param array $requirements
     * @return RouteInterface
     */
    public function setRequirements(array $requirements);

    /**
     * Sets a requirement by specified name and value
     * @param string $name
     * @param string $requirement
     * @return RouteInterface
     */
    public function setRequirement($name, $requirement);

    /**
     * Add the requirements of the route (not replace)
     * @param array $requirements
     * @return RouteInterface
     */
    public function addRequirements(array $requirements);

    /**
     * Gets all requirements
     * @return array
     */
    public function getRequirements();

    /**
     * Gets a requirement by its name
     * @param string $name
     * @param string $default
     * @return string|null
     */
    public function getRequirement($name, $default = null);

    /**
     * Sets the route schemes
     * @param array $schemes
     * @return RouteInterface
     */
    public function setSchemes(array $schemes);

    /**
     * Gets all schemes
     * @return array
     */
    public function getSchemes();

    /**
     * Sets the route request methods
     * @param array $methods
     * @return RouteInterface
     */
    public function setMethods(array $methods);

    /**
     * Gets all request methods that the route supports
     * @return array
     */
    public function getMethods();

    /**
     * Set the route host
     * @param string $host
     * @return RouteInterface
     */
    public function setHost($host);

    /**
     * Gets the host
     * @return string
     */
    public function getHost();

    /**
     * Gets the route host regex
     * @return string
     */
    public function getHostRegex();
}