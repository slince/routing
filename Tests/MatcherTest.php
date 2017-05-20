<?php
namespace Slince\Routing\Tests;

use Slince\Routing\Matcher;
use Slince\Routing\RequestContext;
use Slince\Routing\Route;
use Slince\Routing\RouteCollection;

class MatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testContext()
    {
        $matcher = new Matcher();
        $this->assertNull($matcher->getRequest());
        $matcher->setRequest(RequestContext::create());
        $this->assertInstanceOf('Slince\Routing\RequestContext', $matcher->getRequest());
    }
    
    public function testSimpleMatcher()
    {
        $routes = new RouteCollection();
        $route = new Route('/path', '');
        $routes->newRoute($route);
        $matcher = new Matcher();
        $_route = $matcher->match('/path', $routes);
        $this->assertEquals($_route, $route);
    }

    public function testMatchWithRegex()
    {
        $routes = new RouteCollection();
        $route = new Route('/foo/{id}/bar/{name}', '');
        $route->setHost('{main}.foo.com');
        $routes->newRoute($route);

        $matcher = new Matcher();
        $context = RequestContext::create();
        $context->setHost('www.foo.com');
        $matcher->setRequest($context);

        $_route = $matcher->match('/foo/100/bar/steven', $routes);
        $this->assertEquals($_route, $route);
        $this->assertEquals([
            'main' => 'www',
            'id' => 100,
            'name' => 'steven'
        ], $route->getParameters());
    }

    public function testRouteNotFoundException()
    {
        $routes = new RouteCollection();
        $route = new Route('/foo/{id}/bar/{name}', '');
        $route->setHost('{main}.foo.com');
        $route->setRequirements([
            'id' => '\d+',
            'name' => '\w+',
            'main' => 'm'
        ]);
        $routes->newRoute($route);

        $matcher = new Matcher();
        $context = RequestContext::create();
        $context->setHost('www.foo.com');
        $matcher->setRequest($context);
        $this->setExpectedExceptionRegExp('Slince\Routing\Exception\RouteNotFoundException');
        $matcher->match('/foo/100/bar/steven', $routes);
    }

    public function testMethodNotAllowedException()
    {
        $routes = new RouteCollection();
        $route = new Route('/foo/{id}/bar/{name}', '');
        $route->setMethods(['POST', 'PUT']);
        $routes->newRoute($route);
        $context = RequestContext::create();
        $this->assertEquals(['post', 'put'], $route->getMethods());
        $this->assertEquals('GET', $context->getMethod());
        $matcher = new Matcher($context);
        $this->setExpectedExceptionRegExp('Slince\Routing\Exception\MethodNotAllowedException');
        $matcher->match('/foo/100/bar/steven', $routes);
    }

    public function testSchemes()
    {
        $routes = new RouteCollection();
        $route = new Route('/foo/{id}/bar/{name}', '');
        $route->setSchemes(['https']);
        $routes->newRoute($route);
        $context = RequestContext::create();
        $context->setScheme('https');
        $this->assertEquals(['https'], $route->getSchemes());
        $this->assertEquals('https', $context->getScheme());
        $matcher = new Matcher($context);
        $this->assertEquals($route, $matcher->match('/foo/100/bar/steven', $routes));
        $context->setScheme('http');
        $this->setExpectedExceptionRegExp('Slince\Routing\Exception\RouteNotFoundException');
        $matcher->match('/foo/100/bar/steven', $routes);
    }
}