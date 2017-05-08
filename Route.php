<?php
/**
 * slince routing library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\Routing;

class Route implements RouteInterface
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
     * path
     * @var string
     */
    protected $path;

    /**
     * action
     * @var mixed
     */
    protected $action;

    /**
     * Defaults
     * @var array
     */
    protected $defaults;

    /**
     * requirements
     * @var array
     */
    protected $requirements;

    /**
     * schemes
     * @var array
     */
    protected $schemes;

    /**
     * host
     * @var string
     */
    protected $host;

    /**
     * methods
     * @var array
     */
    protected $methods;

    /**
     * parameters
     * @var array
     */
    protected $parameters;

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

    /**
     * 变量
     * @var array
     */
    protected $variables = [];

    public function __construct(
        $path,
        $action,
        array $defaults = [],
        array $requirements = [],
        $host = '',
        array $schemes = [],
        array $methods = []
    ) {
        $this->setPath($path);
        $this->setAction($action);
        $this->setDefaults($defaults);
        $this->setRequirements($requirements);
        $this->setHost($host);
        $this->setSchemes($schemes);
        $this->setMethods($methods);
    }

    /**
     * {@inheritdoc}
     */
    public function setPath($path)
    {
        $this->path = '/' . trim($path, '/');
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getPathRegex()
    {
        return $this->pathRegex;
    }

    /**
     * 设置action
     * @param $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 获取action
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * 获取默认参数
     * @param $name
     * @return mixed|null
     */
    public function getDefault($name)
    {
        return isset($this->defaults[$name]) ? $this->defaults[$name] : null;
    }

    /**
     * 检查是否有默认参数
     * @param $name
     * @return bool
     */
    public function hasDefault($name)
    {
        return isset($this->defaults[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function setParameter($name, $parameter)
    {
        $this->parameters[$name] = $parameter;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameter($name, $default = null)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function hasParameter($name)
    {
        return isset($this->parameters[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequirements(array $requirements)
    {
        $this->requirements = $requirements;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequirement($name, $requirement)
    {
        $this->requirements[$name] = $requirement;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addRequirements(array $requirements)
    {
        $this->requirements += $requirements;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirement($name, $default = null)
    {
        return isset($this->requirements[$name]) ? $this->requirements[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function setSchemes(array $schemes)
    {
        $this->schemes = $schemes;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemes()
    {
        return $this->schemes;
    }

    /**
     * {@inheritdoc}
     */
    public function setMethods(array $methods)
    {
        $this->methods = array_map('strtolower', $methods);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * {@inheritdoc}
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function getHostRegex()
    {
        return $this->hostRegex;
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
     * @return Route
     */
    public function compile($recompile = false)
    {
        if (!$this->isCompiled || $recompile) {
            $this->hostRegex = $this->parseToRegex($this->getHost());
            $this->pathRegex = $this->parseToRegex($this->getPath());
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
     * @return string
     */
    protected function parseToRegex($path)
    {
        $regex = preg_replace_callback('#\{([a-zA-Z0-9_,]*)\}#i', function ($matches) {
            $this->variables[] = $matches[1];
            return "(?P<{$matches[1]}>" . (isset($this->requirements[$matches[1]]) ? $this->requirements[$matches[1]] : '.+') . ')';
        }, $path);
        return "#^{$regex}$#i";
    }
}