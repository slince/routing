<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

use Psr\Http\Message\ServerRequestInterface;
use Slince\Routing\Exception\InvalidArgumentException;

class Generator
{
    /**
     * The request context
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * The variable
     * @var array
     */
    protected $lastRouteVariables = [];

    public function __construct(ServerRequestInterface $request = null)
    {
        $this->request = $request;
    }

    /**
     * Sets the request context
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Gets the request context
     * @return ServerRequestInterface $context
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Generates the url for the route
     * @param Route $route
     * @param array $parameters
     * @param boolean $absolute
     * @return string
     */
    public function generate(Route $route, $parameters = [], $absolute = false)
    {
        if ($absolute && !$this->request) {
            throw new InvalidArgumentException("You must provide the request context to generate the full url");
        }
        $urlSlugs = [];
        //generate absolute url
        if ($absolute) {
            $urlSlugs[] = $this->getRouteSchemeAndHost($route);
        }
        $urlSlugs[] = $this->getRoutePath($route, $parameters);
        // Build query string
        $extraParameters = array_diff_key($parameters, array_flip($this->lastRouteVariables));
        if (!empty($extraParameters) && $query = http_build_query($extraParameters, '', '&')) {
            $urlSlugs[] =  '?' . $query;
        }
        return implode('', $urlSlugs);
    }

    /**
     * 获取route的scheme和port
     * @param Route $route
     * @param array $parameters
     * @return string
     */
    protected function getRouteSchemeAndHost(Route $route, array $parameters = [])
    {
        $scheme = $this->request->getUri()->getScheme();
        $requiredSchemes = $route->getSchemes();
        if (!empty($requiredSchemes) && !in_array($scheme, $requiredSchemes)) {
            $scheme = $requiredSchemes[0];
        }
        if (!$route->getHost()) {
            $host = $this->request->getUri()->getHost();
            if ($port = $this->request->getUri()->getPort()) {
                if (strcasecmp($scheme, 'http') == 0 && $port != 80) {
                    $host .= ':' . $this->request->getUri()->getPort();
                } elseif (strcasecmp($scheme, 'https') == 0 && $port != 443) {
                    $host .= ':' . $this->request->getUri()->getPort();
                }
            }
        } else {
            $host = $this->replaceRouteNamedParameters($route, $route->getHost(), $parameters);
        }
        return $scheme . '://' . $host;
    }

    /**
     * Gets the route path
     * @param Route $route
     * @param array $parameters
     * @return string
     */
    protected function getRoutePath(Route $route, $parameters)
    {
        return $this->replaceRouteNamedParameters($route, $route->getPath(), $parameters);
    }

    /**
     * Replaces the named parameters of the route path or host
     * @param Route $route
     * @param string $path
     * @param array $parameters
     * @throws InvalidArgumentException
     * @return string
     */
    protected function replaceRouteNamedParameters(Route $route, $path, $parameters)
    {
        return preg_replace_callback('#[/\.]?\{([a-zA-Z0-9_,]*)\}#', function ($match) use ($route, $parameters) {
            $this->lastRouteVariables[] = $match[1];
            //The named parameter value must be provided if the route has not the default value fot it
            if ((!isset($parameters[$match[1]]) || is_null($parameters[$match[1]])) && !$route->hasDefault($match[1])) {
                throw new InvalidArgumentException(sprintf('Missing parameter "%s"', $match[1]));
            }
            $providedValue = isset($parameters[$match[1]]) && !is_null($parameters[$match[1]]) ?
                $parameters[$match[1]] : $route->getDefault($match[1]);

            if ($route->hasRequirement($match[1])
                && !preg_match('#^' . $route->getRequirement($match[1]) . '$#', $providedValue)
            ){
                $message = sprintf('Parameter "%s" must match "%s" ("%s" given) to generate a corresponding URL.',
                    $match[1], $route->getRequirement($match[1]), $providedValue);
                throw new InvalidArgumentException($message);
            }
            if ($route->getDefault($match[1]) === $providedValue) {
                return '';
            }
            return str_replace('{' . $match[1] . '}', $providedValue, $match[0]);
        }, $path);
    }
}