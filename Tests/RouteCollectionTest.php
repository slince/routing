<?php
namespace Slince\Routing\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Routing\Route;
use Slince\Routing\RouteCollection;

class RouteCollectionTest extends TestCase
{
    public function testConstructor()
    {
        $routes = [
            (new Route('/foo', 'action1'))->setName('foo'),
            (new Route('/bar', 'action2')),
        ];
        $routes = new RouteCollection($routes);
        $this->assertCount(2, $routes);
        $this->assertEquals('/foo', $routes->getByName('foo')->getPath());
        $this->assertEquals('/bar', $routes->getByAction('action2')->getPath());
    }

    public function testAdd()
    {
        $routes = new RouteCollection();
        $this->assertCount(0, $routes);
        $routes->add(new Route('/foo', 'Pages::foo'));
        $this->assertCount(1, $routes);
        return $routes;
    }

    /**
     * @depends testAdd
     * @param RouteCollection $routes
     */
    public function testGetRoute(RouteCollection $routes)
    {
        $route = new Route('/bar', 'Pages::bar');
        $route->setName('bar');
        $routes->add($route);
        $this->assertTrue($route === $routes->getByName('bar'));
        $this->assertTrue($route === $routes->getByAction('Pages::bar'));
        $this->assertEquals(['bar' => $route], $routes->getNamedRoutes());
    }

    public function testAll()
    {
        $routes = new RouteCollection();
        $route = new Route('/foo', 'Pages::foo');
        $routes->add($route);
        $this->assertEquals([$route], $routes->all());
    }

    public function testHttp()
    {
        $routes = new RouteCollection();
        $route = $routes->http('/foo', 'action1');
        $this->assertCount(1, $routes);
        $this->assertEquals(['http'], $route->getSchemes());
        return $routes;
    }

    public function testHttps()
    {
        $routes = new RouteCollection();
        $route = $routes->https('/bar', 'action');
        $this->assertCount(1, $routes);
        $this->assertEquals(['https'], $route->getSchemes());
    }

    public function testGet()
    {
        $routes = new RouteCollection();
        $route = $routes->get('/bar', 'action');
        $this->assertCount(1, $routes);
        $this->assertEquals(['GET', 'HEAD'], $route->getMethods());
    }

    public function testPost()
    {
        $routes = new RouteCollection();
        $route = $routes->post('/bar', 'action');
        $this->assertCount(1, $routes);
        $this->assertEquals(['POST'], $route->getMethods());
    }

    public function testPut()
    {
        $routes = new RouteCollection();
        $route = $routes->put('/bar', 'action');
        $this->assertCount(1, $routes);
        $this->assertEquals(['PUT'], $route->getMethods());
    }

    public function testPatch()
    {
        $routes = new RouteCollection();
        $route = $routes->patch('/bar', 'action');
        $this->assertCount(1, $routes);
        $this->assertEquals(['PATCH'], $route->getMethods());
    }

    public function testDelete()
    {
        $routes = new RouteCollection();
        $route = $routes->delete('/bar', 'action');
        $this->assertCount(1, $routes);
        $this->assertEquals(['DELETE'], $route->getMethods());
    }

    public function testSimpleGroup()
    {
        $routes = new RouteCollection();
        $routes->group('admin', function(RouteCollection $routes){
            $routes->get('/users', 'action');
        });
        $route = $routes->getByAction('action');
        $this->assertEquals('/admin/users', $route->getPath());

        $routes->group('/api', function(RouteCollection $routes){
            $routes->get('/users', 'action2');
        });
        $route = $routes->getByAction('action2');
        $this->assertEquals('/api/users', $route->getPath());

        $routes->group('/web/', function(RouteCollection $routes){
            $routes->get('/users/', 'action3');
        });
        $route = $routes->getByAction('action3');
        $this->assertEquals('/web/users', $route->getPath());
    }

    public function testGroup()
    {
        $routes = new RouteCollection();
        $routes->get('/users', 'action');
        $routes->group([
            'host' => 'locale.domain.com:422',
            'methods' => 'GET',
            'schemes' => 'https',
            'requirements' => [
                'locale' => 'en',
                'id' => '\w+'
            ],
            'foo' => 'bar'
        ], function(RouteCollection $routes){
            $routes->http('/products', 'Products::index')->setName('products.index');
            $routes->get('/products/{id}', 'Products::view')->setName('products.view');
            $routes->delete('/products/{id}', 'Products::delete')
                ->setName('products.delete')
                ->setRequirement('locale', 'en_US');
        });
        $this->assertCount(4, $routes);

        $productsIndexRoute = $routes->getByName('products.index');
        $productsViewRoute = $routes->getByName('products.view');
        $productsDeleteRoute = $routes->getByName('products.delete');

        $this->assertEquals('locale.domain.com:422', $productsIndexRoute->getHost());
        $this->assertEquals('locale.domain.com:422', $productsViewRoute->getHost());
        $this->assertEquals('locale.domain.com:422', $productsDeleteRoute->getHost());

        $this->assertEquals(['GET'], $productsIndexRoute->getMethods());
        $this->assertEquals(['GET', 'HEAD'], $productsViewRoute->getMethods());
        $this->assertEquals(['DELETE'], $productsDeleteRoute->getMethods());

        $this->assertEquals(['http'], $productsIndexRoute->getSchemes());
        $this->assertEquals(['https'], $productsViewRoute->getSchemes());
        $this->assertEquals(['https'], $productsDeleteRoute->getSchemes());

        $this->assertEquals([
            'locale' => 'en',
            'id' => '\w+'
        ], $productsIndexRoute->getRequirements());
        $this->assertEquals([
            'locale' => 'en',
            'id' => '\w+'
        ], $productsViewRoute->getRequirements());
        $this->assertEquals([
            'locale' => 'en_US',
            'id' => '\w+'
        ], $productsDeleteRoute->getRequirements());

        $this->assertEquals(['foo' => 'bar'], $productsIndexRoute->getParameters());
        $this->assertEquals(['foo' => 'bar'], $productsViewRoute->getParameters());
        $this->assertEquals(['foo' => 'bar'], $productsDeleteRoute->getParameters());
    }
}