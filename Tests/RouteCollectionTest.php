<?php
namespace Slince\Routing\Tests;

use Slince\Routing\Route;
use Slince\Routing\RouteBuilderTrait;
use Slince\Routing\RouteCollection;

class RouteCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testAddRoute()
    {
        $routes = new RouteCollection();
        $this->assertEquals([], $routes->all());
        $routes->newRoute(new Route('/path', ''));
        $this->assertNotEmpty($routes->all());
    }

    public function testConstruct()
    {
        $routes = new RouteCollection([
            new Route('/foo', ''),
            new Route('/bar', ''),
        ]);
        $this->assertCount(2, $routes);
    }

    public function testNamedRoute()
    {
        $routes = new RouteCollection();
        $route = new Route('/foo', '');
        $routes->newRoute($route, 'foo');
        $this->assertEquals($route, $routes->getByName('foo'));
    }

    public function testActionRoute()
    {
        $routes = new RouteCollection();
        $route = new Route('/foo', 'foo@bar');
        $routes->newRoute($route, 'foo');
        $this->assertEquals($route, $routes->getByAction('foo@bar'));
    }

    public function testRouteBuilder()
    {
        $routes = new RouteCollection();
        $routeBuilder = new RouteBuilderTrait('/', $routes);
        $route = $routeBuilder->http('/foo1', [
            'name' => 'foo1'
        ]);
        $this->assertEquals([], $route->getMethods());
        $route = $routeBuilder->get('/foo2', [
            'name' => 'foo2'
        ]);
        $this->assertEquals(['get', 'head'], $route->getMethods());
        $route = $routeBuilder->post('/foo3', [
            'name' => 'foo3'
        ]);
        $this->assertEquals(['post'], $route->getMethods());
        $route = $routeBuilder->put('/foo4', [
            'name' => 'foo4'
        ]);
        $this->assertEquals(['put'], $route->getMethods());
        $route = $routeBuilder->delete('/foo5', [
            'name' => 'foo5'
        ]);
        $this->assertEquals(['delete'], $route->getMethods());
    }

    public function testPrefix()
    {
        $routes = new RouteCollection();
        $routeBuilder = new RouteBuilderTrait('/', $routes);
        $routeBuilder->prefix('/foo', function(RouteBuilderTrait $routes){
            $routes->http('/bar', [
                'name' => 'bar'
            ]);
        });
        $this->assertEquals('/foo/bar', $routes->getByName('bar')->getPath());
    }
}