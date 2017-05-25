<?php
namespace Slince\Routing\Tests;

use PHPUnit\Framework\TestCase;
use Slince\Routing\Route;

class RouteTest extends TestCase
{
    public function testConstruct()
    {
        $route = new Route('/foo', 'Pages::foo');
        $this->assertEquals('/foo', $route->getPath());
        $this->assertEquals('Pages::foo', $route->getAction());
    }

    public function testName()
    {
        $route = new Route('/foo', 'Pages::foo');
        $route->setName('foo');
        $this->assertEquals('foo', $route->getName());
    }

    public function testPath()
    {
        $route = new Route('/{foo}', 'Pages::foo');
        $route->setPath('/{bar}');
        $this->assertEquals('/{bar}', $route->getPath());
        $route->setPath('');
        $this->assertEquals('/', $route->getPath());
        $route->setPath('bar');
        $this->assertEquals('/bar', $route->getPath());
        $route->setPath('//path');
        $this->assertEquals('/path', $route->getPath());
    }

    public function testDefaults()
    {
        $route = new Route('/{foo}', 'Pages::foo');
        $route->setDefaults(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $route->getDefaults());
        $this->assertEquals($route, $route->setDefaults([]));
        $route->setDefault('foo', 'bar');
        $this->assertEquals('bar', $route->getDefault('foo'));
        $route->setDefault('foo2', 'bar2');
        $this->assertEquals('bar2', $route->getDefault('foo2'));
        $this->assertNull($route->getDefault('not_defined'));
        $route->setDefaults(['foo' => 'foo']);
        $route->addDefaults(['bar' => 'bar']);
        $this->assertEquals($route, $route->addDefaults([]));
        $this->assertEquals(['foo' => 'foo', 'bar' => 'bar'], $route->getDefaults());
    }

    public function testRequirements()
    {
        $route = new Route('/{foo}', 'Pages::foo');
        $route->setRequirements(['foo' => '\d+']);
        $this->assertEquals(['foo' => '\d+'], $route->getRequirements());
        $this->assertEquals('\d+', $route->getRequirement('foo'));
        $this->assertNull($route->getRequirement('bar'));
        $this->assertEquals($route, $route->setRequirements([]));
        $route->setRequirements(['foo' => '\d+']);
        $route->addRequirements(['bar' => '\d+']);
        $this->assertEquals($route, $route->addRequirements([]));
        $this->assertEquals(['foo' => '\d+', 'bar' => '\d+'], $route->getRequirements());
    }

    public function testRequirement()
    {
        $route = new Route('/{foo}', 'Pages::foo');
        $this->assertFalse($route->hasRequirement('foo'));
        $route->setRequirement('foo', '\d+');
        $this->assertEquals('\d+', $route->getRequirement('foo'));
        $this->assertTrue($route->hasRequirement('foo'));
    }

    public function testParameters()
    {
        $route = new Route('/{foo}', 'Pages::foo');
        $route->setParameters(['bar' => 'baz']);
        $this->assertTrue($route->hasParameter('bar'));
        $route->setParameters(['foo' => 'bar', 'bar' => 'foo']);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'foo'], $route->getParameters());
        $route->addParameters(['foo' => 'baz', 'baz' => 'foo']);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'foo', 'baz' => 'foo'], $route->getParameters());

        $route->setParameters(['foo' => null]);
        $this->assertTrue($route->hasParameter('foo'));
    }

    public function testHost()
    {
        $route = new Route('/{foo}', 'Pages::foo');
        $route->setHost('{locale}.domain.com');
        $this->assertEquals('{locale}.domain.com', $route->getHost());
    }

    public function testScheme()
    {
        $route = new Route('/{foo}', 'Pages::foo');
        $this->assertEquals([], $route->getSchemes());
        $this->assertFalse($route->hasScheme('http'));
        $route->setSchemes(['hTTp']);
        $this->assertEquals(['http'], $route->getSchemes());
        $this->assertTrue($route->hasScheme('htTp'));
        $this->assertFalse($route->hasScheme('httpS'));
        $route->setSchemes(['HttpS', 'hTTp']);
        $this->assertEquals(['https', 'http'], $route->getSchemes());
        $this->assertTrue($route->hasScheme('htTp'));
        $this->assertTrue($route->hasScheme('httpS'));
        $route->setSchemes('https');
        $this->assertTrue($route->hasScheme('https'));
    }

    public function testMethod()
    {
        $route = new Route('/{foo}', 'Pages::foo');
        $this->assertEquals([], $route->getMethods());
        $route->setMethods(['gEt']);
        $this->assertEquals(['GET'], $route->getMethods());
        $route->setMethods(['gEt', 'PosT']);
        $this->assertEquals(['GET', 'POST'], $route->getMethods());
        $this->assertTrue($route->hasMethod('Get'));
        $route->setMethods('get');
        $this->assertTrue($route->hasMethod('get'));
    }

    public function testCompile()
    {
        $route = new Route('/{foo}', 'Pages::foo');
        $route->setRequirement('foo', '\w+');
        $route->setHost('{locale}.domain.com');
        $this->assertFalse($route->isCompiled());
        $this->assertNull($route->getHostRegex());
        $this->assertNull($route->getPathRegex());
        $route->compile();
        $this->assertTrue($route->isCompiled());
        $this->assertEquals('#^(?P<locale>[^/\.]+).domain.com$#i',  $route->getHostRegex());
        $this->assertEquals('#^/(?P<foo>\w+)$#',  $route->getPathRegex());

        $this->assertEquals(['locale', 'foo'], $route->getVariables());
    }
}