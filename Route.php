<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

class Route
{
    /**
     * head
     * @var string
     */
    const HEAD = 'HEAD';

    /**
     * get
     * @var string
     */
    const GET = 'GET';

    /**
     * post
     * @var string
     */
    const POST = 'POST';

    /**
     * put
     * @var string
     */
    const PUT = 'PUT';

    /**
     * patch
     * @var string
     */
    const PATCH = 'PATCH';

    /**
     * delete
     * @var string
     */
    const DELETE = 'DELETE';

    /**
     * purge
     * @var string
     */
    const PURGE = 'PURGE';

    /**
     * options
     * @var string
     */
    const OPTIONS = 'OPTIONS';

    /**
     * trace
     * @var string
     */
    const TRACE = 'TRACE';

    /**
     * connect
     * @var string
     */
    const CONNECT = 'CONNECT';

    /**
     * The route name
     * @var string
     */
    protected $name;

    /**
     * Path
     * @var string
     */
    protected $path;

    /**
     * Action
     * @var mixed
     */
    protected $action;

    /**
     * schemes
     * @var array
     */
    protected $schemes = [];

    /**
     * host
     * @var string
     */
    protected $host;

    /**
     * methods
     * @var array
     */
    protected $methods = [];

    /**
     * requirements
     * @var array
     */
    protected $requirements = [];

    /**
     * Defaults
     * @var array
     */
    protected $defaults= [];

    /**
     * parameters
     * @var array
     */
    protected $parameters = [];

    /**
     * The computed parameters
     * @var array
     */
    protected $computedParameters = [];

    /**
     * whether the route has been compiled
     * @var bool
     */
    protected $isCompiled = false;

    /**
     * the host regex
     * @var string
     */
    protected $hostRegex;

    /**
     * the path regex
     * @var string
     */
    protected $pathRegex;

    protected $hostVariables = [];

    protected $pathVariables = [];

    /**
     * 变量
     * @var array
     */
    protected $variables = [];

    public function __construct($path, $action) {
        $this->setPath($path);
        $this->setAction($action);
    }

    /**
     * Sets route name
     * @param string $name
     * @return $this;
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the route path
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = '/' . trim($path, '/');
        return $this;
    }

    /**
     * Gets the route path
     * @return string path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Gets the path regex
     * @return string
     */
    public function getPathRegex()
    {
        return $this->pathRegex;
    }

    /**
     * Sets the action for the route
     * @param mixed $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Gets the action
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Sets the route schemes
     * @param array $schemes
     * @return $this
     */
    public function setSchemes(array $schemes)
    {
        $this->schemes = array_map('strtolower', $schemes);
        return $this;
    }

    /**
     * Checks whether the route has the scheme
     * @param string $scheme
     * @return bool
     */
    public function hasScheme($scheme)
    {
        return in_array(strtolower($scheme), $this->schemes);
    }

    /**
     * Gets all schemes
     * @return array
     */
    public function getSchemes()
    {
        return $this->schemes;
    }

    /**
     * Sets the route request methods
     * @param array $methods
     * @return $this
     */
    public function setMethods(array $methods)
    {
        $this->methods = array_map('strtoupper', $methods);
        return $this;
    }

    /**
     * Checks whether the route has the request method
     * @param string $method
     * @return bool
     */
    public function hasMethod($method)
    {
        return in_array(strtoupper($method), $this->methods);
    }

    /**
     * Gets all request methods that the route supports
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Set the route host
     * @param string $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Gets the host
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Gets the route host regex
     * @return string
     */
    public function getHostRegex()
    {
        return $this->hostRegex;
    }

    /**
     * Sets the requirements of the route
     * @param array $requirements
     * @return $this
     */
    public function setRequirements(array $requirements)
    {
        $this->requirements = $requirements;
        return $this;
    }

    /**
     * Sets a requirement by specified name and value
     * @param string $name
     * @param string $requirement
     * @return $this
     */
    public function setRequirement($name, $requirement)
    {
        $this->requirements[$name] = $requirement;
        return $this;
    }

    /**
     * Add the requirements of the route (not replace)
     * @param array $requirements
     * @return $this
     */
    public function addRequirements(array $requirements)
    {
        $this->requirements += $requirements;
        return $this;
    }

    /**
     * Gets all requirements
     * @return array
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * Checks whether the route has the requirement
     * @param string $name
     * @return bool
     */
    public function hasRequirement($name)
    {
        return isset($this->requirements[$name]);
    }

    /**
     * Gets a requirement by its name
     * @param string $name
     * @param string $default
     * @return string|null
     */
    public function getRequirement($name, $default = null)
    {
        return isset($this->requirements[$name]) ? $this->requirements[$name] : $default;
    }

    /**
     *  Gets the defaults
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Sets a default item
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setDefault($name, $value)
    {
        $this->defaults[$name] = $value;
        return $this;
    }

    /**
     * Sets the defaults
     * @param array $defaults
     * @return $this
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * Add the defaults of the route (not replace)
     * @param array $defaults
     * @return $this
     */
    public function addDefaults(array $defaults)
    {
        $this->defaults += $defaults;
        return $this;
    }

    /**
     * Gets the default option value by its name
     * @param string $name
     * @return mixed|null
     */
    public function getDefault($name)
    {
        return isset($this->defaults[$name]) ? $this->defaults[$name] : null;
    }

    /**
     * Checks whether the route has the default option
     * @param string $name
     * @return bool
     */
    public function hasDefault($name)
    {
        return isset($this->defaults[$name]);
    }

    /**
     * Sets the route parameters
     * @param string $name
     * @param mixed $parameter
     * @return $this
     */
    public function setParameter($name, $parameter)
    {
        $this->parameters[$name] = $parameter;
        return $this;
    }

    /**
     * Gets the route parameters
     * @param string $name
     * @param string $default
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
    }

    /**
     * Checks whether the parameter exists
     * @param string $name
     * @return mixed
     */
    public function hasParameter($name)
    {
        return isset($this->parameters[$name]);
    }

    /**
     * Sets all parameters
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Add the paramters of the route (not replace)
     * @param array $parameters
     * @return $this
     */
    public function addParameters(array $parameters)
    {
        $this->parameters += $parameters;
        return $this;
    }

    /**
     * Gets all parameters
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $computedParameters
     */
    public function setComputedParameters($computedParameters)
    {
        $this->computedParameters = $computedParameters;
    }

    /**
     * @return array
     */
    public function getComputedParameters()
    {
        return $this->computedParameters;
    }

    /**
     * {@inheritdoc}
     */
    public function isCompiled()
    {
        return $this->isCompiled;
    }

    /**
     * Complies the route
     * @param boolean $reCompile
     * @return Route
     */
    public function compile($reCompile = false)
    {
        if (!$this->isCompiled || $reCompile) {
            if ($this->getHost()) {
                $this->hostRegex = $this->parsePattern($this->getHost(), true);
            }
            $this->pathRegex = $this->parsePattern($this->getPath(), false);
            $this->variables = array_merge($this->hostVariables, $this->pathVariables);
            $this->isCompiled = true;
        }
        return $this;
    }

    /**
     * Gets all variables that been compiled
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Parses the path to regex
     * @param string $path
     * @param boolean $isHost
     * @return string
     */
    protected function parsePattern($path, $isHost)
    {
        $variables = [];
        $regex = preg_replace_callback('#[/\.]?\{([a-zA-Z0-9_,]*)\}#i', function ($matches) use(&$variables){
            $variables[] = $matches[1];
            $subRegex = "(?P<{$matches[1]}>" . (isset($this->requirements[$matches[1]]) ? $this->requirements[$matches[1]] : '[^/\.]+') . ')';
            $regex = str_replace('\{' . $matches[1] . '\}', $subRegex, preg_quote($matches[0], '#'));
            if ($this->hasDefault($matches[1])) {
                $regex = "(?:{$regex})?";
            }
            return $regex;
        }, $path);
        $regex = "#^{$regex}$#";
        if ($isHost) {
            $regex .= 'i';
            $this->hostVariables = $variables;
        } else {
            $this->pathVariables = $variables;
        }
        return $regex;
    }
}