<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

use Slince\Routing\Exception\InvalidArgumentException;

class Generator
{
    /**
     * The request context
     * @var RequestContext
     */
    protected $context;

    /**
     * Whether to strictly checks the requirements
     * @var boolean
     */
    protected $strictRequirements = false;

    /**
     * The variable
     * @var array
     */
    protected $routeVariables = [];

    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * Sets the request context
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
     * Sets whether to strictly check the requirements
     * @param boolean $enabled
     */
    public function setStrictRequirements($enabled)
    {
        $this->strictRequirements = $enabled;
    }

    /**
     * Checks whether to strictly check the requirements
     * @return boolean
     */
    public function isStrictRequirements()
    {
        return $this->strictRequirements;
    }

    /**
     * Generates the url for the route
     * @param Route $route
     * @param array $parameters
     * @param boolean $absolute
     * @return string
     */
    public function generate(Route $route, $parameters = [], $absolute = true)
    {
        $computedParameters = array_replace($route->getDefaults(), $parameters);
        $urlSlugs = [];
        //generate absolute url
        if ($absolute) {
            list($scheme, $port) = $this->getRouteSchemeAndPort($route);
            $host = $this->getRouteHost($route, $computedParameters);
            $urlSlugs[] = "{$scheme}://{$host}{$port}";
        }
        $urlSlugs[] = $this->getRoutePath($route, $parameters);
        // Build query string
        $extraParameters = array_diff_key($parameters, array_flip($this->routeVariables));
        if ($extraParameters && $query = http_build_query($extraParameters, '', '&')) {
            $urlSlugs[] =  '?' . $query;
        }
        return implode('', $urlSlugs);
    }

    /**
     * 获取route的scheme和port
     * @param Route $route
     * @return array
     */
    protected function getRouteSchemeAndPort(Route $route)
    {
        $scheme = $this->context->getScheme();
        $requiredSchemes = $route->getSchemes();
        if ($requiredSchemes && !in_array($scheme, $requiredSchemes)) {
            $scheme = reset($requiredSchemes);
        }
        $port = '';
        if (strcasecmp($scheme, 'http') == 0 && $this->context->getPort() != 80) {
            $port = ':' . $this->context->getPort();
        } elseif (strcasecmp($scheme, 'https') == 0 && $this->context->getPort() != 443) {
            $port = ':' . $this->context->getPort();
        }
        return [$scheme, $port];
    }

    /**
     * Gets the route host
     * @param Route $route
     * @param array $parameters
     * @return string
     */
    protected function getRouteHost(Route $route, $parameters)
    {
        //If the route has no required host, returns the current host
        if (!$route->getHost()) {
            return $this->context->getHost();
        }
        return $this->replaceRouteNamedParameters($route->getHost(), $parameters, $route->getRequirements());
    }

    /**
     * Gets the route path
     * @param Route $route
     * @param array $parameters
     * @return string
     */
    protected function getRoutePath(Route $route, $parameters)
    {
        return $this->replaceRouteNamedParameters($route->getPath(), $parameters, $route->getRequirements());
    }

    /**
     * Replaces the named parameters of the route path or host
     * @param string $path
     * @param array $parameters
     * @param array $requirements
     * @throws InvalidArgumentException
     * @return string
     */
    protected function replaceRouteNamedParameters($path, $parameters, $requirements = [])
    {
        return preg_replace_callback('#\{([a-zA-Z0-9_,]*)\}#', function ($matches) use ($parameters, $requirements) {
            $this->routeVariables[] = $matches[1];
            //The named parameter value must be provided if the strict mode
            if (!isset($parameters[$matches[1]]) && $this->strictRequirements) {
                throw new InvalidArgumentException(sprintf('Missing parameter "%s"', $matches[1]));
            }
            $supportVariable = isset($parameters[$matches[1]]) ? $parameters[$matches[1]] : '';
            if ($this->strictRequirements) {
                if (isset($requirements[$matches[1]]) && !preg_match('#^' . $requirements[$matches[1]] . '$#',
                        $supportVariable)
                ) {
                    $message = sprintf('Parameter "%s" must match "%s" ("%s" given) to generate a corresponding URL.',
                        $matches[1], $requirements[$matches[1]], $supportVariable);
                    throw new InvalidArgumentException($message);
                }
            }
            return $supportVariable;
        }, $path);
    }
}